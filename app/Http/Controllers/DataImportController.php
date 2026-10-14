<?php

namespace App\Http\Controllers;

use App\Imports\StaffImport;
use App\Imports\StudentImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class DataImportController extends Controller
{
    public function index()
    {
        return view('admin.imports.index');
    }

    public function importStaff(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new StaffImport, $request->file('file'));
            return back()->with('success', 'Staff data imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function importStudents(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new StudentImport, $request->file('file'));
            return back()->with('success', 'Student data imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
