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
        Schema::table('he_admissions', function (Blueprint $table) {
            $table->string('uni_reg_no')->nullable()->after('uni_roll_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('he_admissions', function (Blueprint $table) {
            $table->dropColumn('uni_reg_no');
        });
    }
};
