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
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('student_id')
                  ->constrained('students', 'id')
                  ->onDelete('cascade');
            
            $table->foreignId('subject_id')
                  ->constrained('subjects', 'id');
            
            $table->enum('class_level', ['11', '12']);
            $table->enum('exam_type', ['Oct_test', 'Dec_test', 'Send_up', 'Final']);
            $table->year('academic_year');
            
            // Marks
            $table->integer('obtained_theory')->nullable();
            $table->integer('obtained_practical')->nullable()->default(0);
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Constraints
            $table->unique(['student_id', 'subject_id', 'class_level', 'exam_type', 'academic_year'], 'uq_result_entry');
            
            // Indexes
            $table->index(['academic_year', 'exam_type', 'class_level'], 'idx_result_academic_year');
            $table->index(['student_id', 'subject_id', 'academic_year'], 'idx_result_student_subject');
        });
        
        // Add check constraint for marks validation (skip for SQLite in testing)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE results ADD CONSTRAINT chk_result_marks CHECK (obtained_theory IS NULL OR obtained_theory >= 0)');
            DB::statement('ALTER TABLE results ADD CONSTRAINT chk_result_practical_marks CHECK (obtained_practical IS NULL OR obtained_practical >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
