<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Expand step of the role migration (todo/todo.md §28): members point at a
 * tenant role row, the legacy `role` column is dropped by the contract step.
 */
return new class extends Migration
{
    private const array SYSTEM_ROLES = ['owner' => 'Owner', 'admin' => 'Admin', 'member' => 'Member'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenant_members', function (Blueprint $table): void {
            $table->ulid('role_id')->nullable()->after('user_id');
            $table->boolean('is_owner')->default(false)->after('role_id');
        });

        $now = now();

        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            foreach (self::SYSTEM_ROLES as $key => $name) {
                DB::table('roles')->insertOrIgnore([
                    'id' => (string) Str::ulid(),
                    'tenant_id' => $tenantId,
                    'key' => $key,
                    'name' => $name,
                    'system_key' => $key,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::statement(<<<'SQL'
            UPDATE tenant_members
            SET role_id = roles.id
            FROM roles
            WHERE roles.tenant_id = tenant_members.tenant_id AND roles.system_key = tenant_members.role
            SQL);

        DB::statement('ALTER TABLE tenant_members ALTER COLUMN role_id SET NOT NULL');
        DB::statement('ALTER TABLE tenant_members ALTER COLUMN role DROP NOT NULL');

        Schema::table('tenant_members', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'id']);
            $table->index('role_id');
            $table->foreign(['tenant_id', 'role_id'])->references(['tenant_id', 'id'])->on('roles')->restrictOnDelete();
        });

        // `is_owner` is derived from the role: it is recomputed on every write so it cannot be forged.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION tenant_members_sync_owner() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                NEW.is_owner := EXISTS (SELECT 1 FROM roles WHERE roles.id = NEW.role_id AND roles.system_key = 'owner');

                RETURN NEW;
            END;
            $$
            SQL);
        DB::statement('CREATE TRIGGER tenant_members_sync_owner BEFORE INSERT OR UPDATE ON tenant_members FOR EACH ROW EXECUTE FUNCTION tenant_members_sync_owner()');
        DB::statement('UPDATE tenant_members SET role_id = role_id');
        DB::statement('CREATE UNIQUE INDEX tenant_members_single_owner ON tenant_members (tenant_id) WHERE is_owner');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS tenant_members_sync_owner ON tenant_members');
        DB::statement('DROP FUNCTION IF EXISTS tenant_members_sync_owner()');
        DB::statement('DROP INDEX IF EXISTS tenant_members_single_owner');

        DB::statement(<<<'SQL'
            UPDATE tenant_members
            SET role = COALESCE(roles.system_key, 'member')
            FROM roles
            WHERE roles.id = tenant_members.role_id
            SQL);
        DB::statement('ALTER TABLE tenant_members ALTER COLUMN role SET NOT NULL');

        Schema::table('tenant_members', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id', 'role_id']);
            $table->dropIndex(['role_id']);
            $table->dropUnique(['tenant_id', 'id']);
            $table->dropColumn(['role_id', 'is_owner']);
        });
    }
};
