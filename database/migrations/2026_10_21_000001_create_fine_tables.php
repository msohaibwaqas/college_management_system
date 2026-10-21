<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fine_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->enum('fine_type', ['lecture_absent', 'test_absent', 'test_fail']);
            $table->string('exam_name', 150)->nullable(); // Used for test_absent and test_fail
            $table->decimal('amount_per_unit', 10, 2);    // e.g. 1.00, 50.00, 100.00, 150.00
            $table->decimal('pass_percentage', 5, 2)->default(33.00); // For test_fail
            $table->year('academic_year')->nullable();
            $table->string('class_level', 10)->nullable(); // '11', '12', or null for all
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['fine_type', 'is_active']);
            $table->index(['exam_name', 'academic_year']);
        });

        Schema::create('student_fines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('admission_id')->nullable();
            $table->unsignedBigInteger('fine_rule_id')->nullable();
            $table->enum('fine_type', ['lecture_absent', 'test_absent', 'test_fail']);
            $table->string('exam_name', 150)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->year('academic_year');
            $table->string('period', 50)->nullable(); // e.g. "2026-10" or "October 2026"
            $table->integer('units_count')->default(1); // count of absents or failed papers
            $table->decimal('amount_per_unit', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('waived_amount', 10, 2)->default(0.00);
            $table->decimal('balance_amount', 10, 2);
            $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'waived'])->default('unpaid');
            $table->text('reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->text('payment_note')->nullable();
            $table->timestamp('waived_at')->nullable();
            $table->unsignedBigInteger('waived_by')->nullable();
            $table->text('waiver_reason')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('admission_id')->references('id')->on('admissions')->onDelete('set null');
            $table->foreign('fine_rule_id')->references('id')->on('fine_rules')->onDelete('set null');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null');

            $table->index(['student_id', 'status']);
            $table->index(['fine_type', 'academic_year', 'period']);
            $table->index(['exam_name', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fines');
        Schema::dropIfExists('fine_rules');
    }
};
