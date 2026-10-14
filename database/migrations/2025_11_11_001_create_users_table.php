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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            $table->string('username', 50)->unique();
            $table->string('email', 120)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->string('remember_token', 100)->nullable();
            
            $table->string('first_name', 80);
            $table->string('last_name', 80)->nullable();
            $table->string('cnic', 15)->nullable()->unique();
            $table->string('contact_number', 20)->nullable();
            $table->string('profile_picture', 255)->nullable();
            
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])
                  ->default('pending');
            $table->boolean('is_super_admin')->default(false);
            
            // Two Factor Authentication
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            
            // Login tracking
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            
            // Audit trail (self-referencing)
            $table->foreignId('created_by')->nullable()
                  ->constrained('users', 'id')
                  ->nullOnDelete();
            $table->foreignId('updated_by')->nullable()
                  ->constrained('users', 'id')
                  ->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()
                  ->constrained('users', 'id')
                  ->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Indexes
            $table->index('status', 'idx_user_status');
            $table->index('email', 'idx_user_email');
            $table->index('contact_number', 'idx_user_contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
