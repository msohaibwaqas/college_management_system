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
        // 1. Standardize existing data in admissions
        DB::statement("UPDATE `admissions` SET `status` = 'Active' WHERE `status` IN ('Admitted', 'admitted', 'active', 'Applied', 'applied')");
        DB::statement("UPDATE `admissions` SET `status` = 'Struck Off' WHERE `status` IN ('struck_off', 'Struck Off')");
        DB::statement("UPDATE `admissions` SET `status` = 'Graduated' WHERE `status` IN ('graduated', 'Graduated')");
        DB::statement("UPDATE `admissions` SET `status` = 'Dropped' WHERE `status` IN ('dropped', 'Dropped')");
        DB::statement("UPDATE `admissions` SET `status` = 'Suspended' WHERE `status` IN ('suspended', 'Suspended')");

        // 2. Standardize existing data in he_admissions
        DB::statement("UPDATE `he_admissions` SET `status` = 'Active' WHERE `status` IN ('Admitted', 'admitted', 'active', 'Applied', 'applied')");
        DB::statement("UPDATE `he_admissions` SET `status` = 'Struck Off' WHERE `status` IN ('struck_off', 'Struck Off')");
        DB::statement("UPDATE `he_admissions` SET `status` = 'Graduated' WHERE `status` IN ('graduated', 'Graduated')");
        DB::statement("UPDATE `he_admissions` SET `status` = 'Dropped' WHERE `status` IN ('dropped', 'Dropped')");
        DB::statement("UPDATE `he_admissions` SET `status` = 'Suspended' WHERE `status` IN ('suspended', 'Suspended')");

        // 3. Narrow and standardize the ENUM column definition in MySQL
        DB::statement("ALTER TABLE `admissions` MODIFY COLUMN `status` ENUM('Active', 'Struck Off', 'Graduated', 'Dropped', 'Suspended') NOT NULL DEFAULT 'Active'");
        DB::statement("ALTER TABLE `he_admissions` MODIFY COLUMN `status` ENUM('Active', 'Struck Off', 'Graduated', 'Dropped', 'Suspended') NOT NULL DEFAULT 'Active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `admissions` MODIFY COLUMN `status` ENUM('Applied', 'Admitted', 'Active', 'Graduated', 'Dropped', 'Suspended', 'Struck Off', 'struck_off') NOT NULL DEFAULT 'Applied'");
        DB::statement("ALTER TABLE `he_admissions` MODIFY COLUMN `status` ENUM('Applied', 'Admitted', 'Active', 'Graduated', 'Dropped', 'Suspended', 'Struck Off', 'struck_off') NOT NULL DEFAULT 'Applied'");
    }
};
