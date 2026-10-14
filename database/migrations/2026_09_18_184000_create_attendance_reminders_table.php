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
        Schema::create('attendance_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->string('class_level', 20);
            $table->integer('admission_year');
            $table->string('section', 50)->nullable();
            $table->date('attendance_date');
            $table->text('message')->nullable();
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');

            $table->index(['staff_id', 'is_used']);
            $table->index(['staff_id', 'subject_id', 'class_level', 'admission_year', 'attendance_date', 'is_used'], 'att_reminders_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_reminders');
    }
};
