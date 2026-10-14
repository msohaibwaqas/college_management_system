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
        Schema::create('teacher_workloads', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('staff_id')
                  ->constrained('staff', 'id')
                  ->onDelete('cascade');
            
            $table->year('academic_year');
            $table->integer('total_assigned_subjects')->default(0);
            $table->integer('total_assigned_students')->default(0);
            $table->integer('max_allowed_students')->default(100);
            
            $table->enum('workload_status', ['underloaded', 'balanced', 'overloaded'])
                  ->default('balanced');
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Constraints
            $table->unique(['staff_id', 'academic_year'], 'uq_teacher_workload_year');
            
            // Indexes
            $table->index('workload_status', 'idx_workload_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_workloads');
    }
};
