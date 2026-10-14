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
        Schema::create('graduation_batches', function (Blueprint $table) {
            $table->id();
            $table->string('program'); // BS, ADP, Inter.
            $table->year('academic_year');
            $table->enum('status', ['draft', 'processed', 'completed'])->default('draft');
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('graduation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('graduation_batches')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('graduation_date')->nullable();
            $table->enum('status', ['eligible', 'ineligible', 'graduated'])->default('eligible');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('alumni_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('current_city')->nullable();
            $table->string('employer')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_profiles');
        Schema::dropIfExists('graduation_records');
        Schema::dropIfExists('graduation_batches');
    }
};
