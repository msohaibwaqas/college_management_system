<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('struck_off_settings')) {
            Schema::create('struck_off_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('absent_threshold')->default(10);
                $table->unsignedInteger('max_readmissions_per_year')->default(2);
                $table->string('calculation_period', 20)->default('monthly'); // 'monthly' or 'session'
                $table->timestamps();
            });

            // Seed default settings row
            DB::table('struck_off_settings')->insert([
                'absent_threshold'          => 10,
                'max_readmissions_per_year' => 2,
                'calculation_period'        => 'monthly',
                'created_at'                => now(),
                'updated_at'                => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('struck_off_settings');
    }
};
