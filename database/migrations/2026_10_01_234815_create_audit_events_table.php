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
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 100);
            $table->string('subject_type', 100)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->text('reason')->nullable();
            $table->string('source', 10);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('correlation_id', 64);
            $table->string('causation_id', 64)->nullable();
            $table->timestampTz('occurred_at');

            $table->index(['tenant_id', 'occurred_at']);
            $table->index('correlation_id');
            $table->index(['subject_type', 'subject_id']);
        });

        DB::statement("ALTER TABLE audit_events ADD CONSTRAINT audit_events_source_check CHECK (source IN ('http', 'cli', 'queue', 'api', 'ai'))");

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION audit_events_append_only() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'AUDIT_APPEND_ONLY: audit_events cannot be modified or deleted';
            END;
            $$
            SQL);

        DB::statement('CREATE TRIGGER audit_events_no_row_change BEFORE UPDATE OR DELETE ON audit_events FOR EACH ROW EXECUTE FUNCTION audit_events_append_only()');
        DB::statement('CREATE TRIGGER audit_events_no_truncate BEFORE TRUNCATE ON audit_events FOR EACH STATEMENT EXECUTE FUNCTION audit_events_append_only()');

        // Platform events (sign-ins) have no tenant: anyone may write them, only operators read them.
        TenantRowSecurity::enable('audit_events', extraInsertPredicate: 'tenant_id IS NULL');

        $app = config()->string('database.roles.app.username');

        if (DB::table('pg_roles')->where('rolname', $app)->exists()) {
            DB::statement("REVOKE UPDATE, DELETE, TRUNCATE ON audit_events FROM \"{$app}\"");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        DB::statement('DROP FUNCTION IF EXISTS audit_events_append_only()');
    }
};
