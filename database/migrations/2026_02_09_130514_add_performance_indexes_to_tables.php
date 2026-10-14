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
        /*
        Schema::table('admissions', function (Blueprint $table) {
            $table->index('admission_year');
            $table->index('status');
            $table->index('current_class');
            $table->index('program_type');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->index('first_name');
            $table->index('last_name');
        });
        */

        Schema::table('attendances', function (Blueprint $table) {
            $table->index('attendance_date');
            $table->index('status');
        });

        Schema::table('results', function (Blueprint $table) {
            $table->index('exam_type');
            $table->index('academic_year');
        });

        Schema::table('he_admissions', function (Blueprint $table) {
            $table->index('current_semester');
            $table->index('program_id');
            $table->index('status');
            $table->index('admission_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropIndex(['admission_year']);
            $table->dropIndex(['status']);
            $table->dropIndex(['current_class']);
            $table->dropIndex(['program_type']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['first_name']);
            $table->dropIndex(['last_name']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['attendance_date']);
            $table->dropIndex(['status']);
        });

        Schema::table('results', function (Blueprint $table) {
            $table->dropIndex(['exam_type']);
            $table->dropIndex(['academic_year']);
        });

        Schema::table('he_admissions', function (Blueprint $table) {
            $table->dropIndex(['current_semester']);
            $table->dropIndex(['program_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['admission_year']);
        });
    }
};
