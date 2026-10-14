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
        Schema::create('he_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('he_admission_id')->constrained('he_admissions')->onDelete('cascade');
            $table->foreignId('bs_subject_id')->constrained('bs_subjects')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade'); // Teacher who marked
            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'leave', 'late'])->default('present');
            $table->string('remarks')->nullable();
            
            $table->timestamps();
            
            // Unique: one attendance per student per subject per day
            $table->unique(['he_admission_id', 'bs_subject_id', 'attendance_date'], 'uq_he_attendance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('he_attendances');
    }
};
