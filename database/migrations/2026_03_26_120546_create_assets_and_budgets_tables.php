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
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('asset_categories')->onDelete('cascade');
            $table->string('name');
            $table->string('serial_number')->unique()->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 15, 2)->nullable();
            $table->enum('condition', ['new', 'good', 'damaged', 'disposed'])->default('good');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('department_name');
            $table->string('fiscal_year'); // e.g., 2025-26
            $table->decimal('allocated_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('expenditures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->string('description');
            $table->string('paid_to')->nullable();
            $table->string('category')->nullable(); // Utility, Maintenance, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenditures');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
    }
};
