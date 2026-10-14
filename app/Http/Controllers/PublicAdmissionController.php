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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicAdmissionController extends Controller
{
    /**
     * Show the public admission form
     */
    public function showForm(Request $request)
    {
        $program = $request->input('program', 'Inter');
        if (!AdmissionApplication::areAdmissionsOpen($program)) {
            return view('public.apply-closed', compact('program'));
        }

        $bises = BiseRecord::all();
        $bloodGroups = BloodGroup::all();
        $quotas = Quota::all();
        $settings = AdmissionSetting::getForProgram($program);

        if (in_array($program, ['ADP', 'BS'])) {
            // HE programs: load available programs and HSSC groups from DB
            $programs = Program::where('type', $program)->get();
            $hsscGroups = SubjectGroup::whereNull('deleted_at')->orderBy('name')->pluck('name');
            $subjects = collect();
            $subjectGroups = collect();
        } else {
            // Inter: load elective subjects and subject groups
            $programs = collect();
            $hsscGroups = collect();
            $subjects = Subject::where('type', 'Elective')->orderBy('name')->get();
            $subjectGroups = SubjectGroup::with('subjects')->get();
        }

        return view('public.apply', compact(
            'bises', 'subjects', 'bloodGroups', 'quotas', 'settings',
            'subjectGroups', 'program', 'programs', 'hsscGroups'
        ));
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
            'subjects' => $subjects->pluck('name') // For backward compatibility
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
            $settings = AdmissionSetting::getForProgram($program);
            $admissionYear = $settings->admission_year ?? date('Y');

            $data = $validated;
            $data['picture'] = $request->file('picture')->store('application_pictures', 'public');
            $data['ssc_result_card'] = $request->file('ssc_result_card')->store('application_docs', 'public');
            $data['b_form_pic'] = $request->file('b_form_pic')->store('application_docs', 'public');
            $data['father_cnic_pic'] = $request->file('father_cnic_pic')->store('application_docs', 'public');

            $data['application_number'] = AdmissionApplication::generateApplicationNumber($admissionYear);
            $data['source'] = 'online';
            $data['status'] = 'submitted';
            $data['admission_year'] = $admissionYear;
            $data['program_type'] = $program;
            
            $application = AdmissionApplication::create($data);

            // Auto-classify subject group for Inter only
            if (!$isHe) {
                $application->updateSubjectGroup();
            }

            return redirect()->route('apply.status', ['search_term' => $application->application_number])
                ->with('success', 'Your application has been submitted successfully! Your application number is ' . $application->application_number);

        } catch (\Exception $e) {
            \Log::error('Online application failed: ' . $e->getMessage());
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
        $isHe = in_array($program, ['ADP', 'BS']);

        $bises = BiseRecord::all();
        $bloodGroups = BloodGroup::all();
        $quotas = Quota::all();
        $settings = InstituteSetting::getSettings();

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

        return view('public.apply', compact(
            'application', 'bises', 'subjects', 'bloodGroups', 'quotas',
            'settings', 'searchTerm', 'subjectGroups', 'program', 'programs', 'hsscGroups'
        ));
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
            $data = $validated;
            
            if ($request->hasFile('picture')) {
                $data['picture'] = $request->file('picture')->store('application_pictures', 'public');
            }
            if ($request->hasFile('ssc_result_card')) {
                $data['ssc_result_card'] = $request->file('ssc_result_card')->store('application_docs', 'public');
            }
            if ($request->hasFile('b_form_pic')) {
                $data['b_form_pic'] = $request->file('b_form_pic')->store('application_docs', 'public');
            }
            if ($request->hasFile('father_cnic_pic')) {
                $data['father_cnic_pic'] = $request->file('father_cnic_pic')->store('application_docs', 'public');
            }

            $data['status'] = 'submitted';
            $data['review_remarks'] = "--- Application Corrected on " . now()->format('d M Y h:i A') . " ---\n" . ($application->review_remarks ?? '');

            $application->update($data);
            if (!$isHe) {
                $application->updateSubjectGroup();
            }

            return redirect()->route('apply.status', ['search_term' => $application->application_number])
                ->with('success', 'Your application has been corrected and resubmitted successfully.');

        } catch (\Exception $e) {
            \Log::error('Correction failed: ' . $e->getMessage());
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
            $path = $request->file('paid_voucher')->store('paid_vouchers', 'public');

            $application->update([
                'paid_voucher_path' => $path,
            ]);

            return back()->with('success', 'Paid voucher uploaded successfully! The clerk will verify your payment.');
        } catch (\Exception $e) {
            \Log::error('Voucher upload failed: ' . $e->getMessage());
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

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Build and run validation rules, branching on program type.
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
            'father_occupation' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-Z\s]*$/'],
            'father_annual_income' => 'nullable|integer',
            'student_contact' => ['required', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'whatsapp_no' => ['nullable', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'email' => 'nullable|email:filter|max:120',
            'address' => 'required|string',
            'blood_group_id' => 'nullable|exists:blood_groups,id',
            'quota_id' => 'nullable|exists:quotas,id',
            'picture' => $isUpdate ? 'nullable|image|max:2048' : 'required|image|max:2048',
            'ssc_result_card' => $isUpdate ? 'nullable|image|max:5120' : 'required|image|max:5120',
            'b_form_pic' => $isUpdate ? 'nullable|image|max:5120' : 'required|image|max:5120',
            'father_cnic_pic' => $isUpdate ? 'nullable|image|max:5120' : 'required|image|max:5120',
        ];

        $sscRules = [
            'bise_id' => 'required|exists:bise_records,id',
            'ssc_roll_no' => 'required|numeric|min:0',
            'ssc_reg_no' => [
                'required', 'string', 'max:50',
                function ($attribute, $value, $fail) use ($request, $existing) {
                    $rollNo = $request->input('ssc_roll_no');
                    if (!$rollNo) return;

                    $existsInStudents = \App\Models\SscRecord::where('roll_no', $rollNo)
                        ->where('reg_no', $value)->exists();
                    if ($existsInStudents) {
                        $fail('This student (SSC Roll/Reg) is already admitted in the system.');
                        return;
                    }

                    $query = AdmissionApplication::where('ssc_roll_no', $rollNo)
                        ->where('ssc_reg_no', $value)
                        ->where('status', '!=', 'rejected');
                    if ($existing) {
                        $query->where('id', '!=', $existing->id);
                    }
                    if ($query->exists()) {
                        $fail('An application with this SSC Roll and Registration number already exists.');
                    }
                }
            ],
            'ssc_school_name' => 'required|string|max:255',
            'ssc_total_marks' => 'required|numeric|min:1',
            'ssc_obtained_marks' => [
                'required', 'numeric', 'min:0', 'lte:ssc_total_marks',
                function ($attribute, $value, $fail) use ($request) {
                    $total = floatval($request->input('ssc_total_marks'));
                    if ($total > 0 && floatval($value) < ($total * 0.33)) {
                        $fail('Obtained marks must be at least 33% of total marks.');
                    }
                }
            ],
            'ssc_passing_year' => ['required', 'digits:4', 'integer', 'min:' . (date('Y') - 10), 'max:' . date('Y')],
            'ssc_passing_session' => 'required|in:1st Annual,2nd Annual',
            'ssc_group' => 'required|string',
        ];

        if ($isHe) {
            // ADP/BS: program selection + HSSC record
            $heRules = [
                'program_id' => 'required|exists:programs,id',
                'hssc_bise_id' => 'required|exists:bise_records,id',
                'hssc_roll_no' => 'required|numeric|min:0',
                'hssc_reg_no' => 'required|string|max:50',
                'hssc_college_name' => 'required|string|max:255',
                'hssc_total_marks' => 'required|numeric|min:1',
                'hssc_obtained_marks' => [
                    'required', 'numeric', 'min:0', 'lte:hssc_total_marks',
                    function ($attribute, $value, $fail) use ($request) {
                        $total = floatval($request->input('hssc_total_marks'));
                        if ($total > 0 && floatval($value) < ($total * 0.33)) {
                            $fail('HSSC obtained marks must be at least 33% of total marks.');
                        }
                    }
                ],
                'hssc_passing_year' => ['required', 'digits:4', 'integer', 'min:' . (date('Y') - 10), 'max:' . date('Y')],
                'hssc_passing_session' => 'required|in:1st Annual,2nd Annual',
                'hssc_group' => 'required|string|max:100',
            ];

            $rules = array_merge($commonRules, $sscRules, $heRules);
        } else {
            // Inter: elective subjects + subject group
            $interRules = [
                'subject_group_id' => 'required|exists:subject_groups,id',
                'elective_subject_1' => 'required|exists:subjects,id',
                'elective_subject_2' => 'required|exists:subjects,id|different:elective_subject_1',
                'elective_subject_3' => 'required|exists:subjects,id|different:elective_subject_1|different:elective_subject_2',
            ];
            $rules = array_merge($commonRules, $sscRules, $interRules);
        }

        return $request->validate($rules, [
            'first_name.regex' => 'The first name must only contain letters and spaces.',
            'last_name.regex' => 'The last name must only contain letters and spaces.',
            'father_name.regex' => 'The father name must only contain letters and spaces.',
            'father_occupation.regex' => 'The occupation must only contain letters and spaces.',
            'cnic.regex' => 'The CNIC must follow the format 00000-0000000-0.',
            'father_cnic.regex' => 'The Father CNIC must follow the format 00000-0000000-0.',
            'student_contact.regex' => 'The contact number must follow the format 03XX-XXXXXXX.',
            'father_contact.regex' => 'The father contact number must follow the format 03XX-XXXXXXX.',
            'whatsapp_no.regex' => 'The WhatsApp number must follow the format 03XX-XXXXXXX.',
            'ssc_result_card.required' => 'The SSC Result Card image is required.',
            'b_form_pic.required' => 'B-Form/CNIC Picture is required.',
            'father_cnic_pic.required' => "Father's CNIC Picture is required.",
        ]);
    }
}
