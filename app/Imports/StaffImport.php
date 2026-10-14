<?php

namespace App\Imports;

use App\Models\Staff;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StaffImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new Staff([
            'first_name' => $row['first_name'],
            'last_name'  => $row['last_name'],
            'email'      => $row['email'],
            'phone'      => $row['phone'],
            'designation'=> $row['designation'],
            'gender'     => $row['gender'] ?? 'male',
            'status'     => 'active',
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required',
            'email'      => 'required|email|unique:staff,email',
        ];
    }
}
