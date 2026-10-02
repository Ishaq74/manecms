<?php

use App\Domain\Audit\AuditLog;
use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Tenancy\Actions\CreateTenant;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('refuses to update or delete an audit event as the application role', function (string $statement): void {
    $event = AuditEvent::factory()->create();
    app(TenantDatabaseContext::class)->apply($event->tenant_id, null);

    expect(fn () => DB::statement($statement, [$event->id]))
        ->toThrow(fn (QueryException $exception) => expect($exception->getCode())->toBe('42501'));
})->with([
    'update' => ["update audit_events set action = 'tampered' where id = ?"],
    'delete' => ['delete from audit_events where id = ?'],
]);

it('refuses to update or delete an audit event even as the table owner', function (string $statement): void {
    $event = AuditEvent::factory()->create();

    expect(fn () => asOwner(fn () => DB::statement($statement, [$event->id])))
        ->toThrow(QueryException::class, 'AUDIT_APPEND_ONLY');
})->with([
    'update' => ["update audit_events set action = 'tampered' where id = ?"],
    'delete' => ['delete from audit_events where id = ?'],
]);

it('writes no audit event when the audited action rolls back', function (): void {
    Workspace::creating(fn (): never => throw new RuntimeException('Simulated failure'));

    expect(fn () => app(CreateTenant::class)(User::factory()->create(), 'Acme'))
        ->toThrow(RuntimeException::class);

    expect(asOwner(fn (): int => AuditEvent::query()->count()))->toBe(0);
});

it('records the creation of a tenant and its first workspace for the creator', function (): void {
    $user = User::factory()->create();

    $workspace = app(CreateTenant::class)($user, 'Acme');

    $events = asOwner(fn () => AuditEvent::query()->orderBy('action')->get());

    expect($events->pluck('action')->all())->toBe(['tenancy.tenant.created', 'tenancy.workspace.created'])
        ->and($events->pluck('tenant_id')->unique()->all())->toBe([$workspace->tenant_id])
        ->and($events->pluck('actor_id')->unique()->all())->toBe([$user->id])
        ->and($events->pluck('correlation_id')->unique())->toHaveCount(1);
});

it('masks secrets before writing an audit event', function (): void {
    [, $workspace, $member] = joinWorkspace();
    enterWorkspace($member, $workspace);

    $event = app(AuditLog::class)->record('identity.test', after: [
        'name' => 'Visible',
        'password' => 'hunter2',
        'nested' => ['api_key' => 'sk-live', 'remember_token' => 'abc'],
    ]);

    $stored = (string) asOwner(fn () => DB::table('audit_events')->where('id', $event->id)->value('after'));

    expect($stored)->toContain('Visible')
        ->not->toContain('hunter2')
        ->not->toContain('sk-live')
        ->not->toContain('abc');
});

it('hides the audit of another tenant', function (): void {
    [, $workspace, $member] = joinWorkspace();
    AuditEvent::factory()->create(['tenant_id' => $member->tenant_id]);
    AuditEvent::factory()->count(3)->create();

    enterWorkspace($member, $workspace);

    expect(AuditEvent::query()->pluck('tenant_id')->unique()->all())->toBe([$member->tenant_id]);
});

it('lets the application write platform events without reading them back', function (): void {
    $user = User::factory()->create();

    $event = app(AuditLog::class)->record('identity.login.succeeded', $user, actorId: $user->id, platform: true);

    expect(AuditEvent::query()->whereKey($event->id)->exists())->toBeFalse()
        ->and(asOwner(fn (): bool => AuditEvent::query()->whereKey($event->id)->exists()))->toBeTrue();
});
