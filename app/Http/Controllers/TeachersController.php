<?php
// app/Http/Controllers/TeachersController.php
namespace App\Http\Controllers;

use App\Models\{Subject, Teacher, Student, StudentTeacherAssignment, Result, TestResult, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response; // Add this
use League\Csv\Reader;
use App\Traits\HasAdmissionYearAndSubject;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf; // Changed from PDF to Pdf

class TeachersController extends Controller
{
    use HasAdmissionYearAndSubject;

    public function viewTeachers(Request $request)
    {
        $subjectFilter = $request->input('subject');
        $statusFilter = $request->input('teacher_status');
        $accountFilter = $request->input('account_status') === 'exists';
        $query = DB::table('teachers as t')
            ->select('t.teacher_id', 't.teacher_name', 't.cnic', 's.subject_name', 't.teacher_status')
            ->join('subjects as s', 't.subject_id', '=', 's.subject_id');
        if (!empty($subjectFilter)) {
            $query->where('s.subject_name', $subjectFilter);
        }
        if (!empty($statusFilter)) {
            $query->where('t.teacher_status', $statusFilter);
        }
        $teachers = $query->orderBy('s.subject_name', 'ASC')->get();
        $teachers = $teachers->map(function ($teacher) {
            $username = strtolower(str_replace(' ', '_', $teacher->teacher_name)) . '_' . $teacher->teacher_id;
            $teacher->account_exists = \App\Models\User::where('username', $username)->exists();
            return $teacher;
        });
        // Get subjects for filter dropdown
        $subjects = DB::table('subjects')->select('subject_name')->orderBy('subject_name')->get();
        return view('teachers.index', compact('teachers', 'subjects', 'subjectFilter', 'statusFilter', 'accountFilter'));
    }
       public function createTeacherAccount($id)
    {
    $teacher = Teacher::findOrFail($id);
    if ($teacher->user) {
        return response()->json(['error' => 'Account already exists for this teacher.']);
    }
    DB::beginTransaction();
    try {
        $username = strtolower(str_replace(' ', '_', $teacher->teacher_name)) . '_' . $teacher->teacher_id;
        $password = '123';
        $user = User::create([
            'username' => $username,
            'password' => Hash::make($password),
            'user_role' => 'teacher',
            'teacher_id' => $teacher->teacher_id,
            'created_at' => now(),
        ]);
        DB::commit();
        return response()->json(['success' => true, 'message' => "Teacher account created successfully! Username: $username, Password: $password"]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Error creating teacher account: ' . $e->getMessage()]);
    }
    }
    public function showAddForm()
    {
        $subjects = Subject::orderBy('subject_name', 'ASC')->get();
        return view('teachers.teacherAdd', compact('subjects'));
    }
    public function teacherAdd(Request $request)
    {
        $request->validate([
            'teacher_name' => 'required|string|max:255',
            'cnic' => 'required|regex:/^\d{5}-\d{7}-\d{1}$/',
            'subject_id' => 'required|exists:subjects,subject_id',
            
        ], [
            'cnic.regex' => 'CNIC must be in the format: 12345-7892011-9',
            'username.unique' => 'Username already exists.',
            'subject_id.exists' => 'Selected subject does not exist.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);
        $cnic_stored = preg_replace('/[^0-9]/', '', $request->cnic); // Remove hyphens
        DB::beginTransaction();
        try {
            $teacher = Teacher::create([
                'teacher_name' => $request->teacher_name,
                'cnic' => $cnic_stored,
                'subject_id' => $request->subject_id,
            ]);
            DB::commit();
            return back()->with('success', 'Teacher added successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error adding teacher: ' . $e->getMessage())->withInput();
        }
    }
    public function updateTeacherStatus(Request $request)
    {
    $request->validate([
        'teacher_id' => 'required|exists:teachers,teacher_id',
        'teacher_status' => 'required|in:active,transferred,retired,left',
    ]);

    try {
        $teacher = Teacher::findOrFail($request->teacher_id);
        $teacher->teacher_status = $request->teacher_status;
        $teacher->save(); // <-- make sure to call save()
        return back()->with('success', 'Teacher status updated successfully!');
    } catch (\Exception $e) {
        return back()->with('error', 'Error updating teacher status: ' . $e->getMessage());
    }
    }
        public function showEditForm($id)
    {
        $teacher = Teacher::findOrFail($id);
        $subjects = Subject::orderBy('subject_name', 'ASC')->get();
        return view('teachers.teacherEdit', compact('teacher', 'subjects'));
    }
        public function updateTeacher(Request $request, $id)
    {
        $request->validate([
            'teacher_name' => 'required|string|max:255',
            'cnic' => 'required|regex:/^\d{5}-\d{7}-\d{1}$/',
            'subject_id' => 'required|exists:subjects,subject_id',
        ], [
            'cnic.regex' => 'CNIC must be in the format: 12345-7892011-9',
            'subject_id.exists' => 'Selected subject does not exist.',
        ]);
        $teacher = Teacher::findOrFail($id);
        $cnic_stored = preg_replace('/[^0-9]/', '', $request->cnic);
        DB::beginTransaction();
        try {
            $teacher->update([
                'teacher_name' => $request->teacher_name,
                'cnic' => $cnic_stored,
                'subject_id' => $request->subject_id,
            ]);
            DB::commit();
            return redirect()->route('teachers.index')->with('success', 'Teacher updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating teacher: ' . $e->getMessage())->withInput();
        }
    }
    public function deleteTeacher($id)
    {
    $teacher = Teacher::findOrFail($id);
    DB::beginTransaction();
    try {
        StudentTeacherAssignment::where('teacher_id', $teacher->teacher_id)->delete();
        $teacher->delete(); // <-- deletes teacher
        if ($teacher->user_id) {
            User::where('user_id', $teacher->user_id)->delete(); // <-- deletes user account
        }
        DB::commit();
        return redirect()->route('teachers.index')->with('success', 'Teacher deleted successfully!');
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->route('teachers.index')->with('error', 'Error deleting teacher: ' . $e->getMessage());
    }
    }
    public function deleteTeacherAccount($teacher_id)
    {
    $teacher = Teacher::findOrFail($teacher_id);
    if ($teacher->user) {
        $teacher->user->delete();
        return back()->with('success', 'Teacher account deleted successfully!');
    }
    return back()->with('error', 'Teacher account does not exist.');
    }
   ////////////////////////////////////////////////////
   public function teacherPanel(Request $request)
{
    $selected_year     = $request->input('admission_year');
    $selected_subject  = $request->input('subject_id');
    $selected_teacher  = $request->input('teacher_id');
    $selected_option   = $request->input('option_type'); // NEW

    // -------------------------
    // 1. Load Year Dropdown
    // -------------------------
    $years = Student::select('admission_year')
        ->distinct()
        ->orderByDesc('admission_year')
        ->get();

    // -------------------------
    // 2. Load Subjects
    // -------------------------
    $subjects = collect();
    if ($selected_year) {
        $subjects = Subject::whereIn('subject_id', function ($query) use ($selected_year) {
            $query->select('ss.subject_id')
                ->from('student_subjects as ss')
                ->join('students', 'students.student_id', '=', 'ss.student_id')
                ->where('students.admission_year', $selected_year);
        })->orderBy('subject_name')->get();
    }

    // -------------------------
    // 3. Load Teachers based on Year + Subject
    // -------------------------
    $teachers = collect();
    if ($selected_year && $selected_subject) {
        $teachers = Teacher::whereIn('teacher_id', function ($query) use ($selected_year, $selected_subject) {
            $query->select('sta.teacher_id')
                ->from('student_teacher_assignments as sta')
                ->join('students', 'students.student_id', '=', 'sta.student_id')
                ->where('students.admission_year', $selected_year)
                ->where('sta.subject_id', $selected_subject);
        })->orderBy('teacher_name')->get();
    }

    // ----------------------------------------------------
    // 4. Load Students (Basic)
    // ----------------------------------------------------
    $students = collect();
    $teacherName = '';

    if ($selected_year && $selected_subject && $selected_teacher) {
        $teacherName = Teacher::where('teacher_id', $selected_teacher)->value('teacher_name');

        $students = Student::select(
                'students.student_id',
                'students.bise_roll_no',
                'students.name',
                'students.father_name',
                'students.admission_year'
            )
            ->join('student_teacher_assignments as sta', 'students.student_id', '=', 'sta.student_id')
            ->where('students.admission_year', $selected_year)
            ->where('sta.subject_id', $selected_subject)
            ->where('sta.teacher_id', $selected_teacher)
            ->orderBy('students.bise_roll_no')
            ->get();
    }

    // ----------------------------------------------------
    // 5. LOAD EXTRA DATA BASED ON OPTION TYPE
    // ----------------------------------------------------
    $results = collect();

    if ($selected_option == 'results' && count($students) > 0) {
        $results = Result::where('subject_id', $selected_subject)
            ->whereIn('bise_roll_no', $students->pluck('bise_roll_no'))
            ->get()
            ->keyBy('bise_roll_no');
    }

    if ($selected_option == 'roll_numbers') {
        $students->transform(function ($student) {
            $student->adjusted_roll = $this->applyRollNumberAdjustment(
                $student->bise_roll_no, 
                $student->admission_year
            );
            return $student;
        });
    }

    if ($selected_option == 'award_list') {
        // You can attach award list logic here
        // example
        $students->transform(function ($s) use ($selected_subject) {
            $s->award_marks = Result::where('subject_id', $selected_subject)
                ->where('bise_roll_no', $s->bise_roll_no)
                ->value('marks_obtained');
            return $s;
        });
    }

    return view('teachers.teacherPanel', compact(
        'years',
        'subjects',
        'teachers',
        'students',
        'results',
        'selected_year',
        'selected_subject',
        'selected_teacher',
        'selected_option',
        'teacherName'
    ));
}
    public function getSubjects(Request $request)
{
    $year = $request->admission_year;
    if(!$year) return response()->json([]);

    $subjects = Subject::whereHas('students', function($q) use($year){
        $q->where('admission_year', $year);
    })->get(['subject_id', 'subject_name']);

    return response()->json($subjects);
}


public function getTeachersByYearSubject(Request $request)
{
    $year = $request->admission_year;
    $subject = $request->subject_id;

    $teachers = Teacher::whereIn('teacher_id', function ($query) use ($year, $subject) {
        $query->select('sta.teacher_id')
            ->from('student_teacher_assignments as sta')
            ->join('students', 'students.student_id', '=', 'sta.student_id')
            ->where('students.admission_year', $year)
            ->where('sta.subject_id', $subject);
    })->orderBy('teacher_name')->get();

    return response()->json($teachers);
}
public function exportData(Request $request)
{
    $year = $request->input('admission_year');
    $subject = $request->input('subject_id');
    $teacher = $request->input('teacher_id');
    $option = $request->input('option_type', 'students');
    $format = $request->input('format', 'csv'); // default csv

    // basic validation
    if (!$year || !$subject || !$teacher) {
        return back()->with('error', 'Missing parameters for export.');
    }

    if ($format === 'pdf') {
        return $this->exportPdf($request);
    }

    return $this->exportCsv($request);
}

/**
 * Export CSV (Excel-friendly)
 */
public function exportCsv(Request $request)
{
    $year = $request->input('admission_year');
    $subject = $request->input('subject_id');
    $teacher = $request->input('teacher_id');
    $option = $request->input('option_type', 'students');

    // fetch students as in your existing function
    $students = Student::select(
        'students.bise_roll_no',
        'students.name',
        'students.father_name',
        'students.admission_year'
    )
    ->join('student_teacher_assignments as sta', 'students.student_id', '=', 'sta.student_id')
    ->where('students.admission_year', $year)
    ->where('sta.subject_id', $subject)
    ->where('sta.teacher_id', $teacher)
    ->orderBy('students.bise_roll_no', 'ASC')
    ->get();

    $filename = now()->format('Ymd_His') . "_teacher_{$teacher}_{$option}.csv";

    $handle = fopen('php://temp', 'r+');

    if ($option === 'students') {
        fputcsv($handle, ['Roll No', 'Name', 'Father Name']);
        foreach ($students as $s) {
            fputcsv($handle, [$s->bise_roll_no, $s->name, $s->father_name]);
        }
    } elseif ($option === 'roll_numbers') {
        fputcsv($handle, ['Adjusted Roll No']);
        foreach ($students as $s) {
            $adj = $this->applyRollNumberAdjustment($s->bise_roll_no, $s->admission_year);
            fputcsv($handle, [$adj]);
        }
    } elseif ($option === 'results') {
        // load results map
        $results = Result::where('subject_id', $subject)
            ->whereIn('bise_roll_no', $students->pluck('bise_roll_no'))
            ->get()->keyBy('bise_roll_no');
        fputcsv($handle, ['Roll No', 'Name', 'Father Name', 'Marks Obtained', 'Total Marks', 'Status']);
        foreach ($students as $s) {
            $r = $results->get($s->bise_roll_no);
            fputcsv($handle, [
                $s->bise_roll_no,
                $s->name,
                $s->father_name,
                $r->marks_obtained ?? '',
                $r->total_marks ?? '',
                $r->result_status ?? ''
            ]);
        }
    } elseif ($option === 'award_list') {
        // CSV representation of award list (rows with 4 Roll/Marks pairs)
        // Header lines:
        $collegeName = config('app.name', 'College');
        $teacherName = Teacher::where('teacher_id', $teacher)->value('teacher_name');
        $class = ($year == date('Y')) ? '1st Year' : '2nd Year';

        // header rows
        fputcsv($handle, ["College: $collegeName"]);
        fputcsv($handle, ["Teacher: $teacherName"]);
        fputcsv($handle, ["Class: $class"]);
        fputcsv($handle, ["Total Students: " . $students->count()]);
        fputcsv($handle, []); // blank
        // column row
        fputcsv($handle, ['Roll No.', 'Marks', 'Roll No.', 'Marks', 'Roll No.', 'Marks', 'Roll No.', 'Marks']);

        // chunk students into 4-per-row
        $chunks = $students->chunk(4);
        foreach ($chunks as $chunk) {
            $row = [];
            foreach ($chunk as $s) {
                $row[] = $this->applyRollNumberAdjustment($s->bise_roll_no, $s->admission_year);
                $row[] = ''; // empty marks cell
            }
            // pad
            while (count($row) < 8) $row[] = '';
            fputcsv($handle, $row);
        }
    }

    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    return Response::make($csv, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ]);
}

/**
 * Export PDF - handles students/results/roll_numbers/award_list
 */
public function exportPdf(Request $request)
{
    $year = $request->input('admission_year');
    $subject = $request->input('subject_id');
    $teacher = $request->input('teacher_id');
    $option = $request->input('option_type', 'students');

    if (!$year || !$subject || !$teacher) {
        return back()->with('error', 'Missing parameters for PDF export.');
    }

    // fetch students
    $students = Student::select(
        'students.bise_roll_no',
        'students.name',
        'students.father_name',
        'students.admission_year'
    )
    ->join('student_teacher_assignments as sta', 'students.student_id', '=', 'sta.student_id')
    ->where('students.admission_year', $year)
    ->where('sta.subject_id', $subject)
    ->where('sta.teacher_id', $teacher)
    ->orderBy('students.bise_roll_no', 'ASC')
    ->get();

    $teacherName = Teacher::where('teacher_id', $teacher)->value('teacher_name');
    $collegeName = config('app.name', 'College');

    // For results option, build results keyed by roll
    $results = collect();
    if ($option === 'results') {
        $results = Result::where('subject_id', $subject)
            ->whereIn('bise_roll_no', $students->pluck('bise_roll_no'))
            ->get()->keyBy('bise_roll_no');
    }

    // For roll_numbers option apply adjustment
    if ($option === 'roll_numbers' || $option === 'award_list') {
        $students->transform(function ($s) {
            $s->adjusted_roll = $this->applyRollNumberAdjustment($s->bise_roll_no, $s->admission_year);
            return $s;
        });
    }

    // Choose PDF view templates:
    if ($option === 'award_list') {
        $pdf = Pdf::loadView('teachers.pdf.award_list', [
            'students' => $students,
            'teacherName' => $teacherName,
            'collegeName' => $collegeName,
            'class' => ($year == date('Y')) ? '1st Year' : '2nd Year',
            'year' => $year
        ])->setPaper('a4', 'portrait');

        $filename = "award_list_{$teacher}_{$year}.pdf";
        return $pdf->stream($filename); // opens in browser for print
    }

    // generic students/results/rolls view
    $pdf = Pdf::loadView('teachers.pdf.generic_export', [
        'students' => $students,
        'results' => $results,
        'teacherName' => $teacherName,
        'option' => $option,
        'collegeName' => $collegeName,
        'year' => $year
    ])->setPaper('a4', 'portrait');

    $filename = "{$option}_{$teacher}_{$year}.pdf";
    return $pdf->download($filename);
}

/**
 * Opens award PDF in browser (print)
 */
public function printAwardPdf(Request $request)
{
    // simply reuse exportPdf with option_type=award_list but stream
    $request->merge(['format' => 'pdf', 'option_type' => 'award_list']);
    return $this->exportPdf($request);
}

/**
 * Your roll adjustment helper (already provided earlier)
 */
protected function applyRollNumberAdjustment($rollNo, $admissionYear)
{
    $currentYear = date('Y');
    $shouldApplyAdjustment = $admissionYear >= ($currentYear - 2); // Apply for last 2 years + current year

    if ($shouldApplyAdjustment) {
        $base = ($admissionYear % 100) * 10000;
        return $rollNo - $base;
    }

    return $rollNo; // Return original for older years
}
public function teacherResultAjax(Request $request)
{
    $selected_year     = $request->input('admission_year');
    $selected_subject  = $request->input('subject_id');
    $selected_teacher  = $request->input('teacher_id');
    $selected_option   = $request->input('option_type');

    // Initialize
    $students = collect();
    $results = collect();
    $teacherName = '';

    if ($selected_year && $selected_subject && $selected_teacher) {
        $teacherName = Teacher::where('teacher_id', $selected_teacher)->value('teacher_name');

        // Fetch students assigned to this teacher, year, subject
        $students = Student::select(
                'students.student_id',
                'students.bise_roll_no',
                'students.name',
                'students.father_name',
                'students.admission_year'
            )
            ->join('student_teacher_assignments as sta', 'students.student_id', '=', 'sta.student_id')
            ->where('students.admission_year', $selected_year)
            ->where('sta.subject_id', $selected_subject)
            ->where('sta.teacher_id', $selected_teacher)
            ->orderBy('students.bise_roll_no')
            ->get();

        // Handle option-specific data
        if ($selected_option == 'results') {
            // Load results keyed by roll number
            $results = Result::where('subject_id', $selected_subject)
                ->whereIn('bise_roll_no', $students->pluck('bise_roll_no'))
                ->get()
                ->keyBy('bise_roll_no');

            // Attach result_status to each student (pass/fail/absent)
            $students->transform(function ($student) use ($results) {
                if (isset($results[$student->bise_roll_no])) {
                    $student->marks_obtained = $results[$student->bise_roll_no]->marks_obtained;
                    $student->total_marks    = $results[$student->bise_roll_no]->total_marks;
                    $student->result_status  = $results[$student->bise_roll_no]->result_status;
                } else {
                    $student->marks_obtained = null;
                    $student->total_marks    = null;
                    $student->result_status  = 'absent';
                }
                return $student;
            });
        }

        if ($selected_option == 'roll_numbers') {
            $students->transform(function ($s) {
                $s->adjusted_roll = $this->applyRollNumberAdjustment($s->bise_roll_no, $s->admission_year);
                return $s;
            });
        }

        if ($selected_option == 'award_list') {
            $students->transform(function ($s) use ($selected_subject) {
                $s->award_marks = Result::where('subject_id', $selected_subject)
                    ->where('bise_roll_no', $s->bise_roll_no)
                    ->value('marks_obtained');
                return $s;
            });
        }
    }



    // Render HTML table
    $html = view('teachers.partials.students_table', compact(
        'students', 'results', 'selected_option', 'teacherName'
    ))->render();

    return response()->json([
        'html'  => $html,
    ]);
}
public function teacherStudentManagement(Request $request)
{
    $years = Student::select('admission_year')->distinct()->orderByDesc('admission_year')->get();
    $selectedYear = $request->admission_year ?? null;
    $selectedSubject = $request->subject_id ?? null;
    $selectedTeacher = $request->teacher_id ?? null;

    return view('teachers.teacherStudentManagement', compact(
        'years', 'selectedYear', 'selectedSubject', 'selectedTeacher'
    ));
}

    public function getActiveTeachers(Request $request)
{
    $year = $request->admission_year;   // we keep it for filter, if needed later
    $subject = $request->subject_id;

    if (!$subject) return response()->json([]);

    // Fetch teachers directly from teachers table
    $teachers = Teacher::where('teacher_status', 'active')
        ->where('subject_id', $subject)
        ->get(['teacher_id', 'teacher_name']);

    return response()->json($teachers);
}

    public function assignStudentsView(Request $request)
{
    $year = $request->admission_year;
    $subject = $request->subject_id;
    $teacher = $request->teacher_id;

    if (!$year || !$subject || !$teacher) {
        return response()->json([
            'html' => '<div class="alert alert-info">Please select year, subject, and teacher.</div>'
        ]);
    }

    // Students of selected year
    // Students who HAVE this subject in student_subjects
    // Students NOT assigned to ANY teacher for this subject
    $students = Student::select('students.*')
        ->join('student_subjects as ss', 'ss.student_id', '=', 'students.student_id')
        ->where('students.admission_year', $year)
        ->where('ss.subject_id', $subject)
        ->whereNotIn('students.student_id', function($q) use ($subject) {
            $q->select('student_id')
              ->from('student_teacher_assignments')
              ->where('subject_id', $subject);
        })
        ->orderBy('students.bise_roll_no', 'ASC')
        ->with(['subjects' => function($q){
            $q->select('subjects.subject_id', 'subjects.subject_name');
        }])
        ->get();

    $html = view('teachers.partials.assign_students_table', compact('students'))->render();

    return response()->json(['html' => $html]);
    }

public function assignStudents(Request $request)
{
    $studentIds = $request->student_ids ?? [];
    $teacher_id = $request->teacher_id;
    $subject_id = $request->subject_id;

    foreach ($studentIds as $student_id) {

        // Check if student already has a teacher for this subject
        $exists = \App\Models\StudentTeacherSubject::where('student_id', $student_id)
            ->where('subject_id', $subject_id)
            ->exists();

        if (!$exists) {
            // Get class_level and academic_year from student's admission
            $student = \App\Models\Student::find($student_id);
            $admission = $student ? $student->admissions()->latest()->first() : null;
            $class_level = $admission ? $admission->current_class : '11';
            $academic_year = $admission ? $admission->admission_year : date('Y');

            // Assign only if not already assigned
            \App\Models\StudentTeacherSubject::create([
                'student_id' => $student_id,
                'subject_id' => $subject_id,
                'staff_id' => $teacher_id, // teacher_id is likely staff_id
                'class_level' => $class_level,
                'academic_year' => $academic_year,
                'assignment_type' => 'compulsory', // Default
            ]);
        }
    }

    return response()->json(['success' => true]);
}
public function unassignStudentsView(Request $request)
{
    $year = $request->admission_year;
    $subject = $request->subject_id;
    $teacher = $request->teacher_id;

    if (!$year || !$subject || !$teacher) {
        return response()->json([
            'html' => '<div class="alert alert-info">Please select year, subject, and teacher.</div>'
        ]);
    }

    $students = Student::select('students.*')
        ->join('student_teacher_subjects as sta', 'students.id', '=', 'sta.student_id')
        ->whereHas('admissions', function($q) use ($year) {
            $q->where('admission_year', $year);
        })
        ->where('sta.subject_id', $subject)
        ->where('sta.staff_id', $teacher)
        ->orderBy('students.id', 'ASC')
        ->with(['subjects' => function($q){
            $q->select('subjects.id', 'subjects.name');
        }])
        ->get();

    $html = view('teachers.partials.unassign_students_table', compact('students'))->render();
    return response()->json(['html' => $html]);
}
public function unassignStudents(Request $request)
{
    $studentIds = $request->student_ids ?? [];
    $subject_id = $request->subject_id;
    $teacher_id = $request->teacher_id;

    if (!empty($studentIds) && $subject_id && $teacher_id) {
        $assignments = \App\Models\StudentTeacherSubject::whereIn('student_id', $studentIds)
            ->where('subject_id', $subject_id)
            ->where('staff_id', $teacher_id)
            ->get();

        foreach ($assignments as $assignment) {
            $assignment->delete();
        }
    }

    return response()->json(['success' => true]);
}

}
