<?php

use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Exceptions\TenantContextRequired;
use App\Domain\Tenancy\Jobs\Middleware\RunInTenantContext;
use App\Domain\Tenancy\Jobs\TenantScopedJob;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class CountTenantWorkspaces implements ShouldQueue, TenantScopedJob
{
    use Queueable;

    public static ?int $counted = null;

    public function __construct(public string $tenant) {}

    public function tenantId(): string
    {
        return $this->tenant;
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new RunInTenantContext];
    }

    public function handle(): void
    {
        self::$counted = Workspace::query()->count();
    }
}

beforeEach(function (): void {
    CountTenantWorkspaces::$counted = null;
});

it('runs a tenant-scoped job inside its tenant only', function (): void {
    [$tenantId] = asOwner(function (): array {
        $member = TenantMember::factory()->owner()->create();
        Workspace::factory()->count(2)->for($member->tenant)->create();
        Workspace::factory()->count(3)->create();

        return [$member->tenant_id];
    });

    dispatch(new CountTenantWorkspaces($tenantId));

    expect(CountTenantWorkspaces::$counted)->toBe(2)
        ->and(app(TenantDatabaseContext::class)->current()['tenant'])->toBeNull();
});

it('fails closed when a tenant-scoped job has no tenant', function (): void {
    expect(fn () => dispatch(new CountTenantWorkspaces('')))->toThrow(TenantContextRequired::class)
        ->and(CountTenantWorkspaces::$counted)->toBeNull();
});
