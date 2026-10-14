<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Admissions;
use App\Models\Contact;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\DB;

class StudentImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return DB::transaction(function () use ($row) {
            // 1. Create Student
            $student = Student::create([
                'first_name' => $row['first_name'],
                'last_name'  => $row['last_name'],
                'gender'     => $row['gender'] ?? 'Male',
                'cnic'       => $row['cnic'] ?? null,
            ]);

            // 2. Create Admission (for Roll No)
            if (isset($row['roll_no'])) {
                Admissions::create([
                    'student_id' => $student->id,
                    'college_roll_no' => $row['roll_no'],
                    'academic_year' => $row['academic_year'] ?? date('Y'),
                    'current_class' => $row['class'] ?? '11',
                    'status' => 'Active',
                ]);
            }

            // 3. Create Contact
            if (isset($row['email']) || isset($row['phone'])) {
                Contact::create([
                    'student_id' => $student->id,
                    'email' => $row['email'] ?? null,
                    'student_contact' => $row['phone'] ?? null,
                ]);
            }

            return $student;
        });
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required',
            'roll_no'    => 'required|unique:admissions,college_roll_no',
        ];
    }
}
