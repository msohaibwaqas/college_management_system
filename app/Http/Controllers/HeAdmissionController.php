<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HeAdmission;
use App\Models\Student;
use App\Models\Program;
use App\Models\BloodGroup;
use App\Models\Quota;
use App\Models\BiseRecord;
use App\Models\Father;
use App\Models\Contact;
use App\Models\SscRecord;
use App\Models\HsscRecord;
use App\Models\HeStudentSubject;
use App\Models\StudentAttribute;
use App\Models\Admissions;
use App\Models\AdmissionSetting;
use App\Services\FeeCalculationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class HeAdmissionController extends Controller
{
    protected $feeService;

    public function __construct(FeeCalculationService $feeService)
    {
        $this->feeService = $feeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Build Query for HE Admissions
        $query = HeAdmission::with(['student.father', 'student.contact', 'program']);

        // Search (Name, Roll No)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('roll_no', 'like', "%{$search}%")
                  ->orWhereHas('student', function($subQ) use ($search) {
                      $subQ->where('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%")
                           ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                  });
            });
        }

        // Filter by Program
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        // Filter by Admission Year
        if ($request->filled('admission_year')) {
            $query->where('admission_year', $request->admission_year);
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by Semester
        if ($request->filled('semester')) {
            $query->where('current_semester', $request->semester);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'roll_no');
        $sortOrder = $request->get('sort_order', 'asc');

        if ($sortBy === 'name') {
             $query->leftJoin('students', 'he_admissions.student_id', '=', 'students.id')
                  ->orderBy('students.first_name', $sortOrder)
                  ->select('he_admissions.*');
        } elseif (in_array($sortBy, ['roll_no', 'admission_year', 'status', 'current_semester'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $heAdmissions = $query->paginate($perPage)->appends($request->all());

        $legacyQuery = Admissions::whereIn('program_type', ['BS', 'ADP', 'DIT'])
            ->with(['student.father', 'student.contact']);
            
        if ($request->filled('search')) {
             $search = $request->search;
             $legacyQuery->where(function($q) use ($search) {
                $q->where('college_roll_no', 'like', "%{$search}%")
                  ->orWhereHas('student', function($subQ) use ($search) {
                      $subQ->where('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }
        
        $legacyAdmissions = $legacyQuery->limit(20)->get();

        // Get filter options
        $programs = Program::whereIn('type', ['BS', 'ADP', 'DIT'])->get();
        $admissionYears = HeAdmission::select('admission_year')->distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');

        return view('he.admissions.index', compact('heAdmissions', 'legacyAdmissions', 'programs', 'admissionYears'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $bloodGroups = BloodGroup::all();
        $quotas = Quota::all();
        $bises = BiseRecord::all();
        $programs = Program::whereIn('type', ['BS', 'ADP', 'DIT'])->get();
        return view('he.admissions.create', compact('bloodGroups', 'quotas', 'bises', 'programs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Personal
            'first_name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z\s]+$/'],
            'last_name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z\s]+$/'],
            'gender' => 'required|in:Male,Female,Other',
            'date_of_birth' => 'required|date',
            'student_cnic' => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'blood_group_id' => 'nullable|exists:blood_groups,id',
            'quota_id' => 'nullable|exists:quotas,id',
            'picture' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'religion' => 'required|string|max:50',
            'nationality' => 'required|string|max:50',
            
            // Father
            'father_name' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z\s]+$/'],
            'father_cnic' => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'father_contact' => ['nullable', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'father_occupation' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-Z\s]*$/'],
            'father_annual_income' => 'nullable|integer',
            
            // Contact
            'student_contact' => ['required', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'whatsapp_no' => ['nullable', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'email' => 'nullable|email:filter|max:120',
            'address' => 'required|string',
            
            // HE Admission
            'program_id' => 'required|exists:programs,id',
            // Skipped fields in form: current_semester, admission_year, roll_no, status
            
            // SSC Record
            'ssc_bise_id' => 'required|exists:bise_records,id',
            'ssc_roll_no' => 'required|numeric|min:0',
            'ssc_reg_no' => [
                'required', 'string', 'max:50',
                function ($attribute, $value, $fail) use ($request) {
                    $rollNo = $request->input('ssc_roll_no');
                    if (!$rollNo) return;
                    $existsInStudents = \App\Models\SscRecord::where('roll_no', $rollNo)->where('reg_no', $value)->exists();
                    if ($existsInStudents) { $fail('This student (SSC Roll/Reg) is already admitted in the system.'); }
                }
            ],
            'ssc_school_name' => 'required|string|max:255',
            'ssc_total_mark' => 'required|numeric|min:1',
            'ssc_obtained_mark' => 'required|numeric|min:0|lte:ssc_total_mark',
            'ssc_passing_year' => 'required|integer|min:1900|max:2099',
            'ssc_passing_session' => 'required|string',
            'ssc_group' => 'required|string',
            
            // HSSC Record
            'hssc_bise_id' => 'required|exists:bise_records,id',
            'hssc_roll_no' => 'required|numeric|min:0',
            'hssc_reg_no_academic' => 'required|string|max:50',
            'hssc_college_name' => 'required|string|max:255',
            'hssc_total_mark' => 'required|numeric|min:1',
            'hssc_obtained_mark' => 'required|numeric|min:0|lte:hssc_total_mark',
            'hssc_passing_year' => 'required|integer|min:1900|max:2099',
            'hssc_passing_session' => 'required|string',
            'hssc_group' => 'required|string',
        ]);

        try {
            return DB::transaction(function () use ($request, $validated) {
                $picturePath = null;
                if ($request->hasFile('picture')) {
                    $picturePath = $request->file('picture')->store('student_pictures', 'public');
                }

                // 1. Create or Update Student
                $studentData = [
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'date_of_birth' => $validated['date_of_birth'],
                    'gender' => $validated['gender'],
                    'religion' => $validated['religion'],
                    'nationality' => $validated['nationality'],
                    'cnic' => $validated['student_cnic'] ?? null,
                ];

                if ($picturePath) {
                    $studentData['picture'] = $picturePath;
                }

                $student = Student::updateOrCreate(
                    ['cnic' => $validated['student_cnic'] ?? 'NONE-' . uniqid()], // Fallback if no CNIC
                    array_merge($studentData, ['updated_by' => Auth::id()])
                );
                
                if ($student->wasRecentlyCreated) {
                    $student->created_by = Auth::id();
                    $student->save();
                }

                // 2. Student Attributes
                StudentAttribute::updateOrCreate(
                    ['student_id' => $student->id],
                    [
                        'blood_group_id' => $validated['blood_group_id'],
                        'quota_id' => $validated['quota_id'],
                        'student_cnic' => $validated['student_cnic'],
                        'updated_by' => Auth::id(),
                        'created_by' => $student->wasRecentlyCreated ? Auth::id() : null,
                    ]
                );

                // 3. Father Info
                Father::updateOrCreate(
                    ['student_id' => $student->id],
                    [
                        'name' => $validated['father_name'],
                        'cnic' => $validated['father_cnic'],
                        'contact' => $validated['father_contact'],
                        'occupation' => $validated['father_occupation'],
                        'annual_income' => $validated['father_annual_income'],
                        'updated_by' => Auth::id(),
                        'created_by' => $student->wasRecentlyCreated ? Auth::id() : null,
                    ]
                );

                // 4. Contact
                Contact::updateOrCreate(
                    ['student_id' => $student->id],
                    [
                        'student_contact' => $validated['student_contact'],
                        'whatsapp_no' => $validated['whatsapp_no'],
                        'email' => $validated['email'],
                        'address' => $validated['address'],
                        'picture' => $picturePath ?? $student->picture,
                        'updated_by' => Auth::id(),
                        'created_by' => $student->wasRecentlyCreated ? Auth::id() : null,
                    ]
                );

                // 5. SSC Record
                SscRecord::updateOrCreate(
                    ['student_id' => $student->id],
                    [
                        'bise_id' => $validated['ssc_bise_id'],
                        'roll_no' => $validated['ssc_roll_no'],
                        'reg_no' => $validated['ssc_reg_no'],
                        'school_name' => $validated['ssc_school_name'],
                        'total_mark' => $validated['ssc_total_mark'],
                        'obtained_mark' => $validated['ssc_obtained_mark'],
                        'passing_year' => $validated['ssc_passing_year'],
                        'passing_session' => $validated['ssc_passing_session'],
                        'ssc_group' => $validated['ssc_group'],
                        'updated_by' => Auth::id(),
                        'created_by' => $student->wasRecentlyCreated ? Auth::id() : null,
                    ]
                );

                // 6. HSSC Record
                HsscRecord::updateOrCreate(
                    ['student_id' => $student->id],
                    [
                        'bise_id' => $validated['hssc_bise_id'],
                        'roll_no' => $validated['hssc_roll_no'],
                        'reg_no' => $validated['hssc_reg_no_academic'],
                        'college_name' => $validated['hssc_college_name'],
                        'total_mark' => $validated['hssc_total_mark'],
                        'obtained_mark' => $validated['hssc_obtained_mark'],
                        'passing_year' => $validated['hssc_passing_year'],
                        'passing_session' => $validated['hssc_passing_session'],
                        'hssc_group' => $validated['hssc_group'],
                        'updated_by' => Auth::id(),
                        'created_by' => $student->wasRecentlyCreated ? Auth::id() : null,
                    ]
                );

                // 7. HE Admission
                $program = Program::find($validated['program_id']);
                $settings = AdmissionSetting::getForProgram($program->type);
                
                $heAdmission = HeAdmission::create([
                    'student_id' => $student->id,
                    'program_id' => $validated['program_id'],
                    'roll_no' => null, // Will be assigned later
                    'admission_year' => $settings->admission_year ?? date('Y'),
                    'current_semester' => 1,
                    'status' => 'Admitted',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                // 8. Generate Fee Voucher
                $voucherItems = $this->feeService->calculateVoucherItemsForHE($heAdmission);
                $this->feeService->createVoucher($student, $voucherItems);

                return redirect()->route('admissions.index')->with('success', 'HE Admission completed successfully. Fee voucher has been generated.');
            });

        } catch (\Exception $e) {
            \Log::error('HE Admission creation failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\HeAdmission $admission)
    {
        $admission->load(['student', 'program', 'subjects.bsSubject']);
        return view('he.admissions.show', compact('admission'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(\App\Models\HeAdmission $admission)
    {
        $students = \App\Models\Student::whereHas('heAdmissions')
            ->orWhereHas('admissions', function($q) {
                $q->whereIn('program_type', ['BS', 'ADP', 'DIT']);
            })
            ->orWhere('id', $admission->student_id)
            ->get();

        $programs = \App\Models\Program::whereIn('type', ['BS', 'ADP', 'DIT'])->get();
        return view('he.admissions.edit', compact('admission', 'students', 'programs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\HeAdmission $admission)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'program_id' => 'required|exists:programs,id',
            'roll_no' => 'nullable|string|max:50',
            'admission_year' => 'required|integer|min:2000|max:2099',
            'current_semester' => 'required|integer|min:1|max:10',
            'status' => 'required|in:Applied,Admitted,Active,Graduated,Dropped,Suspended',
        ]);

        $validated['updated_by'] = auth()->id();
        $admission->update($validated);

        return redirect()->route('admissions.index')->with('success', 'Admission updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\HeAdmission $admission)
    {
        $admission->update(['deleted_by' => auth()->id()]);
        $admission->delete();

        return redirect()->route('admissions.index')->with('success', 'Admission deleted successfully.');
    }

    public function searchStudent(Request $request)
    {
        $rollNo = $request->query('roll_no');
        if (!$rollNo) {
            return response()->json(['error' => 'Roll number is required'], 400);
        }

        $admission = Admissions::where(function (Builder $query) use ($rollNo) {
                $query->where('bise_roll_11', $rollNo)
                      ->orWhere('bise_roll_12', $rollNo);
            })
            ->with(['student.attribute', 'student.father', 'student.contact', 'student.sscRecord'])
            ->first();

        if ($admission && $admission->student) {
            $student = $admission->student;
            $student->load(['attribute', 'father', 'contact', 'sscRecord']);
            $hsscRecord = HsscRecord::where('student_id', $student->id)->first();
            
            return response()->json([
                'found' => true,
                'source' => 'Former Intermediate Student',
                'data' => $this->formatStudentData($student, $hsscRecord, $admission)
            ]);
        }

        $hsscRecord = HsscRecord::where('roll_no', $rollNo)
            ->with(['student.attribute', 'student.father', 'student.contact', 'student.sscRecord'])
            ->first();

        if ($hsscRecord && $hsscRecord->student) {
            $student = $hsscRecord->student;
            return response()->json([
                'found' => true,
                'source' => 'Existing Student Record',
                'data' => $this->formatStudentData($student, $hsscRecord)
            ]);
        }

        return response()->json(['found' => false]);
    }

    private function formatStudentData($student, $hsscRecord = null, $admission = null)
    {
        $settings = \App\Models\InstituteSetting::getSettings();
        
        if (!$hsscRecord && $admission) {
            $finalResults = \App\Models\Result::where('student_id', $student->id)->where('exam_type', 'Final')->get();
            $totalMarks = $finalResults->sum('total_marks');
            $obtainedMarks = $finalResults->sum(function($r) {
                return ($r->obtained_theory ?? 0) + ($r->obtained_practical ?? 0);
            });

            return [
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'gender' => $student->gender,
                'date_of_birth' => $student->date_of_birth->format('Y-m-d'),
                'student_cnic' => $student->cnic,
                'blood_group_id' => $student->attribute->blood_group_id ?? null,
                'quota_id' => $student->attribute->quota_id ?? null,
                'religion' => $student->religion ?? 'Islam',
                'nationality' => $student->nationality ?? 'Pakistani',
                
                'father_name' => $student->father->name ?? '',
                'father_cnic' => $student->father->cnic ?? '',
                'father_contact' => $student->father->contact ?? '',
                'father_occupation' => $student->father->occupation ?? '',
                'father_annual_income' => $student->father->annual_income ?? '',
                
                'student_contact' => $student->contact->student_contact ?? '',
                'whatsapp_no' => $student->contact->whatsapp_no ?? '',
                'email' => $student->contact->email ?? '',
                'address' => $student->contact->address ?? '',
                
                'ssc_bise_id' => $student->sscRecord->bise_id ?? '',
                'ssc_roll_no' => $student->sscRecord->roll_no ?? '',
                'ssc_reg_no' => $student->sscRecord->reg_no ?? '',
                'ssc_school_name' => $student->sscRecord->school_name ?? '',
                'ssc_total_mark' => $student->sscRecord->total_mark ?? '',
                'ssc_obtained_mark' => $student->sscRecord->obtained_mark ?? '',
                'ssc_passing_year' => $student->sscRecord->passing_year ?? '',
                'ssc_passing_session' => $student->sscRecord->passing_session ?? '',
                'ssc_group' => $student->sscRecord->ssc_group ?? '',
                
                'hssc_bise_id' => $student->sscRecord->bise_id ?? '',
                'hssc_roll_no' => $admission->bise_roll_12 ?: $admission->bise_roll_11,
                'hssc_reg_no_academic' => $admission->hssc_reg_no ?? '',
                'hssc_college_name' => $settings->institute_title ?? 'This College',
                'hssc_total_mark' => $totalMarks ?: '',
                'hssc_obtained_mark' => $obtainedMarks ?: '',
                'hssc_passing_year' => $admission->academic_year ?? date('Y'),
                'hssc_passing_session' => '1st Annual',
                'hssc_group' => $admission->program_name ?? '',
            ];
        }

        return [
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'gender' => $student->gender,
            'date_of_birth' => $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : '',
            'student_cnic' => $student->cnic,
            'blood_group_id' => $student->attribute->blood_group_id ?? null,
            'quota_id' => $student->attribute->quota_id ?? null,
            'religion' => $student->religion ?? 'Islam',
            'nationality' => $student->nationality ?? 'Pakistani',
            
            'father_name' => $student->father->name ?? '',
            'father_cnic' => $student->father->cnic ?? '',
            'father_contact' => $student->father->contact ?? '',
            'father_occupation' => $student->father->occupation ?? '',
            'father_annual_income' => $student->father->annual_income ?? '',
            
            'student_contact' => $student->contact->student_contact ?? '',
            'whatsapp_no' => $student->contact->whatsapp_no ?? '',
            'email' => $student->contact->email ?? '',
            'address' => $student->contact->address ?? '',
            
            'ssc_bise_id' => $student->sscRecord->bise_id ?? '',
            'ssc_roll_no' => $student->sscRecord->roll_no ?? '',
            'ssc_reg_no' => $student->sscRecord->reg_no ?? '',
            'ssc_school_name' => $student->sscRecord->school_name ?? '',
            'ssc_total_mark' => $student->sscRecord->total_mark ?? '',
            'ssc_obtained_mark' => $student->sscRecord->obtained_mark ?? '',
            'ssc_passing_year' => $student->sscRecord->passing_year ?? '',
            'ssc_passing_session' => $student->sscRecord->passing_session ?? '',
            'ssc_group' => $student->sscRecord->ssc_group ?? '',
            
            'hssc_bise_id' => $hsscRecord->bise_id ?? '',
            'hssc_roll_no' => $hsscRecord->roll_no ?? '',
            'hssc_reg_no_academic' => $hsscRecord->reg_no ?? '',
            'hssc_college_name' => $hsscRecord->college_name ?? '',
            'hssc_total_mark' => $hsscRecord->total_mark ?? '',
            'hssc_obtained_mark' => $hsscRecord->obtained_mark ?? '',
            'hssc_passing_year' => $hsscRecord->passing_year ?? '',
            'hssc_passing_session' => $hsscRecord->passing_session ?? '',
            'hssc_group' => $hsscRecord->hssc_group ?? '',
        ];
    }
}
