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
        Schema::create('impersonations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('operator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 500);
            $table->timestampTz('started_at');
            $table->timestampTz('expires_at');
            $table->timestampTz('ended_at')->nullable();
            $table->string('end_reason', 20)->nullable();

            $table->index(['user_id', 'started_at']);
        });

        DB::statement('ALTER TABLE impersonations ADD CONSTRAINT impersonations_other_user CHECK (operator_id <> user_id)');
        DB::statement("ALTER TABLE impersonations ADD CONSTRAINT impersonations_at_most_30_minutes CHECK (expires_at > started_at AND expires_at <= started_at + interval '30 minutes')");
        DB::statement("ALTER TABLE impersonations ADD CONSTRAINT impersonations_end_reason_known CHECK ((ended_at IS NULL) = (end_reason IS NULL) AND (end_reason IS NULL OR end_reason IN ('stopped', 'expired')))");
        DB::statement('CREATE UNIQUE INDEX impersonations_one_open_per_operator ON impersonations (operator_id) WHERE ended_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impersonations');
    }
};
