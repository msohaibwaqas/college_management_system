<?php

namespace App\Http\Controllers;

use App\Actions\Admission\AdmitHeApplicantAction;
use App\Http\Requests\AdmissionApplication\StoreAdmissionApplicationRequest;
use App\Http\Requests\AdmissionApplication\UpdateAdmissionApplicationRequest;
use App\Models\AdmissionApplication;
use App\Models\AdmissionSetting;
use App\Models\BiseRecord;
use App\Models\BloodGroup;
use App\Models\Program;
use App\Models\Quota;
use App\Models\SscRecord;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Services\AdmissionApplicationService;
use App\Services\FeeCalculationService;
use App\Services\MeritListService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdmissionApplicationController extends Controller
{
    public function __construct(
        protected AdmissionApplicationService $admissionService,
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
                'linked_student_id' => SscRecord::select('student_id')
                    ->whereColumn('roll_no', 'admission_applications.ssc_roll_no')
                    ->whereColumn('reg_no', 'admission_applications.ssc_reg_no')
                    ->limit(1)
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('admission_year')) {
            $query->where('admission_year', $request->admission_year);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('subject_group')) {
            $query->where('subject_group', $request->subject_group);
        }

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
    public function store(StoreAdmissionApplicationRequest $request)
    {
        try {
            $application = $this->admissionService->createWalkInApplication(
                $request->validated(),
                $request->file('picture'),
                Auth::id()
            );

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

        $result = $this->admissionService->reviewApplication(
            $application,
            $request->action,
            $request->review_remarks,
            Auth::id()
        );

        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', "Application has been {$result['label']} successfully.");
    }

    /**
     * Generate a fee voucher for an approved application
     */
    public function generateVoucher(Request $request, AdmissionApplication $application)
    {
        $result = $this->admissionService->generateVoucher($application);

        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', 'Fee voucher generated successfully! Voucher #: ' . $result['voucher']->voucher_number);
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
        $request->validate([
            'items' => 'required|array',
            'items.*.name' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
        ]);

        $result = $this->admissionService->generateCustomVoucher($application, $request->input('items'));

        if (!$result['success']) {
            return redirect()->route('admission-applications.show', $application)->with('error', $result['error']);
        }

        return redirect()->route('admission-applications.show', $application)->with('success', 'Custom voucher generated successfully!');
    }

    /**
     * Confirm payment for an application
     */
    public function confirmPayment(Request $request, AdmissionApplication $application)
    {
        $request->validate([
            'paid_amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
        ]);

        $result = $this->admissionService->confirmPayment(
            $application,
            (float) $request->paid_amount,
            $request->payment_date,
            Auth::id()
        );

        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', 'Payment confirmed successfully! This student will be included in the merit list.');
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
            $sscRecord = SscRecord::where('roll_no', $application->ssc_roll_no)
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
    public function update(UpdateAdmissionApplicationRequest $request, AdmissionApplication $application)
    {
        if ($application->status === 'admitted') {
            return back()->with('error', 'Cannot update an application that has already been admitted.');
        }

        try {
            $this->admissionService->updateApplication(
                $application,
                $request->validated(),
                $request->file('picture')
            );

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
        $result = $this->admissionService->deleteApplication($application);

        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        return redirect()->route('admission-applications.index')
            ->with('success', 'Application and all associated records have been permanently deleted.');
    }
}
