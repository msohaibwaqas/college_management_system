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
            // Remove redundant fields that can be accessed via staff relationship
            // Drop index first for SQLite compatibility
            $table->dropUnique(['cnic']);
            $table->dropIndex('idx_user_contact');
            
            $table->dropColumn([
                'first_name',
                'last_name',
                'cnic',
                'contact_number',
                'profile_picture',
            ]);
            
            // Keep email as it might be different from staff email for login purposes
            // Keep status as user account status might differ from staff status
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 80)->nullable()->after('staff_id');
            $table->string('last_name', 80)->nullable()->after('first_name');
            $table->string('cnic', 15)->nullable()->after('last_name');
            $table->string('contact_number', 20)->nullable()->after('cnic');
            $table->string('profile_picture', 255)->nullable()->after('contact_number');
        });
    }
};
