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
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')
                  ->constrained('users', 'id')
                  ->onDelete('cascade');
            
            $table->string('employee_id', 50)->unique();
            $table->string('qualification', 255)->nullable();
            $table->string('specialization', 255)->nullable();
            $table->date('joining_date')->nullable();
            $table->tinyInteger('experience_years')->unsigned()->default(0);
            $table->text('bio')->nullable();
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Constraints
            $table->unique('user_id', 'uq_teacher_user');
            
            // Indexes
            $table->index('employee_id', 'idx_teacher_employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_profiles');
    }
};
