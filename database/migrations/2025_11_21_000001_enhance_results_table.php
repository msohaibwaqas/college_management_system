<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite workaround: Recreate table with new schema
            Schema::dropIfExists('results');
            
            Schema::create('results', function (Blueprint $table) {
                $table->id();
                
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
                $table->foreignId('subject_id')->constrained('subjects');
                
                $table->enum('class_level', ['11', '12']);
                $table->string('exam_type', 50);
                $table->string('exam_name', 100);
                $table->year('academic_year');
                $table->integer('total_marks')->nullable();
                
                // Marks
                $table->integer('obtained_theory')->nullable();
                $table->integer('obtained_practical')->nullable()->default(0);
                
                // Audit trail
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
                
                $table->timestamps();
                $table->timestamp('deleted_at')->nullable();
                
                // Constraints
                $table->unique(['student_id', 'subject_id', 'class_level', 'exam_type', 'exam_name', 'academic_year'], 'uq_result_extended');
                
                // Indexes
                $table->index(['academic_year', 'exam_type', 'class_level'], 'idx_result_academic_year');
                $table->index(['student_id', 'subject_id', 'academic_year'], 'idx_result_student_subject');
            });
            
            return;
        }

        Schema::table('results', function (Blueprint $table) {
            // Drop existing constraint
            $table->dropUnique('uq_result_entry');
            
            // Add new columns
            $table->integer('total_marks')->after('academic_year')->nullable();
            $table->string('exam_name')->after('exam_type')->nullable()->comment('Specific name for class tests, e.g., "Chapter 1"');
        });

        // Modify exam_type to be string to support more types including 'Class_test'
        DB::statement("ALTER TABLE results MODIFY COLUMN exam_type VARCHAR(50) NOT NULL");

        Schema::table('results', function (Blueprint $table) {
            // Add new unique constraint
        });
        
        // Make exam_name not null (after migration, assuming empty table or data fixed)
        DB::statement("ALTER TABLE results MODIFY COLUMN exam_name VARCHAR(100) NOT NULL");

        Schema::table('results', function (Blueprint $table) {
             $table->unique(['student_id', 'subject_id', 'class_level', 'exam_type', 'exam_name', 'academic_year'], 'uq_result_extended');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropUnique('uq_result_extended');
            $table->dropColumn('total_marks');
            $table->dropColumn('exam_name');
        });

        // Revert exam_type to enum (approximate, data loss possible if not careful)
        DB::statement("ALTER TABLE results MODIFY COLUMN exam_type ENUM('Oct_test', 'Dec_test', 'Send_up', 'Final') NOT NULL");
        
        Schema::table('results', function (Blueprint $table) {
            $table->unique(['student_id', 'subject_id', 'class_level', 'exam_type', 'academic_year'], 'uq_result_entry');
        });
    }
};
