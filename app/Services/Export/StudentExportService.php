<?php

namespace App\Services\Export;

use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentExportService
{
    /**
     * Stream a CSV download with proper headers and callback.
     */
    public function streamCsv(string $filename, array $headerColumns, iterable $records, callable $rowMapper): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '_' . date('Y-m-d_His') . '.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($headerColumns, $records, $rowMapper) {
            $handle = fopen('php://output', 'w');
            
            // Output UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, $headerColumns);

            foreach ($records as $record) {
                fputcsv($handle, $rowMapper($record));
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export HSSC Students collection.
     */
    public function exportHssc(iterable $students): StreamedResponse
    {
        $columns = [
            'Student Name',
            'Father Name',
            'College Roll No',
            'BISE Roll 11',
            'BISE Roll 12',
            'Admission Year',
            'Elective 1',
            'Elective 2',
            'Elective 3',
        ];

        return $this->streamCsv('students_hssc_export', $columns, $students, function ($student) {
            $admission = $student->admissions?->first();
            $electives = $student->electives ? $student->electives->sortBy('sequence') : collect();

            return [
                trim($student->first_name . ' ' . $student->last_name),
                $student->father?->name ?? '',
                $admission?->college_roll_no ?? '',
                $admission?->bise_roll_11 ?? '',
                $admission?->bise_roll_12 ?? '',
                $admission?->admission_year ?? '',
                $electives->get(0)?->subject?->name ?? '',
                $electives->get(1)?->subject?->name ?? '',
                $electives->get(2)?->subject?->name ?? '',
            ];
        });
    }

    /**
     * Export Higher Education (BS/ADP) admissions collection.
     */
    public function exportHe(iterable $admissions): StreamedResponse
    {
        $columns = [
            'Roll No',
            'Student Name',
            'Father Name',
            'Program',
            'Current Semester',
            'Admission Year',
            'Contact',
            'Status',
            'CNIC',
            'Gender',
        ];

        return $this->streamCsv('he_admissions_export', $columns, $admissions, function ($admission) {
            return [
                $admission->roll_no ?? 'N/A',
                $admission->student?->full_name ?? 'N/A',
                $admission->student?->father?->name ?? 'N/A',
                $admission->program?->name ?? 'N/A',
                'Semester ' . $admission->current_semester,
                $admission->admission_year,
                $admission->student?->contact?->student_contact ?? 'N/A',
                $admission->status,
                $admission->student?->cnic ?? 'N/A',
                $admission->student?->gender ?? 'N/A',
            ];
        });
    }
}
