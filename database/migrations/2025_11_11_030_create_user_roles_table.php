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
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')
                  ->constrained('users', 'id')
                  ->onDelete('cascade');
            
            $table->foreignId('role_id')
                  ->constrained('roles', 'id')
                  ->onDelete('cascade');
            
            // Audit trail
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
            
            // Constraints
            $table->unique(['user_id', 'role_id'], 'uq_user_role');
            
            // Indexes
            $table->index('user_id', 'idx_user_roles_user');
            $table->index('role_id', 'idx_user_roles_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
