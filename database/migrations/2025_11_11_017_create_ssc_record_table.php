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
        Schema::create('ssc_records', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('student_id')
                  ->constrained('students', 'id')
                  ->onDelete('cascade');
            
            $table->foreignId('bise_id')
                  ->constrained('bise_records', 'id');
            
            $table->string('roll_no', 50);
            $table->string('reg_no', 50);
            $table->string('school_name', 255);
            $table->integer('total_mark');
            $table->integer('obtained_mark');
            $table->year('passing_year');
            $table->enum('passing_session', ['1st Annual', '2nd Annual']);
            $table->enum('ssc_group', ['Science', 'Arts']);
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Constraints
            $table->unique(['roll_no', 'reg_no'], 'uq_ssc_roll_reg');
            
            // Indexes
            $table->index('student_id');
            $table->index('bise_id');
        });
        
        // Add check constraint for marks validation (skip for SQLite in testing)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE ssc_records ADD CONSTRAINT chk_ssc_marks CHECK (obtained_mark <= total_mark)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssc_records');
    }
};
