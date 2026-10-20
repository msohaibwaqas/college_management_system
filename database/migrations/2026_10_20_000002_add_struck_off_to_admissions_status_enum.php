<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `admissions` MODIFY COLUMN `status` ENUM('Applied', 'Admitted', 'Active', 'Graduated', 'Dropped', 'Suspended', 'Struck Off', 'struck_off') NOT NULL DEFAULT 'Applied'");
        DB::statement("ALTER TABLE `he_admissions` MODIFY COLUMN `status` ENUM('Applied', 'Admitted', 'Active', 'Graduated', 'Dropped', 'Suspended', 'Struck Off', 'struck_off') NOT NULL DEFAULT 'Applied'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `admissions` MODIFY COLUMN `status` ENUM('Applied', 'Admitted', 'Active', 'Graduated', 'Dropped', 'Suspended') NOT NULL DEFAULT 'Applied'");
        DB::statement("ALTER TABLE `he_admissions` MODIFY COLUMN `status` ENUM('Applied', 'Admitted', 'Active', 'Graduated', 'Dropped', 'Suspended') NOT NULL DEFAULT 'Applied'");
    }
};
