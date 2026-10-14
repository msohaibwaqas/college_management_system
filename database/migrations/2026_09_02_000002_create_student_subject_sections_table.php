<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Replace the per-year 'section' on admissions with a dedicated
     * student_subject_sections table (section is per student per subject).
     */
    public function up(): void
    {
        // Remove wrong column added previously
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn('section');
        });

        // Create dedicated sections table (section is per-student per-subject per-year)
        Schema::create('student_subject_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedInteger('admission_year');
            $table->string('section', 50)->comment('Section name, e.g. G5, ICS-3, A, B');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // A student has exactly one section per subject per year
            $table->unique(['student_id', 'subject_id', 'admission_year'], 'uq_student_subject_section');

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_subject_sections');

        Schema::table('admissions', function (Blueprint $table) {
            $table->string('section', 50)->nullable()->after('status');
        });
    }
};
