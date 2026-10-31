<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupUnscheduledAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:clean-unscheduled 
                            {--roll= : Filter by college roll number (e.g. 201)}
                            {--year= : Filter by admission year (e.g. 2026)}
                            {--fix : Actually delete the unscheduled attendance records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and clean attendance records marked on days when lectures were not scheduled in the timetable';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shouldFix = $this->option('fix');
        $rollFilter = $this->option('roll');
        $yearFilter = $this->option('year');

        $this->info("=========================================================");
        $this->info("     AUDIT & CLEAN UNSCHEDULED ATTENDANCE RECORDS        ");
        $this->info("=========================================================");

        if ($shouldFix) {
            $this->warn("MODE: FIX ENABLED (Unscheduled attendance records will be removed)");
        } else {
            $this->info("MODE: DRY RUN (Run with --fix to remove invalid records)");
        }

        // Query attendances joined with admission, student, section, and subject
        $query = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->join('subjects', 'attendances.subject_id', '=', 'subjects.id')
            ->leftJoin('student_subject_sections', function($join) {
                $join->on('attendances.student_id', '=', 'student_subject_sections.student_id')
                     ->on('attendances.subject_id', '=', 'student_subject_sections.subject_id')
                     ->on('admissions.admission_year', '=', 'student_subject_sections.admission_year')
                     ->whereNull('student_subject_sections.deleted_at');
            })
            ->whereNull('students.deleted_at')
            ->whereNull('admissions.deleted_at')
            ->select(
                'attendances.id as attendance_id',
                'attendances.student_id',
                'attendances.subject_id',
                'attendances.staff_id',
                'attendances.attendance_date',
                'attendances.status',
                'admissions.college_roll_no',
                'admissions.admission_year',
                'students.first_name',
                'students.last_name',
                'subjects.name as subject_name',
                'student_subject_sections.section as student_section'
            );

        if ($rollFilter) {
            $query->where('admissions.college_roll_no', $rollFilter);
        }

        if ($yearFilter) {
            $query->where('admissions.admission_year', (int) $yearFilter);
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            $this->info("No attendance records found matching the given filters.");
            return 0;
        }

        // Preload all active timetable entries grouped by staff, subject, and admission year
        $timetables = DB::table('timetable_entries')
            ->where('is_active', 1)
            ->select('staff_id', 'subject_id', 'admission_year', 'section', 'day_of_week')
            ->get();

        $invalidRecords = collect();

        foreach ($records as $record) {
            $staffId = $record->staff_id;
            $subjectId = $record->subject_id;
            $year = $record->admission_year;
            $sec = trim($record->student_section ?? '');
            $dayOfWeek = Carbon::parse($record->attendance_date)->format('l');

            // Find matching timetable entries for this staff, subject, and year
            $subTt = $timetables->where('subject_id', $subjectId)
                ->where('admission_year', $year);

            if ($staffId) {
                $staffTt = $subTt->where('staff_id', $staffId);
                if ($staffTt->isNotEmpty()) {
                    $subTt = $staffTt;
                }
            }

            // If no timetable configured at all for this subject/year, skip
            if ($subTt->isEmpty()) {
                continue;
            }

            // Check if there are scheduled entries on this day of week
            if (!empty($sec)) {
                $secTt = $subTt->filter(fn($e) => trim($e->section ?? '') === $sec);
                if ($secTt->isNotEmpty()) {
                    // This section has a timetable schedule
                    $isScheduled = $secTt->contains(fn($e) => $e->day_of_week === $dayOfWeek);
                    if (!$isScheduled) {
                        $scheduledDays = $secTt->pluck('day_of_week')->unique()->implode(', ');
                        $invalidRecords->push([
                            'id' => $record->attendance_id,
                            'roll_no' => $record->college_roll_no,
                            'student_name' => trim($record->first_name . ' ' . $record->last_name),
                            'subject' => $record->subject_name,
                            'section' => $sec,
                            'date' => $record->attendance_date,
                            'day' => $dayOfWeek,
                            'status' => $record->status,
                            'scheduled_days' => $scheduledDays,
                        ]);
                    }
                    continue;
                }
            }

            // Section is empty or section has no specific timetable: check general entries
            $isScheduled = $subTt->contains(fn($e) => $e->day_of_week === $dayOfWeek);
            if (!$isScheduled) {
                $scheduledDays = $subTt->pluck('day_of_week')->unique()->implode(', ');
                $invalidRecords->push([
                    'id' => $record->attendance_id,
                    'roll_no' => $record->college_roll_no,
                    'student_name' => trim($record->first_name . ' ' . $record->last_name),
                    'subject' => $record->subject_name,
                    'section' => $sec ?: 'None',
                    'date' => $record->attendance_date,
                    'day' => $dayOfWeek,
                    'status' => $record->status,
                    'scheduled_days' => $scheduledDays,
                ]);
            }
        }

        if ($invalidRecords->isEmpty()) {
            $this->info("✔ All checked attendance records are aligned with active timetable scheduled days!");
            return 0;
        }

        $this->warn("Found {$invalidRecords->count()} attendance record(s) marked on unscheduled days:");

        $sample = $invalidRecords->take(25)->map(function ($item) {
            return [
                $item['roll_no'],
                $item['student_name'],
                $item['subject'],
                $item['section'],
                $item['date'] . ' (' . $item['day'] . ')',
                $item['status'],
                $item['scheduled_days'],
            ];
        })->toArray();

        $this->table(
            ['Roll No', 'Student', 'Subject', 'Sec', 'Date (Day)', 'Marked Status', 'Scheduled On'],
            $sample
        );

        if ($invalidRecords->count() > 25) {
            $remaining = $invalidRecords->count() - 25;
            $this->line("... and {$remaining} more unscheduled records.");
        }

        if ($shouldFix) {
            $idsToDelete = $invalidRecords->pluck('id')->toArray();
            $deletedCount = DB::table('attendances')->whereIn('id', $idsToDelete)->delete();
            $this->info("✔ Successfully removed {$deletedCount} unscheduled attendance record(s).");
        } else {
            $this->comment("Run `php artisan attendance:clean-unscheduled --fix` (with optional `--roll=201`) to delete these invalid records.");
        }

        return 0;
    }
}
