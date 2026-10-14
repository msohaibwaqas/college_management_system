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
        Schema::create('staff_academic_qualifications', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('staff_id')
                  ->constrained('staff', 'id')
                  ->onDelete('cascade');
            
            $table->enum('degree_level', ['ssc', 'hssc', 'bachelor', 'master', 'bs', 'mphil', 'phd', 'postdoc']);
            $table->string('subject', 150)->nullable();
            $table->string('group_field', 50)->nullable();
            $table->integer('total_marks')->nullable();
            $table->integer('obtained_marks')->nullable();
            $table->string('degree_image', 255)->nullable();
            $table->boolean('is_verified')->default(false);
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Constraints
            $table->unique(['staff_id', 'degree_level'], 'uq_staff_degree');
            
            // Indexes
            $table->index(['staff_id', 'degree_level'], 'idx_staff_degree_level');
        });
        
        // Add check constraint for marks validation (skip for SQLite in testing)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE staff_academic_qualifications ADD CONSTRAINT chk_staff_marks CHECK (obtained_marks <= total_marks)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_academic_qualifications');
    }
};
