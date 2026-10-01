<?php

use App\Domain\Tenancy\Database\TenantRowSecurity;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $userTenants = 'SELECT tenant_id FROM tenant_members WHERE user_id = '.TenantRowSecurity::CURRENT_USER;

        TenantRowSecurity::enable('tenants', 'id', "id IN ({$userTenants})");
        TenantRowSecurity::enable('workspaces', 'tenant_id', "tenant_id IN ({$userTenants})");
        TenantRowSecurity::enable('tenant_members', 'tenant_id', 'user_id = '.TenantRowSecurity::CURRENT_USER);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        TenantRowSecurity::disable('tenant_members');
        TenantRowSecurity::disable('workspaces');
        TenantRowSecurity::disable('tenants');
    }
};
