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
            $table->string('color_primary', 7)->default('#006159')->after('inquiry_email');
            $table->string('color_secondary', 7)->default('#2e9f82')->after('color_primary');
            $table->string('color_highlight', 7)->default('#d6ec8c')->after('color_secondary');
            $table->string('color_success', 7)->default('#68c170')->after('color_highlight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institute_settings', function (Blueprint $table) {
            $table->dropColumn(['color_primary', 'color_secondary', 'color_highlight', 'color_success']);
        });
    }
};
