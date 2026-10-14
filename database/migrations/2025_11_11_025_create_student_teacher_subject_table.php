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
        Schema::create('student_teacher_subjects', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('student_id')
                  ->constrained('students', 'id')
                  ->onDelete('cascade');
            
            $table->foreignId('staff_id')
                  ->constrained('staff', 'id')
                  ->onDelete('cascade');
            
            $table->foreignId('subject_id')
                  ->constrained('subjects', 'id')
                  ->onDelete('cascade');
            
            $table->enum('class_level', ['11', '12']);
            $table->year('academic_year');
            $table->enum('assignment_type', ['compulsory', 'elective']);
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Constraints
            $table->unique(['student_id', 'subject_id', 'class_level', 'academic_year'], 'uq_student_teacher_subject');
            
            // Indexes
            $table->index(['student_id', 'staff_id'], 'idx_student_teacher');
            $table->index(['staff_id', 'student_id'], 'idx_teacher_students');
            $table->index(['subject_id', 'student_id'], 'idx_subject_students');
            $table->index(['student_id', 'academic_year'], 'idx_student_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_teacher_subjects');
    }
};
