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
        Schema::table('timetables', function (Blueprint $table) {
            $table->foreignId('subject_group_id')
                  ->nullable()
                  ->after('admission_year')
                  ->constrained('subject_groups')
                  ->nullOnDelete();

            $table->boolean('is_break')
                  ->default(false)
                  ->after('staff_id');

            // Make subject_id nullable for break entries
            $table->foreignId('subject_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->dropForeign(['subject_group_id']);
            $table->dropColumn(['subject_group_id', 'is_break']);
        });
    }
};
