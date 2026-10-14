<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only run if the base table exists (for test compatibility)
        if (!Schema::hasTable('invigilator_assignments')) {
            return;
        }
        
        // Check if column already exists
        if (!Schema::hasColumn('invigilator_assignments', 'he_seating_plan_id')) {
            Schema::table('invigilator_assignments', function (Blueprint $table) {
                // Add he_seating_plan_id for BS/ADP section (nullable)
                $table->unsignedBigInteger('he_seating_plan_id')->nullable();
                
                // Only add foreign key constraint for MySQL
                if (DB::getDriverName() !== 'sqlite') {
                    $table->foreign('he_seating_plan_id')
                          ->references('id')
                          ->on('he_seating_plans')
                          ->onDelete('cascade');
                }
            });
        }
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invigilator_assignments', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['he_seating_plan_id']);
            }
            if (Schema::hasColumn('invigilator_assignments', 'he_seating_plan_id')) {
                $table->dropColumn('he_seating_plan_id');
            }
        });
    }
};
