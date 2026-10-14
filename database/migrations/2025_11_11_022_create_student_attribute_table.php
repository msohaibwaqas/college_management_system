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
        Schema::create('student_attributes', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('student_id')
                  ->constrained('students', 'id')
                  ->onDelete('cascade');
            
            $table->foreignId('blood_group_id')->nullable()
                  ->constrained('blood_groups', 'id')
                  ->nullOnDelete();
            
            $table->foreignId('quota_id')->nullable()
                  ->constrained('quotas', 'id')
                  ->nullOnDelete();
            
            $table->string('student_cnic', 15)->nullable();
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Constraints
            $table->unique('student_id', 'uq_attr_student');
            
            // Indexes
            $table->index('blood_group_id');
            $table->index('quota_id');
            $table->index('student_cnic', 'idx_student_cnic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attributes');
    }
};
