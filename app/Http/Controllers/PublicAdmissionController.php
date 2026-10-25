<?php

namespace App\Http\Controllers;

use App\Models\AdmissionApplication;
use App\Models\AdmissionSetting;
use App\Models\InstituteSetting;
use App\Services\AdmissionApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicAdmissionController extends Controller
{
    public function __construct(
        protected AdmissionApplicationService $applicationService
    ) {}

    /**
     * Show the public admission form
     */
    public function showForm(Request $request)
    {
        $program = $request->input('program', 'Inter');
        if (!AdmissionApplication::areAdmissionsOpen($program)) {
            return view('public.apply-closed', compact('program'));
        }

        $data = $this->applicationService->getPublicFormData($program);
        return view('public.apply', $data);
    }

    /**
     * Get compulsory subjects based on religion and nationality (Inter only)
     */
    public function getCompulsorySubjects(Request $request)
    {
        $program = $request->input('program', 'Inter');
        $classLevel = ($program === 'Inter') ? '11' : ($program === 'ADP' ? 'ADP-1' : 'BS-1');
        
        $subjects = \App\Models\SubjectAssignmentRule::getSubjectsForStudent(
            $classLevel, 
            $request->input('nationality', 'Pakistani'), 
            $request->input('religion', 'Islam')
        );

        return response()->json([
            'subject_names' => $subjects->pluck('name'),
            'subject_ids' => $subjects->pluck('id'),
            'subjects' => $subjects->pluck('name')
        ]);
    }

    /**
     * Submit an online application
     */
    public function store(Request $request)
    {
        $program = $request->input('program_type', 'Inter');
        if (!AdmissionApplication::areAdmissionsOpen($program)) {
            return redirect()->route('apply.form', ['program' => $program])
                ->with('error', 'Admissions are currently closed.');
        }

        $isHe = in_array($program, ['ADP', 'BS']);
        $validated = $this->validateApplication($request, $isHe);

        try {
            $files = array_filter([
                'picture' => $request->file('picture'),
                'ssc_result_card' => $request->file('ssc_result_card'),
                'b_form_pic' => $request->file('b_form_pic'),
                'father_cnic_pic' => $request->file('father_cnic_pic'),
            ]);

            $application = $this->applicationService->createOnlineApplication($validated, $files, $program);

            return redirect()->route('apply.status', ['search_term' => $application->application_number])
                ->with('success', 'Your application has been submitted successfully! Your application number is ' . $application->application_number);

        } catch (\Exception $e) {
            Log::error('Online application failed: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'An error occurred while submitting your application: ' . $e->getMessage());
        }
    }

    /**
     * Show the application correction form
     */
    public function edit(Request $request, AdmissionApplication $application)
    {
        $searchTerm = $request->input('search_term');
        if (!$searchTerm || ($searchTerm !== $application->application_number && $searchTerm !== $application->cnic)) {
            return redirect()->route('apply.status')->with('error', 'Unauthorized access.');
        }

        if ($application->status !== 'rejected') {
            return redirect()->route('apply.status', ['search_term' => $searchTerm])->with('error', 'Only rejected applications can be edited.');
        }

        $program = $application->program_type ?? 'Inter';
        $data = $this->applicationService->getPublicFormData($program, $application, $searchTerm);

        return view('public.apply', $data);
    }

    /**
     * Update the rejected application
     */
    public function update(Request $request, AdmissionApplication $application)
    {
        if ($application->status !== 'rejected') {
            return redirect()->route('apply.status')->with('error', 'Only rejected applications can be corrected.');
        }

        $program = $application->program_type ?? 'Inter';
        $isHe = in_array($program, ['ADP', 'BS']);
        $validated = $this->validateApplication($request, $isHe, $application);

        try {
            $files = array_filter([
                'picture' => $request->file('picture'),
                'ssc_result_card' => $request->file('ssc_result_card'),
                'b_form_pic' => $request->file('b_form_pic'),
                'father_cnic_pic' => $request->file('father_cnic_pic'),
            ]);

            $this->applicationService->correctRejectedApplication($application, $validated, $files);

            return redirect()->route('apply.status', ['search_term' => $application->application_number])
                ->with('success', 'Your application has been corrected and resubmitted successfully.');

        } catch (\Exception $e) {
            Log::error('Correction failed: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'An error occurred while correcting your application.');
        }
    }

    /**
     * Check application status page
     */
    public function checkStatus(Request $request)
    {
        $application = null;
        $settings = InstituteSetting::getSettings();
        $searchTerm = $request->input('search_term');

        if ($request->filled('search_term')) {
            $application = AdmissionApplication::where('application_number', $searchTerm)
                ->orWhere('cnic', $searchTerm)
                ->with(['voucher', 'electiveSubject1', 'electiveSubject2', 'electiveSubject3', 'bise', 'program'])
                ->first();

            if (!$application) {
                return back()->with('error', 'Application not found. Please check your application number or CNIC.');
            }
        }

        return view('public.application-status', compact('application', 'settings', 'searchTerm'));
    }

    /**
     * Upload paid voucher image
     */
    public function uploadPaidVoucher(Request $request, AdmissionApplication $application)
    {
        $request->validate([
            'paid_voucher' => 'required|image|mimes:jpeg,jpg,png|max:5120',
        ]);

        if (!in_array($application->status, ['fee_pending'])) {
            return back()->with('error', 'You can only upload voucher proof when your fee is pending.');
        }

        try {
            $this->applicationService->uploadPaidVoucher($application, $request->file('paid_voucher'));

            return back()->with('success', 'Paid voucher uploaded successfully! The clerk will verify your payment.');
        } catch (\Exception $e) {
            Log::error('Voucher upload failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to upload voucher. Please try again.');
        }
    }

    /**
     * Download fee voucher
     */
    public function downloadVoucher(AdmissionApplication $application)
    {
        if (!$application->voucher) {
            return back()->with('error', 'No voucher has been generated for this application.');
        }

        $application->load(['voucher', 'electiveSubject1', 'electiveSubject2', 'electiveSubject3', 'bise', 'program']);
        $settings = InstituteSetting::getSettings();
        $voucher = $application->voucher;

        return view('pdfs.fee-voucher', compact('application', 'settings', 'voucher'));
    }

    /**
     * Build and run validation rules.
     */
    private function validateApplication(Request $request, bool $isHe, ?AdmissionApplication $existing = null): array
    {
        $isUpdate = $existing !== null;

        $commonRules = [
            'first_name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z\s]+$/'],
            'last_name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z\s]+$/'],
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:Male,Female,Other',
            'cnic' => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'religion' => 'required|string|max:50',
            'nationality' => 'required|string|max:50',
            'domicile' => 'required|string|max:50',
            'father_name' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z\s]+$/'],
            'father_cnic' => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'father_contact' => ['nullable', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'father_occupation' => 'nullable|string|max:100',
            'father_annual_income' => 'nullable|numeric|min:0',
            'student_contact' => ['nullable', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'whatsapp_no' => ['nullable', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'email' => 'nullable|email|max:100',
            'address' => 'required|string|max:255',
            'blood_group_id' => 'nullable|exists:blood_groups,id',
            'quota_id' => 'nullable|exists:quotas,id',
            'shift' => 'required|in:Morning,Evening',
            'ssc_passed' => 'required|in:Passed,Failed,Result Awaited',
            'ssc_roll_no' => 'required|string|max:50',
            'ssc_reg_no' => 'required|string|max:50',
            'ssc_bise_id' => 'required|exists:bise_records,id',
            'ssc_passing_year' => 'required|integer|min:2000|max:' . (date('Y')),
            'ssc_passing_session' => 'required|in:Annual,Supplementary',
            'ssc_school_name' => 'required|string|max:200',
            'ssc_group' => 'required|in:Science,Arts,Humanities,Computer Science,General,Technical',
            'ssc_total_marks' => 'required|integer|min:1',
            'ssc_obtained_marks' => 'required|integer|min:0|lte:ssc_total_marks',
            'picture' => $isUpdate ? 'nullable|image|mimes:jpeg,jpg,png|max:2048' : 'required|image|mimes:jpeg,jpg,png|max:2048',
            'ssc_result_card' => $isUpdate ? 'nullable|file|mimes:jpeg,jpg,png,pdf|max:3072' : 'required|file|mimes:jpeg,jpg,png,pdf|max:3072',
            'b_form_pic' => $isUpdate ? 'nullable|file|mimes:jpeg,jpg,png,pdf|max:3072' : 'required|file|mimes:jpeg,jpg,png,pdf|max:3072',
            'father_cnic_pic' => $isUpdate ? 'nullable|file|mimes:jpeg,jpg,png,pdf|max:3072' : 'required|file|mimes:jpeg,jpg,png,pdf|max:3072',
        ];

        if ($isHe) {
            $heRules = [
                'program_id' => 'required|exists:programs,id',
                'hssc_passed' => 'required|in:Passed,Result Awaited',
                'hssc_roll_no' => 'required|string|max:50',
                'hssc_reg_no' => 'nullable|string|max:50',
                'hssc_bise_id' => 'required|exists:bise_records,id',
                'hssc_passing_year' => 'required|integer|min:2000|max:' . date('Y'),
                'hssc_passing_session' => 'required|in:Annual,Supplementary',
                'hssc_college_name' => 'required|string|max:200',
                'hssc_group' => 'required|string|max:100',
                'hssc_total_marks' => 'required|integer|min:1',
                'hssc_obtained_marks' => 'required|integer|min:0|lte:hssc_total_marks',
            ];
            return $request->validate(array_merge($commonRules, $heRules));
        }

        $interRules = [
            'elective_subject_1_id' => 'required|exists:subjects,id',
            'elective_subject_2_id' => 'required|exists:subjects,id|different:elective_subject_1_id',
            'elective_subject_3_id' => 'required|exists:subjects,id|different:elective_subject_1_id|different:elective_subject_2_id',
        ];

        return $request->validate(array_merge($commonRules, $interRules));
    }
}
