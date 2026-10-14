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
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            
            // Core admission data
            $table->foreignId('student_id')
                  ->constrained('students', 'id')
                  ->onDelete('cascade');
            
            $table->year('admission_year');
            $table->year('academic_year');
            
            // Unique identifiers
            $table->string('college_roll_no', 50);
            $table->string('hssc_reg_no', 50)->unique();
            
            // BISE roll numbers
            $table->string('bise_roll_11', 50)->nullable();
            $table->string('bise_roll_12', 50)->nullable();
            
            // Class and status tracking
            $table->enum('current_class', ['11', '12'])->default('11');
            $table->enum('status', ['Applied', 'Admitted', 'Active', 'Graduated', 'Dropped', 'Suspended'])
                  ->default('Applied');
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Indexes
            $table->index(['admission_year', 'current_class', 'status']);
            $table->unique(['student_id', 'academic_year']);
            $table->unique(['college_roll_no', 'admission_year'], 'uq_roll_no_year');
        });
        
        // Add check constraint for academic year validation (skip for SQLite in testing)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE admissions ADD CONSTRAINT chk_academic_year CHECK (academic_year >= admission_year)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
