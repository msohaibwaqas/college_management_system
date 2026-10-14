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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_no')->unique();
            $table->string('type'); // Bus, Van, etc.
            $table->integer('capacity');
            $table->enum('ownership', ['owned', 'rented'])->default('owned');
            $table->enum('status', ['active', 'maintenance', 'out_of_service'])->default('active');
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->string('name');
            $table->string('cnic')->unique();
            $table->string('license_no');
            $table->date('license_expiry');
            $table->string('mobile');
            $table->timestamps();
        });

        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('start_point');
            $table->string('end_point');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('set null');
            $table->decimal('monthly_fee', 10, 2);
            $table->timestamps();
        });

        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('transport_routes')->onDelete('cascade');
            $table->string('stop_name');
            $table->time('pickup_time');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('student_transport', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('route_id')->constrained('transport_routes')->onDelete('cascade');
            $table->foreignId('stop_id')->constrained('route_stops')->onDelete('cascade');
            $table->date('assigned_from');
            $table->date('assigned_to')->nullable();
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transport');
        Schema::dropIfExists('route_stops');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('vehicles');
    }
};
