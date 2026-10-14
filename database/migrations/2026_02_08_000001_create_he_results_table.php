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
        Schema::create('he_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('he_admission_id')->constrained('he_admissions')->onDelete('cascade');
            $table->foreignId('bs_subject_id')->constrained('bs_subjects')->onDelete('cascade');
            $table->integer('semester_no');
            $table->enum('exam_type', ['Mid', 'Sessional', 'Final']);
            $table->integer('theory_marks')->nullable();
            $table->integer('practical_marks')->nullable();
            $table->integer('total_marks')->default(0);
            $table->boolean('is_absent')->default(false);
            
            // Locking mechanism - once submitted, only admin/principal can edit
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            
            $table->year('academic_year');
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Unique constraint: one result per student per subject per exam type per semester
            $table->unique(['he_admission_id', 'bs_subject_id', 'semester_no', 'exam_type'], 'uq_he_result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('he_results');
    }
};
