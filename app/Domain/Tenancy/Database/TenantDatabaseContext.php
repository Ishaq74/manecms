<?php

namespace App\Domain\Tenancy\Database;

use Closure;
use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;

/**
 * The PostgreSQL session variables that row level security policies read.
 *
 * `app.tenant_id` scopes every read and write to one tenant; `app.user_id` only
 * lets a user list the tenants and workspaces they belong to before a workspace
 * is chosen. An empty value means "not set", which the policies treat as no
 * access at all.
 */
final class TenantDatabaseContext
{
    private bool $dirty = false;

    public function __construct(private readonly DatabaseManager $database) {}

    public function apply(?string $tenantId, ?int $userId): void
    {
        $this->database->connection()->select(
            "select set_config('app.tenant_id', ?, false), set_config('app.user_id', ?, false)",
            [$tenantId ?? '', $userId === null ? '' : (string) $userId],
        );

        $this->dirty = $tenantId !== null || $userId !== null;
    }

    public function clear(): void
    {
        if ($this->dirty) {
            $this->apply(null, null);
        }
    }

    /**
     * @return array{tenant: string|null, user: int|null}
     */
    public function current(): array
    {
        $row = (array) $this->database->connection()->selectOne(
            "select current_setting('app.tenant_id', true) as tenant, current_setting('app.user_id', true) as usr",
        );

        $tenant = is_string($row['tenant'] ?? null) && $row['tenant'] !== '' ? $row['tenant'] : null;
        $user = is_string($row['usr'] ?? null) && ctype_digit($row['usr']) ? (int) $row['usr'] : null;

        return ['tenant' => $tenant, 'user' => $user];
    }

    /**
     * Run a callback with the given scope, then restore the previous one.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function runAs(?string $tenantId, ?int $userId, Closure $callback): mixed
    {
        $previous = $this->current();
        $this->apply($tenantId, $userId);

        try {
            return $callback();
        } finally {
            $this->apply($previous['tenant'], $previous['user']);
        }
    }

    /**
     * Run a callback as the table owner, which bypasses row level security.
     *
     * Only fixtures and maintenance use this. The application role cannot switch
     * to the owner, so calling it at runtime fails instead of widening access.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function withoutRowSecurity(Closure $callback): mixed
    {
        $owner = config()->string('database.roles.owner.username');

        if (preg_match('/^[a-z_][a-z0-9_]*$/', $owner) !== 1) {
            throw new InvalidArgumentException("Invalid owner role name [{$owner}].");
        }

        $connection = $this->database->connection();

        // A savepoint scopes SET LOCAL, so a failing callback rolls the role back with it.
        return $connection->transaction(function () use ($connection, $owner, $callback): mixed {
            $previous = (array) $connection->selectOne('select current_user as name');
            $previousRole = is_string($previous['name'] ?? null) ? $previous['name'] : $owner;

            $connection->statement("SET LOCAL ROLE \"{$owner}\"");
            $result = $callback();
            // Nested calls restore the caller's role instead of resetting to the session user.
            $connection->statement('SET LOCAL ROLE '.$connection->getPdo()->quote($previousRole));

            return $result;
        });
    }
}
