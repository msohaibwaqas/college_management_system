<?php

namespace App\Services;

use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ResultService centralizes query scoping, subject sorting, and marks
 * persistence logic that was previously duplicated across multiple
 * ResultController methods.
 */
class ResultService
{
    /**
     * The canonical priority ordering for HSSC subjects used
     * when displaying result sheets and final mark-sheets.
     */
    public const SUBJECT_PRIORITY = [
        'Translation Of Holy Quran',
        'Urdu',
        'English',
        'Islamic Education',
        'Pakistan Studies',
    ];

    // ─── Teacher Scoping ───────────────────────────────────────────

    /**
     * Determine whether the current authenticated user is a teacher
     * and, if so, return their staff record.
     *
     * @return array{bool, \App\Models\Staff|null}
     */
    public function resolveTeacherContext(): array
    {
        $user = Auth::user();
        $isTeacher = $user && $user->isTeacher();
        $staff = $isTeacher ? $user->staff : null;

        return [$isTeacher, $staff];
    }

    /**
     * Apply teacher-scoping to a query by restricting to subjects
     * assigned via student_teacher_subjects.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \App\Models\Staff|null  $staff
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToTeacherSubjects($query, $staff)
    {
        if (!$staff) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('subject_id', function ($q) use ($staff) {
            $q->select('subject_id')
              ->from('student_teacher_subjects')
              ->where('staff_id', $staff->id);
        });
    }

    /**
     * Apply teacher-scoping to a student query by restricting
     * to students assigned to a specific teacher for a specific subject.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \App\Models\Staff  $staff
     * @param  int  $subjectId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStudentsToTeacher($query, $staff, int $subjectId)
    {
        return $query->whereHas('studentTeacherSubjects', function ($q) use ($staff, $subjectId) {
            $q->where('staff_id', $staff->id)
              ->where('subject_id', $subjectId);
        });
    }

    // ─── Subject Sorting ───────────────────────────────────────────

    /**
     * Sort a collection of subjects using the canonical HSSC priority
     * ordering (Holy Quran → Urdu → English → Islamic Ed → Pak Studies),
     * then by subject code descending for remaining subjects.
     *
     * @param  \Illuminate\Support\Collection  $subjects
     * @return \Illuminate\Support\Collection
     */
    public function sortByPriority($subjects)
    {
        $priority = self::SUBJECT_PRIORITY;

        return $subjects->sort(function ($a, $b) use ($priority) {
            $posA = array_search($a->name, $priority);
            $posB = array_search($b->name, $priority);

            if ($posA !== false && $posB !== false) {
                return $posA - $posB;
            }
            if ($posA !== false) return -1;
            if ($posB !== false) return 1;

            return strcmp($b->code, $a->code);
        });
    }

    // ─── Student Subject Merging ───────────────────────────────────

    /**
     * Merge a student's compulsory and elective subjects into a single
     * sorted collection.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Support\Collection
     */
    public function getMergedSubjects(Student $student)
    {
        $compulsory = $student->compulsorySubjects->map(fn($item) => $item->subject);
        $electives = $student->electives->map(fn($item) => $item->subject);

        return $this->sortByPriority($compulsory->merge($electives));
    }

    // ─── Final Result Persistence ──────────────────────────────────

    /**
     * Upsert a single class-level result row (Class 11 or Class 12).
     * This consolidates the identical updateOrCreate logic from
     * storeFinalResult() and store11thResult().
     *
     * @param  int     $studentId
     * @param  int     $subjectId
     * @param  string  $classLevel   '11' or '12'
     * @param  int     $academicYear
     * @param  int     $totalMarks
     * @param  int|null $theory
     * @param  int|null $practical
     * @param  bool    $isAbsent
     * @return \App\Models\Result
     */
    public function upsertFinalResult(
        int $studentId,
        int $subjectId,
        string $classLevel,
        int $academicYear,
        int $totalMarks,
        ?int $theory,
        ?int $practical,
        bool $isAbsent
    ): Result {
        return Result::updateOrCreate(
            [
                'student_id'    => $studentId,
                'subject_id'    => $subjectId,
                'class_level'   => $classLevel,
                'exam_type'     => 'Final',
                'exam_name'     => 'Final',
                'academic_year' => $academicYear,
            ],
            [
                'total_marks'       => $totalMarks,
                'obtained_theory'   => $theory,
                'obtained_practical'=> $practical,
                'is_absent'         => $isAbsent,
                'created_by'        => Auth::id(),
                'updated_by'        => Auth::id(),
            ]
        );
    }

    /**
     * Search for a student by admission year and a BISE roll number
     * field (bise_roll_11 or bise_roll_12).
     *
     * @param  int     $admissionYear
     * @param  string  $rollField    'bise_roll_11' or 'bise_roll_12'
     * @param  string  $rollValue
     * @return \App\Models\Student|null
     */
    public function findStudentByBiseRoll(int $admissionYear, string $rollField, string $rollValue): ?Student
    {
        return Student::with(['admissions', 'father', 'compulsorySubjects.subject', 'electives.subject'])
            ->whereHas('admissions', function ($q) use ($admissionYear, $rollField, $rollValue) {
                $q->where('admission_year', $admissionYear)
                  ->where($rollField, $rollValue);
            })
            ->first();
    }

    // ─── Higher Education (HE) Result Operations ────────────────────

    /**
     * Check if a teacher is assigned to a specific BS subject/program/semester.
     */
    public function isTeacherAssignedToHeSubject(int $staffId, int $subjectId, int $programId, int $semesterNo, int $academicYear): bool
    {
        return \App\Models\HeTeacherAssignment::where('staff_id', $staffId)
            ->where('bs_subject_id', $subjectId)
            ->whereHas('heAdmission', function ($q) use ($programId, $semesterNo, $academicYear) {
                $q->where('program_id', $programId)
                  ->where('current_semester', '>=', $semesterNo)
                  ->where('admission_year', $academicYear);
            })
            ->exists();
    }

    /**
     * Scope an HE admission query to only include students assigned to a teacher for a subject.
     */
    public function scopeHeStudentsToTeacher($query, $staff, int $subjectId)
    {
        return $query->whereHas('teacherAssignments', function ($q) use ($staff, $subjectId) {
            $q->where('staff_id', $staff->id)->where('bs_subject_id', $subjectId);
        });
    }

    /**
     * Batch save / upsert HE marks.
     *
     * @return array{saved: int, locked_skipped: int}
     */
    public function upsertHeResults(array $marks, int $semesterNo, string $examType, int $academicYear, int $userId, bool $canEditLocked): array
    {
        $savedCount = 0;
        $lockedSkipped = 0;

        foreach ($marks as $markData) {
            $existing = \App\Models\HeResult::where('he_admission_id', $markData['he_admission_id'])
                ->where('bs_subject_id', $markData['bs_subject_id'])
                ->where('semester_no', $semesterNo)
                ->where('exam_type', $examType)
                ->first();

            $isAbsent = $markData['is_absent'] ?? false;
            $theory = $isAbsent ? 0 : ($markData['theory_marks'] ?? null);
            $practical = $isAbsent ? 0 : ($markData['practical_marks'] ?? null);
            $total = $isAbsent ? 0 : (($theory ?? 0) + ($practical ?? 0));

            if ($existing) {
                if ($existing->is_locked && !$canEditLocked) {
                    $lockedSkipped++;
                    continue;
                }

                $existing->update([
                    'theory_marks' => $theory,
                    'practical_marks' => $practical,
                    'total_marks' => $total,
                    'is_absent' => $isAbsent,
                    'updated_by' => $userId,
                ]);
            } else {
                \App\Models\HeResult::create([
                    'he_admission_id' => $markData['he_admission_id'],
                    'bs_subject_id' => $markData['bs_subject_id'],
                    'semester_no' => $semesterNo,
                    'exam_type' => $examType,
                    'theory_marks' => $theory,
                    'practical_marks' => $practical,
                    'total_marks' => $total,
                    'is_absent' => $isAbsent,
                    'academic_year' => $academicYear,
                    'is_locked' => false,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
            $savedCount++;
        }

        return ['saved' => $savedCount, 'locked_skipped' => $lockedSkipped];
    }

    /**
     * Lock HE results by array of result IDs.
     */
    public function lockHeResults(array $resultIds, int $userId): int
    {
        $lockedCount = 0;
        foreach ($resultIds as $resultId) {
            $result = \App\Models\HeResult::find($resultId);
            if ($result && !$result->is_locked) {
                $result->lock($userId);
                $lockedCount++;
            }
        }
        return $lockedCount;
    }

    // ─── HSSC Test & Marks Management ──────────────────────────────

    /**
     * Get scheduled test details for a specific date with batch & exam metadata.
     */
    public function getTestDetailsForDate(string $testDate, ?\App\Models\Staff $staff = null): \Illuminate\Support\Collection
    {
        $query = \App\Models\Datesheet::with(['subject'])
            ->whereDate('test_date', $testDate);

        if ($staff) {
            $this->scopeToTeacherSubjects($query, $staff);
        }

        return $query->get()->map(function ($test) {
            $admission = \App\Models\Admissions::where('admission_year', $test->admission_year)
                ->whereIn('status', ['Admitted', 'Active'])
                ->first();

            $latestResult = Result::where('subject_id', $test->subject_id)
                ->where('academic_year', $test->admission_year)
                ->where('class_level', $admission?->current_class ?? 'Unknown')
                ->latest()
                ->first();

            $existingExams = Result::where('subject_id', $test->subject_id)
                ->where('academic_year', $test->admission_year)
                ->where('class_level', $admission?->current_class ?? 'Unknown')
                ->select('exam_type', 'exam_name', 'total_marks', DB::raw('MAX(created_at) as last_created'))
                ->groupBy('exam_type', 'exam_name', 'total_marks')
                ->orderBy('last_created', 'desc')
                ->limit(10)
                ->get();

            return [
                'id' => $test->id,
                'admission_year' => $test->admission_year,
                'class_level' => $admission?->current_class ?? 'Unknown',
                'subject_id' => $test->subject_id,
                'subject_name' => $test->subject?->name ?? 'Unknown',
                'subject_type' => $test->subject?->type ?? 'Unknown',
                'existing_exams' => $existingExams,
                'latest_exam_info' => $latestResult ? [
                    'exam_type' => $latestResult->exam_type,
                    'exam_name' => $latestResult->exam_name,
                    'total_marks' => $latestResult->total_marks,
                ] : null,
            ];
        });
    }

    /**
     * Fetch students and existing results for marks entry.
     */
    public function getStudentsForMarksEntry(
        int $year,
        string $classLevel,
        int $subjectId,
        string $examType,
        ?string $examName = null,
        ?\App\Models\Staff $staff = null,
        bool $isTeacher = false
    ): array {
        $subject = Subject::findOrFail($subjectId);

        $studentsQuery = Student::with(['admissions', 'father'])
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->where('admissions.admission_year', $year)
            ->select('students.*', 'admissions.college_roll_no');

        if ($isTeacher && $staff) {
            $this->scopeStudentsToTeacher($studentsQuery, $staff, $subjectId);
        }

        if ($subject->type === 'Elective') {
            $studentsQuery->whereHas('electives', function ($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            });
        } else {
            $studentsQuery->where(function ($q) use ($subjectId) {
                $q->whereHas('compulsorySubjects', function ($sq) use ($subjectId) {
                    $sq->where('subject_id', $subjectId);
                })->orWhereNotExists(function ($eq) {
                    $eq->select(DB::raw(1))
                        ->from('student_compulsory_subjects')
                        ->whereColumn('student_compulsory_subjects.student_id', 'students.id');
                });
            })->whereIn('admissions.status', ['Admitted', 'Active']);
        }

        $students = $studentsQuery->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED)')->get();

        $existingResults = Result::where('academic_year', $year)
            ->where('class_level', $classLevel)
            ->where('subject_id', $subjectId)
            ->where('exam_type', $examType)
            ->where('exam_name', $examName ?: $examType)
            ->get()
            ->keyBy('student_id');

        return [
            'students' => $students,
            'existing_results' => $existingResults,
            'subject' => $subject,
        ];
    }

    /**
     * Generate custom attribute labels for validation of marks.
     */
    public function getMarksValidationAttributes(array $marks): array
    {
        $customAttributes = [];
        $studentIds = array_keys($marks);
        $students = Student::with('admissions')
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        foreach ($marks as $studentId => $mark) {
            $student = $students->get($studentId);
            $name = $student ? trim($student->first_name . ' ' . ($student->last_name ?? '')) : "Student #$studentId";
            $rollNo = ($student && $student->admissions->first()) ? $student->admissions->first()->college_roll_no : '';
            $displayName = $rollNo ? "$name (Roll: $rollNo)" : $name;

            $customAttributes["marks.$studentId.theory"] = "$displayName theory";
            $customAttributes["marks.$studentId.practical"] = "$displayName practical";
        }

        return $customAttributes;
    }

    /**
     * Persist test marks for multiple students with audit notifications.
     */
    public function saveMarks(array $data, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        $marks = $data['marks'] ?? [];
        $studentIds = array_keys($marks);
        $students = Student::with('admissions')->whereIn('id', $studentIds)->get()->keyBy('id');

        DB::transaction(function () use ($data, $marks, $students, $userId) {
            foreach ($marks as $studentId => $mark) {
                $isAbsent = isset($mark['is_absent']) && $mark['is_absent'];

                if (!$isAbsent &&
                    ($mark['theory'] === null || $mark['theory'] === '') &&
                    (!isset($mark['practical']) || $mark['practical'] === null || $mark['practical'] === '')) {
                    continue;
                }

                $originalMarks = Result::where([
                    'student_id' => $studentId,
                    'subject_id' => $data['subject_id'],
                    'class_level' => $data['class_level'],
                    'exam_type' => $data['exam_type'],
                    'exam_name' => $data['exam_name'],
                    'academic_year' => $data['academic_year'],
                ])->first();

                $newTheory = $isAbsent ? 0 : ($mark['theory'] !== null && $mark['theory'] !== '' ? $mark['theory'] : null);
                $newPractical = $isAbsent ? 0 : (isset($mark['practical']) && $mark['practical'] !== null && $mark['practical'] !== '' ? $mark['practical'] : null);

                // Detect Change and notify admins
                if ($originalMarks) {
                    $theoryChanged = $originalMarks->obtained_theory != $newTheory;
                    $practicalChanged = $originalMarks->obtained_practical != $newPractical;

                    if ($theoryChanged || $practicalChanged) {
                        $student = $students->get($studentId);
                        $subject = Subject::find($data['subject_id']);
                        $user = Auth::user();
                        $rollNo = ($student && $student->admissions->first()) ? $student->admissions->first()->college_roll_no : '';
                        $staffName = ($user && $user->staff) ? trim($user->staff->first_name . ' ' . ($user->staff->last_name ?? '')) : ($user->username ?? 'Staff');

                        $adminUsers = \App\Models\User::whereHas('roles', function ($q) {
                            $q->whereIn('name', ['admin', 'principal', 'hssc_admin']);
                        })->get();

                        $details = [
                            'student_name' => $student ? trim($student->first_name . ' ' . ($student->last_name ?? '')) : "Student #$studentId",
                            'student_roll_no' => $rollNo,
                            'subject' => $subject->name ?? $subject->title ?? 'Subject',
                            'user_name' => $staffName,
                            'old_theory' => $originalMarks->obtained_theory ?? 'N/A',
                            'new_theory' => $newTheory ?? 'N/A',
                            'old_practical' => $originalMarks->obtained_practical ?? 'N/A',
                            'new_practical' => $newPractical ?? 'N/A',
                        ];

                        foreach ($adminUsers as $admin) {
                            $admin->notify(new \App\Notifications\ResultMarkChanged($details));
                        }
                    }
                }

                Result::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $data['subject_id'],
                        'class_level' => $data['class_level'],
                        'exam_type' => $data['exam_type'],
                        'exam_name' => $data['exam_name'],
                        'academic_year' => $data['academic_year'],
                    ],
                    [
                        'total_marks' => $data['total_marks'],
                        'obtained_theory' => $newTheory,
                        'obtained_practical' => $newPractical,
                        'is_absent' => $isAbsent,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]
                );
            }
        });
    }

    /**
     * Store final results across Class 11 and Class 12 for a single student.
     */
    public function saveStudentFinalResults(int $studentId, int $admissionYear, array $marks): void
    {
        DB::transaction(function () use ($studentId, $admissionYear, $marks) {
            foreach ($marks as $subjectId => $classData) {
                $subject = Subject::find($subjectId);
                if (!$subject) continue;

                // Class 11
                if (isset($classData[11])) {
                    $data11 = $classData[11];
                    $isAbsent11 = isset($data11['is_absent']) && $data11['is_absent'];
                    $theory11 = $isAbsent11 ? 0 : ($data11['theory'] ?? null);

                    if ($theory11 !== null || $isAbsent11) {
                        $this->upsertFinalResult(
                            $studentId, $subjectId, '11', $admissionYear,
                            $subject->total_11, $theory11, null, $isAbsent11
                        );
                    }
                }

                // Class 12
                if (isset($classData[12])) {
                    $data12 = $classData[12];
                    $isAbsent12 = (isset($data12['theory_absent']) && $data12['theory_absent']) || (isset($data12['practical_absent']) && $data12['practical_absent']);

                    $theory12 = isset($data12['theory_absent']) ? 0 : ($data12['theory'] ?? null);
                    $practical12 = isset($data12['practical_absent']) ? 0 : ($data12['practical'] ?? null);

                    $hasTheory = $theory12 !== null || isset($data12['theory_absent']);
                    $hasPractical = $practical12 !== null || isset($data12['practical_absent']);

                    if ($hasTheory || $hasPractical) {
                        $this->upsertFinalResult(
                            $studentId, $subjectId, '12', $admissionYear + 1,
                            ($subject->theory_12 ?? 0) + ($subject->practical_12 ?? 0),
                            $theory12, $practical12, $isAbsent12
                        );
                    }
                }
            }
        });
    }

    /**
     * Store 11th class final results for a single student.
     */
    public function saveStudent11thResults(int $studentId, int $admissionYear, array $marks): void
    {
        DB::transaction(function () use ($studentId, $admissionYear, $marks) {
            foreach ($marks as $subjectId => $classData) {
                $subject = Subject::find($subjectId);
                if (!$subject) continue;

                if (isset($classData[11])) {
                    $data11 = $classData[11];
                    $isAbsent = isset($data11['is_absent']) && $data11['is_absent'];
                    $theory11 = $isAbsent ? 0 : ($data11['theory'] ?? null);

                    if ($theory11 !== null || $isAbsent) {
                        $this->upsertFinalResult(
                            $studentId, $subjectId, '11', $admissionYear,
                            $subject->total_11, $theory11, null, $isAbsent
                        );
                    }
                }
            }
        });
    }

    /**
     * Calculate stats and retrieve paginated student list for final results overview.
     */
    public function getFinalResultsOverview(
        int $year,
        string $type = 'entered',
        string $sort = 'college_roll_no',
        string $direction = 'asc',
        int $perPage = 20,
        ?\App\Models\Staff $staff = null,
        bool $isTeacher = false
    ): array {
        $stats = [
            'total' => 0,
            'entered' => 0,
            'remaining' => 0,
        ];

        // 1. Calculate Stats for this Admission Year
        $totalQuery = \App\Models\Admissions::where('admission_year', $year);
        if ($isTeacher && $staff) {
            $totalQuery->whereHas('student.studentTeacherSubjects', function ($q) use ($staff) {
                $q->where('staff_id', $staff->id);
            });
        }
        $stats['total'] = $totalQuery->count();

        // 2. Calculate Students with Final Results
        $enteredQuery = Result::where('exam_type', 'Final')
            ->whereHas('student.admissions', function ($q) use ($year) {
                $q->where('admission_year', $year);
            });

        if ($isTeacher) {
            if ($staff) {
                $enteredQuery->whereIn('subject_id', function ($q) use ($staff) {
                    $q->select('subject_id')
                        ->from('student_teacher_subjects')
                        ->where('staff_id', $staff->id);
                })->whereIn('student_id', function ($q) use ($staff) {
                    $q->select('student_id')
                        ->from('student_teacher_subjects')
                        ->where('staff_id', $staff->id);
                });
            } else {
                $enteredQuery->whereRaw('1 = 0');
            }
        }

        $stats['entered'] = $enteredQuery->distinct('student_id')->count('student_id');
        $stats['remaining'] = max(0, $stats['total'] - $stats['entered']);

        // 3. Prepare Query for the list
        $query = Student::with(['admissions', 'results' => function ($q) {
                $q->where('exam_type', 'Final');
            }, 'results.subject', 'father'])
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->where('admissions.admission_year', $year)
            ->select('students.*');

        if ($type === 'entered') {
            $query->whereHas('results', function ($q) {
                $q->where('exam_type', 'Final');
            });
        } elseif ($type === 'remaining') {
            $query->whereDoesntHave('results', function ($q) {
                $q->where('exam_type', 'Final');
            });
        }

        if ($isTeacher) {
            if ($staff) {
                $query->whereHas('studentTeacherSubjects', function ($q) use ($staff) {
                    $q->where('staff_id', $staff->id);
                });

                $query->with(['results' => function ($q) use ($staff) {
                    $q->where('exam_type', 'Final')
                        ->whereIn('subject_id', function ($subQ) use ($staff) {
                            $subQ->select('subject_id')
                                ->from('student_teacher_subjects')
                                ->where('staff_id', $staff->id);
                        });
                }]);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        if ($sort === 'college_roll_no') {
            $query->orderBy('admissions.college_roll_no', $direction);
        } elseif ($sort === 'bise_roll_11') {
            $query->orderBy('admissions.bise_roll_11', $direction);
        } elseif ($sort === 'bise_roll_12') {
            $query->orderBy('admissions.bise_roll_12', $direction);
        } elseif ($sort === 'grand_total') {
            $query->selectSub(function ($q) {
                $q->selectRaw('SUM(obtained_theory + COALESCE(obtained_practical, 0))')
                    ->from('results')
                    ->whereColumn('student_id', 'students.id')
                    ->where('exam_type', 'Final');
            }, 'grand_total_sum')
            ->orderBy('grand_total_sum', $direction);
        }

        if (!in_array($perPage, [20, 50, 100, 500])) {
            $perPage = 20;
        }

        $students = $query->paginate($perPage)->withQueryString();

        return [
            'students' => $students,
            'stats' => $stats,
        ];
    }

}
