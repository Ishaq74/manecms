<?php

namespace App\Domain\Tenancy\Database;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Enables and forces row level security on a tenant-scoped table (todo/todo.md §17.1).
 *
 * Writes are always limited to the current tenant. Reads may be widened by an
 * extra predicate, used by the tenancy tables so a user can list their own
 * tenants before a workspace is chosen.
 */
final class TenantRowSecurity
{
    public const string CURRENT_TENANT = "nullif(current_setting('app.tenant_id', true), '')";

    public const string CURRENT_USER = "nullif(current_setting('app.user_id', true), '')::bigint";

    public static function enable(string $table, string $tenantColumn = 'tenant_id', ?string $extraReadPredicate = null): void
    {
        self::guard($table);
        self::guard($tenantColumn);

        $writable = "{$tenantColumn} = ".self::CURRENT_TENANT;
        $readable = $extraReadPredicate === null ? $writable : "({$writable}) OR ({$extraReadPredicate})";

        DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
        DB::statement("CREATE POLICY {$table}_tenant_select ON {$table} FOR SELECT USING ({$readable})");
        DB::statement("CREATE POLICY {$table}_tenant_insert ON {$table} FOR INSERT WITH CHECK ({$writable})");
        DB::statement("CREATE POLICY {$table}_tenant_update ON {$table} FOR UPDATE USING ({$writable}) WITH CHECK ({$writable})");
        DB::statement("CREATE POLICY {$table}_tenant_delete ON {$table} FOR DELETE USING ({$writable})");
    }

    public static function disable(string $table): void
    {
        self::guard($table);

        foreach (['select', 'insert', 'update', 'delete'] as $command) {
            DB::statement("DROP POLICY IF EXISTS {$table}_tenant_{$command} ON {$table}");
        }

        DB::statement("ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
    }

    private static function guard(string $identifier): void
    {
        if (preg_match('/^[a-z_][a-z0-9_]*$/', $identifier) !== 1) {
            throw new InvalidArgumentException("Invalid identifier [{$identifier}].");
        }
    }
}
