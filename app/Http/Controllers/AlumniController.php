<?php

namespace App\Http\Controllers;

use App\Models\AlumniProfile;
use App\Models\GraduationBatch;
use App\Models\Student;
use Illuminate\Http\Request;

class AlumniController extends Controller
{
    public function index()
    {
        $alumni = AlumniProfile::with('student')->paginate(20);
        return view('admin.alumni.index', compact('alumni'));
    }

    public function batches()
    {
        $batches = GraduationBatch::withCount('records')->get();
        return view('admin.alumni.batches', compact('batches'));
    }

    public function processGraduation(Request $request)
    {
        // Logic to check eligibility (No pending fee, etc)
        // Then move student state to 'graduated' and create AlumniProfile.
    }
}
