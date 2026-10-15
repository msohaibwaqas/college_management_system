<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_struck_off_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');

            // The teacher (staff) who struck off this student
            $table->unsignedBigInteger('struck_off_by');
            $table->foreign('struck_off_by')->references('id')->on('staff');

            // The subject in which the threshold was breached
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');

            // Absence count at time of strike-off (for audit)
            $table->unsignedInteger('absence_count_at_strike')->default(0);

            $table->timestamp('struck_off_at');
            $table->text('struck_off_reason')->nullable();

            // Readmission info
            $table->unsignedBigInteger('readmitted_by')->nullable();
            $table->foreign('readmitted_by')->references('id')->on('users');
            $table->timestamp('readmitted_at')->nullable();
            $table->text('readmission_note')->nullable();

            // Academic year used to enforce 2-per-year cap
            $table->unsignedInteger('academic_year');

            // Current state of this log entry
            $table->enum('status', ['struck_off', 'readmitted'])->default('struck_off');

            $table->timestamps();

            // Indexes
            $table->index(['student_id', 'academic_year']);
            $table->index(['struck_off_by']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_struck_off_logs');
    }
};
