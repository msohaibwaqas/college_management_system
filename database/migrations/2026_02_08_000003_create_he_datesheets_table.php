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
        Schema::create('he_datesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->onDelete('cascade');
            $table->integer('semester_no');
            $table->foreignId('bs_subject_id')->constrained('bs_subjects')->onDelete('cascade');
            $table->enum('exam_type', ['Mid', 'Final']);
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->year('academic_year');
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Unique: one entry per subject per exam type per academic year
            $table->unique(['program_id', 'semester_no', 'bs_subject_id', 'exam_type', 'academic_year'], 'uq_he_datesheet');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('he_datesheets');
    }
};
