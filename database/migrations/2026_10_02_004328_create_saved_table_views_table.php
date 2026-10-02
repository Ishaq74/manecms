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
        Schema::create('saved_table_views', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('table_key', 100);
            $table->string('name', 80);
            $table->jsonb('state');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'user_id', 'table_key', 'name']);
        });

        TenantRowSecurity::enable('saved_table_views');

        // A saved view is private to its author: this restrictive policy is ANDed with the tenant ones.
        $currentUser = TenantRowSecurity::CURRENT_USER;
        DB::statement("CREATE POLICY saved_table_views_author ON saved_table_views AS RESTRICTIVE FOR ALL USING (user_id = {$currentUser}) WITH CHECK (user_id = {$currentUser})");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_table_views');
    }
};
