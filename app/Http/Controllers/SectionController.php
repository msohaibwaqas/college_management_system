<?php

namespace App\Http\Controllers;

use App\Models\Admissions;
use App\Models\Student;
use App\Models\Subject;
use App\Models\StudentSubjectSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SectionController extends Controller
{
    /**
     * Show the Sections management page.
     * Admin selects admission_year + subject → sees all enrolled students
     * for that subject and can assign/change their section for that subject.
     */
    public function index(Request $request)
    {
        // All available admission years
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        $selectedYear    = $request->input('admission_year');
        $selectedSubject = $request->input('subject_id');

        // Subjects for selected year (populated via AJAX, but we also pass for
        // direct page load with both params set)
        $subjects = collect();
        if ($selectedYear) {
            $subjects = Subject::where(function ($q) use ($selectedYear) {
                $q->whereHas('studentElectives.student.admissions', function ($sq) use ($selectedYear) {
                    $sq->where('admission_year', $selectedYear);
                })
                ->orWhereHas('studentCompulsorySubjects.student.admissions', function ($sq) use ($selectedYear) {
                    $sq->where('admission_year', $selectedYear);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name']);
        }

        $students        = collect();
        $sections        = collect();
        $subjectName     = '';

        if ($selectedYear && $selectedSubject) {
            $subjectName = Subject::find($selectedSubject)?->name ?? '';

            // All enrolled students who have this subject
            $students = Student::with([
                    'admissions' => fn($q) => $q->where('admission_year', $selectedYear),
                    'father',
                    'electives.subject',
                    'subjectSections' => fn($q) => $q
                        ->where('subject_id', $selectedSubject)
                        ->where('admission_year', $selectedYear),
                ])
                ->whereHas('admissions', fn($q) => $q
                    ->where('admission_year', $selectedYear)
                    ->whereIn('status', ['Active', 'Admitted'])
                )
                ->where(function ($q) use ($selectedSubject) {
                    $q->whereHas('electives', fn($sq) => $sq->where('subject_id', $selectedSubject))
                      ->orWhereHas('compulsorySubjects', fn($sq) => $sq->where('subject_id', $selectedSubject));
                })
                ->join('admissions', function ($join) use ($selectedYear) {
                    $join->on('admissions.student_id', '=', 'students.id')
                         ->where('admissions.admission_year', $selectedYear)
                         ->whereNull('admissions.deleted_at');
                })
                ->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED)')
                ->select('students.*')
                ->get();

            // Summary of existing sections for this year+subject (ONLY for students enrolled in this subject!)
            $enrolledStudentIds = $students->pluck('id');
            $sections = StudentSubjectSection::where('admission_year', $selectedYear)
                ->where('subject_id', $selectedSubject)
                ->whereIn('student_id', $enrolledStudentIds)
                ->select('section')
                ->selectRaw('COUNT(*) as student_count')
                ->groupBy('section')
                ->orderBy('section')
                ->get();
        }

        return view('student-affairs.sections', compact(
            'admissionYears',
            'subjects',
            'selectedYear',
            'selectedSubject',
            'subjectName',
            'students',
            'sections'
        ));
    }

    /**
     * AJAX: get subjects for a given admission year (for the year dropdown).
     */
    public function getSubjects(Request $request)
    {
        $year = $request->admission_year;
        if (!$year) return response()->json([]);

        $subjects = Subject::where(function ($q) use ($year) {
            $q->whereHas('studentElectives.student.admissions', function ($sq) use ($year) {
                $sq->where('admission_year', $year);
            })
            ->orWhereHas('studentCompulsorySubjects.student.admissions', function ($sq) use ($year) {
                $sq->where('admission_year', $year);
            });
        })
        ->orderBy('name')
        ->get(['id', 'name']);

        return response()->json($subjects);
    }

    /**
     * Save section assignments for selected students (for a specific subject).
     */
    public function store(Request $request)
    {
        $request->validate([
            'admission_year' => 'required|integer',
            'subject_id'     => 'required|integer|exists:subjects,id',
            'student_ids'    => 'required|array|min:1',
            'student_ids.*'  => 'integer|exists:students,id',
            'section'        => 'required|string|max:50',
        ]);

        $year       = (int) $request->admission_year;
        $subjectId  = (int) $request->subject_id;
        $studentIds = $request->student_ids;
        $section    = strtoupper(trim($request->section));
        $userId     = Auth::id();

        DB::transaction(function () use ($year, $subjectId, $studentIds, $section, $userId) {
            foreach ($studentIds as $studentId) {
                $record = StudentSubjectSection::withTrashed()->firstOrNew([
                    'student_id'     => $studentId,
                    'subject_id'     => $subjectId,
                    'admission_year' => $year,
                ]);

                if ($record->trashed()) {
                    $record->restore();
                }

                $record->fill([
                    'section'    => $section,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ])->save();

                // Clear legacy teacher assignment attendance_section overrides
                \App\Models\StudentTeacherSubject::where('student_id', $studentId)
                    ->where('subject_id', $subjectId)
                    ->update(['attendance_section' => null]);
            }
        });

        return redirect()
            ->route('student-affairs.sections.index', [
                'admission_year' => $year,
                'subject_id'     => $subjectId,
            ])
            ->with('success', "Section '{$section}' assigned to " . count($studentIds) . " student(s) successfully.");
    }

    /**
     * Clear the section for selected students or all students for a subject.
     */
    public function clearSection(Request $request)
    {
        $request->validate([
            'admission_year' => 'required|integer',
            'subject_id'     => 'required|integer|exists:subjects,id',
            'student_ids'    => 'nullable|array',
            'clear_all'      => 'nullable|boolean',
        ]);

        $year      = (int) $request->admission_year;
        $subjectId = (int) $request->subject_id;

        if ($request->clear_all) {
            StudentSubjectSection::where('admission_year', $year)
                ->where('subject_id', $subjectId)
                ->delete();

            return redirect()
                ->route('student-affairs.sections.index', [
                    'admission_year' => $year,
                    'subject_id'     => $subjectId,
                ])
                ->with('success', 'All section assignments cleared for this subject.');
        }

        if (!empty($request->student_ids)) {
            StudentSubjectSection::where('admission_year', $year)
                ->where('subject_id', $subjectId)
                ->whereIn('student_id', $request->student_ids)
                ->delete();
        }

        return redirect()
            ->route('student-affairs.sections.index', [
                'admission_year' => $year,
                'subject_id'     => $subjectId,
            ])
            ->with('success', 'Section cleared for selected students.');
    }

    /**
     * Copy/Sync section assignments from a source subject to the current target subject
     * ONLY for students who take the target subject in the specified admission year.
     */
    public function copySections(Request $request)
    {
        $request->validate([
            'admission_year'   => 'required|integer',
            'target_subject_id'=> 'required|integer|exists:subjects,id',
            'source_subject_id'=> 'required|integer|exists:subjects,id|different:target_subject_id',
        ]);

        $year            = (int) $request->admission_year;
        $targetSubjectId = (int) $request->target_subject_id;
        $sourceSubjectId = (int) $request->source_subject_id;
        $userId          = Auth::id();

        // Get student IDs who take target_subject_id for this year
        $targetStudentIds = Student::whereHas('admissions', fn($q) => $q
                ->where('admission_year', $year)
                ->whereIn('status', ['Active', 'Admitted'])
            )
            ->where(function ($q) use ($targetSubjectId) {
                $q->whereHas('electives', fn($sq) => $sq->where('subject_id', $targetSubjectId))
                  ->orWhereHas('compulsorySubjects', fn($sq) => $sq->where('subject_id', $targetSubjectId));
            })
            ->pluck('id');

        if ($targetStudentIds->isEmpty()) {
            return redirect()->back()->with('error', 'No enrolled students found for the target subject in this year.');
        }

        $sourceSections = StudentSubjectSection::where('admission_year', $year)
            ->where('subject_id', $sourceSubjectId)
            ->whereIn('student_id', $targetStudentIds)
            ->get();

        if ($sourceSections->isEmpty()) {
            $sourceName = Subject::find($sourceSubjectId)?->name ?? 'Source Subject';
            return redirect()->back()->with('error', "No section assignments found in {$sourceName} for students enrolled in this target subject.");
        }

        $copiedCount = 0;
        DB::transaction(function() use ($sourceSections, $targetSubjectId, $year, $userId, &$copiedCount) {
            foreach ($sourceSections as $sourceSec) {
                $record = StudentSubjectSection::withTrashed()->firstOrNew([
                    'student_id'     => $sourceSec->student_id,
                    'subject_id'     => $targetSubjectId,
                    'admission_year' => $year,
                ]);

                if ($record->trashed()) {
                    $record->restore();
                }

                $record->fill([
                    'section'    => $sourceSec->section,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ])->save();

                \App\Models\StudentTeacherSubject::where('student_id', $sourceSec->student_id)
                    ->where('subject_id', $targetSubjectId)
                    ->update(['attendance_section' => null]);

                $copiedCount++;
            }
        });

        $sourceName = Subject::find($sourceSubjectId)?->name ?? 'Source Subject';
        $targetName = Subject::find($targetSubjectId)?->name ?? 'Target Subject';

        return redirect()
            ->route('student-affairs.sections.index', [
                'admission_year' => $year,
                'subject_id'     => $targetSubjectId,
            ])
            ->with('success', "Successfully copied section assignments for {$copiedCount} student(s) from {$sourceName} to {$targetName}!");
    }
}
