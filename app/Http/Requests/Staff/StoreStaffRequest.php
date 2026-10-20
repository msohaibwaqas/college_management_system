<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && $user->isAdministrativeStaff();
    }

    public function rules(): array
    {
        return [
            'staff_id'                     => 'required|string|max:50|unique:staff,staff_id',
            'first_name'                   => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z\s]+$/'],
            'last_name'                    => ['nullable', 'string', 'max:80', 'regex:/^[a-zA-Z\s]*$/'],
            'father_name'                  => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z\s]+$/'],
            'cnic'                         => ['required', 'string', 'max:15', 'unique:staff,cnic', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'contact_number'               => ['nullable', 'string', 'max:20', 'regex:/^03\d{2}-\d{7}$/'],
            'whatsapp_number'              => ['nullable', 'string', 'max:20', 'regex:/^03\d{2}-\d{7}$/'],
            'date_of_birth'                => 'required|date',
            'category'                     => 'required|in:teaching,non_teaching',
            'designation'                  => 'required|string|max:100',
            'bps'                          => 'required|integer|min:1|max:22',
            'gender'                       => 'required|in:male,female',
            'status'                       => 'required|in:active,left,transferred,retired,suspended',
            'email'                        => 'nullable|email|max:120|unique:staff,email',
            'address'                      => 'nullable|string',
            'blood_group_id'               => 'nullable|exists:blood_groups,id',
            'highest_qualification'        => 'nullable|string|max:100',
            'entry_date_govt_service'      => 'nullable|date',
            'hed_joining_date'             => 'nullable|date',
            'current_college_joining_date' => 'nullable|date',
            'picture'                      => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'subject_specializations'      => 'nullable|array',
            'subject_specializations.*'    => 'exists:subjects,id',
            'bs_subject_specializations'   => 'nullable|array',
            'bs_subject_specializations.*' => 'exists:bs_subjects,id',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.regex'      => 'The first name must only contain letters and spaces.',
            'last_name.regex'       => 'The last name must only contain letters and spaces.',
            'father_name.regex'     => 'The father name must only contain letters and spaces.',
            'cnic.regex'            => 'The CNIC must follow the format 00000-0000000-0.',
            'contact_number.regex'  => 'The contact number must follow the format 03XX-XXXXXXX.',
            'whatsapp_number.regex' => 'The WhatsApp number must follow the format 03XX-XXXXXXX.',
        ];
    }
}
