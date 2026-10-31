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
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'hod_staff_id')) {
                $table->unsignedBigInteger('hod_staff_id')->nullable()->after('practical_12');
                $table->foreign('hod_staff_id')->references('id')->on('staff')->nullOnDelete();
            }
        });

        if (!DB::table('roles')->where('name', 'hod')->exists()) {
            DB::table('roles')->insert([
                'name' => 'hod',
                'description' => 'Head of Department - manages subject sections and student assignments',
                'guard_name' => 'web',
                'is_system_role' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'hod_staff_id')) {
                $table->dropForeign(['hod_staff_id']);
                $table->dropColumn('hod_staff_id');
            }
        });

        DB::table('roles')->where('name', 'hod')->delete();
    }
};
