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
        Schema::create('institute_settings', function (Blueprint $table) {
            $table->id();
            $table->string('institute_title', 150);
            $table->string('institute_subtitle', 150)->nullable();
            $table->string('institute_location', 100)->nullable();
            $table->string('short_name', 50)->nullable();
            $table->string('full_name', 200)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('reg_number', 50)->nullable();
            $table->string('ems_code', 50)->nullable();
            $table->string('admission_type', 100)->nullable();
            $table->string('landline', 20)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->string('inquiry_email', 100)->nullable();
            $table->string('inquiry_password')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->text('motto')->nullable();
            $table->text('address')->nullable();
            $table->text('google_map_url')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institute_settings');
    }
};
