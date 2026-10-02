<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Replays the P05 migrations over data shaped like the P04 schema (todo/roadmap.md,
 * Definition of Done: "montée depuis le schéma précédent"). Everything runs in a
 * transaction of the owner connection, so the schema is back as it was afterwards.
 */

const LAST_P04_MIGRATION = '2026_10_02_004328_create_saved_table_views_table';

it('upgrades P04 memberships to roles, the owner flag and synchronised permissions', function (): void {
    $owner = DB::connection('pgsql_owner');
    $owner->beginTransaction();

    try {
        $steps = $owner->table('migrations')->where('migration', '>', LAST_P04_MIGRATION)->count();
        Artisan::call('migrate:rollback', ['--database' => 'pgsql_owner', '--step' => $steps, '--force' => true]);

        expect($owner->getSchemaBuilder()->hasColumn('tenant_members', 'role'))->toBeTrue()
            ->and($owner->getSchemaBuilder()->hasTable('roles'))->toBeFalse();

        $tenantId = (string) Str::ulid();
        $owner->table('tenants')->insert(['id' => $tenantId, 'name' => 'Héritage', 'created_at' => now(), 'updated_at' => now()]);

        foreach (['owner' => 'heritage-owner', 'admin' => 'heritage-admin', 'member' => 'heritage-member'] as $role => $name) {
            $userId = $owner->table('users')->insertGetId([
                'name' => $name,
                'email' => "{$name}@example.test",
                'password' => 'not-a-hash',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $owner->table('tenant_members')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'role' => $role,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Artisan::call('migrate', ['--database' => 'pgsql_owner', '--force' => true]);

        $members = $owner->table('tenant_members')
            ->join('roles', 'roles.id', '=', 'tenant_members.role_id')
            ->join('users', 'users.id', '=', 'tenant_members.user_id')
            ->where('tenant_members.tenant_id', $tenantId)
            ->orderBy('users.name')
            ->get(['users.name', 'roles.system_key', 'tenant_members.is_owner'])
            ->map(fn (object $row): array => [$row->name, $row->system_key, (bool) $row->is_owner])
            ->all();

        $grants = $owner->table('role_permissions')
            ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
            ->where('roles.tenant_id', $tenantId)
            ->where('roles.system_key', 'owner')
            ->count();

        expect($members)->toBe([
            ['heritage-admin', 'admin', false],
            ['heritage-member', 'member', false],
            ['heritage-owner', 'owner', true],
        ])
            ->and($owner->getSchemaBuilder()->hasColumn('tenant_members', 'role'))->toBeFalse()
            ->and($grants)->toBe(14);
    } finally {
        $owner->rollBack();
    }
});
