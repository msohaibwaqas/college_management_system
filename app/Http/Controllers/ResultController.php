<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Admissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Notifications\ResultMarkChanged;
use App\Services\ResultService;

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

        // Apply filters
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

        $testsQuery->orderByDesc('academic_year');

        // Teachers cannot view the result index — redirect early
        [$isTeacher, $staff] = $this->resultService->resolveTeacherContext();

        if ($isTeacher) {
            return redirect()->route('dashboard')->with('error', 'Access denied. You can only enter marks for your scheduled tests.');
        }

        $tests = $testsQuery->paginate(20);

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

        // Get all scheduled test dates from datesheets
        $query = \App\Models\Datesheet::with(['subject'])
            ->select('test_date', 'admission_year', 'subject_id')
            ->orderBy('test_date', 'desc');

        // Restrict to teacher's subjects if applicable
        [$isTeacher, $staff] = $this->resultService->resolveTeacherContext();

        if ($isTeacher) {
            $this->resultService->scopeToTeacherSubjects($query, $staff);
        }

        $testDates = $query->get()
            ->groupBy(function($item) {
                return $item->test_date->format('Y-m-d');
            })
            ->map(function($group) {
                return [
                    'date' => $group->first()->test_date,
                    'tests' => $group
                ];
            });

        return view('results.create', compact('testDates', 'preSelected'));
    }
    
    /**
     * Get test details for a specific date
     */
    public function getTestDetails(Request $request)
    {
        $request->validate([
            'test_date' => 'required|date'
        ]);

        $query = \App\Models\Datesheet::with(['subject'])
            ->whereDate('test_date', $request->test_date);

        // Restrict to teacher's subjects if applicable
        [$isTeacher, $staff] = $this->resultService->resolveTeacherContext();

        if ($isTeacher) {
            $this->resultService->scopeToTeacherSubjects($query, $staff);
        }

        $tests = $query->get()
            ->map(function($test) {
                // Get batch info
                $admission = \App\Models\Admissions::where('admission_year', $test->admission_year)
                    ->whereIn('status', ['Admitted', 'Active'])
                    ->first();
                
                // Get latest result info for this test if available (to auto-fill exam details)
                $latestResult = Result::where('subject_id', $test->subject_id)
                    ->where('academic_year', $test->admission_year)
                    ->where('class_level', $admission?->current_class ?? 'Unknown')
                    ->latest() // Get the most recent entry
                    ->first();

                // Get existing exams for this subject/year/class to prevent duplicates
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
                        'total_marks' => $latestResult->total_marks
                    ] : null
                ];
            });
        
        return response()->json(['tests' => $tests]);
    }

    /**
     * Fetch students for marks entry
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

        $year = $request->academic_year;
        $classLevel = $request->class_level;
        $subjectId = $request->subject_id;
        
        // 1. Get Students enrolled in this subject for this year
        // Logic:
        // - Compulsory subjects: All students of that class level (checking admission year logic)
        // - Elective subjects: Students who have this elective
        
        $subject = Subject::findOrFail($subjectId);

        // Resolve teacher context for scope restriction
        [$isTeacher, $staff] = $this->resultService->resolveTeacherContext();

        if ($isTeacher && !$staff) {
            return response()->json(['error' => 'Teacher record not found.'], 403);
        }
        
        $studentsQuery = Student::with(['admissions', 'father'])
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->where('admissions.admission_year', $year)
            ->select('students.*', 'admissions.college_roll_no');

        if ($isTeacher) {
            $this->resultService->scopeStudentsToTeacher($studentsQuery, $staff, $subjectId);
        }

        if ($subject->type === 'Elective') {
            $studentsQuery->whereHas('electives', function($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            });
        } else {
            // For compulsory, only take students who either:
            // 1. Have this subject explicitly assigned
            // 2. OR have NO compulsory subjects assigned at all (fallback for legacy/default)
            $studentsQuery->where(function($q) use ($subjectId, $year) {
                $q->whereHas('compulsorySubjects', function($sq) use ($subjectId) {
                    $sq->where('subject_id', $subjectId);
                })->orWhereNotExists(function($eq) use ($year) {
                    $eq->select(DB::raw(1))
                        ->from('student_compulsory_subjects')
                        ->whereColumn('student_compulsory_subjects.student_id', 'students.id');
                });
            })->whereIn('admissions.status', ['Admitted', 'Active']);
        }
        
        $students = $studentsQuery->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED)')->get();

        // 2. Check for existing marks
        $existingResults = Result::where('academic_year', $year)
            ->where('class_level', $classLevel)
            ->where('subject_id', $subjectId)
            ->where('exam_type', $request->exam_type)
            ->where('exam_name', $request->exam_name ?? $request->exam_type)
            ->get()
            ->keyBy('student_id');

        return response()->json([
            'students' => $students,
            'existing_results' => $existingResults,
            'subject' => $subject
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $customAttributes = [];
        if ($request->has('marks') && is_array($request->marks)) {
            $studentIds = array_keys($request->marks);
            $students = Student::with('admissions')
                ->whereIn('id', $studentIds)
                ->get()
                ->keyBy('id');

            foreach ($request->marks as $studentId => $mark) {
                $student = $students->get($studentId);
                $name = $student ? trim($student->first_name . ' ' . ($student->last_name ?? '')) : "Student #$studentId";
                $rollNo = ($student && $student->admissions->first()) ? $student->admissions->first()->college_roll_no : '';
                $displayName = $rollNo ? "$name (Roll: $rollNo)" : $name;

                $customAttributes["marks.$studentId.theory"] = "$displayName theory";
                $customAttributes["marks.$studentId.practical"] = "$displayName practical";
            }
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

        $students = $students ?? collect();
        DB::transaction(function () use ($request, $students) {
            foreach ($request->marks as $studentId => $mark) {
                $isAbsent = isset($mark['is_absent']) && $mark['is_absent'];
                
                // Skip if not absent and both theory and practical are empty
                if (!$isAbsent && 
                    ($mark['theory'] === null || $mark['theory'] === '') && 
                    (!isset($mark['practical']) || $mark['practical'] === null || $mark['practical'] === '')) {
                    continue;
                }

                $originalMarks = Result::where([
                    'student_id' => $studentId,
                    'subject_id' => $request->subject_id,
                    'class_level' => $request->class_level,
                    'exam_type' => $request->exam_type,
                    'exam_name' => $request->exam_name,
                    'academic_year' => $request->academic_year,
                ])->first();

                $newTheory = $isAbsent ? 0 : ($mark['theory'] !== null ? $mark['theory'] : null);
                $newPractical = $isAbsent ? 0 : (isset($mark['practical']) && $mark['practical'] !== null ? $mark['practical'] : null);

                // Detect Change
                if ($originalMarks) {
                    $theoryChanged = $originalMarks->obtained_theory != $newTheory;
                    $practicalChanged = $originalMarks->obtained_practical != $newPractical;

                    if ($theoryChanged || $practicalChanged) {
                        $student = $students->get($studentId);
                        $subject = Subject::find($request->subject_id);
                        $user = Auth::user();
                        $rollNo = ($student && $student->admissions->first()) ? $student->admissions->first()->college_roll_no : '';
                        $staffName = ($user && $user->staff) ? trim($user->staff->first_name . ' ' . ($user->staff->last_name ?? '')) : ($user->username ?? 'Staff');

                        $adminUsers = User::whereHas('roles', function($q) {
                            $q->whereIn('name', ['admin', 'principal', 'hssc_admin']);
                        })->get();

                        $details = [
                            'student_name' => trim($student->first_name . ' ' . ($student->last_name ?? '')),
                            'student_roll_no' => $rollNo,
                            'subject' => $subject->name ?? $subject->title,
                            'user_name' => $staffName,
                            'old_theory' => $originalMarks->obtained_theory ?? 'N/A',
                            'new_theory' => $newTheory ?? 'N/A',
                            'old_practical' => $originalMarks->obtained_practical ?? 'N/A',
                            'new_practical' => $newPractical ?? 'N/A',
                        ];

                        foreach ($adminUsers as $admin) {
                            $admin->notify(new ResultMarkChanged($details));
                        }
                    }
                }

                Result::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $request->subject_id,
                        'class_level' => $request->class_level,
                        'exam_type' => $request->exam_type,
                        'exam_name' => $request->exam_name,
                        'academic_year' => $request->academic_year,
                    ],
                    [
                        'total_marks' => $request->total_marks,
                        'obtained_theory' => $newTheory,
                        'obtained_practical' => $newPractical,
                        'is_absent' => $isAbsent,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]
                );
            }
        });

        return response()->json(['success' => true, 'message' => 'Results saved successfully!']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        // Similar to create but with pre-filled data
        // We can reuse the create view and logic, just passing the parameters
        return $this->create($request);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        // Reusing store method as it uses updateOrCreate
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
            $request->admission_year, 'bise_roll_12', $request->bise_roll_12
        );

        if (!$student) {
            return redirect()->route('results.final.create')
                ->with('error', 'Student not found with the given details.')
                ->withInput();
        }

        $subjects = $this->resultService->getMergedSubjects($student);

        // Fetch existing final results
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
        
        DB::transaction(function () use ($request, $student) {
            foreach ($request->marks as $subjectId => $classData) {
                $subject = Subject::find($subjectId);
                if (!$subject) continue;

                // Class 11
                if (isset($classData[11])) {
                    $data11 = $classData[11];
                    $isAbsent11 = isset($data11['is_absent']) && $data11['is_absent'];
                    $theory11 = $isAbsent11 ? 0 : ($data11['theory'] ?? null);
                    
                    if ($theory11 !== null || $isAbsent11) {
                        $this->resultService->upsertFinalResult(
                            $student->id, $subjectId, '11', $request->admission_year,
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
                        $this->resultService->upsertFinalResult(
                            $student->id, $subjectId, '12', $request->admission_year + 1,
                            ($subject->theory_12 ?? 0) + ($subject->practical_12 ?? 0),
                            $theory12, $practical12, $isAbsent12
                        );
                    }
                }
            }
        });

        return redirect()->route('results.final.create')
            ->with('success', 'Final results saved successfully!')
            ->withInput(['admission_year' => $request->admission_year]); 
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
            $request->admission_year, 'bise_roll_11', $request->bise_roll_11
        );

        if (!$student) {
            return redirect()->route('results.11th.create')
                ->with('error', 'Student not found with the given details.')
                ->withInput();
        }

        $subjects = $this->resultService->getMergedSubjects($student);

        // Fetch existing 11th final results
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
        
        DB::transaction(function () use ($request, $student) {
            foreach ($request->marks as $subjectId => $classData) {
                $subject = Subject::find($subjectId);
                if (!$subject) continue;

                // Class 11 Only
                if (isset($classData[11])) {
                    $data11 = $classData[11];
                    $theory11 = isset($data11['is_absent']) ? 0 : ($data11['theory'] ?? null);
                    
                    if ($theory11 !== null || isset($data11['is_absent'])) {
                        $this->resultService->upsertFinalResult(
                            $student->id, $subjectId, '11', $request->admission_year,
                            $subject->total_11, $theory11, null, isset($data11['is_absent'])
                        );
                    }
                }
            }
        });

        return redirect()->route('results.11th.create')
            ->with('success', '11th Class results saved successfully!')
            ->withInput(['admission_year' => $request->admission_year]); 
    }

    /**
     * View all final results for a given admission year
     */
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
            'remaining' => 0
        ];
        
        if ($request->filled('admission_year')) {
            $year = $request->admission_year;

            [$isTeacher, $staff] = $this->resultService->resolveTeacherContext();

            // 1. Calculate Stats for this Admission Year
            $totalQuery = Admissions::where('admission_year', $year);
            if ($isTeacher && $staff) {
                $totalQuery->whereHas('student.studentTeacherSubjects', function($q) use ($staff) {
                    $q->where('staff_id', $staff->id);
                });
            }
            $stats['total'] = $totalQuery->count();

            // 2. Calculate Students with Final Results
            $enteredQuery = Result::where('exam_type', 'Final')
                ->whereHas('student.admissions', function($q) use ($year) {
                    $q->where('admission_year', $year);
                });

            if ($isTeacher) {
                if ($staff) {
                    $enteredQuery->whereIn('subject_id', function($q) use ($staff) {
                        $q->select('subject_id')
                          ->from('student_teacher_subjects')
                          ->where('staff_id', $staff->id);
                    })->whereIn('student_id', function($q) use ($staff) {
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
            $type = $request->get('type', 'entered');
            
            $query = Student::with(['admissions', 'results' => function($q) {
                    $q->where('exam_type', 'Final');
                }, 'results.subject', 'father'])
                ->join('admissions', 'students.id', '=', 'admissions.student_id')
                ->where('admissions.admission_year', $year)
                ->select('students.*');

            if ($type === 'entered') {
                $query->whereHas('results', function($q) {
                    $q->where('exam_type', 'Final');
                });
            } elseif ($type === 'remaining') {
                $query->whereDoesntHave('results', function($q) {
                    $q->where('exam_type', 'Final');
                });
            }
            // type 'total' has no extra filter

            if ($isTeacher) {
                if ($staff) {
                    $query->whereHas('studentTeacherSubjects', function($q) use ($staff) {
                        $q->where('staff_id', $staff->id);
                    });
                    
                    $query->with(['results' => function($q) use ($staff) {
                        $q->where('exam_type', 'Final')
                          ->whereIn('subject_id', function($subQ) use ($staff) {
                              $subQ->select('subject_id')
                                   ->from('student_teacher_subjects')
                                   ->where('staff_id', $staff->id);
                          });
                    }]);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            // Sorting
            $sort = $request->get('sort', 'college_roll_no');
            $direction = $request->get('direction', 'asc');
            if (!in_array($direction, ['asc', 'desc'])) $direction = 'asc';

            if ($sort === 'college_roll_no') {
                $query->orderBy('admissions.college_roll_no', $direction);
            } elseif ($sort === 'bise_roll_11') {
                $query->orderBy('admissions.bise_roll_11', $direction);
            } elseif ($sort === 'bise_roll_12') {
                $query->orderBy('admissions.bise_roll_12', $direction);
            } elseif ($sort === 'grand_total') {
                $query->selectSub(function($q) {
                    $q->selectRaw('SUM(obtained_theory + COALESCE(obtained_practical, 0))')
                      ->from('results')
                      ->whereColumn('student_id', 'students.id')
                      ->where('exam_type', 'Final');
                }, 'grand_total_sum')
                ->orderBy('grand_total_sum', $direction);
            }

            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100, 500])) {
                $perPage = 20;
            }

            $students = $query->paginate($perPage)->withQueryString();
        }

        return view('results.final_view', compact('admissionYears', 'students', 'stats'));
    }
}