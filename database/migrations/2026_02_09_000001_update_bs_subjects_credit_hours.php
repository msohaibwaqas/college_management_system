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
        Schema::table('bs_subjects', function (Blueprint $table) {
            $table->dropColumn('credit_hours');
            $table->integer('theory_credit_hours')->default(0)->after('code');
            $table->integer('practical_credit_hours')->default(0)->after('theory_credit_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bs_subjects', function (Blueprint $table) {
            $table->dropColumn(['theory_credit_hours', 'practical_credit_hours']);
            $table->integer('credit_hours')->default(3)->after('code');
        });
    }
};
