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
        Schema::create('student_leaves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days_count')->default(1);
            $table->string('leave_type', 50)->default('casual'); // casual, medical, family_visit, urgent_affair, other
            $table->text('reason');
            $table->string('approved_by')->default('Principal Office'); // Principal, Vice Principal, etc.
            $table->unsignedBigInteger('marked_by'); // User ID of Admin / Principal / Clerk who entered it
            $table->string('document_path')->nullable(); // Uploaded scanned application
            $table->enum('status', ['approved', 'cancelled'])->default('approved');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('marked_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['student_id', 'start_date', 'end_date']);
            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_leaves');
    }
};
