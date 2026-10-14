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
        Schema::create('he_student_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('he_admission_id')->constrained('he_admissions')->onDelete('cascade');
            $table->foreignId('bs_subject_id')->constrained('bs_subjects')->onDelete('cascade');
            $table->integer('semester_no');
            $table->enum('status', ['Enrolled', 'Passed', 'Failed', 'Withdrawn'])->default('Enrolled');
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            
            $table->unique(['he_admission_id', 'bs_subject_id'], 'uq_he_adm_subj');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('he_student_subjects');
    }
};
