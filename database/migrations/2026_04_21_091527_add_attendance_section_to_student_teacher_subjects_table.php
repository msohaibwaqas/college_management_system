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
        Schema::table('student_teacher_subjects', function (Blueprint $table) {
            $table->string('attendance_section', 50)->nullable()->after('assignment_type');
            $table->index(['staff_id', 'subject_id', 'attendance_section'], 'idx_sts_staff_subject_section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_teacher_subjects', function (Blueprint $table) {
            $table->dropIndex('idx_sts_staff_subject_section');
            $table->dropColumn('attendance_section');
        });
    }
};
