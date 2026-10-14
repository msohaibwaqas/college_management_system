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
        Schema::create('staff_service_histories', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('staff_id')
                  ->constrained('staff', 'id')
                  ->onDelete('cascade');
            
            $table->string('institution_name', 255);
            $table->string('designation', 100);
            $table->tinyInteger('bps')->unsigned();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->string('reason_for_leaving', 255)->nullable();
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Indexes
            $table->index('staff_id', 'idx_staff_service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_service_histories');
    }
};
