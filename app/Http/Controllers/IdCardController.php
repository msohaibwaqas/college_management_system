<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Admissions;

class IdCardController extends Controller
{
    public function index()
    {
        // Show a form to filter students for ID card generation
        return view('students.id_cards.index');
    }

    public function generate(Request $request)
    {
        $query = Admissions::with(['student', 'student.father', 'student.contact', 'student.electives.subject'])
            ->where(function($q) {
                $q->where('status', 'active')
                  ->orWhere('status', 'admitted');
            });

        if ($request->has('class') && $request->class != '') {
            $query->where('current_class', $request->class);
        }

        if ($request->has('section') && $request->section != '') {
            // Assuming section is in admissions or we need to join
            // For now, let's assume we filter by class only or specific IDs
        }

        if ($request->has('student_ids')) {
            $ids = explode(',', $request->student_ids);
            $query->whereIn('student_id', $ids);
            
            // Preserve the order from the URL
            $idsString = implode(',', $ids);
            $query->orderByRaw("FIELD(student_id, {$idsString})");
        }

        $students = $query->get();

        return view('students.id_cards.print', compact('students'));
    }
}
