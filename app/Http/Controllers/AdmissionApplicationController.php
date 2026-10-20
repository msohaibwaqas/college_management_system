<?php

namespace App\Http\Controllers;

use App\Models\AdmissionSetting;
use App\Models\AdmissionApplication;
use App\Models\FeeVoucher;
use App\Models\BiseRecord;
use App\Models\Subject;
use App\Models\BloodGroup;
use App\Models\Quota;
use App\Models\InstituteSetting;
use App\Models\SubjectGroup;
use App\Models\Program;
use App\Models\HeAdmission;
use App\Models\SscRecord;
use App\Models\HsscRecord;
use App\Models\Student;
use App\Models\Father;
use App\Models\Contact;
use App\Models\StudentAttribute;
use App\Services\MeritListService;
use App\Services\FileUploadService;
use App\Services\FeeCalculationService;
use App\Actions\Admission\AdmitHeApplicantAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdmissionApplicationController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService,
        protected FeeCalculationService $feeCalculationService,
        protected MeritListService $meritListService,
        protected AdmitHeApplicantAction $admitHeApplicantAction
    ) {}

    /**
     * List all applications with filters
     */
    public function index(Request $request)
    {
        $query = AdmissionApplication::with(['electiveSubject1', 'electiveSubject2', 'electiveSubject3', 'bise', 'voucher'])
            ->select('admission_applications.*')
            ->addSelect([
                'linked_student_id' => \App\Models\SscRecord::select('student_id')
                    ->whereColumn('roll_no', 'admission_applications.ssc_roll_no')
                    ->whereColumn('reg_no', 'admission_applications.ssc_reg_no')
                    ->limit(1)
            ]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by year
        if ($request->filled('admission_year')) {
            $query->where('admission_year', $request->admission_year);
        }

        // Filter by source
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // Filter by subject group
        if ($request->filled('subject_group')) {
            $query->where('subject_group', $request->subject_group);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%")
                  ->orWhere('application_number', 'like', "%{$search}%")
                  ->orWhere('student_contact', 'like', "%{$search}%");
            });
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(25)->appends($request->except('page'));

        $admissionYears = AdmissionApplication::distinct()->pluck('admission_year')->sortDesc();

        return view('admission-applications.index', compact('applications', 'admissionYears'));
    }

    /**
     * Show form to create a walk-in application
     */
    public function create(Request $request)
    {
        $program = $request->input('program', 'Inter');
        $isHe = in_array($program, ['ADP', 'BS']);
        $bises = BiseRecord::all();
        $bloodGroups = BloodGroup::all();
        $quotas = Quota::all();
        $settings = AdmissionSetting::getForProgram($program);

        if ($isHe) {
            $programs = Program::where('type', $program)->get();
            $hsscGroups = SubjectGroup::whereNull('deleted_at')->orderBy('name')->pluck('name');
            $subjects = collect();
            $subjectGroups = collect();
        } else {
            $programs = collect();
            $hsscGroups = collect();
            $subjects = Subject::where('type', 'Elective')->orderBy('name')->get();
            $subjectGroups = SubjectGroup::with('subjects')->get();
        }

        return view('admission-applications.create', compact(
            'bises', 'subjects', 'bloodGroups', 'quotas', 'settings',
            'subjectGroups', 'program', 'programs', 'hsscGroups'
        ));
    }

    /**
     * Store a walk-in application (clerk-entered)
     */
    public function store(Request $request)
    {
        $programType = $request->input('program_type', 'Inter');
        $isHe = in_array($programType, ['ADP', 'BS']);

        $commonRules = [
            'first_name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z\s]+$/'],
            'last_name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z\s]+$/'],
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:Male,Female,Other',
            'cnic' => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'religion' => 'required|string|max:50',
            'nationality' => 'required|string|max:50',
            'father_name' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z\s]+$/'],
            'father_cnic' => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'father_contact' => ['nullable', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'father_occupation' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-Z\s]*$/'],
            'father_annual_income' => 'nullable|integer',
            'student_contact' => ['required', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'whatsapp_no' => ['nullable', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'email' => 'nullable|email:filter|max:120',
            'address' => 'required|string',
            'admission_year' => 'required|digits:4|integer',
            'blood_group_id' => 'nullable|exists:blood_groups,id',
            'quota_id' => 'nullable|exists:quotas,id',
            'picture' => 'nullable|image|max:2048',
            'program_type' => 'required|string|in:Inter,ADP,BS,DIT',
            'bise_id' => 'required|exists:bise_records,id',
            'ssc_roll_no' => 'required|numeric|min:0',
            'ssc_reg_no' => [
                'required', 'string', 'max:50',
                function ($attribute, $value, $fail) use ($request) {
                    $rollNo = $request->input('ssc_roll_no');
                    if (!$rollNo) return;
                    $existsInStudents = SscRecord::where('roll_no', $rollNo)->where('reg_no', $value)->exists();
                    if ($existsInStudents) { $fail('This student (SSC Roll/Reg) is already admitted in the system.'); return; }
                    $existsInApps = AdmissionApplication::where('ssc_roll_no', $rollNo)->where('ssc_reg_no', $value)->where('status', '!=', 'rejected')->exists();
                    if ($existsInApps) { $fail('An application with this SSC Roll and Registration number already exists.'); }
                }
            ],
            'ssc_school_name' => 'required|string|max:255',
            'ssc_total_marks' => 'required|numeric|min:1',
            'ssc_obtained_marks' => ['required', 'numeric', 'min:0', 'lte:ssc_total_marks'],
            'ssc_passing_year' => ['required', 'digits:4', 'integer', 'min:' . (date('Y') - 10), 'max:' . date('Y')],
            'ssc_passing_session' => 'required|in:1st Annual,2nd Annual',
            'ssc_group' => 'required|string',
        ];

        if ($isHe) {
            $extraRules = [
                'program_id' => 'required|exists:programs,id',
                'hssc_bise_id' => 'required|exists:bise_records,id',
                'hssc_roll_no' => 'required|numeric|min:0',
                'hssc_reg_no' => 'required|string|max:50',
                'hssc_college_name' => 'required|string|max:255',
                'hssc_total_marks' => 'required|numeric|min:1',
                'hssc_obtained_marks' => ['required', 'numeric', 'min:0', 'lte:hssc_total_marks'],
                'hssc_passing_year' => ['required', 'digits:4', 'integer', 'min:' . (date('Y') - 10), 'max:' . date('Y')],
                'hssc_passing_session' => 'required|in:1st Annual,2nd Annual',
                'hssc_group' => 'required|string|max:100',
            ];
        } else {
            $extraRules = [
                'subject_group_id' => 'required|exists:subject_groups,id',
                'elective_subject_1' => 'required|exists:subjects,id',
                'elective_subject_2' => 'required|exists:subjects,id|different:elective_subject_1',
                'elective_subject_3' => 'required|exists:subjects,id|different:elective_subject_1|different:elective_subject_2',
            ];
        }

        $validated = $request->validate(array_merge($commonRules, $extraRules));

        try {
            $picturePath = null;
            if ($request->hasFile('picture')) {
                $picturePath = $this->fileUploadService->uploadToDisk(
                    $request->file('picture'),
                    'application_pictures',
                    'public'
                );
            }

            $application = AdmissionApplication::create(array_merge($validated, [
                'application_number' => AdmissionApplication::generateApplicationNumber($validated['admission_year']),
                'source' => 'walk_in',
                'status' => 'approved',
                'nationality' => $validated['nationality'] ?? 'Pakistani',
                'picture' => $picturePath,
                'program_type' => $programType,
                'created_by' => Auth::id(),
            ]));

            if (!$isHe) {
                $application->updateSubjectGroup();
            }

            return redirect()->route('admission-applications.show', $application)
                ->with('success', 'Application created successfully! Application Number: ' . $application->application_number);

        } catch (\Exception $e) {
            \Log::error('Application creation failed: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * View application details
     */
    public function show(AdmissionApplication $application)
    {
        $application->load([
            'electiveSubject1', 'electiveSubject2', 'electiveSubject3',
            'bise', 'reviewer', 'createdBy', 'voucher.issuedBy', 'voucher.confirmedBy',
            'bloodGroup', 'quota',
        ]);

        return view('admission-applications.show', compact('application'));
    }

    /**
     * Review an online application (approve/reject)
     */
    public function review(Request $request, AdmissionApplication $application)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'review_remarks' => 'nullable|string|max:500',
        ]);

        if (!in_array($application->status, ['submitted', 'under_review'])) {
            return back()->with('error', 'This application cannot be reviewed in its current status.');
        }

        $newStatus = $request->action === 'approve' ? 'approved' : 'rejected';

        $application->update([
            'status' => $newStatus,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_remarks' => $request->review_remarks,
        ]);

        // Auto-classify subject group on approval
        if ($newStatus === 'approved') {
            $application->updateSubjectGroup();
        }

        $actionLabel = $request->action === 'approve' ? 'approved' : 'rejected';
        return back()->with('success', "Application has been {$actionLabel} successfully.");
    }

    /**
     * Generate a fee voucher for an approved application
     */
    public function generateVoucher(Request $request, AdmissionApplication $application)
    {
        if (!in_array($application->status, ['approved', 'submitted', 'under_review'])) {
            return back()->with('error', 'Voucher can only be generated for approved or submitted applications.');
        }

        // Check if voucher already exists
        if ($application->voucher) {
            return back()->with('error', 'A voucher has already been generated for this application.');
        }

        try {
            $voucher = $this->feeCalculationService->generateVoucherForApplication($application);

            $application->update([
                'fee_voucher_number' => $voucher->voucher_number,
                'status' => 'fee_pending',
            ]);

            return back()->with('success', 'Fee voucher generated successfully! Voucher #: ' . $voucher->voucher_number);
        } catch (\Exception $e) {
            \Log::error('Voucher generation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate voucher. Please try again.');
        }
    }

    /**
     * Show preview and adjustment form for a voucher
     */
    public function voucherPreview(AdmissionApplication $application)
    {
        if (!in_array($application->status, ['approved', 'submitted', 'under_review'])) {
            return back()->with('error', 'Voucher can only be generated for approved or submitted applications.');
        }

        if ($application->voucher) {
            return redirect()->route('admission-applications.show', $application)->with('error', 'Voucher already exists.');
        }

        $items = $this->feeCalculationService->calculateVoucherItems($application);

        return view('admission-applications.voucher-preview', compact('application', 'items'));
    }

    /**
     * Generate voucher with custom items/amounts
     */
    public function generateVoucherCustom(Request $request, AdmissionApplication $application)
    {
        if (!in_array($application->status, ['approved', 'submitted', 'under_review'])) {
            return back()->with('error', 'Voucher can only be generated for approved or submitted applications.');
        }

        if ($application->voucher) {
             return redirect()->route('admission-applications.show', $application)->with('error', 'Voucher already exists.');
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.name' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
        ]);

        try {
            $items = collect($request->input('items'))->map(function($item) {
                return [
                    'name' => $item['name'],
                    'amount' => (float)$item['amount'],
                    'fee_head_id' => $item['fee_head_id'] ?? null,
                    'is_refundable' => isset($item['is_refundable']) ? (bool)$item['is_refundable'] : false,
                ];
            });

            $voucher = $this->feeCalculationService->createVoucher($application, $items);

            $application->update([
                'fee_voucher_number' => $voucher->voucher_number,
                'status' => 'fee_pending',
            ]);

            return redirect()->route('admission-applications.show', $application)->with('success', 'Custom voucher generated successfully!');
        } catch (\Exception $e) {
            \Log::error('Custom voucher generation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate voucher. ' . $e->getMessage());
        }
    }

    /**
     * Confirm payment for an application
     */
    public function confirmPayment(Request $request, AdmissionApplication $application)
    {
        if (!in_array($application->status, ['fee_pending'])) {
            return back()->with('error', 'Payment can only be confirmed for fee-pending applications.');
        }

        $request->validate([
            'paid_amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
        ]);

        try {
            DB::transaction(function () use ($application, $request) {
                // Update voucher
                if ($application->voucher) {
                    $application->voucher->update([
                        'paid_amount' => $request->paid_amount,
                        'payment_date' => $request->payment_date,
                        'status' => 'paid',
                        'confirmed_by' => Auth::id(),
                    ]);
                }

                // Update application
                $application->update([
                    'status' => 'fee_paid',
                    'fee_confirmed_at' => now(),
                ]);
            });

            return back()->with('success', 'Payment confirmed successfully! This student will be included in the merit list.');
        } catch (\Exception $e) {
            \Log::error('Payment confirmation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to confirm payment. Please try again.');
        }
    }

    /**
     * Show merit list generation form
     */
    public function generateMeritList(Request $request)
    {
        $program = $request->input('program', 'Inter');
        $settings = AdmissionSetting::getForProgram($program);
        $admissionYears = AdmissionApplication::where('program_type', $program)
            ->distinct()
            ->pluck('admission_year')
            ->push($settings->admission_year)
            ->filter()
            ->unique()
            ->sortDesc();

        $preview = null;
        $selectedYear = $request->input('admission_year');
        $rollRanges = $request->input('roll_ranges', MeritListService::DEFAULT_ROLL_RANGES);

        if ($selectedYear) {
            $preview = $this->meritListService->preview((int) $selectedYear, $rollRanges, $program);
        }

        $defaultRanges = MeritListService::DEFAULT_ROLL_RANGES;

        return view('admission-applications.merit-list', compact(
            'admissionYears', 'preview', 'selectedYear', 'defaultRanges', 'rollRanges', 'program'
        ));
    }

    /**
     * Process merit list — create students and admissions
     */
    public function processMeritList(Request $request)
    {
        $request->validate([
            'admission_year' => 'required|integer|digits:4',
            'roll_ranges' => 'required|array',
            'roll_ranges.*' => 'required|integer|min:1',
            'confirm' => 'required|accepted',
        ]);

        try {
            $result = $this->meritListService->generate(
                (int) $request->admission_year,
                $request->roll_ranges,
                $request->input('program_type', 'Inter')
            );

            return redirect()->route('admission-applications.index', ['status' => 'admitted'])
                ->with('success', "Merit list generated successfully! {$result['total_admitted']} students admitted.");

        } catch (\Exception $e) {
            \Log::error('Merit list generation failed: ' . $e->getMessage());
            return back()->with('error', 'Merit list generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Admit a single late Inter applicant
     */
    public function admitLate(AdmissionApplication $application)
    {
        if ($application->status !== 'fee_paid') {
            return back()->with('error', 'Only fee-paid applications can be admitted late.');
        }

        if ($application->isHeProgram()) {
            return $this->admitHeApplicant($application);
        }

        try {
            $result = $this->meritListService->admitLateApplicant($application);

            return back()->with('success', 
                "Student admitted with Roll Number: {$result['roll_number']} in {$result['group']} group.");
        } catch (\Exception $e) {
            \Log::error('Late admission failed: ' . $e->getMessage());
            return back()->with('error', 'Late admission failed: ' . $e->getMessage());
        }
    }

    /**
     * Directly admit a fee-paid ADP/BS applicant into a HeAdmission record.
     * Creates Student, Father, Contact, SscRecord, HsscRecord, and HeAdmission.
     */
    public function admitHeApplicant(AdmissionApplication $application)
    {
        if ($application->status !== 'fee_paid') {
            return back()->with('error', 'Only fee-paid applications can be admitted.');
        }

        if (!$application->isHeProgram()) {
            return back()->with('error', 'This admission flow is only for ADP/BS applications.');
        }

        if (!$application->program_id) {
            return back()->with('error', 'No program selected on this application. Please edit the application first.');
        }

        try {
            $heAdmission = $this->admitHeApplicantAction->execute($application);

            return redirect()->route('admissions.show', $heAdmission)
                ->with('success', 'Student admitted successfully into ' . $heAdmission->program->name . ' — Semester 1.');
        } catch (\Exception $e) {
            \Log::error('HE applicant admission failed: ' . $e->getMessage());
            return back()->with('error', 'Admission failed: ' . $e->getMessage());
        }
    }

    public function edit(AdmissionApplication $application)
    {
        if ($application->status === 'admitted') {
            // Find the linked student via SscRecord
            $sscRecord = \App\Models\SscRecord::where('roll_no', $application->ssc_roll_no)
                ->where('reg_no', $application->ssc_reg_no)
                ->first();

            if ($sscRecord) {
                return redirect()->route('students.edit', $sscRecord->student_id)
                    ->with('info', 'This application is already admitted. You are now editing the student record.');
            }

            return back()->with('error', 'This application is admitted but linked student record was not found.');
        }

        $bises = BiseRecord::all();
        $subjects = Subject::where('type', 'Elective')->orderBy('name')->get();
        $bloodGroups = BloodGroup::all();
        $quotas = Quota::all();
        $subjectGroups = SubjectGroup::with('subjects')->get();
        
        return view('admission-applications.edit', compact('application', 'bises', 'subjects', 'bloodGroups', 'quotas', 'subjectGroups'));
    }

    /**
     * Update an application
     */
    public function update(Request $request, AdmissionApplication $application)
    {
        if ($application->status === 'admitted') {
            return back()->with('error', 'Cannot update an application that has already been admitted.');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z\s]+$/'],
            'last_name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z\s]+$/'],
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:Male,Female,Other',
            'cnic' => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'religion' => 'required|string|max:50',
            'nationality' => 'required|string|max:50',
            'father_name' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z\s]+$/'],
            'father_cnic' => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'father_contact' => ['nullable', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'father_occupation' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-Z\s]*$/'],
            'father_annual_income' => 'nullable|integer',
            'student_contact' => ['required', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'whatsapp_no' => ['nullable', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'email' => 'nullable|email:filter|max:120',
            'address' => 'required|string',
            'bise_id' => 'required|exists:bise_records,id',
            'ssc_roll_no' => 'required|numeric|min:0',
            'ssc_reg_no' => [
                'required', 'string', 'max:50',
                function ($attribute, $value, $fail) use ($request, $application) {
                    $rollNo = $request->input('ssc_roll_no');
                    if (!$rollNo) return;

                    // Check other applications
                    $existsInApps = AdmissionApplication::where('ssc_roll_no', $rollNo)
                        ->where('ssc_reg_no', $value)
                        ->where('status', '!=', 'rejected')
                        ->where('id', '!=', $application->id)
                        ->exists();

                    if ($existsInApps) {
                        $fail('An application with this SSC Roll and Registration number already exists.');
                    }
                }
            ],
            'ssc_school_name' => 'required|string|max:255',
            'ssc_total_marks' => 'required|numeric|min:1',
            'ssc_obtained_marks' => [
                'required', 
                'numeric', 
                'min:0', 
                'lte:ssc_total_marks',
                function ($attribute, $value, $fail) use ($request) {
                    $total = floatval($request->input('ssc_total_marks'));
                    if ($total > 0 && floatval($value) < ($total * 0.33)) {
                        $fail('Obtained marks must be at least 33% of total marks.');
                    }
                }
            ],
            'ssc_passing_year' => ['required', 'digits:4', 'integer', 'min:' . (date('Y') - 10), 'max:' . date('Y')],
            'ssc_passing_session' => 'required|in:1st Annual,2nd Annual',
            'ssc_group' => 'required|in:Science,Arts',
            'admission_year' => 'required|digits:4|integer',
            'subject_group_id' => 'required|exists:subject_groups,id',
            'elective_subject_1' => 'required|exists:subjects,id',
            'elective_subject_2' => 'required|exists:subjects,id|different:elective_subject_1',
            'elective_subject_3' => 'required|exists:subjects,id|different:elective_subject_1|different:elective_subject_2',
            'blood_group_id' => 'nullable|exists:blood_groups,id',
            'quota_id' => 'nullable|exists:quotas,id',
            'picture' => 'nullable|image|max:2048',
        ]);

        try {
            if ($request->hasFile('picture')) {
                $validated['picture'] = $this->fileUploadService->uploadToDisk(
                    $request->file('picture'),
                    'application_pictures',
                    'public',
                    $application->picture
                );
            }

            $application->update($validated);
            
            // Re-classify subject group
            $application->updateSubjectGroup();

            return redirect()->route('admission-applications.show', $application)
                ->with('success', 'Application updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Update application failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete an application
     */
    public function destroy(AdmissionApplication $application)
    {
        if ($application->status === 'admitted') {
            return back()->with('error', 'Cannot delete an admitted student application.');
        }

        if ($application->voucher && $application->voucher->status === 'paid') {
            return back()->with('error', 'Cannot delete application with a paid voucher.');
        }

        DB::transaction(function () use ($application) {
            // Cleanup files if they exist
            $files = [
                $application->picture,
                $application->ssc_result_card,
                $application->b_form_pic,
                $application->father_cnic_pic,
                $application->paid_voucher_path,
            ];

            foreach ($files as $file) {
                if ($file && \Storage::disk('public')->exists($file)) {
                    \Storage::disk('public')->delete($file);
                }
            }

            // Force delete associated voucher items manually to be sure
            if ($application->voucher) {
                $application->voucher->items()->delete();
                $application->voucher->forceDelete();
            }
            
            // Force delete the application (triggers DB cascade for other relations if any)
            $application->forceDelete();
        });

        return redirect()->route('admission-applications.index')
            ->with('success', 'Application and all associated records have been permanently deleted.');
    }
}
