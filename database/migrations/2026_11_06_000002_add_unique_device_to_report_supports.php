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
        Schema::table('report_supports', function (Blueprint $table): void {
            $table->unique(['report_id', 'device_token'], 'report_supports_unique_device');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_supports', function (Blueprint $table): void {
            $table->dropUnique('report_supports_unique_device');
        });
    }
};

