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
        Schema::create('he_seating_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('he_datesheet_id')->constrained('he_datesheets')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
        });
        
        Schema::create('he_seating_plan_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('he_seating_plan_id')->constrained('he_seating_plans')->onDelete('cascade');
            $table->foreignId('he_admission_id')->constrained('he_admissions')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->integer('seat_number')->nullable();
            $table->integer('row_number')->nullable();
            
            $table->timestamps();
            
            // Unique: one seat per student per seating plan
            $table->unique(['he_seating_plan_id', 'he_admission_id'], 'uq_he_seating_detail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('he_seating_plan_details');
        Schema::dropIfExists('he_seating_plans');
    }
};
