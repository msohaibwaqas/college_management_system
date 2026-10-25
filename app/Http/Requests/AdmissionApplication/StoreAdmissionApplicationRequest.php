<?php

namespace App\Http\Requests\AdmissionApplication;

use App\Models\AdmissionApplication;
use App\Models\SscRecord;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdmissionApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $programType = $this->input('program_type', 'Inter');
        $isHe = in_array($programType, ['ADP', 'BS']);

        $rules = [
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
                function ($attribute, $value, $fail) {
                    $rollNo = $this->input('ssc_roll_no');
                    if (!$rollNo) return;
                    $existsInStudents = SscRecord::where('roll_no', $rollNo)->where('reg_no', $value)->exists();
                    if ($existsInStudents) {
                        $fail('This student (SSC Roll/Reg) is already admitted in the system.');
                        return;
                    }
                    $existsInApps = AdmissionApplication::where('ssc_roll_no', $rollNo)
                        ->where('ssc_reg_no', $value)
                        ->where('status', '!=', 'rejected')
                        ->exists();
                    if ($existsInApps) {
                        $fail('An application with this SSC Roll and Registration number already exists.');
                    }
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
            $rules['program_id'] = 'required|exists:programs,id';
            $rules['hssc_bise_id'] = 'required|exists:bise_records,id';
            $rules['hssc_roll_no'] = 'required|numeric|min:0';
            $rules['hssc_reg_no'] = 'required|string|max:50';
            $rules['hssc_college_name'] = 'required|string|max:255';
            $rules['hssc_total_marks'] = 'required|numeric|min:1';
            $rules['hssc_obtained_marks'] = ['required', 'numeric', 'min:0', 'lte:hssc_total_marks'];
            $rules['hssc_passing_year'] = ['required', 'digits:4', 'integer', 'min:' . (date('Y') - 10), 'max:' . date('Y')];
            $rules['hssc_passing_session'] = 'required|in:1st Annual,2nd Annual';
            $rules['hssc_group'] = 'required|string|max:100';
        } else {
            $rules['subject_group_id'] = 'required|exists:subject_groups,id';
            $rules['elective_subject_1'] = 'required|exists:subjects,id';
            $rules['elective_subject_2'] = 'required|exists:subjects,id|different:elective_subject_1';
            $rules['elective_subject_3'] = 'required|exists:subjects,id|different:elective_subject_1|different:elective_subject_2';
        }

        return $rules;
    }
}
