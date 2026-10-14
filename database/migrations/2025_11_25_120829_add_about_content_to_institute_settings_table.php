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
        Schema::table('institute_settings', function (Blueprint $table) {
            $table->text('our_history')->nullable()->after('banner_image');
            $table->text('our_mission')->nullable()->after('our_history');
            $table->text('our_vision')->nullable()->after('our_mission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institute_settings', function (Blueprint $table) {
            $table->dropColumn(['our_history', 'our_mission', 'our_vision']);
        });
    }
};
