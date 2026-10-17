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
            $this->subjectName . ' (Combined Marks | Teacher)',
            '11th BISE Marks',
            '12th BISE Marks',
            'Total BISE Marks',
            'Teacher Name',
            'Status'
        ];
    }

    public function map($row): array
    {
        $m11 = $row['marks_11'];
        $m12 = $row['marks_12'];
        if (!empty($row['details_12'])) {
            $m12 .= ' [' . $row['details_12'] . ']';
        }

        $combined = 'Total: ' . $row['combined_marks'] . ' (11th: ' . $m11 . ', 12th: ' . $m12 . ') | ' . $row['teacher_name'];

        return [
            $row['roll_no'],
            $row['bise_roll_11'],
            $row['bise_roll_12'],
            $row['student_name'],
            $row['father_name'],
            $combined,
            $m11,
            $m12,
            $row['combined_marks'],
            $row['teacher_name'],
            $row['status']
        ];
    }
}
