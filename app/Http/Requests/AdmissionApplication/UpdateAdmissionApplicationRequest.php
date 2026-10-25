<?php

namespace App\Http\Requests\AdmissionApplication;

use App\Models\AdmissionApplication;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdmissionApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $application = $this->route('application');
        $applicationId = $application instanceof AdmissionApplication ? $application->id : $application;

        return [
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
                function ($attribute, $value, $fail) use ($applicationId) {
                    $rollNo = $this->input('ssc_roll_no');
                    if (!$rollNo) return;

                    $existsInApps = AdmissionApplication::where('ssc_roll_no', $rollNo)
                        ->where('ssc_reg_no', $value)
                        ->where('status', '!=', 'rejected')
                        ->where('id', '!=', $applicationId)
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
                function ($attribute, $value, $fail) {
                    $total = floatval($this->input('ssc_total_marks'));
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
        ];
    }
}
