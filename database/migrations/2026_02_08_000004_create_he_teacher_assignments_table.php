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
        Schema::create('he_teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('he_admission_id')->constrained('he_admissions')->onDelete('cascade');
            $table->foreignId('bs_subject_id')->constrained('bs_subjects')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade'); // The teacher
            $table->year('academic_year');
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            
            // Unique: one teacher per student per subject
            $table->unique(['he_admission_id', 'bs_subject_id'], 'uq_he_teacher_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('he_teacher_assignments');
    }
};
