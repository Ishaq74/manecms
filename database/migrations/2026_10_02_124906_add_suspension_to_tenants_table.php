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
        Schema::table('tenants', function (Blueprint $table): void {
            $table->timestampTz('suspended_at')->nullable();
            $table->string('suspension_reason', 500)->nullable();
        });

        DB::statement('ALTER TABLE tenants ADD CONSTRAINT tenants_suspension_has_reason CHECK ((suspended_at IS NULL) = (suspension_reason IS NULL))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['suspended_at', 'suspension_reason']);
        });
    }
};
