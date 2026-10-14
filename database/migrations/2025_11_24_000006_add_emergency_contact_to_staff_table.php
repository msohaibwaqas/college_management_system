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
        Schema::table('staff', function (Blueprint $table) {
            $table->string('emergency_contact_name', 120)->nullable()->after('current_address');
            $table->string('emergency_contact_number', 20)->nullable()->after('emergency_contact_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['emergency_contact_name', 'emergency_contact_number']);
        });
    }
};
