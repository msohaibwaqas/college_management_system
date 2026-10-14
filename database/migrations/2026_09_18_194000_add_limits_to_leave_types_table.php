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
        Schema::table('leave_types', function (Blueprint $table) {
            $table->integer('max_leaves_per_month')->default(2)->after('default_days');
            $table->integer('special_case_max_leaves_per_month')->default(7)->after('max_leaves_per_month');
            $table->date('period_start_date')->nullable()->after('special_case_max_leaves_per_month');
            $table->date('period_end_date')->nullable()->after('period_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn([
                'max_leaves_per_month',
                'special_case_max_leaves_per_month',
                'period_start_date',
                'period_end_date'
            ]);
        });
    }
};
