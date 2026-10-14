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
        Schema::table('users', function (Blueprint $table) {
            // Adding staff_id nullable because super admin might not be a staff member initially
            // or we might want to allow system users.
            // However, per user request "only a record in staff can have user account", 
            // we will enforce this in application logic, but keep DB flexible or strict?
            // Let's make it nullable for now to avoid breaking existing users (like the initial admin),
            // but we can add a foreign key constraint.
            $table->foreignId('staff_id')->nullable()->after('id')
                  ->constrained('staff', 'id')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->dropColumn('staff_id');
        });
    }
};
