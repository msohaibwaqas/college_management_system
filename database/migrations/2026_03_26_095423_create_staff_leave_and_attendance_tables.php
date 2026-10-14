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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->integer('default_days')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_document')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('staff_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->year('year');
            $table->integer('allocated_days');
            $table->integer('used_days')->default(0);
            $table->integer('pending_days')->default(0);
            $table->timestamps();
            // Each staff has one balance per leave type per year
            $table->unique(['staff_id', 'leave_type_id', 'year'], 'staff_leave_year_unique');
        });

        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('total_days', 4, 1); // allow 0.5 for half days
            $table->boolean('is_half_day')->default(false);
            $table->text('reason');
            $table->string('document_path')->nullable();
            
            // Workflow Status: pending, hod_approved, principal_approved, rejected, cancelled
            $table->string('status')->default('pending');
            $table->foreignId('current_approver_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('leave_approval_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_application_id')->constrained('leave_applications')->cascadeOnDelete();
            $table->foreignId('action_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('action'); // hod_approved, principal_approved, rejected
            $table->text('comments')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_daily_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status'); // present, absent, late, half_day, leave, holiday
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->foreignId('leave_application_id')->nullable()->constrained('leave_applications')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->unique(['staff_id', 'date']);
        });

        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('date')->unique();
            $table->string('type')->default('public'); // public, gazetted, local
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_holidays');
        Schema::dropIfExists('staff_daily_attendance');
        Schema::dropIfExists('leave_approval_logs');
        Schema::dropIfExists('leave_applications');
        Schema::dropIfExists('staff_leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
