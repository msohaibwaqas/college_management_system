<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Student\StudentImportService;

/**
 * Controller orchestrating bulk student imports via CSV.
 * Delegates file parsing, validation, and database writes to StudentImportService.
 */
class StudentImportController extends Controller
{
    public function __construct(
        protected StudentImportService $importService
    ) {}

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
        $programType = $request->program_type;

        $result = $this->importService->parseAndValidate($file->getRealPath(), $programType);

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        // Store validated data in session for preview step
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
     * Step 3: Perform the import after user confirms.
     */
    public function confirmImport(Request $request)
    {
        $rows = session('import_validated_rows');
        $programType = session('import_program_type');

        if (!$rows) {
            return redirect()->route('students.import')->with('error', 'No import data found. Please upload a CSV file first.');
        }

        $approvedRows = array_filter($rows, fn($r) => $r['status'] === 'Approved');

        if (count($approvedRows) === 0) {
            return redirect()->route('students.import')->with('error', 'No approved rows to import.');
        }

        try {
            $successCount = $this->importService->importApprovedRows($approvedRows, $programType);

            session()->forget([
                'import_validated_rows',
                'import_program_type',
                'import_headers',
                'import_approved_count',
                'import_error_count'
            ]);

            return redirect()->route('students.index')->with('success', "$successCount $programType students imported successfully.");
        } catch (\Exception $e) {
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

        return $this->importService->streamValidatedCsv($rows, $headers, $programType);
    }

    /**
     * Download sample HSSC CSV.
     */
    public function downloadSample()
    {
        return $this->importService->streamSampleCsv('HSSC');
    }

    /**
     * Download sample Higher Education CSV.
     */
    public function downloadSampleHe()
    {
        return $this->importService->streamSampleCsv('HE');
    }
}
