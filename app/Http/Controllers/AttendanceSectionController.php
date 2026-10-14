<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentTeacherSubject;
use App\Models\Subject;

class AttendanceSectionController extends Controller
{
    /**
     * Show the section management page for a specific subject-class.
     */
    /**
     * Show the section management page for a specific subject-class (Blocked).
     */
    public function index(Request $request)
    {
        return redirect()->route('attendance.index')->with('error', 'Section management by teachers has been disabled. Student sections are managed by Student Affairs.');
    }

    /**
     * Store/update attendance sections for multiple students (Blocked).
     */
    public function store(Request $request)
    {
        return redirect()->route('attendance.index')->with('error', 'Section management by teachers has been disabled. Student sections are managed by Student Affairs.');
    }
}
