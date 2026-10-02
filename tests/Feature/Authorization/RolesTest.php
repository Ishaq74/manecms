<?php

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Authorization\Actions\CreateRole;
use App\Domain\Authorization\Actions\DeleteRole;
use App\Domain\Authorization\Actions\SyncPermissions;
use App\Domain\Authorization\Actions\UpdateRole;
use App\Domain\Authorization\Enums\SystemRole;
use App\Domain\Authorization\Errors\AuthorizationDenied;
use App\Domain\Authorization\Models\Role;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantInvitation;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Permissions\TenancyPermission;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Feature\Authorization\Fixtures\TestPermission;

it('gives every new tenant its three system roles with their permissions', function (): void {
    $tenant = Tenant::factory()->create();

    $grants = asOwner(fn () => $tenant->roles()->get()->mapWithKeys(fn (Role $role): array => [$role->system_key->value ?? $role->key => $role->permissionKeys()]));

    expect($grants->keys()->sort()->values()->all())->toBe(['admin', 'member', 'owner'])
        ->and($grants['owner'])->toContain(TenancyPermission::TenantTransfer->key())
        ->and($grants['admin'])->toContain(TenancyPermission::MemberInvite->key())
        ->and($grants['admin'])->not->toContain(TenancyPermission::TenantTransfer->key())
        ->and($grants['member'])->toBe([TenancyPermission::MemberView->key()]);
});

it('synchronises the catalogue: new keys added, removed keys dropped, privileged keys taken from custom roles', function (): void {
    $tenant = Tenant::factory()->create();
    $custom = asOwner(fn () => Role::factory()->for($tenant)->withPermissions([TenancyPermission::MemberView->key()])->create());

    config(['authorization.permissions' => [...config('authorization.permissions'), TestPermission::class]]);
    asOwner(fn () => app(SyncPermissions::class)());

    $admin = asOwner(fn () => $tenant->roles()->where('system_key', SystemRole::Admin)->sole());

    expect(asOwner(fn () => DB::table('permissions')->where('key', TestPermission::Publish->key())->value('capability')))->toBe('guarded')
        ->and(asOwner(fn () => $admin->permissionKeys()))->toContain(TestPermission::Publish->key())
        ->and(asOwner(fn () => $admin->permissionKeys()))->not->toContain(TestPermission::Deploy->key());

    asOwner(fn () => $custom->syncPermissions([TestPermission::Read->key()]));
    config(['authorization.permissions' => array_values(array_diff(config('authorization.permissions'), [TestPermission::class]))]);
    asOwner(fn () => app(SyncPermissions::class)());

    expect(asOwner(fn () => DB::table('permissions')->where('key', 'like', 'testing.%')->count()))->toBe(0)
        ->and(asOwner(fn () => $custom->permissionKeys()))->toBe([]);
});

it('runs the synchronisation from the command line', function (): void {
    $exitCode = asOwner(fn (): int => Artisan::call('authorization:sync-permissions'));

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('14 permissions synchronised')
        ->and(asOwner(fn () => DB::table('permissions')->count()))->toBe(14);
});

it('keeps the catalogue read-only for the application role', function (): void {
    expect(fn () => DB::table('permissions')->where('key', TenancyPermission::MemberView->key())->update(['capability' => 'privileged']))
        ->toThrow(QueryException::class);
});

it('protects system roles in the database', function (): void {
    $tenant = Tenant::factory()->create();
    $owner = asOwner(fn () => $tenant->roles()->where('system_key', SystemRole::Owner)->sole());

    expect(fn () => asOwner(fn () => $owner->delete()))->toThrow(QueryException::class)
        ->and(fn () => asOwner(fn () => DB::table('roles')->where('id', $owner->id)->update(['key' => 'boss'])))->toThrow(QueryException::class);
});

it('rejects two roles whose names differ only by case in a tenant', function (): void {
    $tenant = Tenant::factory()->create();
    Role::factory()->for($tenant)->create(['name' => 'Éditeur', 'key' => 'editeur']);

    expect(fn () => Role::factory()->for($tenant)->create(['name' => 'ÉDITEUR', 'key' => 'editeur-2']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('rejects a member whose role belongs to another tenant', function (): void {
    $member = TenantMember::factory()->member()->create();
    $foreignRole = asOwner(fn () => Tenant::factory()->create()->roles()->where('system_key', SystemRole::Admin)->sole());

    expect(fn () => asOwner(fn () => $member->update(['role_id' => $foreignRole->id])))
        ->toThrow(fn (QueryException $exception) => expect($exception->getCode())->toBe('23503'));
});

it('lets the owner create, update and delete a custom role, with an audit trail', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);

    $role = app(CreateRole::class)($user, '  Rédacteur  web ', [TenancyPermission::MemberView->key(), TenancyPermission::WorkspaceCreate->key()]);

    expect($role->key)->toBe('redacteur-web')
        ->and($role->name)->toBe('Rédacteur web')
        ->and($role->permissionKeys())->toBe([TenancyPermission::MemberView->key(), TenancyPermission::WorkspaceCreate->key()]);

    app(UpdateRole::class)($user, $role->id, 'Rédacteur', [TenancyPermission::MemberView->key()]);

    expect($role->fresh()?->name)->toBe('Rédacteur')
        ->and($role->permissionKeys())->toBe([TenancyPermission::MemberView->key()]);

    app(DeleteRole::class)($user, $role->id);

    expect(Role::query()->whereKey($role->id)->exists())->toBeFalse()
        ->and(AuditEvent::query()->where('action', 'like', 'authorization.role.%')->orderBy('id')->pluck('action')->all())
        ->toBe(['authorization.role.created', 'authorization.role.updated', 'authorization.role.deleted']);
});

it('refuses privileged permissions and reserved names on a custom role', function (string $name, array $permissions, string $field): void {
    [$user, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);

    expect(fn () => app(CreateRole::class)($user, $name, $permissions))
        ->toThrow(fn (ValidationException $exception) => expect($exception->errors())->toHaveKey($field));
})->with([
    'privileged permission' => ['Sécurité', [TenancyPermission::TenantSecurity->key()], 'permissions'],
    'unknown permission' => ['Fantôme', ['tenancy.ghost.haunt'], 'permissions'],
    'system role name' => ['Admin', [], 'name'],
    'blank name' => ['  ', [], 'name'],
]);

it('refuses to delete a role still given to members or pending invitations', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    $used = Role::factory()->for($member->tenant)->create();
    $invited = Role::factory()->for($member->tenant)->create();
    TenantMember::factory()->withRole($used)->create();
    TenantInvitation::factory()->for($member->tenant)->create(['role_id' => $invited->id]);
    enterWorkspace($member, $workspace);

    expect(fn () => app(DeleteRole::class)($user, $used->id))->toThrow(ValidationException::class)
        ->and(fn () => app(DeleteRole::class)($user, $invited->id))->toThrow(ValidationException::class);
});

it('never lets system roles be edited or deleted through the actions', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    $admin = asOwner(fn () => $member->tenant->roles()->where('system_key', SystemRole::Admin)->sole());
    enterWorkspace($member, $workspace);

    expect(fn () => app(UpdateRole::class)($user, $admin->id, 'Chef', []))->toThrow(ModelNotFoundException::class)
        ->and(fn () => app(DeleteRole::class)($user, $admin->id))->toThrow(ModelNotFoundException::class);
});

it('hides the roles of other tenants', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    $foreign = Role::factory()->create();
    enterWorkspace($member, $workspace);

    expect(fn () => app(DeleteRole::class)($user, $foreign->id))->toThrow(ModelNotFoundException::class);
});

it('lets only role managers open the roles page', function (SystemRole $role, int $status): void {
    [$user, $workspace] = joinWorkspace($role);

    $this->actingAs($user)->get(route('roles.index', $workspace))->assertStatus($status);
})->with([
    'owner' => [SystemRole::Owner, 200],
    'admin' => [SystemRole::Admin, 200],
    'member' => [SystemRole::Member, 403],
]);

it('creates a role from the roles page', function (): void {
    [$user, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);

    Livewire::actingAs($user)
        ->test('pages::roles.index')
        ->call('create')
        ->set('name', 'Relecteur')
        ->set('permissions', [TenancyPermission::MemberView->key()])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Relecteur');
});

it('denies the role actions to a member and audits it', function (): void {
    [$user, $workspace, $member] = joinWorkspace(SystemRole::Member);
    enterWorkspace($member, $workspace);

    expect(fn () => app(CreateRole::class)($user, 'Pirate', []))->toThrow(AuthorizationDenied::class)
        ->and(AuditEvent::query()->where('action', 'authorization.denied')->count())->toBe(1);
});
