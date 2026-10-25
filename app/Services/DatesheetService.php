<?php

namespace App\Services;

use App\Models\Admissions;
use App\Models\BsSubject;
use App\Models\Datesheet;
use App\Models\HeDatesheet;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DatesheetService
{
    /**
     * Get student IDs enrolled in a subject for a given HSSC admission year.
     */
    public function getStudentIdsForSubject(int $subjectId, int $admissionYear): Collection
    {
        $subject = Subject::find($subjectId);
        if (!$subject) {
            return collect();
        }

        $query = Student::active()->inBatch($admissionYear);

        if ($subject->type === 'Compulsory') {
            $query->where(function ($q) use ($subjectId) {
                $q->whereHas('compulsorySubjects', function ($sq) use ($subjectId) {
                    $sq->where('subject_id', $subjectId);
                })->orWhereNotExists(function ($eq) {
                    $eq->select(DB::raw(1))
                        ->from('student_compulsory_subjects')
                        ->whereColumn('student_compulsory_subjects.student_id', 'students.id');
                });
            });
        } else {
            $query->whereHas('electives', function ($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            });
        }

        return $query->pluck('id');
    }

    /**
     * Store manual datesheet entries for HSSC subjects.
     *
     * @param int $admissionYear
     * @param string $testDate
     * @param array $subjectIds
     * @param int|null $userId
     * @return array{success: bool, error?: string, saved_count: int, conflicts: array}
     */
    public function storeManualDatesheet(int $admissionYear, string $testDate, array $subjectIds, ?int $userId = null): array
    {
        if (date('N', strtotime($testDate)) == 7) {
            return [
                'success' => false,
                'error' => 'Tests cannot be scheduled on Sunday.',
                'saved_count' => 0,
                'conflicts' => [],
            ];
        }

        $userId = $userId ?? Auth::id();
        $conflicts = [];
        $savedCount = 0;

        DB::transaction(function () use ($admissionYear, $testDate, $subjectIds, $userId, &$conflicts, &$savedCount) {
            $existingTests = Datesheet::where('test_date', $testDate)
                ->where('admission_year', $admissionYear)
                ->get();

            foreach ($subjectIds as $newSubjectId) {
                $newSubject = Subject::find($newSubjectId);
                if (!$newSubject) {
                    continue;
                }

                $hasConflict = false;
                $newStudents = $this->getStudentIdsForSubject($newSubjectId, $admissionYear);

                foreach ($existingTests as $existingTest) {
                    if ($existingTest->subject_id == $newSubjectId) {
                        $conflicts[] = "Subject '{$newSubject->name}' is already scheduled on this date.";
                        $hasConflict = true;
                        break;
                    }

                    $existingStudents = $this->getStudentIdsForSubject($existingTest->subject_id, $admissionYear);
                    $common = $newStudents->intersect($existingStudents);

                    if ($common->isNotEmpty()) {
                        $conflicts[] = "Conflict: Subject '{$newSubject->name}' shares {$common->count()} student(s) with '{$existingTest->subject->name}' scheduled on this date.";
                        $hasConflict = true;
                        break;
                    }
                }

                if (!$hasConflict) {
                    $datesheet = Datesheet::create([
                        'admission_year' => $admissionYear,
                        'test_date' => $testDate,
                        'subject_id' => $newSubjectId,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    $savedCount++;
                    $existingTests->push($datesheet);
                }
            }
        });

        return [
            'success' => true,
            'saved_count' => $savedCount,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Build the summary schedule matrix for HSSC examinations.
     */
    public function getSummarySchedule(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Datesheet::with('subject')->orderBy('test_date');

        if ($startDate) {
            $query->whereDate('test_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('test_date', '<=', $endDate);
        }

        $datesheets = $query->get();

        $grouped = $datesheets->groupBy(function ($item) {
            return $item->test_date->format('Y-m-d');
        });

        $batchClasses = DB::table('admissions')
            ->select('admission_year', 'current_class')
            ->distinct()
            ->whereIn('status', ['Active', 'Admitted'])
            ->pluck('current_class', 'admission_year');

        $schedule = [];

        foreach ($grouped as $date => $tests) {
            $row = [
                'date' => Carbon::parse($date),
                'class_11' => [],
                'class_12' => [],
                'total_students' => 0,
            ];

            foreach ($tests as $test) {
                $class = $batchClasses[$test->admission_year] ?? 'Unknown';
                $studentCount = $this->getStudentIdsForSubject($test->subject_id, $test->admission_year)->count();
                $subjectName = $test->subject->name ?? 'Unknown';

                if ($class == '11') {
                    $row['class_11'][] = $subjectName;
                } elseif ($class == '12') {
                    $row['class_12'][] = $subjectName;
                }

                $row['total_students'] += $studentCount;
            }

            $schedule[] = $row;
        }

        return $schedule;
    }

    /**
     * Automatically generate non-conflicting HSSC datesheet across batches.
     *
     * @param string $startDateStr
     * @param string $endDateStr
     * @param array $admissionYears
     * @param int|null $userId
     * @return array{success: bool, total_scheduled: int, errors: array, message?: string}
     */
    public function generateAutomaticDatesheet(string $startDateStr, string $endDateStr, array $admissionYears, ?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();
        $startDate = Carbon::parse($startDateStr);
        $endDate = Carbon::parse($endDateStr);

        // Generate pool of available dates (excluding Sundays)
        $availableDates = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            if ($currentDate->dayOfWeek !== Carbon::SUNDAY) {
                $availableDates[] = $currentDate->format('Y-m-d');
            }
            $currentDate->addDay();
        }

        if (empty($availableDates)) {
            return [
                'success' => false,
                'total_scheduled' => 0,
                'errors' => ['No available dates in the selected range (excluding Sundays).'],
                'message' => 'No available dates in the selected range (excluding Sundays).',
            ];
        }

        $totalScheduled = 0;
        $errors = [];

        DB::transaction(function () use ($admissionYears, $availableDates, $userId, &$totalScheduled, &$errors) {
            // Global schedule tracker for load balancing across all batches
            $dailyLoad = array_fill_keys($availableDates, 0);

            foreach ($admissionYears as $year) {
                // 1. Get Batch Info (Majority Class)
                $batchInfo = Admissions::where('admission_year', $year)
                    ->whereIn('status', ['Admitted', 'Active'])
                    ->select('current_class', DB::raw('count(*) as total'))
                    ->groupBy('current_class')
                    ->orderByDesc('total')
                    ->first();

                $currentClass = $batchInfo ? $batchInfo->current_class : null;

                // 2. Get Students
                $students = Student::active()
                    ->inBatch($year)
                    ->get(['id']);

                if ($students->isEmpty()) {
                    continue;
                }
                $studentIds = $students->pluck('id');

                // 3. Get Subjects & Categorize
                $compulsoryIds = DB::table('student_compulsory_subjects')
                    ->whereIn('student_id', $studentIds)
                    ->select('subject_id')
                    ->distinct()
                    ->pluck('subject_id');

                $electiveIds = DB::table('student_elective_subjects')
                    ->whereIn('student_id', $studentIds)
                    ->select('subject_id')
                    ->distinct()
                    ->pluck('subject_id');

                $allSubjectIds = $compulsoryIds->merge($electiveIds)->unique();

                // 4. Filter Subjects (Class Level & Name-based Rules)
                $filteredSubjects = $allSubjectIds->filter(function ($sid) use ($currentClass) {
                    $subject = Subject::find($sid);
                    if (!$subject) {
                        return false;
                    }

                    // Name-based exclusions
                    $name = strtolower($subject->name);
                    if ($currentClass == '12' && (str_contains($name, 'islamic') || str_contains($name, 'islamiat'))) {
                        return false; // Class 12 doesn't take Islamic Studies
                    }
                    if ($currentClass == '11' && (str_contains($name, 'pak') && str_contains($name, 'stud'))) {
                        return false; // Class 11 doesn't take Pak Studies
                    }

                    // Standard Class Level Check
                    if ($subject->class_level === 'both') {
                        return true;
                    }
                    if ($currentClass && $subject->class_level != $currentClass) {
                        return false;
                    }

                    return true;
                });

                // 5. Build Metadata
                $subjectMeta = [];
                foreach ($filteredSubjects as $sid) {
                    $subject = Subject::find($sid);
                    $studentIdsForSub = $this->getStudentIdsForSubject($sid, $year);
                    $subjectMeta[$sid] = [
                        'type' => $subject->type,
                        'students' => $studentIdsForSub,
                        'count' => $studentIdsForSub->count(),
                        'object' => $subject,
                    ];
                }

                // 6. Split into Phases
                $compulsoryGroup = $filteredSubjects->filter(function ($sid) use ($subjectMeta) {
                    return $subjectMeta[$sid]['type'] === 'Compulsory';
                })->sortByDesc(function ($sid) use ($subjectMeta) {
                    return $subjectMeta[$sid]['count'];
                });

                $electiveGroup = $filteredSubjects->filter(function ($sid) use ($subjectMeta) {
                    return $subjectMeta[$sid]['type'] !== 'Compulsory';
                })->sortByDesc(function ($sid) use ($subjectMeta) {
                    return $subjectMeta[$sid]['count'];
                });

                // 7. Schedule
                $scheduled = [];
                $existingDatesheets = Datesheet::where('admission_year', $year)->get();
                foreach ($existingDatesheets as $ds) {
                    $d = $ds->test_date->format('Y-m-d');
                    if (!isset($scheduled[$d])) {
                        $scheduled[$d] = [];
                    }
                    $scheduled[$d][] = $ds->subject_id;
                    if (isset($dailyLoad[$d])) {
                        $dailyLoad[$d] += $this->getStudentIdsForSubject($ds->subject_id, $year)->count();
                    }
                }

                $lastCompulsoryDateIndex = -1;

                // Helper closure to schedule a list of subjects
                $scheduleList = function ($subjectList, $startIndex = 0) use (
                    &$scheduled,
                    &$dailyLoad,
                    $availableDates,
                    $subjectMeta,
                    $year,
                    $userId,
                    &$totalScheduled,
                    &$errors,
                    &$lastCompulsoryDateIndex
                ) {
                    foreach ($subjectList as $sid) {
                        $isDone = false;
                        foreach ($availableDates as $availDate) {
                            if (isset($scheduled[$availDate]) && in_array($sid, $scheduled[$availDate])) {
                                $isDone = true;
                                break;
                            }
                        }
                        if ($isDone) {
                            continue;
                        }

                        $meta = $subjectMeta[$sid];
                        $validDates = [];

                        // Search from startIndex
                        for ($i = $startIndex; $i < count($availableDates); $i++) {
                            $date = $availableDates[$i];

                            $hardConflict = false;
                            if (isset($scheduled[$date])) {
                                foreach ($scheduled[$date] as $existingSid) {
                                    $existingStudents = isset($subjectMeta[$existingSid])
                                        ? $subjectMeta[$existingSid]['students']
                                        : $this->getStudentIdsForSubject($existingSid, $year);

                                    if ($meta['students']->intersect($existingStudents)->isNotEmpty()) {
                                        $hardConflict = true;
                                        break;
                                    }
                                }
                            }
                            if (!$hardConflict) {
                                $validDates[] = ['date' => $date, 'index' => $i];
                            }
                        }

                        if (empty($validDates)) {
                            // Try searching BEFORE startIndex if we failed (fallback for electives)
                            if ($startIndex > 0) {
                                for ($i = 0; $i < $startIndex; $i++) {
                                    $date = $availableDates[$i];
                                    $hardConflict = false;
                                    if (isset($scheduled[$date])) {
                                        foreach ($scheduled[$date] as $existingSid) {
                                            $existingStudents = isset($subjectMeta[$existingSid])
                                                ? $subjectMeta[$existingSid]['students']
                                                : $this->getStudentIdsForSubject($existingSid, $year);
                                            if ($meta['students']->intersect($existingStudents)->isNotEmpty()) {
                                                $hardConflict = true;
                                                break;
                                            }
                                        }
                                    }
                                    if (!$hardConflict) {
                                        $validDates[] = ['date' => $date, 'index' => $i];
                                    }
                                }
                            }
                        }

                        if (empty($validDates)) {
                            $errors[] = "Could not schedule '{$meta['object']->name}' for Batch $year.";
                            continue;
                        }

                        // Score Dates: Gap Preference + Load Balancing
                        $bestDate = null;
                        $bestScore = -1;
                        $bestIndex = -1;

                        foreach ($validDates as $item) {
                            $date = $item['date'];
                            $idx = $item['index'];

                            $prevDate = Carbon::parse($date)->subDay()->format('Y-m-d');
                            $nextDate = Carbon::parse($date)->addDay()->format('Y-m-d');

                            $checkGap = function ($checkDate) use ($scheduled, $meta, $year, $subjectMeta) {
                                if (isset($scheduled[$checkDate])) {
                                    foreach ($scheduled[$checkDate] as $existingSid) {
                                        $existingStudents = isset($subjectMeta[$existingSid])
                                            ? $subjectMeta[$existingSid]['students']
                                            : $this->getStudentIdsForSubject($existingSid, $year);
                                        if ($meta['students']->intersect($existingStudents)->isNotEmpty()) {
                                            return true;
                                        }
                                    }
                                }
                                return false;
                            };

                            $gapPenalty = ($checkGap($prevDate) || $checkGap($nextDate)) ? 10000 : 0;
                            $loadScore = $dailyLoad[$date];
                            $totalScore = $gapPenalty + $loadScore;

                            if ($bestScore === -1 || $totalScore < $bestScore) {
                                $bestScore = $totalScore;
                                $bestDate = $date;
                                $bestIndex = $idx;
                            }
                        }

                        if ($bestDate) {
                            Datesheet::create([
                                'admission_year' => $year,
                                'test_date' => $bestDate,
                                'subject_id' => $sid,
                                'created_by' => $userId,
                                'updated_by' => $userId,
                            ]);

                            if (!isset($scheduled[$bestDate])) {
                                $scheduled[$bestDate] = [];
                            }
                            $scheduled[$bestDate][] = $sid;
                            $dailyLoad[$bestDate] += $meta['count'];
                            $totalScheduled++;

                            if ($bestIndex > $lastCompulsoryDateIndex) {
                                $lastCompulsoryDateIndex = $bestIndex;
                            }
                        }
                    }
                };

                // Schedule Compulsory
                $scheduleList($compulsoryGroup, 0);

                // Schedule Elective
                $electiveStartIndex = $lastCompulsoryDateIndex + 1;
                if ($electiveStartIndex >= count($availableDates)) {
                    $electiveStartIndex = 0;
                }
                $scheduleList($electiveGroup, $electiveStartIndex);
            }
        });

        return [
            'success' => true,
            'total_scheduled' => $totalScheduled,
            'errors' => $errors,
        ];
    }

    /**
     * Store Higher Education (BS/ADP) datesheet entry.
     *
     * @param array $data
     * @param int|null $userId
     * @return array{success: bool, datesheet?: HeDatesheet, error?: string}
     */
    public function storeHeDatesheet(array $data, ?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();

        $exists = HeDatesheet::where('program_id', $data['program_id'])
            ->where('semester_no', $data['semester_no'])
            ->where('bs_subject_id', $data['bs_subject_id'])
            ->where('exam_type', $data['exam_type'])
            ->where('academic_year', $data['academic_year'])
            ->exists();

        if ($exists) {
            return [
                'success' => false,
                'error' => 'A datesheet entry already exists for this subject/exam type.',
            ];
        }

        $datesheet = HeDatesheet::create([
            'program_id' => $data['program_id'],
            'semester_no' => $data['semester_no'],
            'bs_subject_id' => $data['bs_subject_id'],
            'exam_type' => $data['exam_type'],
            'exam_date' => $data['exam_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'academic_year' => $data['academic_year'],
            'created_by' => $userId,
        ]);

        return [
            'success' => true,
            'datesheet' => $datesheet,
        ];
    }

    /**
     * Get Higher Education datesheet summary collection.
     */
    public function getHeSummary(int $programId, int $semesterNo, string $examType, int $academicYear): Collection
    {
        return HeDatesheet::with(['program', 'bsSubject'])
            ->where('program_id', $programId)
            ->where('semester_no', $semesterNo)
            ->where('exam_type', $examType)
            ->where('academic_year', $academicYear)
            ->orderBy('exam_date')
            ->get();
    }

    /**
     * Automatically generate Higher Education (BS/ADP) datesheet with optimal gap simulation.
     *
     * @param array $params
     * @param int|null $userId
     * @return array{success: bool, created_count: int, best_gap: int, error?: string}
     */
    public function generateHeAutomaticDatesheet(array $params, ?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();

        $subjects = Semester::where('program_id', $params['program_id'])
            ->where('semester_no', $params['semester_no'])
            ->with('bsSubject')
            ->get();

        if ($subjects->isEmpty()) {
            return [
                'success' => false,
                'created_count' => 0,
                'best_gap' => -1,
                'error' => 'No subjects found for this semester.',
            ];
        }

        $subjectCount = $subjects->count();
        $startDate = Carbon::parse($params['start_date']);
        $endDate = Carbon::parse($params['end_date']);
        $totalDays = $startDate->diffInDays($endDate);

        if ($subjectCount > 1) {
            $maxGapSearch = (int) floor(($totalDays + 7) / ($subjectCount - 1));
        } else {
            $maxGapSearch = 0;
        }

        $bestGap = -1;

        for ($gap = $maxGapSearch; $gap >= 0; $gap--) {
            $tempDate = $startDate->copy();
            if ($tempDate->isSunday()) {
                $tempDate->addDay();
            }

            $fits = true;
            for ($i = 1; $i < $subjectCount; $i++) {
                $tempDate->addDays(1 + $gap);
                while ($tempDate->isSunday()) {
                    $tempDate->addDay();
                }

                if ($tempDate->gt($endDate)) {
                    $fits = false;
                    break;
                }
            }

            if ($fits) {
                $bestGap = $gap;
                break;
            }
        }

        if ($bestGap === -1) {
            return [
                'success' => false,
                'created_count' => 0,
                'best_gap' => -1,
                'error' => "Date range is too short for $subjectCount subjects (excluding Sundays). Please extend the End Date.",
            ];
        }

        DB::beginTransaction();
        try {
            $currentDate = $startDate->copy();
            if ($currentDate->isSunday()) {
                $currentDate->addDay();
            }

            $createdCount = 0;

            foreach ($subjects as $semesterSubject) {
                $existing = HeDatesheet::withTrashed()
                    ->where('program_id', $params['program_id'])
                    ->where('semester_no', $params['semester_no'])
                    ->where('bs_subject_id', $semesterSubject->bs_subject_id)
                    ->where('exam_type', $params['exam_type'])
                    ->where('academic_year', $params['academic_year'])
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                        $existing->update([
                            'exam_date' => $currentDate->toDateString(),
                            'start_time' => $params['default_start_time'],
                            'end_time' => $params['default_end_time'],
                            'created_by' => $userId,
                        ]);
                        $createdCount++;
                    }
                } else {
                    HeDatesheet::create([
                        'program_id' => $params['program_id'],
                        'semester_no' => $params['semester_no'],
                        'bs_subject_id' => $semesterSubject->bs_subject_id,
                        'exam_type' => $params['exam_type'],
                        'exam_date' => $currentDate->toDateString(),
                        'start_time' => $params['default_start_time'],
                        'end_time' => $params['default_end_time'],
                        'academic_year' => $params['academic_year'],
                        'created_by' => $userId,
                    ]);
                    $createdCount++;
                }

                $currentDate->addDays(1 + $bestGap);
                while ($currentDate->isSunday()) {
                    $currentDate->addDay();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'created_count' => $createdCount,
                'best_gap' => $bestGap,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'created_count' => 0,
                'best_gap' => -1,
                'error' => 'Error generating datesheet: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get subjects for a program and semester (for AJAX dropdowns).
     */
    public function getHeSemesterSubjects(int $programId, int $semesterNo): Collection
    {
        return Semester::where('program_id', $programId)
            ->where('semester_no', $semesterNo)
            ->with('bsSubject')
            ->get()
            ->map(function ($semester) {
                return [
                    'id' => $semester->bs_subject_id,
                    'name' => $semester->bsSubject->name ?? 'Unknown',
                    'code' => $semester->bsSubject->code ?? '',
                ];
            });
    }
}
