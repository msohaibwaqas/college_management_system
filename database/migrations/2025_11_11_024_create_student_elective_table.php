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
        Schema::create('student_elective_subjects', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('student_id')
                  ->constrained('students', 'id')
                  ->onDelete('cascade');
            
            $table->foreignId('subject_id')
                  ->constrained('subjects', 'id');
            
            $table->tinyInteger('sequence')->unsigned();
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Constraints
            $table->unique(['student_id', 'sequence'], 'uq_elective_sequence');
            $table->unique(['student_id', 'subject_id'], 'uq_student_subject');
            
            // Indexes
            $table->index('subject_id');
        });
        
        // Add check constraint for sequence validation (skip for SQLite in testing)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE student_elective_subjects ADD CONSTRAINT chk_elective_sequence CHECK (sequence IN (1, 2, 3))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_elective_subjects');
    }
};
