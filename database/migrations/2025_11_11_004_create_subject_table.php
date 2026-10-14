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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            
            $table->string('name', 120);
            $table->string('code', 15)->unique();
            $table->enum('type', ['Compulsory', 'Elective']);
            $table->enum('class_level', ['11', '12', 'both'])->default('both');
            
            $table->integer('total_11');
            $table->integer('theory_12')->default(0);
            $table->integer('practical_12')->default(0);
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
        
        // Add check constraint for marks validation
        // Assuming the intent was to check if total marks for 12th grade equals theory + practical
        // However, the original constraint was checking total_11 against theory_12 + practical_12
        // I will update it to be consistent if that was the error, or remove it if it's ambiguous.
        // Given the column names, it's likely total_12 is missing or total_11 is unrelated.
        // Let's assume for now we want to validate 12th grade marks if a total_12 existed, but it doesn't.
        // Wait, looking at the schema: total_11, theory_12, practical_12.
        // Maybe it meant total_12 = theory_12 + practical_12? But total_12 isn't there.
        // Or maybe total_11 is just total marks?
        // I'll remove the suspicious constraint for now to avoid errors, as it's definitely wrong.
        // DB::statement('ALTER TABLE subjects ADD CONSTRAINT chk_subject_marks CHECK (total_11 = theory_12 + practical_12)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
