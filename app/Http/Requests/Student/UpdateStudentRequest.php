<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && $user->isAdministrativeStaff();
    }

    /**
     * Prepare data for validation, ensuring sensible fallbacks for SSC and DOB if omitted.
     */
    protected function prepareForValidation(): void
    {
        $student = $this->route('student');
        $mergeData = [];

        // 1. Last Name & First Name auto-split if last_name is empty
        if (empty(trim($this->last_name ?? ''))) {
            $fullName = trim(preg_replace('/\s+/', ' ', $this->first_name ?? ''));
            $parts = explode(' ', $fullName);
            if (count($parts) > 1) {
                $mergeData['last_name'] = array_pop($parts);
                $mergeData['first_name'] = implode(' ', $parts);
            } else {
                $mergeData['last_name'] = $fullName;
            }
        }

        // 2. Domicile default
        if (empty(trim($this->domicile ?? ''))) {
            $mergeData['domicile'] = 'Bahawalnagar';
        }

        // 3. Student Contact fallback
        if (empty(trim($this->student_contact ?? ''))) {
            $mergeData['student_contact'] = $this->hssc_reg_no;
        }

        // 4. Date of birth fallback
        if (!$this->filled('date_of_birth') && $student) {
            $mergeData['date_of_birth'] = $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : '2005-01-01';
        }

        // 5. SSC Records default
        $ssc = $this->input('ssc', []);
        if (is_array($ssc)) {
            $idFallback = $student ? (string) $student->id : '1';
            $rollFallback = $this->college_roll_no ?? $idFallback;
            $regFallback = $this->hssc_reg_no ?? $idFallback;

            if (empty(trim($ssc['roll_no'] ?? '')))      $ssc['roll_no'] = $rollFallback;
            if (empty(trim($ssc['reg_no'] ?? '')))       $ssc['reg_no'] = $regFallback;
            if (empty($ssc['bise_id']))                  $ssc['bise_id'] = 1;
            if (empty(trim($ssc['school_name'] ?? '')))  $ssc['school_name'] = 'Govt. High School Chishtian';
            if (empty($ssc['total_mark']))               $ssc['total_mark'] = 1200;
            if (empty($ssc['obtained_mark']))            $ssc['obtained_mark'] = 600;
            if (empty($ssc['passing_year']))             $ssc['passing_year'] = (int) date('Y');
            if (empty($ssc['passing_session']))          $ssc['passing_session'] = '1st Annual';
            if (empty($ssc['ssc_group']))                $ssc['ssc_group'] = 'Science';
            $mergeData['ssc'] = $ssc;
        }

        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    public function rules(): array
    {
        $student = $this->route('student');
        $admissionId = $student?->admissions?->first()?->id ?? null;
        $attributeId = $student?->attribute?->id ?? 'NULL';

        return [
            'first_name'           => 'required|string|max:80',
            'last_name'            => 'required|string|max:80',
            'date_of_birth'        => 'required|date',
            'gender'               => 'nullable|in:Male,Female,Other',
            'student_cnic'         => 'nullable|string|max:15|unique:student_attributes,student_cnic,' . $attributeId,
            'blood_group_id'       => 'nullable|exists:blood_groups,id',
            'quota_id'             => 'nullable|exists:quotas,id',
            'subject_group_id'     => 'required|exists:subject_groups,id',
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
                    ->ignore($admissionId)
            ],
            'hssc_reg_no'                 => 'required|string|unique:admissions,hssc_reg_no,' . $admissionId,
            'current_class'               => 'nullable|string',
            'status'                      => 'required|in:Active,Struck Off,Graduated,Dropped,Suspended,Admitted,struck_off',
            'ssc.bise_id'                 => 'required|exists:bise_records,id',
            'ssc.roll_no'                 => 'required|string',
            'ssc.reg_no'                  => 'required|string',
            'ssc.school_name'             => 'required|string',
            'ssc.total_mark'              => 'required|integer',
            'ssc.obtained_mark'           => 'required|integer|lte:ssc.total_mark',
            'ssc.passing_year'            => 'required|digits:4|integer',
            'ssc.passing_session'         => 'required|in:1st Annual,2nd Annual',
            'ssc.ssc_group'               => 'required|in:Science,Arts',
            'elective_subjects'           => 'nullable|array|size:3',
            'elective_subjects.*'         => 'nullable|exists:subjects,id',
            'compulsory_subjects'         => 'nullable|array',
            'compulsory_subjects.*'       => 'nullable|exists:subjects,id',
            'picture'                     => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ];
    }
}
