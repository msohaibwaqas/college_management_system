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
        // 1. Definition of Salary Grades/Structures
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('base_salary', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Earnings and Deductions bounded to a Salary Structure
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_structure_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g. "Housing Allowance", "Income Tax"
            $table->enum('type', ['earning', 'deduction']);
            $table->enum('amount_type', ['fixed', 'percentage']); // percentage is based on base_salary
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });

        // 3. Link Staff to a Salary Structure and store Bank Info
        Schema::table('staff', function (Blueprint $table) {
            $table->foreignId('salary_structure_id')->nullable()->constrained('salary_structures')->nullOnDelete();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_title')->nullable();
            $table->string('bank_account_number')->nullable();
        });

        // 4. Monthly Batch Process tracking
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->date('payroll_month'); // Stores e.g. 2026-03-01
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'finalized', 'paid'])->default('draft');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Individual Staff Salary Slips per run
        Schema::create('salary_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->decimal('base_salary', 10, 2);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('absence_deduction', 10, 2)->default(0); // Penalties for unapproved absences
            $table->decimal('net_salary', 10, 2);
            $table->json('components_breakdown'); // Snapshot of components {"earnings": [...], "deductions": [...]}
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slips');
        Schema::dropIfExists('payroll_runs');
        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['salary_structure_id']);
            $table->dropColumn(['salary_structure_id', 'bank_name', 'bank_account_title', 'bank_account_number']);
        });
        Schema::dropIfExists('salary_components');
        Schema::dropIfExists('salary_structures');
    }
};
