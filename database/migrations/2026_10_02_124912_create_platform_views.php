<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cross-tenant reads for the operator back-office (todo/pass-06-operator-back-office.md).
 *
 * The views belong to the table owner, so they read across tenants, but each one
 * returns rows only when `app.user_id` is a platform operator. The application role
 * may select from them and nothing else.
 */
return new class extends Migration
{
    private const array VIEWS = ['platform_tenants', 'platform_tenant_members', 'platform_audit_events'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION platform_current_user_is_operator() RETURNS boolean
            LANGUAGE sql STABLE SET search_path = public AS $$
                SELECT EXISTS (
                    SELECT 1 FROM users
                    WHERE users.id = nullif(current_setting('app.user_id', true), '')::bigint
                      AND users.platform_role IS NOT NULL
                )
            $$
            SQL);

        DB::statement(<<<'SQL'
            CREATE VIEW platform_tenants WITH (security_barrier) AS
            SELECT
                tenants.id,
                tenants.name,
                tenants.created_at,
                tenants.archived_at,
                tenants.suspended_at,
                tenants.suspension_reason,
                tenants.require_mfa,
                CASE
                    WHEN tenants.suspended_at IS NOT NULL THEN 'suspended'
                    WHEN tenants.archived_at IS NOT NULL THEN 'archived'
                    ELSE 'active'
                END AS status,
                owners.id AS owner_id,
                owners.name AS owner_name,
                owners.email AS owner_email,
                (SELECT count(*) FROM tenant_members WHERE tenant_members.tenant_id = tenants.id) AS members_count,
                (SELECT count(*) FROM workspaces WHERE workspaces.tenant_id = tenants.id AND workspaces.archived_at IS NULL) AS workspaces_count
            FROM tenants
            LEFT JOIN tenant_members AS ownership ON ownership.tenant_id = tenants.id AND ownership.is_owner
            LEFT JOIN users AS owners ON owners.id = ownership.user_id
            WHERE platform_current_user_is_operator()
            SQL);

        DB::statement(<<<'SQL'
            CREATE VIEW platform_tenant_members WITH (security_barrier) AS
            SELECT
                tenant_members.id,
                tenant_members.tenant_id,
                tenant_members.user_id,
                users.name,
                users.email,
                users.email_verified_at,
                users.platform_role,
                roles.name AS role_name,
                roles.system_key AS role_system_key,
                tenant_members.is_owner,
                tenant_members.created_at
            FROM tenant_members
            JOIN users ON users.id = tenant_members.user_id
            JOIN roles ON roles.id = tenant_members.role_id
            WHERE platform_current_user_is_operator()
            SQL);

        DB::statement(<<<'SQL'
            CREATE VIEW platform_audit_events WITH (security_barrier) AS
            SELECT *
            FROM audit_events
            WHERE audit_events.tenant_id IS NULL AND platform_current_user_is_operator()
            SQL);

        // Suspension is the only cross-tenant write; the function re-checks the operator itself.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION platform_set_tenant_suspension(p_tenant_id char(26), p_reason text) RETURNS boolean
            LANGUAGE plpgsql SECURITY DEFINER SET search_path = public AS $$
            BEGIN
                IF NOT platform_current_user_is_operator() THEN
                    RAISE EXCEPTION 'PLATFORM_OPERATOR_REQUIRED' USING ERRCODE = 'insufficient_privilege';
                END IF;

                UPDATE tenants
                SET suspended_at = CASE WHEN p_reason IS NULL THEN NULL ELSE now() END,
                    suspension_reason = p_reason,
                    updated_at = now()
                WHERE id = p_tenant_id;

                RETURN FOUND;
            END;
            $$
            SQL);
        DB::statement('REVOKE ALL ON FUNCTION platform_set_tenant_suspension(char, text) FROM PUBLIC');

        $app = config('database.roles.app.username');

        if (is_string($app) && DB::selectOne('SELECT 1 AS present FROM pg_roles WHERE rolname = ?', [$app]) !== null) {
            DB::statement(sprintf('GRANT EXECUTE ON FUNCTION platform_set_tenant_suspension(char, text) TO "%s"', $app));

            foreach (self::VIEWS as $view) {
                // Simple views are updatable: the application only ever reads them.
                DB::statement(sprintf('REVOKE INSERT, UPDATE, DELETE, TRUNCATE ON %s FROM "%s"', $view, $app));
                DB::statement(sprintf('GRANT SELECT ON %s TO "%s"', $view, $app));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS platform_set_tenant_suspension(char, text)');

        foreach (array_reverse(self::VIEWS) as $view) {
            DB::statement("DROP VIEW IF EXISTS {$view}");
        }

        DB::statement('DROP FUNCTION IF EXISTS platform_current_user_is_operator()');
    }
};
