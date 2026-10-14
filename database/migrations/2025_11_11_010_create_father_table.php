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
        Schema::create('fathers', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('student_id')
                  ->constrained('students', 'id')
                  ->onDelete('cascade');
            
            $table->string('name', 120);
            $table->string('cnic', 15)->nullable();
            $table->string('contact', 20)->nullable();
            $table->string('occupation', 120)->nullable();
            $table->integer('annual_income')->nullable();
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Constraints
            $table->unique('student_id', 'uq_father_per_student');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fathers');
    }
};
