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
        Schema::create('tenant_members', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->ulid('last_workspace_id')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'user_id']);
            $table->index('user_id');
            $table->foreign(['tenant_id', 'last_workspace_id'])
                ->references(['tenant_id', 'id'])
                ->on('workspaces')
                ->restrictOnDelete();
        });

        DB::statement("CREATE UNIQUE INDEX tenant_members_one_owner_per_tenant ON tenant_members (tenant_id) WHERE role = 'owner'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_members');
    }
};
