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
            $table->string('device_token', 64)->nullable()->after('report_id');
            $table->string('photo_path', 255)->nullable()->after('user_agent_hash');
            $table->text('comment')->nullable()->after('photo_path');

            $table->index('device_token', 'report_supports_device_token_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_supports', function (Blueprint $table): void {
            $table->dropIndex('report_supports_device_token_idx');
            $table->dropColumn(['device_token', 'photo_path', 'comment']);
        });
    }
};

