<?php

namespace App\Http\Controllers;

use App\Models\ParentProfile;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentPortalController extends Controller
{
    /**
     * Parent Dashboard
     */
    public function index()
    {
        $parent = Auth::user()->parentProfile;
        
        if (!$parent) {
            return redirect('/')->with('error', 'Parent profile not found.');
        }

        $students = $parent->students()->with(['admissions', 'vouchers'])->get();

        return view('parent.dashboard', compact('students'));
    }

    /**
     * Specific Child Details
     */
    public function childDetails($id)
    {
        $parent = Auth::user()->parentProfile;
        $student = $parent->students()->with([
            'admissions', 
            'vouchers' => function($q) { $q->orderBy('issue_date', 'desc'); },
            'attendance' => function($q) { $q->whereMonth('date', now()->month); }
        ])->findOrFail($id);

        return view('parent.child_details', compact('student'));
    }
}
