<?php

use App\Domain\Tenancy\Database\TenantRowSecurity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_member_workspaces', function (Blueprint $table): void {
            $table->ulid('tenant_id');
            $table->ulid('tenant_member_id');
            $table->ulid('workspace_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(['tenant_member_id', 'workspace_id']);
            $table->index(['tenant_id', 'workspace_id']);
            $table->foreign(['tenant_id', 'tenant_member_id'])->references(['tenant_id', 'id'])->on('tenant_members')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'workspace_id'])->references(['tenant_id', 'id'])->on('workspaces')->cascadeOnDelete();
        });

        // A user sees their own restrictions in every tenant, so the workspace switcher can honour them.
        $ownMemberships = 'SELECT id FROM tenant_members WHERE user_id = '.TenantRowSecurity::CURRENT_USER;

        TenantRowSecurity::enable('tenant_member_workspaces', 'tenant_id', "tenant_member_id IN ({$ownMemberships})");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_member_workspaces');
    }
};
