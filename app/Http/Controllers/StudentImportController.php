<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Father;
use App\Models\StudentAttribute;
use App\Models\Admissions;
use App\Models\HeAdmission;
use App\Models\StudentCompulsorySubject;
use App\Models\StudentElectiveSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StudentImportController extends Controller
{
    public function index()
    {
        return view('students.import');
    }

    /**
     * Step 1: Validate the CSV and show a preview with row-by-row status.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
            'program_type' => 'required|in:HSSC,HE',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        $programType = $request->program_type;

        // Remove header row
        $headers = [];
        if (count($data) > 0) {
            $firstCell = strtolower(trim($data[0][0]));
            if ($firstCell == 'student name' || $firstCell == 'email address') {
                $headers = array_shift($data);
            }
        }

        if (count($data) === 0) {
            return redirect()->back()->with('error', 'The CSV file contains no data rows.');
        }

        if ($programType === 'HE') {
            $result = $this->validateHeRows($data);
        } else {
            $result = $this->validateHsscRows($data);
        }

        // Store validated data and rows in session for step 2
        session([
            'import_validated_rows' => $result['rows'],
            'import_program_type' => $programType,
            'import_headers' => $result['headers'],
            'import_approved_count' => $result['approved_count'],
            'import_error_count' => $result['error_count'],
        ]);

        return redirect()->route('students.import.preview');
    }

    /**
     * Step 2: Show preview page with validation results.
     */
    public function preview()
    {
        $rows = session('import_validated_rows');
        $programType = session('import_program_type');
        $headers = session('import_headers');
        $approvedCount = session('import_approved_count', 0);
        $errorCount = session('import_error_count', 0);

        if (!$rows) {
            return redirect()->route('students.import')->with('error', 'No import data found. Please upload a CSV file first.');
        }

        return view('students.import-preview', compact('rows', 'programType', 'headers', 'approvedCount', 'errorCount'));
    }

    /**
     * Step 3: Actually perform the import after user confirms.
     */
    public function confirmImport(Request $request)
    {
        $rows = session('import_validated_rows');
        $programType = session('import_program_type');

        if (!$rows) {
            return redirect()->route('students.import')->with('error', 'No import data found. Please upload a CSV file first.');
        }

        // Only import approved rows
        $approvedRows = array_filter($rows, fn($r) => $r['status'] === 'Approved');

        if (count($approvedRows) === 0) {
            return redirect()->route('students.import')->with('error', 'No approved rows to import.');
        }

        DB::beginTransaction();
        try {
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

            DB::commit();

            // Clear session
            session()->forget(['import_validated_rows', 'import_program_type', 'import_headers', 'import_approved_count', 'import_error_count']);

            return redirect()->route('students.index')->with('success', "$successCount $programType students imported successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('students.import')->with('error', 'An error occurred during import: ' . $e->getMessage());
        }
    }

    /**
     * Download the validated CSV with Status column appended.
     */
    public function downloadValidated()
    {
        $rows = session('import_validated_rows');
        $programType = session('import_program_type');
        $headers = session('import_headers');

        if (!$rows) {
            return redirect()->route('students.import')->with('error', 'No import data found.');
        }

        $filename = strtolower($programType) . '_import_validated.csv';

        $responseHeaders = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($rows, $headers) {
            $file = fopen('php://output', 'w');

            // Write header with Status column
            $csvHeaders = $headers;
            $csvHeaders[] = 'Status';
            fputcsv($file, $csvHeaders);

            // Write each row with its status
            foreach ($rows as $row) {
                $csvRow = $row['original'];
                $csvRow[] = $row['status'] === 'Approved' ? 'Approved' : $row['error'];
                fputcsv($file, $csvRow);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $responseHeaders);
    }

    // ======================================================================
    // VALIDATION METHODS (no DB writes)
    // ======================================================================

    private function validateHsscRows(array $data): array
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

    private function validateHeRows(array $data): array
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
            elseif (!\App\Models\Program::where('id', $programId)->exists()) {
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

    // ======================================================================
    // INSERT METHODS (actual DB writes, called only after confirmation)
    // ======================================================================

    private function insertHsscRow(array $row): void
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

    private function insertHeRow(array $row): void
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

        \App\Models\Contact::create([
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

    // ======================================================================
    // SAMPLE CSV DOWNLOADS
    // ======================================================================

    public function downloadSample()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_import_sample.csv"',
        ];

        $columns = [
            'Student Name', 
            'Father Name', 
            'CNIC', 
            'College Roll No', 
            'HSSC Reg No', 
            'Admission Year', 
            'Sub1 ID', 
            'Sub2 ID', 
            'Sub3 ID', 
            'Sub4 ID', 
            'Sub5 ID', 
            'Sub6 ID', 
            'Sub7 ID', 
            'Sub8 ID'
        ];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            // Add a sample row
            fputcsv($file, [
                'John Doe', 
                'Jane Doe', 
                '12345-1234567-1', 
                'R-1001', 
                'REG-2025-001', 
                '2025', 
                '1', '2', '3', '4', '5', // Compulsory IDs
                '6', '7', '8'            // Elective IDs
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadSampleHe()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="he_students_import_sample.csv"',
        ];

        $columns = [
            'Email Address',
            'College Roll Number',
            'University Registration No.',
            'University Roll Number',
            'Student Name',
            'CNIC',
            'Mobile No',
            'Date of Birth',
            'Father\'s / Guardian Name',
            'Father\'s / Guardian Mobile Number',
            'Degree ID',
            'Semester',
            'Admission Year'
        ];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            // Add a sample row
            fputcsv($file, [
                'jane@example.com',
                'B-1001',
                'REG-UNI-2025',
                'UNI-2025-001',
                'Jane Doe',
                '12345-1234567-2',
                '03001234567',
                '2005-08-15',
                'John Doe',
                '03007654321',
                '1', // Degree ID
                '1', // Semester
                '2025' // Admission Year
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
