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
        Schema::create('tenant_invitations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->string('email', 254);
            $table->ulid('role_id');
            $table->jsonb('workspace_ids')->nullable();
            $table->char('token_hash', 64)->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('expires_at');
            $table->timestampTz('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();

            $table->index('tenant_id');
            $table->foreign(['tenant_id', 'role_id'])->references(['tenant_id', 'id'])->on('roles')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE tenant_invitations ADD CONSTRAINT tenant_invitations_email_normalized CHECK (email = lower(btrim(email)))');
        DB::statement('ALTER TABLE tenant_invitations ADD CONSTRAINT tenant_invitations_single_outcome CHECK (accepted_at IS NULL OR revoked_at IS NULL)');
        DB::statement('CREATE UNIQUE INDEX tenant_invitations_one_pending ON tenant_invitations (tenant_id, email) WHERE accepted_at IS NULL AND revoked_at IS NULL');

        TenantRowSecurity::enable('tenant_invitations');

        // The invitee has no tenant context yet: this narrow definer function resolves a token hash
        // to its invitation without opening the table (todo/todo.md §17.1).
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION tenant_invitation_by_token(p_token_hash text)
            RETURNS TABLE (id char(26), tenant_id char(26))
            LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
                SELECT tenant_invitations.id, tenant_invitations.tenant_id
                FROM tenant_invitations
                WHERE tenant_invitations.token_hash = p_token_hash
            $$
            SQL);
        DB::statement('REVOKE ALL ON FUNCTION tenant_invitation_by_token(text) FROM PUBLIC');

        $app = config('database.roles.app.username');

        if (is_string($app) && DB::selectOne('SELECT 1 AS present FROM pg_roles WHERE rolname = ?', [$app]) !== null) {
            DB::statement(sprintf('GRANT EXECUTE ON FUNCTION tenant_invitation_by_token(text) TO "%s"', $app));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS tenant_invitation_by_token(text)');
        Schema::dropIfExists('tenant_invitations');
    }
};
