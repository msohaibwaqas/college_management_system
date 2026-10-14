<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Admissions;
use App\Models\Attendance;
use App\Models\AttendanceArchive;

class ArchiveGraduatedStudentAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * php artisan attendance:archive-graduated
     * php artisan attendance:archive-graduated --year=2024
     * php artisan attendance:archive-graduated --year=2024 --dry-run
     */
    protected $signature = 'attendance:archive-graduated
                            {--year=2024 : The admission year whose graduated students should be archived}
                            {--dry-run : Preview the counts without making any changes}';

    /**
     * The console command description.
     */
    protected $description = 'Archive daily attendance records for graduated students into aggregated totals (total/attended lectures per subject per year)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year    = (int) $this->option('year');
        $dryRun  = $this->option('dry-run');

        $this->info("=== Attendance Archive for Graduated Students (Admission Year: {$year}) ===");

        // 1. Fetch all graduated student IDs for the given admission year
        $graduatedStudentIds = Admissions::where('admission_year', $year)
            ->where('status', 'Graduated')
            ->pluck('student_id');

        if ($graduatedStudentIds->isEmpty()) {
            $this->warn("No graduated students found for admission year {$year}.");
            return self::FAILURE;
        }

        $this->info("Found {$graduatedStudentIds->count()} graduated students for admission year {$year}.");

        // 2. Aggregate attendance: group by student_id, subject_id, and academic year
        $aggregated = Attendance::whereIn('student_id', $graduatedStudentIds)
            ->selectRaw('
                student_id,
                subject_id,
                YEAR(attendance_date) AS academic_year,
                COUNT(*) AS total_lectures,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) AS attended_lectures
            ')
            ->groupBy('student_id', 'subject_id', DB::raw('YEAR(attendance_date)'))
            ->get();

        if ($aggregated->isEmpty()) {
            $this->warn("No attendance records found for the graduated students of admission year {$year}.");
            return self::FAILURE;
        }

        $this->info("Aggregated into {$aggregated->count()} archive rows (student × subject × year).");

        // 3. Check how many are already archived
        $alreadyArchivedCount = AttendanceArchive::whereIn('student_id', $graduatedStudentIds)->count();
        if ($alreadyArchivedCount > 0) {
            $this->warn("{$alreadyArchivedCount} archive record(s) already exist for these students.");
            if (! $this->confirm('Some records are already archived. Do you want to skip duplicates and continue?', true)) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        if ($dryRun) {
            $this->info('[DRY RUN] No changes will be made.');
            $this->table(
                ['student_id', 'subject_id', 'academic_year', 'total_lectures', 'attended_lectures'],
                $aggregated->take(20)->map(fn ($r) => [
                    $r->student_id,
                    $r->subject_id,
                    $r->academic_year,
                    $r->total_lectures,
                    $r->attended_lectures,
                ])->toArray()
            );
            if ($aggregated->count() > 20) {
                $this->line('... and ' . ($aggregated->count() - 20) . ' more rows.');
            }
            $this->info('[DRY RUN] Would archive ' . $aggregated->count() . ' rows and delete ' . Attendance::whereIn('student_id', $graduatedStudentIds)->count() . ' raw records.');
            return self::SUCCESS;
        }

        // 4. Run inside a DB transaction
        $archivedCount = 0;
        $deletedCount  = 0;

        DB::transaction(function () use ($aggregated, $graduatedStudentIds, &$archivedCount, &$deletedCount) {

            // Insert aggregated records (skip existing duplicates via updateOrCreate)
            foreach ($aggregated as $row) {
                AttendanceArchive::updateOrCreate(
                    [
                        'student_id'   => $row->student_id,
                        'subject_id'   => $row->subject_id,
                        'academic_year' => $row->academic_year,
                    ],
                    [
                        'total_lectures'    => $row->total_lectures,
                        'attended_lectures' => $row->attended_lectures,
                    ]
                );
                $archivedCount++;
            }

            // Delete the raw day-by-day records
            $deletedCount = Attendance::whereIn('student_id', $graduatedStudentIds)->delete();
        });

        $this->info("✓ Archived {$archivedCount} aggregated record(s) into attendance_archives.");
        $this->info("✓ Deleted  {$deletedCount} raw attendance record(s) from attendances.");
        $this->info('Done.');

        return self::SUCCESS;
    }
}
