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
        Schema::create('teacher_subject_assignments', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('staff_id')
                  ->constrained('staff', 'id')
                  ->onDelete('cascade');
            
            $table->foreignId('subject_id')
                  ->constrained('subjects', 'id')
                  ->onDelete('cascade');
            
            $table->enum('class_level', ['11', '12', 'both'])->default('both');
            $table->year('academic_year');
            $table->enum('section', ['A', 'B', 'C', 'D', 'E'])->nullable();
            $table->integer('max_students')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Constraints
            $table->unique(['staff_id', 'subject_id', 'class_level', 'academic_year', 'section'], 'uq_teacher_subject_year');
            
            // Indexes
            $table->index('staff_id', 'idx_teacher_assignment_staff');
            $table->index('subject_id', 'idx_teacher_assignment_subject');
            $table->index('academic_year', 'idx_teacher_assignment_year');
            $table->index('is_active', 'idx_teacher_assignment_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_subject_assignments');
    }
};
