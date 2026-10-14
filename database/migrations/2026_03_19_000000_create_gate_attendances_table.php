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
        if (!Schema::hasTable('gate_attendances')) {
            Schema::create('gate_attendances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->date('date');
                $table->time('in_time')->nullable();
                $table->time('out_time')->nullable();
                $table->string('status')->default('Present');
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We probably shouldn't drop it if it was created manually, 
        // but for a standard migration flow, we do.
        // Schema::dropIfExists('gate_attendances');
    }
};
