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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            
            $table->string('staff_id', 50)->unique();
            $table->string('first_name', 80);
            $table->string('last_name', 80)->nullable();
            $table->string('father_name', 120);
            $table->string('cnic', 15)->unique();
            $table->string('contact_number', 20)->nullable();
            $table->text('address')->nullable();
            
            $table->date('date_of_birth');
            $table->foreignId('blood_group_id')->nullable()
                  ->constrained('blood_groups', 'id')
                  ->nullOnDelete();
            
            $table->string('picture', 255)->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->string('email', 120)->nullable();
            
            $table->enum('category', ['teaching', 'non_teaching']);
            $table->string('highest_qualification', 100)->nullable();
            $table->string('designation', 100);
            $table->tinyInteger('bps')->unsigned();
            
            $table->date('entry_date_govt_service')->nullable();
            $table->date('hed_joining_date')->nullable();
            $table->date('current_college_joining_date')->nullable();
            
            $table->enum('status', ['active', 'left', 'transferred', 'retired', 'suspended'])
                  ->default('active');
            $table->enum('gender', ['male', 'female']);
            
            $table->date('first_promotion_date')->nullable();
            $table->date('second_promotion_date')->nullable();
            $table->date('third_promotion_date')->nullable();
            $table->date('fourth_promotion_date')->nullable();
            
            $table->enum('join_current_bps_as', ['selectee', 'promotee'])
                  ->default('selectee');
            
            $table->string('signature_image', 255)->nullable();
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Indexes
            $table->index('staff_id', 'idx_staff_id');
            $table->index('cnic', 'idx_staff_cnic');
            $table->index('category', 'idx_staff_category');
            $table->index('status', 'idx_staff_status');
            $table->index('designation', 'idx_staff_designation');
            $table->index('bps', 'idx_staff_bps');
            $table->index(['first_name', 'last_name', 'designation'], 'idx_staff_name_designation');
            $table->index(['category', 'status', 'bps'], 'idx_staff_category_status');
        });
        
        // Add check constraint for BPS range validation (skip for SQLite in testing)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE staff ADD CONSTRAINT chk_bps_range CHECK (bps BETWEEN 1 AND 22)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
