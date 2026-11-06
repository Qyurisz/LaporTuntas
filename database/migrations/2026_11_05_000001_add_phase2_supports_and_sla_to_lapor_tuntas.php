<?php

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
        Schema::table('categories', function (Blueprint $table): void {
            $table->unsignedInteger('sla_hours')->default(72)->after('icon');
        });

        Schema::table('reports', function (Blueprint $table): void {
            $table->unsignedInteger('supports_count')->default(0)->index()->after('rejection_reason');
        });

        Schema::create('report_supports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->string('ip_hash', 64)->index();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['report_id', 'ip_hash'], 'report_supports_unique_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_supports');

        Schema::table('reports', function (Blueprint $table): void {
            $table->dropColumn('supports_count');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('sla_hours');
        });
    }
};

