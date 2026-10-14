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
        Schema::table('announcements', function (Blueprint $table) {
            $table->boolean('is_holiday')->default(false)->after('recipient_group');
            $table->date('holiday_date')->nullable()->after('is_holiday');
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->boolean('is_special_case')->default(false)->after('is_half_day');
            $table->text('special_case_reason')->nullable()->after('is_special_case');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['is_holiday', 'holiday_date']);
        });

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropColumn(['is_special_case', 'special_case_reason']);
        });
    }
};
