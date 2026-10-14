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
        Schema::create('staff_promotion_histories', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('staff_id')
                  ->constrained('staff', 'id')
                  ->onDelete('cascade');
            
            $table->tinyInteger('promotion_number')->unsigned();
            $table->string('previous_designation', 100);
            $table->string('new_designation', 100);
            $table->tinyInteger('previous_bps')->unsigned();
            $table->tinyInteger('new_bps')->unsigned();
            $table->date('promotion_date');
            $table->string('promotion_order_number', 100)->nullable();
            $table->date('promotion_order_date')->nullable();
            $table->string('promotion_order_file', 255)->nullable();
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Constraints
            $table->unique(['staff_id', 'promotion_number'], 'uq_staff_promotion');
            
            // Indexes
            $table->index('staff_id', 'idx_staff_promotions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_promotion_histories');
    }
};
