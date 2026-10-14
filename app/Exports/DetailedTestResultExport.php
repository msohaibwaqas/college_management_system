<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DetailedTestResultExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $reportData;
    protected $withSubjects;

    public function __construct($reportData, $withSubjects = true)
    {
        $this->reportData = $reportData;
        $this->withSubjects = $withSubjects;
    }

    public function collection()
    {
        return $this->reportData;
    }

    public function headings(): array
    {
        return [
            'Roll No',
            'Student Name',
            'Father Name',
            'English',
            'Urdu / Alt',
            'Islamic / Alt',
            'Tarjuma Quran',
            'Pak Studies',
            'Elective 1',
            'Elective 2',
            'Elective 3',
            'Pass',
            'Fail',
            'Absent',
        ];
    }

    public function map($row): array
    {
        $format = function($data) {
            $marks = $data['marks'];
            if ($this->withSubjects && $data['name'] !== 'N/A') {
                return $data['name'] . ' (' . $marks . ')';
            }
            return $marks;
        };

        return [
            $row['roll_no'],
            $row['student_name'],
            $row['father_name'],
            $format($row['english']),
            $format($row['urdu']),
            $format($row['islamic']),
            $format($row['tarjuma']),
            $format($row['pak_studies']),
            $format($row['elective_1']),
            $format($row['elective_2']),
            $format($row['elective_3']),
            $row['pass_summary'],
            $row['fail_summary'],
            $row['absent_summary'],
        ];
    }
}
