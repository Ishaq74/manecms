<?php

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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('platform_role', 20)->nullable()->after('password');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_platform_role_known CHECK (platform_role IS NULL OR platform_role IN ('operator'))");

        $owner = config()->string('database.roles.owner.username');

        if (preg_match('/^[a-z_][a-z0-9_]*$/', $owner) !== 1) {
            throw new InvalidArgumentException("Invalid owner role name [{$owner}].");
        }

        // Only the table owner, i.e. the platform:* commands, may grant or revoke the platform role.
        DB::statement(<<<SQL
            CREATE OR REPLACE FUNCTION users_protect_platform_role() RETURNS trigger LANGUAGE plpgsql AS \$\$
            BEGIN
                IF (TG_OP = 'INSERT' AND NEW.platform_role IS NOT NULL)
                    OR (TG_OP = 'UPDATE' AND NEW.platform_role IS DISTINCT FROM OLD.platform_role) THEN
                    IF current_user <> '{$owner}' THEN
                        RAISE EXCEPTION 'PLATFORM_ROLE_PROTECTED' USING ERRCODE = 'insufficient_privilege';
                    END IF;
                END IF;

                RETURN NEW;
            END;
            \$\$
            SQL);
        DB::statement('CREATE TRIGGER users_protect_platform_role BEFORE INSERT OR UPDATE ON users FOR EACH ROW EXECUTE FUNCTION users_protect_platform_role()');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS users_protect_platform_role ON users');
        DB::statement('DROP FUNCTION IF EXISTS users_protect_platform_role()');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('platform_role');
        });
    }
};
