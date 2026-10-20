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
}
