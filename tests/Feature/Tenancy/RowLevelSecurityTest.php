<?php

use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * @return array{tenant: Tenant, workspace: Workspace, member: TenantMember}
 */
function tenantFixture(): array
{
    return asOwner(function (): array {
        $member = TenantMember::factory()->owner()->create();

        return [
            'tenant' => $member->tenant,
            'workspace' => Workspace::factory()->for($member->tenant)->create(),
            'member' => $member,
        ];
    });
}

function database(): TenantDatabaseContext
{
    return app(TenantDatabaseContext::class);
}

it('runs the suite with a role that cannot bypass row level security', function (): void {
    $role = DB::selectOne(<<<'SQL'
        select r.rolsuper as superuser, r.rolbypassrls as bypass, pg_has_role(current_user, t.tableowner, 'USAGE') as owns
        from pg_roles r, pg_tables t
        where r.rolname = current_user and t.schemaname = 'public' and t.tablename = 'tenants'
        SQL);

    expect($role?->superuser)->toBeFalse()
        ->and($role?->bypass)->toBeFalse()
        ->and($role?->owns)->toBeFalse();
});

it('reads nothing from the tenancy tables without a context', function (string $table): void {
    tenantFixture();

    expect(DB::table($table)->count())->toBe(0);
})->with(['tenants', 'workspaces', 'tenant_members']);

it('reads only the current tenant on every tenancy table', function (): void {
    $a = tenantFixture();
    $b = tenantFixture();

    database()->apply($a['tenant']->id, null);

    expect(DB::table('tenants')->pluck('id')->all())->toBe([$a['tenant']->id])
        ->and(DB::table('workspaces')->pluck('tenant_id')->unique()->values()->all())->toBe([$a['tenant']->id])
        ->and(DB::table('tenant_members')->pluck('tenant_id')->unique()->values()->all())->toBe([$a['tenant']->id])
        ->and(DB::table('workspaces')->where('tenant_id', $b['tenant']->id)->exists())->toBeFalse();
});

it('keeps a join inside the current tenant', function (): void {
    $a = tenantFixture();
    tenantFixture();

    database()->apply($a['tenant']->id, null);

    $tenants = DB::table('workspaces')
        ->join('tenant_members', 'tenant_members.tenant_id', '=', 'workspaces.tenant_id')
        ->pluck('workspaces.tenant_id')
        ->unique()
        ->values()
        ->all();

    expect($tenants)->toBe([$a['tenant']->id]);
});

it('only counts the current tenant when a query forgets its tenant filter', function (): void {
    $a = tenantFixture();
    tenantFixture();
    tenantFixture();

    database()->apply($a['tenant']->id, null);

    expect(Workspace::query()->count())->toBe(1);
});

it('rejects a row written for another tenant', function (): void {
    $a = tenantFixture();
    $b = tenantFixture();

    database()->apply($a['tenant']->id, null);

    expect(fn () => DB::table('workspaces')->insert([
        'id' => (string) str()->ulid(),
        'tenant_id' => $b['tenant']->id,
        'name' => 'Intrusion',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(fn (QueryException $exception) => expect($exception->getCode())->toBe('42501'));
});

it('neither updates nor deletes rows of another tenant', function (): void {
    $a = tenantFixture();
    $b = tenantFixture();

    database()->apply($a['tenant']->id, null);

    expect(DB::table('workspaces')->where('id', $b['workspace']->id)->update(['name' => 'Hijacked']))->toBe(0)
        ->and(DB::table('tenant_members')->where('id', $b['member']->id)->delete())->toBe(0)
        ->and(DB::table('tenants')->where('id', $b['tenant']->id)->update(['name' => 'Hijacked']))->toBe(0);

    asOwner(function () use ($b): void {
        expect($b['workspace']->fresh()?->name)->toBe($b['workspace']->name)
            ->and($b['member']->fresh())->not->toBeNull()
            ->and($b['tenant']->fresh()?->name)->toBe($b['tenant']->name);
    });
});

it('lets a user list only their own memberships before a workspace is chosen', function (): void {
    $a = tenantFixture();
    $b = tenantFixture();

    database()->apply(null, $a['member']->user_id);

    expect(DB::table('tenant_members')->pluck('id')->all())->toBe([$a['member']->id])
        ->and(DB::table('tenants')->pluck('id')->all())->toBe([$a['tenant']->id])
        ->and(DB::table('workspaces')->where('tenant_id', $b['tenant']->id)->exists())->toBeFalse();
});

it('does not let a user scope write anything', function (): void {
    $a = tenantFixture();

    database()->apply(null, $a['member']->user_id);

    expect(DB::table('workspaces')->where('id', $a['workspace']->id)->update(['name' => 'Renamed']))->toBe(0);
});

it('leaves no scope behind after an HTTP request', function (): void {
    $a = tenantFixture();

    $this->actingAs($a['member']->user)
        ->get(route('workspace.home', $a['workspace']))
        ->assertOk();

    expect(database()->current())->toBe(['tenant' => null, 'user' => null]);
});

it('restores the previous scope after running as another tenant', function (): void {
    $a = tenantFixture();
    $b = tenantFixture();

    database()->apply($a['tenant']->id, 7);
    database()->runAs($b['tenant']->id, null, fn (): int => Workspace::query()->count());

    expect(database()->current())->toBe(['tenant' => $a['tenant']->id, 'user' => 7]);
});

it('reports every tenancy table as protected', function (): void {
    $this->artisan('tenancy:verify-rls')->assertSuccessful();
});

it('reports a tenant-scoped table without row level security', function (): void {
    asOwner(fn () => DB::statement('CREATE TABLE unprotected_probe (id char(26) primary key, tenant_id char(26) not null)'));

    $this->artisan('tenancy:verify-rls')->assertFailed();
});
