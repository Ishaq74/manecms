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
        Schema::create('permissions', function (Blueprint $table): void {
            $table->string('key', 100)->primary();
            $table->string('context', 40);
            $table->string('capability', 20);
            $table->boolean('requires_approval')->default(false);
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE permissions ADD CONSTRAINT permissions_key_format CHECK (key ~ '^[a-z]+(\\.[a-z][a-z-]*){2}$')");
        DB::statement("ALTER TABLE permissions ADD CONSTRAINT permissions_capability_known CHECK (capability IN ('safe', 'guarded', 'privileged', 'code_only', 'immutable'))");

        // The catalogue is owned by code (authorization:sync-permissions): the application role may only read it.
        $app = config('database.roles.app.username');

        if (is_string($app) && $this->roleExists($app)) {
            DB::statement(sprintf('REVOKE INSERT, UPDATE, DELETE ON permissions FROM "%s"', $app));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }

    private function roleExists(string $role): bool
    {
        return DB::selectOne('SELECT 1 AS present FROM pg_roles WHERE rolname = ?', [$role]) !== null;
    }
};
