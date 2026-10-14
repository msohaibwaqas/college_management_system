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
        // Step 1: Add the admission_year column (only if not exists)
        if (!Schema::hasColumn('he_timetables', 'admission_year')) {
            Schema::table('he_timetables', function (Blueprint $table) {
                $table->year('admission_year')->after('semester_no')->default(2025);
            });
        }

        // Step 2: Drop old unique index (need to drop FK first since MySQL ties them)
        // Check if old index exists
        $oldIndexExists = collect(DB::select("SHOW INDEX FROM he_timetables WHERE Key_name = 'uq_he_timetable_slot'"))->isNotEmpty();
        
        if ($oldIndexExists) {
            DB::statement('ALTER TABLE he_timetables DROP FOREIGN KEY he_timetables_program_id_foreign');
            DB::statement('ALTER TABLE he_timetables DROP INDEX uq_he_timetable_slot');
            DB::statement('ALTER TABLE he_timetables ADD CONSTRAINT he_timetables_program_id_foreign FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE');
        }

        // Step 3: Add the new unique constraint
        $newIndexExists = collect(DB::select("SHOW INDEX FROM he_timetables WHERE Key_name = 'uq_he_timetable_batch_slot'"))->isNotEmpty();
        
        if (!$newIndexExists) {
            Schema::table('he_timetables', function (Blueprint $table) {
                $table->unique(
                    ['program_id', 'semester_no', 'admission_year', 'day_of_week', 'period_number'],
                    'uq_he_timetable_batch_slot'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('he_timetables', function (Blueprint $table) {
            $table->dropUnique('uq_he_timetable_batch_slot');
        });

        DB::statement('ALTER TABLE he_timetables DROP FOREIGN KEY he_timetables_program_id_foreign');
        
        Schema::table('he_timetables', function (Blueprint $table) {
            $table->unique(
                ['program_id', 'semester_no', 'day_of_week', 'period_number', 'bs_subject_id'],
                'uq_he_timetable_slot'
            );
        });

        DB::statement('ALTER TABLE he_timetables ADD CONSTRAINT he_timetables_program_id_foreign FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE');

        Schema::table('he_timetables', function (Blueprint $table) {
            $table->dropColumn('admission_year');
        });
    }
};
