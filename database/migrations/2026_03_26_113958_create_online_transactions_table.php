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
        Schema::create('online_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_voucher_id')->constrained('fee_vouchers')->onDelete('cascade');
            $table->string('gateway'); // e.g. 'jazzcash', 'easypaisa'
            $table->string('transaction_reference')->unique(); // ID from gateway
            $table->decimal('amount', 10, 2);
            $table->string('mobile_number')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->json('gateway_response')->nullable(); // Full payload for audit
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_transactions');
    }
};
