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
        Schema::create('hostels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['boys', 'girls']);
            $table->integer('capacity');
            $table->foreignId('warden_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('hostel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_id')->constrained('hostels')->onDelete('cascade');
            $table->string('room_number');
            $table->string('floor')->nullable();
            $table->enum('type', ['single', 'double', 'triple', 'dormitory']);
            $table->integer('capacity');
            $table->decimal('monthly_rent', 10, 2);
            $table->enum('status', ['available', 'full', 'maintenance'])->default('available');
            $table->timestamps();
        });

        Schema::create('hostel_allotments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('hostel_rooms')->onDelete('cascade');
            $table->string('bed_number')->nullable();
            $table->date('allotted_from');
            $table->date('allotted_to')->nullable();
            $table->enum('status', ['active', 'vacated'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_allotments');
        Schema::dropIfExists('hostel_rooms');
        Schema::dropIfExists('hostels');
    }
};
