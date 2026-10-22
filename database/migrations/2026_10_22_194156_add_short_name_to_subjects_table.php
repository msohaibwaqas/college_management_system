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
            $table->string('short_name', 20)->nullable()->after('name');
        });

        $mapping = [
            'Urdu' => 'UR',
            'English' => 'ENG',
            'Translation Of Holy Quran' => 'TRQ',
            'Islamic Education' => 'ISL',
            'Pakistan Studies' => 'PS',
            'Biology' => 'BIO',
            'Physics' => 'PHY',
            'Chemistry' => 'CHE',
            'Mathematics' => 'MATH',
            'Computer Science' => 'CS',
            'Civics' => 'CIV',
            'Health And Physical Education' => 'HPED',
            'History' => 'HISI',
            'Islamic Studies' => 'IST',
            'Economics' => 'ECO',
            'Library Science' => 'LSC',
            'Sociology' => 'SOC',
            'Education' => 'EDU',
            'Psychology' => 'PSY',
            'Statistics' => 'STAT',
            'Geography' => 'GEO',
            'Persian' => 'PER',
            'Punjabi' => 'PUNJ',
            'Urdu Elective' => 'URAD',
            'Arabic' => 'ARB',
            'Political Science' => 'POL',
            'Pakistani Culture' => 'PC',
            'Religious Education' => 'RE',
            'Ethics' => 'ETH',
        ];

        foreach ($mapping as $name => $short) {
            DB::table('subjects')
                ->where('name', $name)
                ->update(['short_name' => $short]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('short_name');
        });
    }
};
