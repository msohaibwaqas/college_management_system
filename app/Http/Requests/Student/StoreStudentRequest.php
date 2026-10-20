<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && $user->isAdministrativeStaff();
    }

    public function rules(): array
    {
        return [
            'first_name'           => 'required|string|max:80',
            'last_name'            => 'required|string|max:80',
            'date_of_birth'        => 'required|date',
            'gender'               => 'required|in:Male,Female,Other',
            'student_cnic'         => 'nullable|string|max:15|unique:student_attributes,student_cnic',
            'blood_group_id'       => 'nullable|exists:blood_groups,id',
            'quota_id'             => 'nullable|exists:quotas,id',
            'subject_group_id'     => 'required|exists:subject_groups,id',
            'religion'             => 'nullable|string|max:50',
            'nationality'          => 'nullable|string|max:50',
            'domicile'             => 'nullable|string|max:50',
            'father_name'          => 'required|string|max:120',
            'father_cnic'          => 'nullable|string|max:15',
            'father_contact'       => 'nullable|string|max:20',
            'father_occupation'    => 'nullable|string|max:120',
            'father_annual_income' => 'nullable|integer',
            'student_contact'      => 'nullable|string|max:20',
            'whatsapp_no'          => 'nullable|string|max:20',
            'email'                => 'nullable|email|max:120',
            'address'              => 'nullable|string',
            'admission_year'       => 'required|digits:4|integer',
            'academic_year'        => 'required|digits:4|integer|gte:admission_year',
            'program_type'         => 'required|in:Inter,ADP,BS,DIT',
            'program_name'         => 'nullable|string|max:100',
            'shift'                => 'nullable|in:Morning,Evening',
            'semester'             => 'nullable|integer|min:1|max:8',
            'college_roll_no'      => [
                'required',
                'string',
                Rule::unique('admissions', 'college_roll_no')
                    ->where(function ($query) {
                        return $query->where('admission_year', $this->admission_year)
                                     ->where('program_type', $this->program_type)
                                     ->where('program_name', trim($this->program_name ?? 'FSc/FA'));
                    })
            ],
            'hssc_reg_no'                 => 'required|string|unique:admissions,hssc_reg_no',
            'current_class'               => 'nullable|string',
            'ssc_records'                 => 'nullable|array',
            'ssc_records.*.bise_id'       => 'required|exists:bise_records,id',
            'ssc_records.*.roll_no'       => 'required|string',
            'ssc_records.*.reg_no'        => 'required|string',
            'ssc_records.*.school_name'   => 'required|string',
            'ssc_records.*.total_mark'    => 'required|integer',
            'ssc_records.*.obtained_mark' => 'required|integer|lte:ssc_records.*.total_mark',
            'ssc_records.*.passing_year'  => 'required|digits:4|integer',
            'ssc_records.*.passing_session' => 'required|in:1st Annual,2nd Annual',
            'ssc_records.*.ssc_group'     => 'required|in:Science,Arts',
            'elective_subjects'           => 'nullable|array',
            'elective_subjects.*'         => 'nullable|exists:subjects,id',
            'compulsory_subjects'         => 'nullable|array',
            'compulsory_subjects.*'       => 'nullable|exists:subjects,id',
            'picture'                     => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ];
    }
}
