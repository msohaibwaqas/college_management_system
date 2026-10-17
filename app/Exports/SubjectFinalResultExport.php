<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SubjectFinalResultExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $reportData;
    protected $subjectName;
    protected $admissionYear;

    public function __construct($reportData, $subjectName, $admissionYear)
    {
        $this->reportData = $reportData;
        $this->subjectName = $subjectName;
        $this->admissionYear = $admissionYear;
    }

    public function collection()
    {
        return $this->reportData;
    }

    public function headings(): array
    {
        return [
            'Roll No',
            'BISE 11th Roll No',
            'BISE 12th Roll No',
            'Student Name',
            'Father Name',
            $this->subjectName . ' (Marks in 12th BISE | Teacher)',
            '12th BISE Marks',
            'Teacher Name',
            'Status'
        ];
    }

    public function map($row): array
    {
        $marksDisplay = $row['marks'];
        if (!empty($row['details'])) {
            $marksDisplay .= ' [' . $row['details'] . ']';
        }

        $combined = $marksDisplay . ' | ' . $row['teacher_name'];

        return [
            $row['roll_no'],
            $row['bise_roll_11'],
            $row['bise_roll_12'],
            $row['student_name'],
            $row['father_name'],
            $combined,
            $marksDisplay,
            $row['teacher_name'],
            $row['status']
        ];
    }
}
