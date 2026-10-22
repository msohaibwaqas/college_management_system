<?php

namespace App\Http\Requests\HeAdmission;

use App\Models\SscRecord;
use Illuminate\Foundation\Http\FormRequest;

class StoreHeAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdministrativeStaff() || auth()->user()->isSuperAdmin());
    }

    public function rules(): array
    {
        return [
            // Personal
            'first_name'           => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z\s]+$/'],
            'last_name'            => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z\s]+$/'],
            'gender'               => 'required|in:Male,Female,Other',
            'date_of_birth'        => 'required|date',
            'student_cnic'         => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'blood_group_id'       => 'nullable|exists:blood_groups,id',
            'quota_id'             => 'nullable|exists:quotas,id',
            'picture'              => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'religion'             => 'required|string|max:50',
            'nationality'          => 'required|string|max:50',

            // Father
            'father_name'          => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z\s]+$/'],
            'father_cnic'          => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'father_contact'       => ['nullable', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'father_occupation'    => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-Z\s]*$/'],
            'father_annual_income' => 'nullable|integer',

            // Contact
            'student_contact'      => ['required', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'whatsapp_no'          => ['nullable', 'string', 'regex:/^03\d{2}-\d{7}$/'],
            'email'                => 'nullable|email:filter|max:120',
            'address'              => 'required|string',

            // HE Admission
            'program_id'           => 'required|exists:programs,id',

            // SSC Record
            'ssc_bise_id'          => 'required|exists:bise_records,id',
            'ssc_roll_no'          => 'required|numeric|min:0',
            'ssc_reg_no'           => [
                'required', 'string', 'max:50',
                function ($attribute, $value, $fail) {
                    $rollNo = $this->input('ssc_roll_no');
                    if (!$rollNo) return;
                    $existsInStudents = SscRecord::where('roll_no', $rollNo)->where('reg_no', $value)->exists();
                    if ($existsInStudents) {
                        $fail('This student (SSC Roll/Reg) is already admitted in the system.');
                    }
                }
            ],
            'ssc_school_name'      => 'required|string|max:255',
            'ssc_total_mark'       => 'required|numeric|min:1',
            'ssc_obtained_mark'    => 'required|numeric|min:0|lte:ssc_total_mark',
            'ssc_passing_year'     => 'required|integer|min:1900|max:2099',
            'ssc_passing_session'  => 'required|string',
            'ssc_group'            => 'required|string',

            // HSSC Record
            'hssc_bise_id'         => 'required|exists:bise_records,id',
            'hssc_roll_no'         => 'required|numeric|min:0',
            'hssc_reg_no_academic' => 'required|string|max:50',
            'hssc_college_name'    => 'required|string|max:255',
            'hssc_total_mark'      => 'required|numeric|min:1',
            'hssc_obtained_mark'   => 'required|numeric|min:0|lte:hssc_total_mark',
            'hssc_passing_year'    => 'required|integer|min:1900|max:2099',
            'hssc_passing_session' => 'required|string',
            'hssc_group'           => 'required|string',
        ];
    }
}
