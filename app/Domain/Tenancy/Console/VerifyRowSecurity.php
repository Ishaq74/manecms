<?php

namespace App\Domain\Tenancy\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fails when a tenant-scoped table is not protected by forced row level security.
 */
#[Signature('tenancy:verify-rls')]
#[Description('Check that every tenant-scoped table enforces row level security')]
final class VerifyRowSecurity extends Command
{
    private const array COMMANDS = ['SELECT', 'INSERT', 'UPDATE', 'DELETE'];

    public function handle(): int
    {
        $tables = $this->tenantScopedTables();
        $failures = [];

        foreach ($tables as $table) {
            $missing = $this->missingPolicies($table['name']);

            if (! $table['enabled'] || ! $table['forced'] || $missing !== []) {
                $failures[] = [
                    $table['name'],
                    $table['enabled'] ? 'yes' : 'no',
                    $table['forced'] ? 'yes' : 'no',
                    $missing === [] ? '-' : implode(', ', $missing),
                ];
            }
        }

        if ($failures !== []) {
            $this->components->error('Some tenant-scoped tables are not protected by row level security.');
            $this->table(['Table', 'Enabled', 'Forced', 'Missing policies'], $failures);

            return self::FAILURE;
        }

        $this->components->info(count($tables).' tenant-scoped tables enforce row level security.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{name: string, enabled: bool, forced: bool}>
     */
    private function tenantScopedTables(): array
    {
        $rows = DB::select(<<<'SQL'
            select c.relname as name, c.relrowsecurity as enabled, c.relforcerowsecurity as forced
            from pg_class c
            join pg_namespace n on n.oid = c.relnamespace
            where n.nspname = 'public'
              and c.relkind = 'r'
              and (
                  c.relname = 'tenants'
                  or exists (
                      select 1 from information_schema.columns col
                      where col.table_schema = 'public' and col.table_name = c.relname and col.column_name = 'tenant_id'
                  )
              )
            order by c.relname
            SQL);

        $tables = [];

        foreach ($rows as $row) {
            $values = (array) $row;

            if (is_string($values['name'] ?? null)) {
                $tables[] = [
                    'name' => $values['name'],
                    'enabled' => ($values['enabled'] ?? false) === true,
                    'forced' => ($values['forced'] ?? false) === true,
                ];
            }
        }

        return $tables;
    }

    /**
     * @return list<string>
     */
    private function missingPolicies(string $table): array
    {
        $commands = array_filter(
            DB::table('pg_policies')->where('schemaname', 'public')->where('tablename', $table)->pluck('cmd')->all(),
            'is_string',
        );

        if (in_array('ALL', $commands, true)) {
            return [];
        }

        return array_values(array_diff(self::COMMANDS, $commands));
    }
}
