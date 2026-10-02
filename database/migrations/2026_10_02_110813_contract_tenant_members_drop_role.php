<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contract step of the role migration: the legacy enum column is gone once
 * every reader uses `role_id` / `is_owner`.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS tenant_members_one_owner_per_tenant');

        Schema::table('tenant_members', function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_members', function (Blueprint $table): void {
            $table->string('role', 20)->nullable()->after('user_id');
        });

        DB::statement(<<<'SQL'
            UPDATE tenant_members
            SET role = COALESCE(roles.system_key, 'member')
            FROM roles
            WHERE roles.id = tenant_members.role_id
            SQL);
        DB::statement("CREATE UNIQUE INDEX tenant_members_one_owner_per_tenant ON tenant_members (tenant_id) WHERE role = 'owner'");
    }
};
