<?php

namespace App\Http\Controllers;

use App\Models\Admissions;
use App\Models\Datesheet;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Services\ResultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    public function __construct(
        protected ResultService $resultService
    ) {}

    /**
     * Display a listing of the results.
     */
    public function index(Request $request)
    {
        $admissionYears = Admissions::distinctAdmissionYears();

        // Teachers cannot view the result index — redirect early
        [$isTeacher, $staff] = $this->resultService->resolveTeacherContext();

        if ($isTeacher) {
            return redirect()->route('dashboard')->with('error', 'Access denied. You can only enter marks for your scheduled tests.');
        }

        // Group by test to show as "Tests" rather than individual student results
        $testsQuery = Result::select(
                'academic_year',
                'class_level',
                'subject_id',
                'exam_type',
                'exam_name',
                'total_marks',
                DB::raw('count(*) as student_count'),
                DB::raw('avg(obtained_theory + IFNULL(obtained_practical, 0)) as average_marks')
            )
            ->groupBy('academic_year', 'class_level', 'subject_id', 'exam_type', 'exam_name', 'total_marks')
            ->with('subject');

        if ($request->filled('academic_year')) {
            $testsQuery->where('academic_year', $request->academic_year);
        }

        if ($request->filled('class_level')) {
            $testsQuery->where('class_level', $request->class_level);
        }

        if ($request->filled('exam_type')) {
            $testsQuery->where('exam_type', $request->exam_type);
        }

        if ($request->filled('subject_id')) {
            $testsQuery->where('subject_id', $request->subject_id);
        }

        $tests = $testsQuery->orderByDesc('academic_year')->paginate(20);
        $subjects = Subject::orderBy('name')->get();

        return view('results.index', compact('tests', 'admissionYears', 'subjects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $preSelected = [
            'subject_id' => $request->subject_id,
            'class' => $request->class,
            'year' => $request->year,
        ];

        $query = Datesheet::with(['subject'])
            ->select('test_date', 'admission_year', 'subject_id')
            ->orderBy('test_date', 'desc');

        [$isTeacher, $staff] = $this->resultService->resolveTeacherContext();

        if ($isTeacher) {
            $this->resultService->scopeToTeacherSubjects($query, $staff);
        }

        $testDates = $query->get()
            ->groupBy(function ($item) {
                return $item->test_date->format('Y-m-d');
            })
            ->map(function ($group) {
                return [
                    'date' => $group->first()->test_date,
                    'tests' => $group,
                ];
            });

        return view('results.create', compact('testDates', 'preSelected'));
    }

    /**
     * Get test details for a specific date (AJAX)
     */
    public function getTestDetails(Request $request)
    {
        $request->validate([
            'test_date' => 'required|date',
            'exam_name' => 'nullable|string',
        ]);

        [$isTeacher, $staff] = $this->resultService->resolveTeacherContext();

        $tests = $this->resultService->getTestDetailsForDate(
            $request->test_date,
            $isTeacher ? $staff : null,
            $request->input('exam_name')
        );

        return response()->json(['tests' => $tests]);
    }

    /**
     * Fetch students for marks entry (AJAX)
     */
    public function fetchStudents(Request $request)
    {
        $request->validate([
            'academic_year' => 'required|integer',
            'class_level' => 'required|in:11,12',
            'subject_id' => 'required|exists:subjects,id',
            'exam_type' => 'required|string',
            'exam_name' => 'nullable|string',
        ]);

        [$isTeacher, $staff] = $this->resultService->resolveTeacherContext();

        if ($isTeacher && !$staff) {
            return response()->json(['error' => 'Teacher record not found.'], 403);
        }

        $data = $this->resultService->getStudentsForMarksEntry(
            (int) $request->academic_year,
            $request->class_level,
            (int) $request->subject_id,
            $request->exam_type,
            $request->exam_name,
            $staff,
            $isTeacher
        );

        return response()->json($data);
    }

    /**
     * Store test marks
     */
    public function store(Request $request)
    {
        $customAttributes = [];
        if ($request->has('marks') && is_array($request->marks)) {
            $customAttributes = $this->resultService->getMarksValidationAttributes($request->marks);
        }

        $request->validate([
            'academic_year' => 'required|integer',
            'class_level' => 'required|in:11,12',
            'subject_id' => 'required|exists:subjects,id',
            'exam_type' => 'required|string',
            'exam_name' => 'required|string',
            'total_marks' => 'required|integer|min:1',
            'marks' => 'required|array',
            'marks.*.theory' => 'nullable|integer|min:0',
            'marks.*.practical' => 'nullable|integer|min:0',
            'marks.*.is_absent' => 'nullable',
        ], [], $customAttributes);

        $this->resultService->saveMarks($request->all(), Auth::id());

        return response()->json(['success' => true, 'message' => 'Results saved successfully!']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        return $this->create($request);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'academic_year' => 'required|integer',
            'class_level' => 'required|in:11,12',
            'subject_id' => 'required|exists:subjects,id',
            'exam_type' => 'required|string',
            'exam_name' => 'required|string',
        ]);

        $deleted = Result::where('academic_year', $request->academic_year)
            ->where('class_level', $request->class_level)
            ->where('subject_id', $request->subject_id)
            ->where('exam_type', $request->exam_type)
            ->where('exam_name', $request->exam_name)
            ->delete();

        return response()->json(['success' => true, 'message' => "Deleted $deleted result records."]);
    }

    /**
     * View individual student results
     */
    public function studentResults(Student $student)
    {
        $results = Result::with('subject')
            ->where('student_id', $student->id)
            ->orderByDesc('academic_year')
            ->orderBy('exam_type')
            ->get();

        $allResults = $results->groupBy('exam_type');

        $finalResults = Result::where('student_id', $student->id)
            ->where('exam_type', 'Final')
            ->with(['subject'])
            ->get();

        return view('results.student', compact('student', 'results', 'allResults', 'finalResults'));
    }

    /**
     * Show form for single student final result entry
     */
    public function createFinalResult()
    {
        if (Auth::user()?->isTeacher()) {
            return redirect()->route('dashboard')->with('error', 'Access denied.');
        }

        $admissionYears = Admissions::distinctAdmissionYears();

        return view('results.final_create', compact('admissionYears'));
    }

    /**
     * Search for a student by admission year and BISE roll no
     */
    public function searchFinalStudent(Request $request)
    {
        $request->validate([
            'admission_year' => 'required|integer',
            'bise_roll_12' => 'required|string',
        ]);

        $admissionYears = Admissions::distinctAdmissionYears();

        $student = $this->resultService->findStudentByBiseRoll(
            (int) $request->admission_year,
            'bise_roll_12',
            $request->bise_roll_12
        );

        if (!$student) {
            return redirect()->route('results.final.create')
                ->with('error', 'Student not found with the given details.')
                ->withInput();
        }

        $subjects = $this->resultService->getMergedSubjects($student);

        $results = Result::where('student_id', $student->id)
            ->where('exam_type', 'Final')
            ->get()
            ->groupBy(['class_level', 'subject_id']);

        return view('results.final_create', compact('student', 'subjects', 'results', 'admissionYears'));
    }

    /**
     * Store final results for single student
     */
    public function storeFinalResult(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'admission_year' => 'required|integer',
            'marks' => 'required|array',
        ]);

        if (Auth::user()?->isTeacher()) {
            return redirect()->back()->with('error', 'Teachers are not authorized to enter final results.');
        }

        $student = Student::findOrFail($request->student_id);

        $this->resultService->saveStudentFinalResults(
            $student->id,
            (int) $request->admission_year,
            $request->marks
        );

        $rollNo = $request->input('bise_roll_12') ?: ($student->admissions->first()?->bise_roll_12 ?? '');
        $rollPrefix = strlen($rollNo) >= 4 ? substr($rollNo, 0, 4) : $rollNo;

        return redirect()->route('results.final.create')
            ->with('success', 'Final results saved successfully!')
            ->withInput([
                'admission_year' => $request->admission_year,
                'bise_roll_12' => $rollPrefix,
            ]);
    }

    /**
     * Show form for single student 11th result entry
     */
    public function create11thResult()
    {
        if (Auth::user()?->isTeacher()) {
            return redirect()->route('dashboard')->with('error', 'Access denied.');
        }

        $admissionYears = Admissions::distinctAdmissionYears();

        return view('results.eleventh_create', compact('admissionYears'));
    }

    /**
     * Search for a student by admission year and BISE roll 11
     */
    public function search11thStudent(Request $request)
    {
        $request->validate([
            'admission_year' => 'required|integer',
            'bise_roll_11' => 'required|string',
        ]);

        $admissionYears = Admissions::distinctAdmissionYears();

        $student = $this->resultService->findStudentByBiseRoll(
            (int) $request->admission_year,
            'bise_roll_11',
            $request->bise_roll_11
        );

        if (!$student) {
            return redirect()->route('results.11th.create')
                ->with('error', 'Student not found with the given details.')
                ->withInput();
        }

        $subjects = $this->resultService->getMergedSubjects($student);

        $results = Result::where('student_id', $student->id)
            ->where('exam_type', 'Final')
            ->where('class_level', '11')
            ->get()
            ->groupBy(['class_level', 'subject_id']);

        return view('results.eleventh_create', compact('student', 'subjects', 'results', 'admissionYears'));
    }

    /**
     * Store 11th results for single student
     */
    public function store11thResult(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'admission_year' => 'required|integer',
            'marks' => 'required|array',
        ]);

        if (Auth::user()?->isTeacher()) {
            return redirect()->back()->with('error', 'Teachers are not authorized to enter final results.');
        }

        $student = Student::findOrFail($request->student_id);

        $this->resultService->saveStudent11thResults(
            $student->id,
            (int) $request->admission_year,
            $request->marks
        );

        $rollNo = $request->input('bise_roll_11') ?: ($student->admissions->first()?->bise_roll_11 ?? '');
        $rollPrefix = strlen($rollNo) >= 4 ? substr($rollNo, 0, 4) : $rollNo;

        return redirect()->route('results.11th.create')
            ->with('success', '11th Class results saved successfully!')
            ->withInput([
                'admission_year' => $request->admission_year,
                'bise_roll_11' => $rollPrefix,
            ]);
    }

    /**
     * View all final results for a given admission year
     */
    public function viewFinalResults(Request $request)
    {
        $admissionYears = Admissions::distinctAdmissionYears();
        $students = collect();
        $stats = [
            'total' => 0,
            'entered' => 0,
            'remaining' => 0,
        ];

        if ($request->filled('admission_year')) {
            [$isTeacher, $staff] = $this->resultService->resolveTeacherContext();

            $data = $this->resultService->getFinalResultsOverview(
                (int) $request->admission_year,
                $request->get('type', 'entered'),
                $request->get('sort', 'college_roll_no'),
                $request->get('direction', 'asc'),
                (int) $request->get('per_page', 20),
                $staff,
                $isTeacher
            );

            $students = $data['students'];
            $stats = $data['stats'];
        }

        return view('results.final_view', compact('admissionYears', 'students', 'stats'));
    }
}
