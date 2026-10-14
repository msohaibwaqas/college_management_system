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
        Schema::create('staff_subject_specializations', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('staff_id')
                  ->constrained('staff', 'id')
                  ->onDelete('cascade');
            
            $table->foreignId('subject_id')
                  ->constrained('subjects', 'id')
                  ->onDelete('cascade');
            
            $table->enum('proficiency_level', ['beginner', 'intermediate', 'expert'])
                  ->default('intermediate');
            $table->tinyInteger('years_of_experience')->unsigned()->default(0);
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Constraints
            $table->unique(['staff_id', 'subject_id'], 'uq_staff_subject');
            
            // Indexes
            $table->index('subject_id');
            $table->index('staff_id', 'idx_staff_specialization');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_subject_specializations');
    }
};
