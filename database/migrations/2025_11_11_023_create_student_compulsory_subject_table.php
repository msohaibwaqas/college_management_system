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
        Schema::create('student_compulsory_subjects', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('student_id')
                  ->constrained('students', 'id')
                  ->onDelete('cascade');
            
            $table->foreignId('subject_id')
                  ->constrained('subjects', 'id');
            
            $table->enum('class_level', ['11', '12']);
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Constraints
            $table->unique(['student_id', 'subject_id', 'class_level'], 'uq_student_compulsory');
            
            // Indexes
            $table->index('subject_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_compulsory_subjects');
    }
};
