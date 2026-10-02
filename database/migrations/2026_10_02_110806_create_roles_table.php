<?php

use App\Domain\Tenancy\Database\TenantRowSecurity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->string('key', 60);
            $table->string('name', 80);
            $table->string('system_key', 20)->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'key']);
            $table->unique(['tenant_id', 'system_key']);
        });

        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_key_format CHECK (key ~ '^[a-z0-9][a-z0-9-]*$')");
        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_system_key_known CHECK (system_key IS NULL OR system_key IN ('owner', 'admin', 'member'))");
        DB::statement('CREATE UNIQUE INDEX roles_tenant_id_name_unique ON roles (tenant_id, lower(name))');

        // System roles are part of the security model: they can be neither renamed nor deleted.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION roles_protect_system() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF OLD.system_key IS NOT NULL AND (
                    TG_OP = 'DELETE'
                    OR NEW.system_key IS DISTINCT FROM OLD.system_key
                    OR NEW.key IS DISTINCT FROM OLD.key
                ) THEN
                    RAISE EXCEPTION 'System role % is immutable', OLD.system_key USING ERRCODE = 'check_violation';
                END IF;

                IF TG_OP = 'UPDATE' AND OLD.system_key IS NULL AND NEW.system_key IS NOT NULL THEN
                    RAISE EXCEPTION 'A custom role cannot become a system role' USING ERRCODE = 'check_violation';
                END IF;

                RETURN CASE WHEN TG_OP = 'DELETE' THEN OLD ELSE NEW END;
            END;
            $$
            SQL);
        DB::statement('CREATE TRIGGER roles_protect_system BEFORE UPDATE OR DELETE ON roles FOR EACH ROW EXECUTE FUNCTION roles_protect_system()');

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->ulid('tenant_id');
            $table->ulid('role_id');
            $table->string('permission_key', 100);
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(['role_id', 'permission_key']);
            $table->index('tenant_id');
            $table->foreign(['tenant_id', 'role_id'])->references(['tenant_id', 'id'])->on('roles')->cascadeOnDelete();
            $table->foreign('permission_key')->references('key')->on('permissions')->cascadeOnDelete();
        });

        TenantRowSecurity::enable('roles');
        TenantRowSecurity::enable('role_permissions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
        DB::statement('DROP FUNCTION IF EXISTS roles_protect_system()');
    }
};
