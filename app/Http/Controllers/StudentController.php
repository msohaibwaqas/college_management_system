<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentAttribute;
use App\Models\Father;
use App\Models\Contact;
use App\Models\Admission;
use App\Models\Admissions;
use App\Models\SscRecord;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\AdmissionApplication;
use App\Models\StudentCompulsorySubject;
use App\Models\StudentElectiveSubject;
use App\Models\BloodGroup;
use App\Models\Quota;
use App\Models\BiseRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    /**
     * List all students
     */
    public function index(Request $request)
    {
        $query = Student::has('admissions')->with([
            'admissions' => function($q) {
                $q->select('id', 'student_id', 'college_roll_no', 'bise_roll_11', 'bise_roll_12', 'admission_year', 'status', 'program_type', 'program_name', 'shift', 'semester', 'current_class');
            },
            'father' => function($q) {
                $q->select('id', 'student_id', 'name');
            },
            'electives.subject' => function($q) {
                $q->select('id', 'name', 'code');
            }
        ]);

        // Determine if we should restrict to teacher's students
        $isTeacher = false;
        $activeRole = session('active_role');
        if ($activeRole) {
            $isTeacher = ($activeRole === 'teacher');
        } else {
            $isTeacher = Auth::user()->hasRole('teacher') && 
                        !Auth::user()->hasRole('admin') && 
                        !Auth::user()->hasRole('principal') && 
                        !Auth::user()->hasRole('clerk') &&
                        !Auth::user()->is_super_admin;
        }

        // If user is a teacher, show only assigned students
        if ($isTeacher) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $query->whereHas('studentTeacherSubjects', function($q) use ($staff) {
                    $q->where('staff_id', $staff->id);
                });
            } else {
                // If no staff record, show no students
                $query->whereRaw('1 = 0');
            }
        }

        // Filter by admission year
        if ($request->filled('admission_year')) {
            $query->whereHas('admissions', function($q) use ($request) {
                $q->where('admission_year', $request->admission_year);
            });
        }

        // Hide graduated students by default
        if (!$request->filled('show_graduated') || $request->show_graduated != '1') {
            $query->whereHas('admissions', function($q) {
                $q->where('status', '!=', 'Graduated');
            });
        }

        // Search by name or roll number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereHas('admissions', function($subQ) use ($search) {
                      $subQ->where('college_roll_no', 'like', "%{$search}%")
                           ->orWhere('bise_roll_11', 'like', "%{$search}%")
                           ->orWhere('bise_roll_12', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'college_roll_no');
        $sortOrder = $request->get('sort_order', 'asc');

        try {
            if (in_array($sortBy, ['bise_roll_11', 'bise_roll_12', 'college_roll_no', 'admission_year'])) {
                // Use orderBy with a subquery to avoid join conflicts
                $query->orderByRaw("(SELECT CAST({$sortBy} AS UNSIGNED) FROM admissions WHERE admissions.student_id = students.id LIMIT 1) {$sortOrder}");
            } elseif ($sortBy === 'status') {
                $query->join('admissions', 'students.id', '=', 'admissions.student_id')
                      ->orderBy('admissions.status', $sortOrder)
                      ->select('students.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } catch (\Exception $e) {
            \Log::error('Sorting error: ' . $e->getMessage());
            // Fallback to default sorting
            $query->orderBy('id', 'asc');
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $students = $query->paginate($perPage)->appends($request->except('page'));

        // Get unique admission years for filter
        $admissionYears = DB::table('admissions')
            ->select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        return view('students.index', compact('students', 'admissionYears'));
    }

    /**
     * Show form to create a new student
     */
    public function create()
    {
        // Optimization: Cache static reference data for 1 hour
        $bloodGroups = \Illuminate\Support\Facades\Cache::remember('blood_groups', 3600, fn() => BloodGroup::all());
        $quotas = \Illuminate\Support\Facades\Cache::remember('quotas', 3600, fn() => Quota::all());
        $bises = \Illuminate\Support\Facades\Cache::remember('bise_records', 3600, fn() => BiseRecord::all());
        $subjects = \Illuminate\Support\Facades\Cache::remember('subjects_elective', 3600, fn() => Subject::where('type', 'Elective')->orderBy('name')->get());
        $subjectGroups = \Illuminate\Support\Facades\Cache::remember('subject_groups_all', 3600, fn() => SubjectGroup::with('subjects')->get());
        
        return view('students.create', compact('bloodGroups', 'quotas', 'bises', 'subjects', 'subjectGroups'));
    }

    /**
     * Store new student
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
            'first_name' => 'required|string|max:80',
            'last_name' => 'required|string|max:80',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:Male,Female,Other',
            'student_cnic' => 'nullable|string|max:15|unique:student_attributes,student_cnic',
            'blood_group_id' => 'nullable|exists:blood_groups,id',
            'quota_id' => 'nullable|exists:quotas,id',
            'subject_group_id' => 'required|exists:subject_groups,id',
            'religion' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:50',
            'domicile' => 'nullable|string|max:50',
            'father_name' => 'required|string|max:120',
            'father_cnic' => 'nullable|string|max:15',
            'father_contact' => 'nullable|string|max:20',
            'father_occupation' => 'nullable|string|max:120',
            'father_annual_income' => 'nullable|integer',
            'student_contact' => 'nullable|string|max:20',
            'whatsapp_no' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:120',
            'address' => 'nullable|string',
            'admission_year' => 'required|digits:4|integer',
            'academic_year' => 'required|digits:4|integer|gte:admission_year',
            'program_type' => 'required|in:Inter,ADP,BS,DIT',
            'program_name' => 'nullable|string|max:100', // Required if ADP/BS
            'shift' => 'nullable|in:Morning,Evening',
            'semester' => 'nullable|integer|min:1|max:8',
            'college_roll_no' => [
                'required',
                'string',
                Rule::unique('admissions', 'college_roll_no')
                    ->where(function ($query) use ($request) {
                        return $query->where('admission_year', $request->admission_year)
                                     ->where('program_type', $request->program_type)
                                     ->where('program_name', trim($request->program_name ?? 'FSc/FA'));
                    })
            ],
            'hssc_reg_no' => 'required|string|unique:admissions,hssc_reg_no',
            'current_class' => 'nullable|string', // Nullable for ADP/BS
            'ssc_records.*.bise_id' => 'required|exists:bise_records,id',
            'ssc_records.*.roll_no' => 'required|string',
            'ssc_records.*.reg_no' => 'required|string',
            'ssc_records.*.school_name' => 'required|string',
            'ssc_records.*.total_mark' => 'required|integer',
            'ssc_records.*.obtained_mark' => 'required|integer|lte:ssc_records.*.total_mark',
            'ssc_records.*.passing_year' => 'required|digits:4|integer',
            'ssc_records.*.passing_session' => 'required|in:1st Annual,2nd Annual',
            'ssc_records.*.ssc_group' => 'required|in:Science,Arts',
            'picture' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        try {
            DB::transaction(function () use ($request) {
                // Handle picture upload
                $picturePath = null;
                if ($request->hasFile('picture')) {
                    $picturePath = $request->file('picture')->store('student_pictures', 'public');
                }

                // 1. Student
                $student = Student::create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'date_of_birth' => $request->date_of_birth,
                    'gender' => $request->gender,
                    'cnic' => $request->student_cnic,
                    'picture' => $picturePath,
                    'created_by' => Auth::id(),
                ]);

                // 2. Student Attribute
                StudentAttribute::create([
                    'student_id' => $student->id,
                    'blood_group_id' => $request->blood_group_id,
                    'quota_id' => $request->quota_id,
                    'religion' => $request->religion,
                    'nationality' => $request->nationality,
                    'domicile' => $request->domicile,
                    'student_cnic' => $request->student_cnic,
                    'created_by' => Auth::id(),
                ]);

                // 3. Father
                Father::create([
                    'student_id' => $student->id,
                    'name' => $request->father_name,
                    'cnic' => $request->father_cnic,
                    'contact' => $request->father_contact,
                    'occupation' => $request->father_occupation,
                    'annual_income' => $request->father_annual_income,
                    'created_by' => Auth::id(),
                ]);

                // 4. Contact
                Contact::create([
                    'student_id' => $student->id,
                    'student_contact' => $request->student_contact,
                    'whatsapp_no' => $request->whatsapp_no,
                    'email' => $request->email,
                    'address' => $request->address,
                    'picture' => $picturePath,
                    'created_by' => Auth::id(),
                ]);

                // 5. Admission
                $admission = Admissions::create([
                    'student_id' => $student->id,
                    'admission_year' => $request->admission_year,
                    'academic_year' => $request->academic_year,
                    'college_roll_no' => $request->college_roll_no,
                    'hssc_reg_no' => $request->hssc_reg_no,
                    'current_class' => $request->current_class ?? 'N/A',
                    'program_type' => $request->program_type,
                    'program_name' => trim($request->program_name ?? 'FSc/FA'),
                    'shift' => $request->shift,
                    'semester' => $request->semester ?? 1,
                    'status' => 'Active',
                    'created_by' => Auth::id(),
                ]);

                // 6. SSC Records
                foreach ($request->ssc_records as $record) {
                    SscRecord::create([
                        'student_id' => $student->id,
                        'bise_id' => $record['bise_id'],
                        'roll_no' => $record['roll_no'],
                        'reg_no' => $record['reg_no'],
                        'school_name' => $record['school_name'],
                        'total_mark' => $record['total_mark'],
                        'obtained_mark' => $record['obtained_mark'],
                        'passing_year' => $record['passing_year'],
                        'passing_session' => $record['passing_session'],
                        'ssc_group' => $record['ssc_group'],
                        'created_by' => Auth::id(),
                    ]);
                }

                // 7. Assign compulsory subjects
                $compulsorySubjectIds = $request->compulsory_subjects;
                if (empty($compulsorySubjectIds)) {
                    $religion = $request->religion ?? 'Islam';
                    $nationality = $request->nationality ?? 'Pakistani';
                    $classLevel = $request->current_class ?? '11';
                    $ruleSubjects = \App\Models\SubjectAssignmentRule::getSubjectsForStudent($classLevel, $nationality, $religion);
                    $compulsorySubjectIds = $ruleSubjects->pluck('id')->toArray();
                }

                if (!empty($compulsorySubjectIds)) {
                    foreach ($compulsorySubjectIds as $subjectId) {
                        StudentCompulsorySubject::create([
                            'student_id' => $student->id,
                            'subject_id' => $subjectId,
                            'class_level' => $request->current_class ?? '11',
                            'created_by' => Auth::id(),
                        ]);
                    }
                }

                if ($request->elective_subjects) {
                    foreach ($request->elective_subjects as $sequence => $subjectId) {
                        StudentElectiveSubject::create([
                            'student_id' => $student->id,
                            'subject_id' => $subjectId,
                            'subject_group_id' => $request->subject_group_id,
                            'sequence' => $sequence + 1,
                            'created_by' => Auth::id(),
                        ]);
                    }
                }
            });

            return redirect()->route('students.index')->with('success', 'Student added successfully!');

        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('DB Exception in store: ' . $e->getMessage());
            // Handle database constraint violations
            if ($e->getCode() == 23000) {
                // Integrity constraint violation
                if (strpos($e->getMessage(), 'uq_ssc_roll_reg') !== false) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'The SSC Roll Number and Registration Number combination already exists in the system. Please check and try again.');
                } elseif (strpos($e->getMessage(), 'college_roll_no') !== false) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'The College Roll Number already exists for this admission year/program. Please use a different roll number.');
                } elseif (strpos($e->getMessage(), 'hssc_reg_no') !== false) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'The HSSC Registration Number already exists. Please use a different registration number.');
                } else {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'A duplicate entry was detected. Error: ' . $e->getMessage());
                }
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Database error: ' . $e->getMessage());

        } catch (\Exception $e) {
            \Log::error('General Exception in store: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'An unexpected error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Student $student)
    {
        $redirectTo = $request->get('redirect_to', url()->previous());
        // Determine if we should restrict
        $isTeacher = false;
        $activeRole = session('active_role');
        if ($activeRole) {
            $isTeacher = ($activeRole === 'teacher');
        } else {
            $isTeacher = Auth::user()->hasRole('teacher') && 
                        !Auth::user()->hasRole('admin') && 
                        !Auth::user()->hasRole('principal') && 
                        !Auth::user()->hasRole('clerk') &&
                        !Auth::user()->is_super_admin;
        }

        if ($isTeacher) {
            return redirect()->route('students.index')->with('error', 'Access denied.');
        }

        // Optimization: Cache static reference data for 1 hour
        $bloodGroups = \Illuminate\Support\Facades\Cache::remember('blood_groups', 3600, fn() => BloodGroup::all());
        $quotas = \Illuminate\Support\Facades\Cache::remember('quotas', 3600, fn() => Quota::all());
        $bises = \Illuminate\Support\Facades\Cache::remember('bise_records', 3600, fn() => BiseRecord::all());
        $subjects = \Illuminate\Support\Facades\Cache::remember('subjects_elective', 3600, fn() => Subject::where('type', 'Elective')->orderBy('name')->get());
        $subjectGroups = \Illuminate\Support\Facades\Cache::remember('subject_groups_all', 3600, fn() => SubjectGroup::with('subjects')->get());

        // Force fresh load of relationships
        $student->load([
            'contact',
            'father',
            'admissions',
            'attribute',
            'sscRecord',
            'compulsorySubjects',
        ]);
        
        // Force fresh electives (bypass cache)
        $student->setRelation('electives', StudentElectiveSubject::where('student_id', $student->id)
            ->orderBy('sequence')
            ->with('subject')
            ->get());

        // Get the current subject group ID from one of the electives
        $currentSubjectGroupId = $student->electives->first() ? $student->electives->first()->subject_group_id : null;

        $admission = $student->admissions->first();
        $collegeRollNo = $admission ? $admission->college_roll_no : '';
        $hsscRegNo = $admission ? $admission->hssc_reg_no : '';

        // Auto-split first_name if last_name is empty
        if (empty(trim($student->last_name ?? ''))) {
            $fullName = trim(preg_replace('/\s+/', ' ', $student->first_name ?? ''));
            $parts = explode(' ', $fullName);
            if (count($parts) > 1) {
                $student->last_name = array_pop($parts);
                $student->first_name = implode(' ', $parts);
            } else {
                $student->last_name = $fullName;
            }
        }

        // Auto-default domicile
        if ($student->attribute && empty(trim($student->attribute->domicile ?? ''))) {
            $student->attribute->domicile = 'Bahawalnagar';
        }

        // Auto-default student_contact
        if ($student->contact && empty(trim($student->contact->student_contact ?? ''))) {
            $student->contact->student_contact = $hsscRegNo;
        }

        // Ensure SSC always exists with sensible defaults
        $biseBahawalpur = BiseRecord::where('name', 'like', '%Bahawalpur%')->first();
        $biseId = $biseBahawalpur ? $biseBahawalpur->id : 1;

        if (!$student->sscRecord) {
            $student->sscRecord = new SscRecord([
                'bise_id' => $biseId,
                'roll_no' => $collegeRollNo,
                'reg_no' => $hsscRegNo,
                'school_name' => 'Govt. High School Chishtian',
                'total_mark' => 1200,
                'obtained_mark' => 600,
                'passing_year' => (int)date('Y'),
                'passing_session' => '1st Annual',
                'ssc_group' => 'Science',
            ]);
        } else {
            if (empty($student->sscRecord->bise_id)) $student->sscRecord->bise_id = $biseId;
            if (empty(trim($student->sscRecord->roll_no ?? ''))) $student->sscRecord->roll_no = $collegeRollNo;
            if (empty(trim($student->sscRecord->reg_no ?? ''))) $student->sscRecord->reg_no = $hsscRegNo;
            if (empty(trim($student->sscRecord->school_name ?? ''))) $student->sscRecord->school_name = 'Govt. High School Chishtian';
            if (empty($student->sscRecord->total_mark)) $student->sscRecord->total_mark = 1200;
            if (empty($student->sscRecord->obtained_mark)) $student->sscRecord->obtained_mark = 600;
            if (empty($student->sscRecord->passing_year)) $student->sscRecord->passing_year = (int)date('Y');
            if (empty($student->sscRecord->passing_session)) $student->sscRecord->passing_session = '1st Annual';
            if (empty($student->sscRecord->ssc_group)) $student->sscRecord->ssc_group = 'Science';
        }

        return view('students.edit', compact('student', 'bloodGroups', 'quotas', 'bises', 'subjects', 'subjectGroups', 'currentSubjectGroupId', 'redirectTo'));
    }


    /**
     * Update student
     */
    public function update(Request $request, Student $student)
    {
        // Determine if we should restrict
        $isTeacher = false;
        $activeRole = session('active_role');
        if ($activeRole) {
            $isTeacher = ($activeRole === 'teacher');
        } else {
            $isTeacher = Auth::user()->hasRole('teacher') && 
                        !Auth::user()->hasRole('admin') && 
                        !Auth::user()->hasRole('principal') && 
                        !Auth::user()->hasRole('clerk') &&
                        !Auth::user()->is_super_admin;
        }

        if ($isTeacher) {
            return redirect()->route('students.index')->with('error', 'Access denied.');
        }

        // Get the admission record to ignore in unique checks
        $admission = $student->admissions->first();
        $admissionId = $admission ? $admission->id : null;

        // Auto-merge defaults before validation
        $biseBahawalpur = BiseRecord::where('name', 'like', '%Bahawalpur%')->first();
        $biseId = $biseBahawalpur ? $biseBahawalpur->id : 1;

        $mergeData = [];

        // 1. Last Name & First Name
        if (empty(trim($request->last_name ?? ''))) {
            $fullName = trim(preg_replace('/\s+/', ' ', $request->first_name ?? ''));
            $parts = explode(' ', $fullName);
            if (count($parts) > 1) {
                $mergeData['last_name'] = array_pop($parts);
                $mergeData['first_name'] = implode(' ', $parts);
            } else {
                $mergeData['last_name'] = $fullName;
            }
        }

        // 2. Domicile
        if (empty(trim($request->domicile ?? ''))) {
            $mergeData['domicile'] = 'Bahawalnagar';
        }

        // 3. Student Contact
        if (empty(trim($request->student_contact ?? ''))) {
            $mergeData['student_contact'] = $request->hssc_reg_no;
        }

        // 4. SSC Record fields
        $ssc = $request->input('ssc', []);
        if (!is_array($ssc)) {
            $ssc = [];
        }
        if (empty($ssc['bise_id'])) $ssc['bise_id'] = $biseId;
        if (empty(trim($ssc['roll_no'] ?? ''))) $ssc['roll_no'] = $request->college_roll_no;
        if (empty(trim($ssc['reg_no'] ?? ''))) $ssc['reg_no'] = $request->hssc_reg_no;
        if (empty(trim($ssc['school_name'] ?? ''))) $ssc['school_name'] = 'Govt. High School Chishtian';
        if (empty($ssc['total_mark'])) $ssc['total_mark'] = 1200;
        if (empty($ssc['obtained_mark'])) $ssc['obtained_mark'] = 600;
        if (empty($ssc['passing_year'])) $ssc['passing_year'] = (int)date('Y');
        if (empty($ssc['passing_session'])) $ssc['passing_session'] = '1st Annual';
        if (empty($ssc['ssc_group'])) $ssc['ssc_group'] = 'Science';
        $mergeData['ssc'] = $ssc;

        $request->merge($mergeData);

        $request->validate([
            'first_name' => 'required|string|max:80',
            'last_name' => 'required|string|max:80',
            'date_of_birth' => 'required|date',
            'gender' => 'nullable|in:Male,Female,Other', // Gender might not be in edit form
            'student_cnic' => 'nullable|string|max:15|unique:student_attributes,student_cnic,' . ($student->attribute ? $student->attribute->id : 'NULL'),
            'blood_group_id' => 'nullable|exists:blood_groups,id',
            'quota_id' => 'nullable|exists:quotas,id',
            'subject_group_id' => 'required|exists:subject_groups,id',
            'father_name' => 'required|string|max:120',
            'father_cnic' => 'nullable|string|max:15',
            'father_contact' => 'nullable|string|max:20',
            'father_occupation' => 'nullable|string|max:120',
            'father_annual_income' => 'nullable|integer',
            'student_contact' => 'nullable|string|max:20',
            'whatsapp_no' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:120',
            'address' => 'nullable|string',
            'admission_year' => 'required|digits:4|integer',
            'academic_year' => 'required|digits:4|integer|gte:admission_year',
            'program_type' => 'required|in:Inter,ADP,BS,DIT',
            'program_name' => 'nullable|string|max:100', // Required if ADP/BS
            'shift' => 'nullable|in:Morning,Evening',
            'semester' => 'nullable|integer|min:1|max:8',
            'college_roll_no' => [
                'required',
                'string',
                Rule::unique('admissions', 'college_roll_no')
                    ->where(function ($query) use ($request) {
                        return $query->where('admission_year', $request->admission_year)
                                     ->where('program_type', $request->program_type)
                                     ->where('program_name', trim($request->program_name ?? 'FSc/FA'));
                    })
                    ->ignore($admissionId)
            ],
            'hssc_reg_no' => 'required|string|unique:admissions,hssc_reg_no,' . $admissionId,
            'current_class' => 'nullable|string',
            'status' => 'required|in:Applied,Admitted,Active,Graduated,Dropped,Suspended',
            'ssc.bise_id' => 'required|exists:bise_records,id',
            'ssc.roll_no' => 'required|string',
            'ssc.reg_no' => 'required|string',
            'ssc.school_name' => 'required|string',
            'ssc.total_mark' => 'required|integer',
            'ssc.obtained_mark' => 'required|integer|lte:ssc.total_mark',
            'ssc.passing_year' => 'required|digits:4|integer',
            'ssc.passing_session' => 'required|in:1st Annual,2nd Annual',
            'ssc.ssc_group' => 'required|in:Science,Arts',
            'elective_subjects' => 'nullable|array|size:3',
            'elective_subjects.*' => 'nullable|exists:subjects,id',
            'picture' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ]);

    try {
        DB::transaction(function () use ($request, $student) {
            // Handle picture upload
            $picturePath = null;
            if ($request->hasFile('picture')) {
                // Delete old picture if exists
                if ($student->picture && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->picture)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($student->picture);
                }
                
                $picturePath = $request->file('picture')->store('student_pictures', 'public');
            }

            // 1. Update Student
            $studentData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'date_of_birth' => $request->date_of_birth,
                'cnic' => $request->student_cnic,
            ];
            
            // Only update gender if it's provided (since it's not in the edit form)
            if ($request->filled('gender')) {
                $studentData['gender'] = $request->gender;
            }
            
            $student->update($studentData);
            
            if ($picturePath) {
                $student->update(['picture' => $picturePath]);
            }

            // 2. Update Student Attribute
            $studentAttribute = $student->attribute;
            if ($studentAttribute) {
                $studentAttribute->update([
                    'blood_group_id' => $request->blood_group_id,
                    'quota_id' => $request->quota_id,
                    'religion' => $request->religion,
                    'nationality' => $request->nationality,
                    'domicile' => $request->domicile,
                    'student_cnic' => $request->student_cnic,
                ]);
            } else {
                $student->attribute()->create([
                    'blood_group_id' => $request->blood_group_id,
                    'quota_id' => $request->quota_id,
                    'religion' => $request->religion,
                    'nationality' => $request->nationality,
                    'domicile' => $request->domicile,
                    'student_cnic' => $request->student_cnic,
                ]);
            }

            // 3. Update Father
            $father = $student->father;
            if ($father) {
                $father->update([
                    'name' => $request->father_name,
                    'cnic' => $request->father_cnic,
                    'contact' => $request->father_contact,
                    'occupation' => $request->father_occupation,
                    'annual_income' => $request->father_annual_income,
                ]);
            } else {
                $student->father()->create([
                    'name' => $request->father_name,
                    'cnic' => $request->father_cnic,
                    'contact' => $request->father_contact,
                    'occupation' => $request->father_occupation,
                    'annual_income' => $request->father_annual_income,
                ]);
            }

            // 4. Update Contact
            $contactData = [
                'student_contact' => $request->student_contact,
                'whatsapp_no' => $request->whatsapp_no,
                'email' => $request->email,
                'address' => $request->address,
            ];

            if ($picturePath) {
                $contactData['picture'] = $picturePath;
            }

            $contact = $student->contact;
            if ($contact) {
                $contact->update($contactData);
            } else {
                $student->contact()->create($contactData);
            }

            // 5. Update Admission
            $admission = $student->admissions->first();
            if ($admission) {
                $admission->update([
                    'admission_year' => $request->admission_year,
                    'academic_year' => $request->academic_year,
                    'college_roll_no' => $request->college_roll_no,
                    'hssc_reg_no' => $request->hssc_reg_no,
                    'current_class' => $request->current_class,
                    'program_type' => $request->program_type,
                    'program_name' => trim($request->program_name ?? 'FSc/FA'),
                    'shift' => $request->shift,
                    'semester' => 1,
                    'status' => $request->status,
                ]);
            }

            // 6. Update SSC Record (single) - Use updateOrCreate to avoid duplicates
            SscRecord::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'bise_id' => $request->ssc['bise_id'],
                    'roll_no' => $request->ssc['roll_no'],
                    'reg_no' => $request->ssc['reg_no'],
                    'school_name' => $request->ssc['school_name'],
                    'total_mark' => $request->ssc['total_mark'],
                    'obtained_mark' => $request->ssc['obtained_mark'],
                    'passing_year' => $request->ssc['passing_year'],
                    'passing_session' => $request->ssc['passing_session'],
                    'ssc_group' => $request->ssc['ssc_group'],
                ]
            );

            // 7. Update Elective Subjects (ONLY if provided in request)
            if ($request->filled('elective_subjects')) {
                // Get new elective IDs
                $newElectiveIds = collect($request->elective_subjects)->filter()->values();
                
                // Get current compulsory subject IDs
                $compulsoryIds = $student->compulsorySubjects()->pluck('subject_id');
                
                // All valid subject IDs for this student
                $validSubjectIds = $compulsoryIds->merge($newElectiveIds)->unique();
                
                // Delete StudentTeacherSubject records for subjects that are no longer assigned
                $unassignedModels = \App\Models\StudentTeacherSubject::where('student_id', $student->id)
                    ->whereNotIn('subject_id', $validSubjectIds)
                    ->get();
                
                foreach ($unassignedModels as $unassignedModel) {
                    $unassignedModel->delete();
                }

                // Clean up section mappings for dropped subjects so data does not become orphaned
                \App\Models\StudentSubjectSection::where('student_id', $student->id)
                    ->whereNotIn('subject_id', $validSubjectIds)
                    ->delete();

                // Delete existing electives (including soft deleted ones)
                StudentElectiveSubject::where('student_id', $student->id)->forceDelete();

                // Log for debugging
                \Log::info('Updating electives for student ' . $student->id, [
                    'elective_subjects' => $request->elective_subjects
                ]);

                // Insert updated electives
                foreach ($request->elective_subjects as $sequence => $subjectId) {
                    if (!empty($subjectId)) {
                        StudentElectiveSubject::create([
                            'student_id' => $student->id,
                            'subject_id' => $subjectId,
                            'subject_group_id' => $request->subject_group_id,
                            'sequence' => $sequence + 1,
                            'created_by' => Auth::id(),
                        ]);
                    }
                }
            }

            // 8. Update / Sync Compulsory Subjects
            $compulsorySubjectIds = $request->compulsory_subjects;
            if (empty($compulsorySubjectIds)) {
                $religion = $request->religion ?? optional($student->attribute)->religion ?? 'Islam';
                $nationality = $request->nationality ?? optional($student->attribute)->nationality ?? 'Pakistani';
                $classLevel = $request->current_class ?? optional($student->admissions->first())->current_class ?? '11';
                $ruleSubjects = \App\Models\SubjectAssignmentRule::getSubjectsForStudent($classLevel, $nationality, $religion);
                $compulsorySubjectIds = $ruleSubjects->pluck('id')->toArray();
            }

            if (!empty($compulsorySubjectIds)) {
                StudentCompulsorySubject::where('student_id', $student->id)->forceDelete();
                foreach ($compulsorySubjectIds as $subjectId) {
                    StudentCompulsorySubject::create([
                        'student_id' => $student->id,
                        'subject_id' => $subjectId,
                        'class_level' => $request->current_class ?? optional($student->admissions->first())->current_class ?? '11',
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            // Clean up teacher assignments and section mappings for subjects no longer assigned
            $validSubjectIds = $student->compulsorySubjects()->pluck('subject_id')
                ->merge($student->electives()->pluck('subject_id'))
                ->unique();

            \App\Models\StudentTeacherSubject::where('student_id', $student->id)
                ->whereNotIn('subject_id', $validSubjectIds)
                ->delete();

            \App\Models\StudentSubjectSection::where('student_id', $student->id)
                ->whereNotIn('subject_id', $validSubjectIds)
                ->delete();
        });

        $redirectTo = $request->input('redirect_to');
        if (!empty($redirectTo) && (str_contains($redirectTo, '/students') || str_contains($redirectTo, 'students'))) {
            return redirect($redirectTo)->with('success', 'Student updated successfully!');
        }

        return redirect()->route('students.index')->with('success', 'Student updated successfully!');
        
    } catch (\Illuminate\Database\QueryException $e) {
        // Handle database constraint violations
        if ($e->getCode() == 23000) {
            // Integrity constraint violation
            if (strpos($e->getMessage(), 'uq_ssc_roll_reg') !== false) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'The SSC Roll Number and Registration Number combination already exists in the system. Please check and try again.');
            } elseif (strpos($e->getMessage(), 'college_roll_no') !== false) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'The College Roll Number already exists for this admission year. Please use a different roll number.');
            } elseif (strpos($e->getMessage(), 'hssc_reg_no') !== false) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'The HSSC Registration Number already exists. Please use a different registration number.');
            } else {
                // Log the full error for debugging
                \Log::error('Duplicate entry error: ' . $e->getMessage());
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'A duplicate entry was detected. Error: ' . $e->getMessage() . ' Please check your data and try again.');
            }
        }
        
        // Log the error for debugging
        \Log::error('Student update failed: ' . $e->getMessage());
        
        return redirect()->back()
            ->withInput()
            ->with('error', 'An error occurred while updating the student. Please try again or contact support if the problem persists.');
            
    } catch (\Exception $e) {
        // Handle general exceptions
        \Log::error('Student update failed: ' . $e->getMessage());
        
        return redirect()->back()
            ->withInput()
            ->with('error', 'An unexpected error occurred. Please try again or contact support if the problem persists.');
    }
}

    /**
     * Delete student (Soft Delete)
     */
    public function destroy(Student $student)
    {
        $student->delete();
        return redirect()->route('students.index')->with('success', 'Student moved to trash successfully!');
    }

    /**
     * View deleted students (Trash)
     */
    public function trash(Request $request)
    {
        $query = Student::onlyTrashed()
            ->with(['admissions', 'father'])
            ->orderBy('deleted_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereHas('admissions', function($subQ) use ($search) {
                      $subQ->where('college_roll_no', 'like', "%{$search}%");
                  });
            });
        }

        $students = $query->paginate(50)->appends($request->except('page'));
            
        return view('students.trash', compact('students'));
    }

    /**
     * Restore deleted student
     */
    public function restore($id)
    {
        $student = Student::onlyTrashed()->findOrFail($id);
        $student->restore();
        
        return redirect()->route('students.trash')->with('success', 'Student restored successfully!');
    }

    /**
     * Permanently delete student
     */
    public function forceDelete($id)
    {
        $student = Student::onlyTrashed()->findOrFail($id);
        
        $student->forceDelete();
        
        return redirect()->route('students.trash')->with('success', 'Student permanently deleted!');
    }

    /**
     * Empty all students from trash
     */
    public function emptyTrash()
    {
        if (!Auth::user()->hasRole('principal') && !Auth::user()->is_super_admin) {
            return redirect()->route('students.trash')->with('error', 'Unauthorized access.');
        }

        Student::onlyTrashed()->forceDelete();

        return redirect()->route('students.trash')->with('success', 'Recycle bin empty successfully!');
    }

    /**
     * Bulk delete students
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id'
        ]);

        try {
            DB::beginTransaction();

            $studentIds = $request->student_ids;
            
            // Optimization: Use single query for bulk delete instead of looping
            $deletedCount = Student::whereIn('id', $studentIds)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} student(s).",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update student status
     */
    public function bulkStatusUpdate(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
            'status' => 'required|in:Applied,Admitted,Active,Graduated,Dropped,Suspended'
        ]);

        try {
            DB::beginTransaction();

            $studentIds = $request->student_ids;
            $status = $request->status;
            $updatedCount = 0;

            // Update admissions status for selected students
            // We assume we are updating their *active* or most recent admission if they have multiple, 
            // but typically a student has one main admission record we care about for status.
            // A safer approach is to update all non-graduated admissions or just the latest one.
            // Given the context, we'll update the admission record associated with the current year or just all for simplicity 
            // as usually students have one active admission track.
            
            // Better approach: Update the admission records where student_id is in the list.
            $updatedCount = Admissions::whereIn('student_id', $studentIds)
                ->update(['status' => $status]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully updated status for {$updatedCount} admission record(s).",
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk promote students from Class 11 to Class 12
     */
    public function bulkPromote(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
        ]);

        try {
            DB::beginTransaction();

            $studentIds = $request->student_ids;
            $promotedCount = 0;

            // Find Class 11 admissions for these students
            $admissions = Admissions::whereIn('student_id', $studentIds)
                ->where('current_class', '11')
                ->whereIn('status', ['Admitted', 'Active'])
                ->get();

            foreach ($admissions as $admission) {
                // Update admission record
                $admission->update([
                    'current_class' => '12',
                    'academic_year' => $admission->academic_year + 1
                ]);

                // Update subject levels
                DB::table('student_compulsory_subjects')
                    ->where('student_id', $admission->student_id)
                    ->where('class_level', '11')
                    ->update(['class_level' => '12']);

                $promotedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully promoted {$promotedCount} student(s) to Class 12.",
                'promoted_count' => $promotedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while promoting students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export students to CSV
     */
    public function export(Request $request)
    {
        $query = Student::with([
            'admissions',
            'father',
            'electives.subject'
        ]);

        // Apply same filters as index
        if ($request->filled('admission_year')) {
            $query->whereHas('admissions', function($q) use ($request) {
                $q->where('admission_year', $request->admission_year);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereHas('admissions', function($subQ) use ($search) {
                      $subQ->where('college_roll_no', 'like', "%{$search}%")
                           ->orWhere('bise_roll_11', 'like', "%{$search}%")
                           ->orWhere('bise_roll_12', 'like', "%{$search}%");
                  });
            });
        }

        $students = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_export_' . date('Y-m-d_His') . '.csv"',
        ];

        $callback = function() use ($students) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Student Name',
                'Father Name',
                'College Roll No',
                'BISE Roll 11',
                'BISE Roll 12',
                'Admission Year',
                'Elective 1',
                'Elective 2',
                'Elective 3'
            ]);

            foreach ($students as $student) {
                $admission = $student->admissions->first();
                $electives = $student->electives->sortBy('sequence');
                
                fputcsv($file, [
                    $student->first_name . ' ' . $student->last_name,
                    $student->father ? $student->father->name : '',
                    $admission ? $admission->college_roll_no : '',
                    $admission ? $admission->bise_roll_11 : '',
                    $admission ? $admission->bise_roll_12 : '',
                    $admission ? $admission->admission_year : '',
                    $electives->get(0) ? $electives->get(0)->subject->name : '',
                    $electives->get(1) ? $electives->get(1)->subject->name : '',
                    $electives->get(2) ? $electives->get(2)->subject->name : '',
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * View single student details
     */
    public function show(Student $student)
    {
        $student->load([
            'admissions',
            'father',
            'contact',
            'attribute.bloodGroup',
            'attribute.quota',
            'sscRecord.bise',
            'compulsorySubjects.subject',
            'electives.subject',
            'studentTeacherSubjects.staff',
            'studentTeacherSubjects.subject',
            'results.subject'
        ]);

        return view('students.show', compact('student'));
    }

    /**
     * Students by subject - filter students by selected subject
     */
    public function studentsBySubject(Request $request)
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        $selectedYear = $request->input('admission_year');
        $selectedSubject = $request->input('subject_id');

        // Get subjects for selected year
        $subjects = collect();
        if ($selectedYear) {
            $subjects = Subject::where('type', 'Elective')
                ->whereHas('studentElectives.student.admissions', function($q) use ($selectedYear) {
                    $q->where('admission_year', $selectedYear);
                })
                ->orderBy('name', 'ASC')
                ->get();
        }

        // Get students for selected year and subject
        $students = collect();
        $subjectName = '';
        
        if ($selectedYear && $selectedSubject) {
            $students = Student::with(['admissions', 'father', 'attribute'])
                ->whereHas('admissions', function($q) use ($selectedYear) {
                    $q->where('admission_year', $selectedYear);
                })
                ->whereHas('electives', function($q) use ($selectedSubject) {
                    $q->where('subject_id', $selectedSubject);
                })
                ->get();

            $subject = Subject::find($selectedSubject);
            $subjectName = $subject ? $subject->name : '';
        }

        return view('students.by_subject', compact('admissionYears', 'subjects', 'selectedYear', 'selectedSubject', 'students', 'subjectName'));
    }

    /**
     * Group students by their subject combinations
     */
    public function groupStudents(Request $request)
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        $selectedYear = $request->input('admission_year');
        $groups = [];

        if ($selectedYear) {
            $students = Student::with(['electives.subject', 'admissions', 'father'])
                ->whereHas('admissions', function($q) use ($selectedYear) {
                    $q->where('admission_year', $selectedYear);
                })
                ->get();

            $classifyGroup = function($subjects) {
                $subs = array_map('strtolower', $subjects);
                $has = fn($sub) => in_array(true, array_map(fn($s) => strpos($s, strtolower($sub)) !== false, $subs));

                if ($has('biology') && $has('physics') && $has('chemistry')) return 'Pre-Medical';
                if ($has('math') && $has('physics') && $has('chemistry')) return 'Pre-Engineering';
                if ($has('math') && $has('computer')) return 'ICS';
                if ($has('computer')) return 'FA-IT';
                return 'FA';
            };

            foreach ($students as $student) {
                $subjectNames = $student->electives->pluck('subject.name')->toArray();
                if (count($subjectNames) > 0) {
                    $group = $classifyGroup($subjectNames);
                    $admission = $student->admissions->first();

                    $groups[$group][] = [
                        'roll_no' => $admission ? $admission->college_roll_no : 'N/A',
                        'name' => $student->first_name . ' ' . $student->last_name,
                        'father_name' => $student->father ? $student->father->name : 'N/A',
                        'subjects' => implode(', ', $subjectNames)
                    ];
                }
            }
        }

        return view('students.groups', compact('admissionYears', 'selectedYear', 'groups'));
    }

    /**
     * Export grouped students as CSV
     */
    public function exportGroupsCSV($year)
    {
        $students = Student::with(['electives.subject', 'admissions', 'father'])
            ->whereHas('admissions', function($q) use ($year) {
                $q->where('admission_year', $year);
            })
            ->get();

        $classifyGroup = function($subjects) {
            $subs = array_map('strtolower', $subjects);
            $has = fn($sub) => in_array(true, array_map(fn($s) => strpos($s, strtolower($sub)) !== false, $subs));

            if ($has('biology') && $has('physics') && $has('chemistry')) return 'Pre-Medical';
            if ($has('math') && $has('physics') && $has('chemistry')) return 'Pre-Engineering';
            if ($has('math') && $has('computer')) return 'ICS';
            if ($has('computer')) return 'FA-IT';
            return 'FA';
        };

        $filename = "grouped_students_{$year}.csv";
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=$filename"];

        $callback = function() use ($students, $classifyGroup) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Group', 'Roll No', 'Name', 'Father Name', 'Subjects']);
            
            foreach ($students as $student) {
                $subjectNames = $student->electives->pluck('subject.name')->toArray();
                $group = count($subjectNames) > 0 ? $classifyGroup($subjectNames) : 'Unknown';
                $admission = $student->admissions->first();
                
                fputcsv($output, [
                    $group,
                    $admission ? $admission->college_roll_no : 'N/A',
                    $student->first_name . ' ' . $student->last_name,
                    $student->father ? $student->father->name : 'N/A',
                    implode(', ', $subjectNames)
                ]);
            }
            
            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show form to change subjects for an admitted student
     */
    public function changeSubjects(Student $student)
    {
        // 1. Check for Class 12 (including graduates/dropped out)
        $latestAdmission = $student->admissions()->latest()->first();
        if ($latestAdmission && $latestAdmission->current_class === '12') {
            return redirect()->route('students.show', $student)->with('error', 'Elective subjects cannot be changed for Class 12 students.');
        }

        // 2. Check for pending Subject Change fee vouchers
        $hasPendingVoucher = $student->vouchers()
            ->where('status', 'pending')
            ->where('remarks', 'Subject Change Fee Difference')
            ->exists();
            
        if ($hasPendingVoucher) {
            return redirect()->route('students.show', $student)->with('error', 'You cannot change subjects because there is already a pending Subject Change fee voucher that must be paid first.');
        }

        $student->load(['electives.subject', 'admissions']);
        $subjects = Subject::where('type', 'Elective')->orderBy('name')->get();
        $subjectGroups = SubjectGroup::with('subjects')->get();
        
        // Find current group ID if all subjects belong to it
        $currentGroupIds = $student->electives->pluck('subject_group_id')->filter()->unique();
        $groupId = $currentGroupIds->count() === 1 ? $currentGroupIds->first() : null;

        // Fallback: If no subject_group_id on electives, try to find a group that matches these subjects
        if (!$groupId && $student->electives->count() === 3) {
            $studentSubjectIds = $student->electives->pluck('subject_id')->sort()->values()->toArray();
            foreach ($subjectGroups as $group) {
                $groupSubjectIds = $group->subjects->pluck('id')->sort()->values()->toArray();
                // Check if all student subjects are in this group
                if (count(array_intersect($studentSubjectIds, $groupSubjectIds)) === 3) {
                    $groupId = $group->id;
                    break;
                }
            }
        }
        
        return view('students.change_subjects', compact('student', 'subjects', 'subjectGroups', 'groupId'));
    }

    /**
     * Update subjects and handle fee difference
     */
    public function updateSubjects(Request $request, Student $student)
    {
        // 1. Check for Class 12 (including graduates/dropped out)
        $latestAdmission = $student->admissions()->latest()->first();
        if ($latestAdmission && $latestAdmission->current_class === '12') {
            return redirect()->route('students.show', $student)->with('error', 'Elective subjects cannot be changed for Class 12 students.');
        }

        // 2. Check for pending Subject Change fee vouchers
        $hasPendingVoucher = $student->vouchers()
            ->where('status', 'pending')
            ->where('remarks', 'Subject Change Fee Difference')
            ->exists();
            
        if ($hasPendingVoucher) {
            return redirect()->route('students.show', $student)->with('error', 'You cannot change subjects because there is already a pending Subject Change fee voucher that must be paid first.');
        }

        $request->validate([
            'elective_subjects' => 'required|array|size:3',
            'elective_subjects.*' => 'required|exists:subjects,id',
        ]);

        try {
            $feeService = new \App\Services\FeeCalculationService();
            $result = $feeService->calculateDifference($student, $request->elective_subjects);

            $difference = $result['difference'];

            if ($difference > 0) {
                // Generate a difference voucher
                $voucher = $feeService->createVoucher($student, $result['extra_items']);
                
                $this->performSubjectUpdate($student, $request->elective_subjects);

                return redirect()->route('students.show', $student)
                    ->with('success', "Subjects updated successfully. A new voucher ({$voucher->voucher_number}) for the fee difference of {$difference} has been generated.")
                    ->with('voucher_id', $voucher->id);
            } else {
                // No difference or negative (we don't refund automatically here)
                $this->performSubjectUpdate($student, $request->elective_subjects);

                return redirect()->route('students.show', $student)
                    ->with('success', 'Subjects updated successfully. No additional fees required.');
            }
        } catch (\Exception $e) {
            \Log::error('Subject change failed: ' . $e->getMessage());
            return back()->with('error', 'Subject change failed: ' . $e->getMessage());
        }
    }

    /**
     * Helper to perform subject update logic
     */
    private function performSubjectUpdate(Student $student, array $subjectIds)
    {
        \DB::transaction(function () use ($student, $subjectIds) {
            // Delete existing electives
            \App\Models\StudentElectiveSubject::where('student_id', $student->id)->forceDelete();

            // Insert new ones
            foreach ($subjectIds as $sequence => $subjectId) {
                \App\Models\StudentElectiveSubject::create([
                    'student_id' => $student->id,
                    'subject_id' => $subjectId,
                    'sequence' => $sequence + 1,
                    'created_by' => \Auth::id(),
                ]);
            }

            // Sync with Teacher Assignment if needed
            $validSubjectIds = $student->compulsorySubjects()->pluck('subject_id')->merge($subjectIds)->unique();
            \App\Models\StudentTeacherSubject::where('student_id', $student->id)
                ->whereNotIn('subject_id', $validSubjectIds)
                ->delete();
        });
    }


    /**
     * Download or print a fee voucher
     */
    public function downloadVoucher(\App\Models\FeeVoucher $voucher)
    {
        $voucher->load(['items', 'student.admissions', 'student.heAdmissions', 'application']);
        $settings = \App\Models\InstituteSetting::getSettings();
        
        return view('pdfs.fee-voucher', compact('voucher', 'settings'));
    }


    /**
     * Confirm payment for a student fee voucher
     */
    public function confirmVoucherPayment(Request $request, \App\Models\FeeVoucher $voucher)
    {
        $request->validate([
            'paid_amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
        ]);

        try {
            $voucher->update([
                'paid_amount' => $request->paid_amount,
                'payment_date' => $request->payment_date,
                'status' => 'paid',
                'confirmed_by' => \Auth::id(),
            ]);

            return back()->with('success', 'Payment for voucher ' . $voucher->voucher_number . ' confirmed successfully.');
        } catch (\Exception $e) {
            \Log::error('Voucher payment confirmation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to confirm payment: ' . $e->getMessage());
        }
    }
}
