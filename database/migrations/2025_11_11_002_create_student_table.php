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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            
            $table->string('first_name', 80);
            $table->string('last_name', 80)->nullable();
            $table->date('date_of_birth');
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Indexes
            $table->index(['first_name', 'last_name', 'date_of_birth'], 'idx_students_name_dob');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
