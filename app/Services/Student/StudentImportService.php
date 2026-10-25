<?php

namespace App\Services\Student;

use App\Models\Student;
use App\Models\Father;
use App\Models\StudentAttribute;
use App\Models\Admissions;
use App\Models\HeAdmission;
use App\Models\Program;
use App\Models\Contact;
use App\Models\StudentCompulsorySubject;
use App\Models\StudentElectiveSubject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service managing CSV parsing, validation, row verification, and database
 * persistence for bulk student imports across HSSC and Higher Education programs.
 */
class StudentImportService
{
    /**
     * Parses and validates raw CSV data rows.
     */
    public function parseAndValidate(string $filePath, string $programType): array
    {
        $data = array_map('str_getcsv', file($filePath));

        // Remove header row if present
        if (count($data) > 0) {
            $firstCell = strtolower(trim($data[0][0]));
            if ($firstCell == 'student name' || $firstCell == 'email address') {
                array_shift($data);
            }
        }

        if (count($data) === 0) {
            return [
                'success' => false,
                'message' => 'The CSV file contains no data rows.'
            ];
        }

        $result = $programType === 'HE' 
            ? $this->validateHeRows($data) 
            : $this->validateHsscRows($data);

        return array_merge(['success' => true], $result);
    }

    /**
     * Validates HSSC rows.
     */
    public function validateHsscRows(array $data): array
    {
        $rows = [];
        $approvedCount = 0;
        $errorCount = 0;

        $seenCnics = [];
        $seenRollNos = [];
        $seenHsscRegNos = [];

        $headers = ['Student Name', 'Father Name', 'CNIC', 'College Roll No', 'HSSC Reg No', 'Admission Year', 'Sub1 ID', 'Sub2 ID', 'Sub3 ID', 'Sub4 ID', 'Sub5 ID', 'Sub6 ID', 'Sub7 ID', 'Sub8 ID'];

        foreach ($data as $index => $row) {
            $rowNumber = $index + 1;
            $error = null;

            if (count($row) < 14) {
                $error = "Insufficient columns. Expected 14, got " . count($row) . ".";
                $rows[] = ['original' => $row, 'data' => $row, 'status' => 'Error', 'error' => $error, 'row_number' => $rowNumber];
                $errorCount++;
                continue;
            }

            $studentName = trim($row[0]);
            $fatherName = trim($row[1]);
            $cnic = trim($row[2]);
            $rollNo = trim($row[3]);
            $hsscRegNo = trim($row[4]);
            $admissionYear = trim($row[5]);
            $rollNoYearKey = $rollNo . '_' . $admissionYear;

            // Basic validation
            if (empty($studentName) || empty($fatherName) || empty($cnic) || empty($rollNo) || empty($hsscRegNo) || empty($admissionYear)) {
                $error = "Missing required fields.";
            }
            // Duplicates within CSV
            elseif (isset($seenCnics[$cnic])) {
                $error = "Duplicate CNIC ($cnic) in CSV (first at row {$seenCnics[$cnic]}).";
            }
            elseif (isset($seenRollNos[$rollNoYearKey])) {
                $error = "Duplicate Roll No ($rollNo) for year ($admissionYear) in CSV (first at row {$seenRollNos[$rollNoYearKey]}).";
            }
            elseif (isset($seenHsscRegNos[$hsscRegNo])) {
                $error = "Duplicate HSSC Reg No ($hsscRegNo) in CSV (first at row {$seenHsscRegNos[$hsscRegNo]}).";
            }
            // Duplicates in database
            elseif (StudentAttribute::where('student_cnic', $cnic)->exists()) {
                $error = "CNIC ($cnic) already exists in database.";
            }
            elseif (Admissions::where('college_roll_no', $rollNo)->where('admission_year', $admissionYear)->exists()) {
                $error = "Roll No ($rollNo) for year ($admissionYear) already exists in database.";
            }
            elseif (Admissions::where('hssc_reg_no', $hsscRegNo)->exists()) {
                $error = "HSSC Reg No ($hsscRegNo) already exists in database.";
            }

            if ($error) {
                $rows[] = ['original' => $row, 'data' => $row, 'status' => 'Error', 'error' => $error, 'row_number' => $rowNumber];
                $errorCount++;
            } else {
                $seenCnics[$cnic] = $rowNumber;
                $seenRollNos[$rollNoYearKey] = $rowNumber;
                $seenHsscRegNos[$hsscRegNo] = $rowNumber;

                $rows[] = ['original' => $row, 'data' => $row, 'status' => 'Approved', 'error' => null, 'row_number' => $rowNumber];
                $approvedCount++;
            }
        }

        return ['rows' => $rows, 'headers' => $headers, 'approved_count' => $approvedCount, 'error_count' => $errorCount];
    }

    /**
     * Validates HE rows.
     */
    public function validateHeRows(array $data): array
    {
        $rows = [];
        $approvedCount = 0;
        $errorCount = 0;

        $seenCnics = [];
        $seenRollNos = [];

        $headers = ['Email Address', 'College Roll Number', 'University Registration No.', 'University Roll Number', 'Student Name', 'CNIC', 'Mobile No', 'Date of Birth', "Father's / Guardian Name", "Father's / Guardian Mobile Number", 'Degree ID', 'Semester', 'Admission Year'];

        foreach ($data as $index => $row) {
            $rowNumber = $index + 1;
            $error = null;

            if (count($row) < 13) {
                $error = "Insufficient columns. Expected 13, got " . count($row) . ".";
                $rows[] = ['original' => $row, 'data' => $row, 'status' => 'Error', 'error' => $error, 'row_number' => $rowNumber];
                $errorCount++;
                continue;
            }

            $email = trim($row[0]);
            $rollNo = trim($row[1]);
            $uniRegNo = trim($row[2]);
            $uniRollNo = trim($row[3]);
            $studentName = trim($row[4]);
            $cnic = trim($row[5]);
            $mobileNo = trim($row[6]);
            $dob = trim($row[7]);
            $fatherName = trim($row[8]);
            $fatherMobile = trim($row[9]);
            $programId = trim($row[10]);
            $currentSemester = trim($row[11]);
            $admissionYear = trim($row[12]);

            if (empty($admissionYear)) {
                $admissionYear = date('Y');
            }

            $rollNoYearKey = $rollNo . '_' . $admissionYear;

            // Basic validation
            if (empty($studentName) || empty($fatherName) || empty($cnic) || empty($rollNo) || empty($programId) || empty($currentSemester) || empty($admissionYear)) {
                $error = "Missing required fields (Name, Father, CNIC, Roll No, Degree ID, Semester, Admission Year).";
            }
            // Verify Program exists
            elseif (!Program::where('id', $programId)->exists()) {
                $error = "Degree ID ($programId) not found in the system.";
            }
            // Duplicates within CSV
            elseif (isset($seenCnics[$cnic])) {
                $error = "Duplicate CNIC ($cnic) in CSV (first at row {$seenCnics[$cnic]}).";
            }
            elseif (isset($seenRollNos[$rollNoYearKey])) {
                $error = "Duplicate Roll No ($rollNo) for year ($admissionYear) in CSV (first at row {$seenRollNos[$rollNoYearKey]}).";
            }
            // Duplicates in database
            elseif (StudentAttribute::where('student_cnic', $cnic)->exists()) {
                $error = "CNIC ($cnic) already exists in database.";
            }
            elseif (HeAdmission::where('roll_no', $rollNo)->where('admission_year', $admissionYear)->exists()) {
                $error = "Roll No ($rollNo) for year ($admissionYear) already exists in database.";
            }

            if ($error) {
                $rows[] = ['original' => $row, 'data' => $row, 'status' => 'Error', 'error' => $error, 'row_number' => $rowNumber];
                $errorCount++;
            } else {
                $seenCnics[$cnic] = $rowNumber;
                $seenRollNos[$rollNoYearKey] = $rowNumber;

                $rows[] = ['original' => $row, 'data' => $row, 'status' => 'Approved', 'error' => null, 'row_number' => $rowNumber];
                $approvedCount++;
            }
        }

        return ['rows' => $rows, 'headers' => $headers, 'approved_count' => $approvedCount, 'error_count' => $errorCount];
    }

    /**
     * Executes batch insertion of approved rows inside a database transaction.
     */
    public function importApprovedRows(array $approvedRows, string $programType): int
    {
        return DB::transaction(function () use ($approvedRows, $programType) {
            $successCount = 0;
            if ($programType === 'HE') {
                foreach ($approvedRows as $row) {
                    $this->insertHeRow($row['data']);
                    $successCount++;
                }
            } else {
                foreach ($approvedRows as $row) {
                    $this->insertHsscRow($row['data']);
                    $successCount++;
                }
            }
            return $successCount;
        });
    }

    /**
     * Persists single HSSC student row.
     */
    public function insertHsscRow(array $row): void
    {
        $studentName = trim($row[0]);
        $fatherName = trim($row[1]);
        $cnic = trim($row[2]);
        $rollNo = trim($row[3]);
        $hsscRegNo = trim($row[4]);
        $admissionYear = trim($row[5]);

        $student = Student::create([
            'first_name' => $studentName,
            'date_of_birth' => '2000-01-01',
            'created_by' => Auth::id(),
        ]);

        Father::create([
            'student_id' => $student->id,
            'name' => $fatherName,
            'created_by' => Auth::id(),
        ]);

        StudentAttribute::create([
            'student_id' => $student->id,
            'student_cnic' => $cnic,
            'created_by' => Auth::id(),
        ]);

        Admissions::create([
            'student_id' => $student->id,
            'college_roll_no' => $rollNo,
            'hssc_reg_no' => $hsscRegNo,
            'admission_year' => $admissionYear,
            'academic_year' => $admissionYear,
            'current_class' => '11',
            'status' => 'Active',
            'created_by' => Auth::id(),
        ]);

        // Assign Compulsory Subjects (Indices 6-10)
        for ($i = 6; $i <= 10; $i++) {
            if (!empty($row[$i])) {
                StudentCompulsorySubject::create([
                    'student_id' => $student->id,
                    'subject_id' => $row[$i],
                    'class_level' => '11',
                    'created_by' => Auth::id(),
                ]);
            }
        }

        // Assign Elective Subjects (Indices 11-13)
        $sequence = 1;
        for ($i = 11; $i <= 13; $i++) {
            if (!empty($row[$i])) {
                StudentElectiveSubject::create([
                    'student_id' => $student->id,
                    'subject_id' => $row[$i],
                    'sequence' => $sequence++,
                    'created_by' => Auth::id(),
                ]);
            }
        }
    }

    /**
     * Persists single Higher Education student row.
     */
    public function insertHeRow(array $row): void
    {
        $email = trim($row[0]);
        $rollNo = trim($row[1]);
        $uniRegNo = trim($row[2]);
        $uniRollNo = trim($row[3]);
        $studentName = trim($row[4]);
        $cnic = trim($row[5]);
        $mobileNo = trim($row[6]);
        $dob = trim($row[7]);
        $fatherName = trim($row[8]);
        $fatherMobile = trim($row[9]);
        $programId = trim($row[10]);
        $currentSemester = trim($row[11]);
        $admissionYear = trim($row[12]);

        if (empty($admissionYear)) {
            $admissionYear = date('Y');
        }

        $dobParsed = date('Y-m-d', strtotime(str_replace('/', '-', $dob)));
        if (!$dobParsed || $dobParsed == '1970-01-01') {
            $dobParsed = '2000-01-01';
        }

        $student = Student::create([
            'first_name' => $studentName,
            'date_of_birth' => $dobParsed,
            'created_by' => Auth::id(),
        ]);

        Father::create([
            'student_id' => $student->id,
            'name' => $fatherName,
            'contact' => $fatherMobile ?: null,
            'created_by' => Auth::id(),
        ]);

        Contact::create([
            'student_id' => $student->id,
            'email' => $email ?: null,
            'student_contact' => $mobileNo ?: null,
            'created_by' => Auth::id(),
        ]);

        StudentAttribute::create([
            'student_id' => $student->id,
            'student_cnic' => $cnic,
            'created_by' => Auth::id(),
        ]);

        HeAdmission::create([
            'student_id' => $student->id,
            'program_id' => $programId,
            'roll_no' => $rollNo,
            'uni_reg_no' => $uniRegNo ?: null,
            'uni_roll_no' => $uniRollNo ?: null,
            'admission_year' => $admissionYear,
            'current_semester' => $currentSemester,
            'status' => 'Active',
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Streams CSV file with appended row-level verification status.
     */
    public function streamValidatedCsv(array $rows, array $headers, string $programType): StreamedResponse
    {
        $filename = strtolower($programType) . '_import_validated.csv';
        $responseHeaders = [
            'Content-Type' => 'text/csv',
            "Content-Disposition" => "attachment; filename=" . $filename,
        ];

        $callback = function() use ($rows, $headers) {
            $file = fopen('php://output', 'w');
            $csvHeaders = $headers;
            $csvHeaders[] = 'Status';
            fputcsv($file, $csvHeaders);

            foreach ($rows as $row) {
                $csvRow = $row['original'];
                $csvRow[] = $row['status'] === 'Approved' ? 'Approved' : $row['error'];
                fputcsv($file, $csvRow);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $responseHeaders);
    }

    /**
     * Streams sample CSV template for either HSSC or HE.
     */
    public function streamSampleCsv(string $type = 'HSSC'): StreamedResponse
    {
        if (strtoupper($type) === 'HE') {
            $filename = 'he_students_import_sample.csv';
            $columns = [
                'Email Address', 'College Roll Number', 'University Registration No.',
                'University Roll Number', 'Student Name', 'CNIC', 'Mobile No',
                'Date of Birth', "Father's / Guardian Name", "Father's / Guardian Mobile Number",
                'Degree ID', 'Semester', 'Admission Year'
            ];
            $sampleRow = [
                'jane@example.com', 'B-1001', 'REG-UNI-2025', 'UNI-2025-001',
                'Jane Doe', '12345-1234567-2', '03001234567', '2005-08-15',
                'John Doe', '03007654321', '1', '1', '2025'
            ];
        } else {
            $filename = 'students_import_sample.csv';
            $columns = [
                'Student Name', 'Father Name', 'CNIC', 'College Roll No',
                'HSSC Reg No', 'Admission Year', 'Sub1 ID', 'Sub2 ID',
                'Sub3 ID', 'Sub4 ID', 'Sub5 ID', 'Sub6 ID', 'Sub7 ID', 'Sub8 ID'
            ];
            $sampleRow = [
                'John Doe', 'Jane Doe', '12345-1234567-1', 'R-1001',
                'REG-2025-001', '2025', '1', '2', '3', '4', '5', '6', '7', '8'
            ];
        }

        $headers = [
            'Content-Type' => 'text/csv',
            "Content-Disposition" => "attachment; filename=" . $filename,
        ];

        $callback = function() use ($columns, $sampleRow) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $sampleRow);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
