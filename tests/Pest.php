<?php

use App\Domain\Tenancy\Context\TenantContext;
use App\Domain\Tenancy\Database\TenantDatabaseContext;
use App\Domain\Tenancy\Enums\TenantRole;
use App\Domain\Tenancy\Models\TenantMember;
use App\Domain\Tenancy\Models\Workspace;
use App\Models\User;
use Tests\Concerns\RefreshDatabaseAsOwner;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabaseAsOwner::class)
    ->group('feature')
    ->in('Feature');

pest()->group('unit')->in('Unit');

pest()->group('architecture')->in('Architecture');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create a user who belongs to a fresh tenant with one workspace.
 *
 * @return array{0: User, 1: Workspace, 2: TenantMember}
 */
function joinWorkspace(TenantRole $role = TenantRole::Owner): array
{
    return asOwner(function () use ($role): array {
        $member = TenantMember::factory()->create(['role' => $role]);
        $workspace = Workspace::factory()->for($member->tenant)->create();

        return [$member->user, $workspace, $member];
    });
}

/**
 * Install the tenant context the way ResolveWorkspace does for HTTP requests.
 */
function enterWorkspace(TenantMember $member, Workspace $workspace): void
{
    app(TenantContext::class)->install($member, $workspace);
}

/**
 * Run a callback as the table owner, outside row level security.
 *
 * For fixtures and for assertions about the whole database; application code
 * under test never runs inside it.
 *
 * @template TResult
 *
 * @param  Closure(): TResult  $callback
 * @return TResult
 */
function asOwner(Closure $callback): mixed
{
    return app(TenantDatabaseContext::class)->withoutRowSecurity($callback);
}
