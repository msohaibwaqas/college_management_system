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
        Schema::table('admissions', function (Blueprint $table) {
            $table->unique(['admission_year', 'bise_roll_11'], 'uq_adm_year_bise_11');
            $table->unique(['admission_year', 'bise_roll_12'], 'uq_adm_year_bise_12');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropUnique('uq_adm_year_bise_11');
            $table->dropUnique('uq_adm_year_bise_12');
        });
    }
};
