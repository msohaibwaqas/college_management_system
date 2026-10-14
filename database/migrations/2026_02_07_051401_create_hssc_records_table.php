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
        Schema::create('hssc_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('bise_id')->constrained('bise_records')->onDelete('cascade');
            $table->string('roll_no');
            $table->string('reg_no');
            $table->string('college_name');
            $table->integer('total_mark');
            $table->integer('obtained_mark');
            $table->integer('passing_year');
            $table->string('passing_session');
            $table->string('hssc_group');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hssc_records');
    }
};
