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
        Schema::table('subject_group_subjects', function (Blueprint $table) {
            $table->boolean('is_slot1')->default(false)->after('is_mandatory');
            $table->boolean('is_slot2')->default(false)->after('is_slot1');
            $table->boolean('is_slot3')->default(false)->after('is_slot2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_group_subjects', function (Blueprint $table) {
            $table->dropColumn(['is_slot1', 'is_slot2', 'is_slot3']);
        });
    }
};
