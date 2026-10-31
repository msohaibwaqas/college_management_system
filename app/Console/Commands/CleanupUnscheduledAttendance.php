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
                            {--year= : Filter by admission year or comma-separated years (e.g. 2025,2026, default: 2025,2026)}
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
        $shouldFix = (bool) $this->option('fix');
        $rollFilter = $this->option('roll');
        $yearInput = $this->option('year');

        $this->info("=========================================================");
        $this->info("     AUDIT & CLEAN UNSCHEDULED ATTENDANCE RECORDS        ");
        $this->info("=========================================================");

        if ($shouldFix) {
            $this->warn("MODE: FIX ENABLED (Unscheduled attendance records will be permanently removed)");
        } else {
            $this->info("MODE: DRY RUN (Run with --fix to safely delete invalid records)");
        }

        // Determine target admission years
        if ($yearInput) {
            $years = array_filter(array_map('intval', explode(',', $yearInput)));
        } elseif ($rollFilter) {
            $years = DB::table('admissions')
                ->where('college_roll_no', $rollFilter)
                ->whereNull('deleted_at')
                ->pluck('admission_year')
                ->unique()
                ->toArray();
            if (empty($years)) {
                $years = [2025, 2026];
            }
        } else {
            $years = [2025, 2026];
        }

        $this->line("Target Admission Year(s): " . implode(', ', $years));
        if ($rollFilter) {
            $this->line("Filtered to Roll No: {$rollFilter}");
        }
        $this->line("");

        // 1. Preload active timetable entries for the target years
        $timetables = DB::table('timetable_entries')
            ->where('is_active', 1)
            ->whereIn('admission_year', $years)
            ->select('subject_id', 'admission_year', 'section', 'day_of_week')
            ->get();

        if ($timetables->isEmpty()) {
            $this->warn("No active timetable entries found for admission year(s): " . implode(', ', $years));
            return 0;
        }

        // Fast lookup structures:
        // $subjectSectionDays[year][subject_id][section][day_of_week] = true
        // $subjectAllDays[year][subject_id][day_of_week] = true
        // $subjectSectionLabels[year][subject_id][section] = "Monday, Tuesday, Wednesday"
        $subjectSectionDays = [];
        $subjectAllDays = [];
        $subjectSectionLabels = [];

        foreach ($timetables as $tt) {
            $y = $tt->admission_year;
            $subId = $tt->subject_id;
            $sec = strtoupper(trim($tt->section ?? ''));
            $day = $tt->day_of_week;

            $subjectAllDays[$y][$subId][$day] = true;
            if ($sec !== '') {
                $subjectSectionDays[$y][$subId][$sec][$day] = true;
            }
        }

        foreach ($subjectSectionDays as $y => $subList) {
            foreach ($subList as $subId => $secList) {
                foreach ($secList as $sec => $days) {
                    $subjectSectionLabels[$y][$subId][$sec] = implode(', ', array_keys($days));
                }
            }
        }

        // 2. Preload Datesheets (exam/test dates are legitimate and should not be deleted)
        $datesheetMap = DB::table('datesheets')
            ->whereIn('admission_year', $years)
            ->select('admission_year', 'subject_id', 'test_date')
            ->get()
            ->groupBy(fn($d) => $d->admission_year . '_' . $d->subject_id)
            ->map(fn($group) => $group->pluck('test_date')->flip()->all())
            ->all();

        // 3. Preload Subject Names
        $subjectNames = DB::table('subjects')->pluck('name', 'id')->all();

        // 4. Preload Students and their Subject Sections for target years
        $admissionsQuery = DB::table('admissions')
            ->join('students', 'admissions.student_id', '=', 'students.id')
            ->whereIn('admissions.admission_year', $years)
            ->whereNull('admissions.deleted_at')
            ->whereNull('students.deleted_at');

        if ($rollFilter) {
            $admissionsQuery->where('admissions.college_roll_no', $rollFilter);
        }

        $admissions = $admissionsQuery->select(
            'admissions.student_id',
            'admissions.college_roll_no',
            'admissions.admission_year',
            'students.first_name',
            'students.last_name'
        )->get();

        if ($admissions->isEmpty()) {
            $this->info("No students found matching the criteria.");
            return 0;
        }

        $studentMap = $admissions->keyBy('student_id');
        $studentIds = $admissions->pluck('student_id')->toArray();

        // Student sections map: student_id . '_' . subject_id => section
        $studentSections = DB::table('student_subject_sections')
            ->whereIn('admission_year', $years)
            ->whereIn('student_id', $studentIds)
            ->whereNull('deleted_at')
            ->select('student_id', 'subject_id', 'section')
            ->get()
            ->keyBy(fn($r) => $r->student_id . '_' . $r->subject_id);

        $this->info("Auditing " . count($studentIds) . " students across admission year(s) " . implode(', ', $years) . "...");

        // 5. Scan Attendances in chunks to keep memory footprint minimal
        $invalidRecords = [];
        $invalidIds = [];
        $summaryByYearSubject = [];
        $affectedStudents = [];

        DB::table('attendances')
            ->whereIn('student_id', $studentIds)
            ->select('id', 'student_id', 'subject_id', 'attendance_date', 'status')
            ->orderBy('id')
            ->chunk(10000, function ($chunk) use (
                &$invalidRecords,
                &$invalidIds,
                &$summaryByYearSubject,
                &$affectedStudents,
                $studentMap,
                $studentSections,
                $subjectSectionDays,
                $subjectAllDays,
                $subjectSectionLabels,
                $datesheetMap,
                $subjectNames
            ) {
                foreach ($chunk as $att) {
                    $student = $studentMap[$att->student_id] ?? null;
                    if (!$student) {
                        continue;
                    }

                    $y = $student->admission_year;
                    $subId = $att->subject_id;

                    // If no timetable configured for this year & subject, skip
                    if (!isset($subjectAllDays[$y][$subId])) {
                        continue;
                    }

                    // If date is a scheduled datesheet test date, it is legitimate
                    $dsKey = $y . '_' . $subId;
                    if (isset($datesheetMap[$dsKey][$att->attendance_date])) {
                        continue;
                    }

                    $dayOfWeek = date('l', strtotime($att->attendance_date));
                    $secKey = $att->student_id . '_' . $subId;
                    $sec = isset($studentSections[$secKey]) ? strtoupper(trim($studentSections[$secKey]->section ?? '')) : '';

                    $isValid = true;
                    $scheduledDaysStr = '';

                    if ($sec !== '' && isset($subjectSectionDays[$y][$subId][$sec])) {
                        $isValid = isset($subjectSectionDays[$y][$subId][$sec][$dayOfWeek]);
                        $scheduledDaysStr = $subjectSectionLabels[$y][$subId][$sec] ?? '';
                    } elseif (isset($subjectAllDays[$y][$subId])) {
                        $isValid = isset($subjectAllDays[$y][$subId][$dayOfWeek]);
                        $scheduledDaysStr = implode(', ', array_keys($subjectAllDays[$y][$subId]));
                    }

                    if (!$isValid) {
                        $invalidIds[] = $att->id;
                        $affectedStudents[$att->student_id] = true;

                        $subName = $subjectNames[$subId] ?? ("Subject #" . $subId);
                        $summaryKey = $y . '_' . $subId;
                        if (!isset($summaryByYearSubject[$summaryKey])) {
                            $summaryByYearSubject[$summaryKey] = [
                                'year' => $y,
                                'subject' => $subName,
                                'subject_id' => $subId,
                                'count' => 0,
                                'students' => []
                            ];
                        }
                        $summaryByYearSubject[$summaryKey]['count']++;
                        $summaryByYearSubject[$summaryKey]['students'][$att->student_id] = true;

                        // Save up to 50 sample records for display
                        if (count($invalidRecords) < 50) {
                            $invalidRecords[] = [
                                'roll_no' => $student->college_roll_no,
                                'student_name' => trim($student->first_name . ' ' . $student->last_name),
                                'year' => $y,
                                'subject' => $subName,
                                'section' => $sec ?: 'None',
                                'date' => $att->attendance_date,
                                'day' => $dayOfWeek,
                                'status' => $att->status,
                                'scheduled_days' => $scheduledDaysStr,
                            ];
                        }
                    }
                }
            });

        $totalInvalid = count($invalidIds);

        if ($totalInvalid === 0) {
            $this->info("✔ All checked attendance records are aligned with active timetable scheduled days!");
            return 0;
        }

        // Print Summary Table by Year and Subject
        $summaryTableData = [];
        foreach ($summaryByYearSubject as $row) {
            $summaryTableData[] = [
                $row['year'],
                $row['subject'],
                $row['subject_id'],
                count($row['students']),
                $row['count'],
            ];
        }

        $this->warn("Found {$totalInvalid} unscheduled attendance record(s) across " . count($affectedStudents) . " student(s):");
        $this->table(
            ['Admission Year', 'Subject Name', 'Subject ID', 'Affected Students', 'Invalid Records'],
            $summaryTableData
        );

        // Print Sample Records
        $this->line("Sample of invalid records found:");
        $sampleData = array_map(function($item) {
            return [
                $item['roll_no'],
                $item['student_name'],
                $item['year'],
                $item['subject'],
                $item['section'],
                $item['date'] . ' (' . $item['day'] . ')',
                $item['status'],
                $item['scheduled_days'],
            ];
        }, array_slice($invalidRecords, 0, 15));

        $this->table(
            ['Roll No', 'Student Name', 'Year', 'Subject', 'Sec', 'Date (Day)', 'Marked', 'Scheduled Days'],
            $sampleData
        );

        if ($totalInvalid > 15) {
            $remaining = $totalInvalid - 15;
            $this->line("... and {$remaining} more unscheduled records.");
        }
        $this->line("");

        // 6. Fix records if requested
        if ($shouldFix) {
            $this->info("Deleting {$totalInvalid} invalid attendance records in batches...");
            $chunks = array_chunk($invalidIds, 1000);
            $deletedCount = 0;

            foreach ($chunks as $chunkIds) {
                $deletedCount += DB::table('attendances')->whereIn('id', $chunkIds)->delete();
            }

            $this->info("✔ SUCCESS: Removed {$deletedCount} illegitimate attendance record(s).");
            $this->info("✔ Repaired attendance statistics for " . count($affectedStudents) . " student(s) in admission year(s) " . implode(', ', $years) . ".");
        } else {
            $yearsParam = implode(',', $years);
            $this->comment("Run the following command with `--fix` to safely delete these {$totalInvalid} invalid records:");
            $this->line("  <fg=yellow;options=bold>php artisan attendance:clean-unscheduled --year={$yearsParam} --fix</>");
        }

        return 0;
    }
}
