<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DetailedFinalResultExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'BISE 11th Roll No',
            'BISE 12th Roll No',
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
        ];
    }

    public function map($row): array
    {
        $format = function($data) {
            $marks = $data['marks'];
            $details = $data['details'] ? " [" . $data['details'] . "]" : "";
            $display = $marks . $details;

            if ($this->withSubjects && $data['name'] !== 'N/A') {
                return $data['name'] . ' (' . $display . ')';
            }
            return $display;
        };

        return [
            $row['roll_no'],
            $row['bise_roll_11'],
            $row['bise_roll_12'],
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
        ];
    }
}
