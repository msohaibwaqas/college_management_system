<?php

namespace App\Http\Controllers;

use App\Models\{Subject, Teacher, UserActivityLog, Student, StudentSubject, Result, Room, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader; // Import League\Csv\Reader
use App\Traits\HasAdmissionYearAndSubject;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class StudentsController extends Controller
{
    use HasAdmissionYearAndSubject;

    /**
     * Apply roll number adjustment based on admission year
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

    protected function logUserActivity($activity)
    {
        if (Auth::check()) {
            UserActivityLog::create([
                'user_id' => Auth::id(),
                'activity' => $activity
            ]);
        }
    }

    public function studentIndex(Request $request)
    {
        $admission_years = $this->getAdmissionYears();
        
        $admission_year = $request->input('admission_year');
        $sort_by = $request->input('sort_by', 'bise_roll_no');
        $sort_order = $request->input('sort_order', 'asc');
        $per_page = $request->input('per_page', 10);

        $valid_sort_fields = ['bise_roll_no', 'reg_no', 'name', 'father_name', 'cnic'];
        if (!in_array($sort_by, $valid_sort_fields)) {
            $sort_by = 'bise_roll_no';
        }
        $sort_order = ($sort_order === 'desc') ? 'desc' : 'asc';

        // Validate per_page parameter
        $valid_per_page = [10, 25, 50, 100, 200];
        if (!in_array($per_page, $valid_per_page)) {
            $per_page = 10; // Default to 10 if invalid value
        }

        $query = DB::table('students')
            ->select([
                'student_id',
                'bise_roll_no',
                'reg_no',
                'name',          
                'father_name',   
                'cnic',          
                'admission_year' 
            ]);

        if ($admission_year) {
            $query->where('admission_year', $admission_year);
        }

        $students = $query->orderBy($sort_by, $sort_order)->paginate($per_page);
        
        // Preserve query parameters in pagination links
        $students = $students->appends([
            'admission_year' => $admission_year,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order,
            'per_page' => $per_page
        ]);

        // Log user activity
        $this->logUserActivity('Visited page: students.studentIndex');

        return view('students.studentIndex', compact('admission_years', 'admission_year', 'students', 'sort_by', 'sort_order', 'per_page'));
    }

    public function viewSingleStudent($id)
    {
        $student = Student::with(['subjects', 'teachers'])->findOrFail($id);
        return view('students.viewSingleStudent', compact('student'));
    }

    public function studentSubjectsEdit($id)
    {
    $student = Student::with(['subjects' => function($query) {
        $query->select('subjects.subject_id', 'subjects.subject_name', 'subjects.subject_type');
    }])->findOrFail($id);
    
    $all_subjects = Subject::orderBy('subject_name', 'ASC')->get();
    
    // Get currently selected subject IDs using the relationship
    $current_subjects = $student->subjects()->pluck('subjects.subject_id')->toArray();

    return view('students.studentSubjectsEdit', compact('student', 'all_subjects', 'current_subjects'));
    }

    public function update(Request $request, $id)
    {
        $validatedData = $this->validateUpdateStudentRequest($request, $id);

        $student = Student::findOrFail($id);
        
        // Remove hyphens from CNIC before storing
        $cnic_stored = preg_replace('/[^0-9]/', '', $validatedData['cnic']);

        $compulsory_subjects = Subject::where('subject_type', 'compulsory')->pluck('subject_id')->toArray();
        $elective_subjects = $validatedData['elective_subjects'];
        $selected_subjects = array_merge($compulsory_subjects, $elective_subjects);

        DB::beginTransaction();
        try {
            // Update student info
            $student->update([
                'name' => $validatedData['name'],
                'father_name' => $validatedData['father_name'],
                'cnic' => $cnic_stored, // Store CNIC without hyphens
                'bise_roll_no' => $validatedData['bise_roll_no'],
                'reg_no' => $validatedData['reg_no'],
                'admission_year' => $validatedData['admission_year'],
            ]);

            // Delete existing subject assignments
            StudentSubject::where('student_id', $student->student_id)->delete();

            // Add new subject assignments
            $this->attachSubjectsToStudent($student, $selected_subjects);

            DB::commit();
            return redirect()->route('students.studentIndex')->with('success', 'Student updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating student: ' . $e->getMessage())->withInput();
        }
    }

    protected function validateUpdateStudentRequest(Request $request, $id)
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'cnic' => 'required|regex:/^\d{5}-\d{7}-\d{1}$/',
            'bise_roll_no' => 'required|integer|unique:students,bise_roll_no,' . $id . ',student_id,admission_year,' . $request->admission_year,
            'reg_no' => 'nullable|string|max:50|unique:students,reg_no,' . $id . ',student_id,admission_year,' . $request->admission_year,
            'admission_year' => 'required|integer',
            'elective_subjects' => 'required|array|size:3',
            'elective_subjects.*' => 'exists:subjects,subject_id'
        ], [
            'cnic.regex' => 'CNIC must be in the format: 12345-7892011-9',
            'bise_roll_no.unique' => 'A student with this BISE Roll No already exists for the selected admission year.',
            'reg_no.unique' => 'A student with this Reg No already exists for this admission year.',
            'elective_subjects.size' => 'You must select exactly 3 elective subjects.',
            'elective_subjects.*.exists' => 'One or more selected elective subjects are invalid.'
        ]);
    }

    public function updateStudent(Request $request)
    {
        $validatedData = $this->validateUpdateStudentRequest($request, $validatedData['student_id']);

        $student_id = $validatedData['student_id'];
        $student = Student::find($student_id);
        $bise_roll_no = $student->bise_roll_no ?? '';

        // Update results bise_roll_no
        Result::where('bise_roll_no', $bise_roll_no)
            ->update(['bise_roll_no' => $validatedData['new_bise_roll_no']]);

        // Update student record
        $student->update([
            'bise_roll_no' => $validatedData['new_bise_roll_no'],
            'name' => $validatedData['name'],
            'father_name' => $validatedData['father_name'],
            'cnic' => preg_replace('/[^0-9]/', '', $validatedData['cnic']), // Store CNIC without hyphens
            'reg_no' => $validatedData['reg_no'],
            'admission_year' => $validatedData['admission_year'],
        ]);

        // Delete existing elective subjects
        $elective_ids = Subject::where('subject_type', 'elective')
            ->pluck('subject_id')
            ->toArray();

        StudentSubject::where('student_id', $student_id)
            ->whereIn('subject_id', $elective_ids)
            ->delete();

        // Insert new elective subjects
        if ($request->has('elective_subjects')) {
            $this->attachSubjectsToStudent($student, $request->elective_subjects);
        }

        $success = "Student updated successfully!";

        $elective_subjects = $student->subjects()
            ->where('subject_type', 'elective')
            ->get(['subjects.subject_id', 'subjects.subject_name']);

        $available_subjects = Subject::where('subject_type', 'elective')
            ->orderBy('subject_name', 'ASC')
            ->get();

        return view('students.updateStudent', compact(
            'student', 'elective_subjects', 'available_subjects', 'success'
        ));
    }

    protected function validateUpdateStudentAPIRequest(Request $request)
    {
        return $request->validate([
            'bise_roll_no' => 'required|integer',
            'reg_no' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'father_name' => 'required|string|max:100',
            'cnic' => 'required|string|regex:/^\d{5}-\d{7}-\d{1}$/'
        ]);
    }

    public function studentSubjectUpdate(Request $request, $id)
    {
        $validatedData = $this->validateUpdateSubjectsRequest($request);

        $student = Student::findOrFail($id);

        DB::beginTransaction();
        try {
            // Get all compulsory subjects for this student
            $compulsory_subjects = $student->subjects->where('subject_type', 'compulsory')->pluck('subject_id')->toArray();
            
            // Combine compulsory and new elective subjects
            $all_subjects = array_merge($compulsory_subjects, $validatedData['elective_subjects']);

            // Sync the subjects (this will detach old and attach new)
            $student->subjects()->sync($all_subjects);

            DB::commit();
            return redirect()->route('students.viewSingleStudent', $student->student_id)
                ->with('success', 'Student subjects updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating student subjects: ' . $e->getMessage());
        }
    }

    protected function validateUpdateSubjectsRequest(Request $request)
    {
        return $request->validate([
            'elective_subjects' => 'required|array|size:3', // Exactly 3 subjects
            'elective_subjects.*' => 'exists:subjects,subject_id',
        ], [
            'elective_subjects.required' => 'You must select elective subjects.',
            'elective_subjects.array' => 'Elective subjects must be an array.',
            'elective_subjects.size' => 'You must select exactly 3 elective subjects.',
            'elective_subjects.*.exists' => 'One or more selected elective subjects are invalid.',
        ]);
    }

    public function updateStudentIndex()
    {
        // Log user visit
        $this->logUserActivity('Visited page: update_student');

        return view('students.updateStudent');
    }

    public function studentEdit($id)
    {
        $student = Student::with(['subjects' => function($query) {
            $query->select('subjects.subject_id', 'subjects.subject_name', 'subjects.subject_type');
        }])->findOrFail($id);
        
        $result_compulsory = Subject::where('subject_type', 'compulsory')->get();
        $result_elective = Subject::where('subject_type', 'elective')->orderBy('subject_name', 'ASC')->get();
        
        // Get currently selected subject IDs
        $current_subjects = $student->subjects->pluck('subject_id')->toArray();
        
        return view('students.studentEdit', compact('student', 'result_compulsory', 'result_elective', 'current_subjects'));
    }

    public function showAddForm()
    {
        $result_compulsory = Subject::where('subject_type', 'compulsory')->get();
        $result_elective = Subject::where('subject_type', 'elective')->orderBy('subject_name', 'ASC')->get();
        
        $default_admission_year = date('Y');
        
        return view('students.addStudent', compact('result_compulsory', 'result_elective', 'default_admission_year'));
    }

    public function addStudent(Request $request)
    {
        \Log::info("Form submitted"); // Log form submission
        \Log::info($request->all()); // Log request data

        try {
            $validatedData = $this->validateAddStudentRequest($request);
            \Log::info("Validation passed"); // Log validation success

            $name = $validatedData['name'];
            $father_name = $validatedData['father_name'];
            $cnic = preg_replace('/[^0-9]/', '', $validatedData['cnic']); // Remove hyphens
            $bise_roll_no = $validatedData['bise_roll_no'];
            $reg_no = $validatedData['reg_no'];
            $admission_year = $validatedData['admission_year'];

            $compulsory_subjects = Subject::where('subject_type', 'compulsory')->pluck('subject_id')->toArray();
            $elective_subjects = $validatedData['elective_subjects'];

            $selected_subjects = array_merge($compulsory_subjects, $elective_subjects);

            DB::beginTransaction();
            try {
                // Create Student
                $student = Student::create([
                    'name' => $name,
                    'father_name' => $father_name,
                    'cnic' => $cnic, // Store CNIC without hyphens
                    'bise_roll_no' => $bise_roll_no,
                    'reg_no' => $reg_no,
                    'admission_year' => $admission_year,
                    'enrollment_status' => 'enrolled', // Ensure this field is set if required
                ]);

                \Log::info("Student created: " . $student->id); // Log student creation

                // Attach subjects
                $this->attachSubjectsToStudent($student, $selected_subjects);

                \Log::info("Subjects attached"); // Log subject attachment

                DB::commit();
                \Log::info('Transaction committed successfully'); // Log transaction commit

                return back()->with('success', 'Student added successfully!');
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Transaction rolled back due to error: ' . $e->getMessage()); // Log transaction rollback
                return back()->with('error', 'Error adding student: ' . $e->getMessage())->withInput();
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed: ' . json_encode($e->errors())); // Log validation errors
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('General error: ' . $e->getMessage()); // Log general errors
            return back()->with('error', 'An error occurred: ' . $e->getMessage())->withInput();
        }
    }

    protected function validateAddStudentRequest(Request $request)
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'cnic' => 'required|regex:/^\d{5}-\d{7}-\d{1}$/',
            'bise_roll_no' => 'required|integer|unique:students,bise_roll_no',
            'reg_no' => 'nullable|string|max:50|unique:students,reg_no,NULL,student_id,admission_year,' . date('Y'),
            'admission_year' => 'required|integer',
            'elective_subjects' => 'required|array|size:3',
            'elective_subjects.*' => 'exists:subjects,subject_id'
        ], [
            'cnic.regex' => 'CNIC must be in the format: 12345-7892011-9',
            'bise_roll_no.unique' => 'A student with this BISE Roll No already exists for the selected admission year.',
            'reg_no.unique' => 'A student with this Reg No already exists for this admission year.',
            'elective_subjects.size' => 'You must select exactly 3 elective subjects.',
            'elective_subjects.*.exists' => 'One or more selected elective subjects are invalid.'
        ]);
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete student subjects first (due to foreign key constraint)
            StudentSubject::where('student_id', $student->student_id)->delete();
            
            // Delete the student
            $student->delete();

            DB::commit();
            return redirect()->route('students.studentIndex')->with('success', 'Student deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('students.studentIndex')->with('error', 'Error deleting student: ' . $e->getMessage());
        }
    }

    public function deleteStudentAPI($id)
    {
        $student = Student::find($id);
        
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found'], 404);
        }

        $student->delete();
        
        return response()->json(['success' => true, 'message' => 'Student deleted successfully']);
    }

    public function studentsBySubject(Request $request)
    {
        $selected_year = $request->input('admission_year');
        $selected_subject = $request->input('subject_id');

        $yearsResult = Student::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->get();

        $subjectResult = $selected_year ? Subject::select('subjects.subject_id', 'subjects.subject_name')
            ->join('student_subjects as ss', 'subjects.subject_id', '=', 'ss.subject_id')
            ->join('students as s', 'ss.student_id', '=', 's.student_id')
            ->where('s.admission_year', $selected_year)
            ->distinct()->orderBy('subjects.subject_name', 'ASC')->get() : collect();

        $students = collect();
        $subjectName = '';

        if ($selected_year && $selected_subject) {
            $students = Student::select('students.bise_roll_no', 'students.name as student_name', 'students.father_name', 'students.cnic')
                ->join('student_subjects as ss', 'students.student_id', '=', 'ss.student_id')
                ->where('students.admission_year', $selected_year)
                ->where('ss.subject_id', $selected_subject)
                ->orderBy('students.bise_roll_no', 'ASC')
                ->get();

            // Apply roll number adjustment to each student
            foreach ($students as $student) {
                $student->adjusted_bise_roll_no = $this->applyRollNumberAdjustment($student->bise_roll_no, $selected_year);
            }

            $subjectName = Subject::where('subject_id', $selected_subject)->value('subject_name');
        }

        // Log user activity
        $this->logUserActivity('Visited page: students-by-subject');

        return view('students.studentsBySubject', compact('yearsResult', 'subjectResult', 'selected_year', 'selected_subject', 'students', 'subjectName'));
    }
    public function exportStudentsBySubjectCSV(Request $request)
    {
    $selected_year = $request->input('admission_year');
    $selected_subject = $request->input('subject_id');

    if (!$selected_year || !$selected_subject) {
        return back()->with('error', 'Please select both Admission Year and Subject.');
    }

    $students = Student::select('students.bise_roll_no', 'students.name as student_name', 'students.father_name', 'students.cnic')
        ->join('student_subjects as ss', 'students.student_id', '=', 'ss.student_id')
        ->where('students.admission_year', $selected_year)
        ->where('ss.subject_id', $selected_subject)
        ->orderBy('students.bise_roll_no', 'ASC')
        ->get();

    // Apply roll number adjustment to each student
    foreach ($students as $student) {
        $student->adjusted_bise_roll_no = $this->applyRollNumberAdjustment($student->bise_roll_no, $selected_year);
    }

    $filename = "students_by_subject_{$selected_year}_{$selected_subject}.csv";
    $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=$filename"];

    $callback = function() use ($students) {
        $output = fopen('php://output', 'w');
        fputcsv($output, ['BISE Roll No', 'Student Name', "Father's Name", 'CNIC']);
        foreach ($students as $student) {
            fputcsv($output, [
                $student->adjusted_bise_roll_no,
                $student->student_name,
                $student->father_name,
                $student->cnic
            ]);
        }
        fclose($output);
    };

    return response()->stream($callback, 200, $headers);
    }

    public function exportStudentsBySubjectExcel(Request $request)
    {
    $selected_year = $request->input('admission_year');
    $selected_subject = $request->input('subject_id');

    if (!$selected_year || !$selected_subject) {
        return back()->with('error', 'Please select both Admission Year and Subject.');
    }

    $students = Student::select('students.bise_roll_no', 'students.name as student_name', 'students.father_name', 'students.cnic')
        ->join('student_subjects as ss', 'students.student_id', '=', 'ss.student_id')
        ->where('students.admission_year', $selected_year)
        ->where('ss.subject_id', $selected_subject)
        ->orderBy('students.bise_roll_no', 'ASC')
        ->get();

    // Apply roll number adjustment to each student
    foreach ($students as $student) {
        $student->adjusted_bise_roll_no = $this->applyRollNumberAdjustment($student->bise_roll_no, $selected_year);
    }

    $filename = "students_by_subject_{$selected_year}_{$selected_subject}.xlsx";
    return \Excel::download(new \App\Exports\StudentsBySubjectExport($students), $filename);
    }

// app/Http/Controllers/StudentsController.php

    public function groupStudents(Request $request)
    {
    $selected_year = $request->input('admission_year');
    $years = Student::select('admission_year')->distinct()->orderByDesc('admission_year')->get();
    $groups = [];

    if ($selected_year) {
        $students = Student::where('admission_year', $selected_year)
            ->with(['subjects' => fn($q) => $q->where('subject_type', 'elective')])
            ->orderBy('bise_roll_no', 'asc')
            ->get();

        $classifyGroup = function ($subjects) {
            $subs = array_map('strtolower', $subjects);
            sort($subs);
            $has = fn($sub) => in_array(true, array_map(fn($s) => strpos($s, strtolower($sub)) !== false, $subs));

            if ($has('biology') && $has('physics') && $has('chemistry')) return 'Pre-Medical';
            if ($has('math') && $has('physics') && $has('chemistry')) return 'Pre-Engineering';
            if ($has('math') && $has('computer')) return 'ICS';
            if ($has('computer')) return 'FA-IT';
            return 'FA';
        };

        foreach ($students as $student) {
            $subject_names = $student->subjects->pluck('subject_name')->toArray();
            if (count($subject_names) > 0) {
                $group = $classifyGroup($subject_names);

                // Apply roll number adjustment
                $adjusted_roll_no = $this->applyRollNumberAdjustment($student->bise_roll_no, $selected_year);

                $groups[$group][] = [
                    'roll_no' => $adjusted_roll_no,
                    'name' => $student->name,
                    'father_name' => $student->father_name,
                    'subjects' => implode(', ', $subject_names)
                ];
            }
        }
    }
    
    // Log user activity
    $this->logUserActivity('Visited page: group-students');

    return view('students.groupStudents', compact('years', 'selected_year', 'groups'));
    }

    public function viewStudentAttendance($studentId)
    {
        $student = Student::findOrFail($studentId);
        
        // Build query with filters
        $query = DB::table('attendance as a')
            ->leftJoin('subjects as s', 'a.subject_id', '=', 's.subject_id')
            ->leftJoin('teachers as t', 'a.teacher_id', '=', 't.teacher_id')
            ->where('a.student_id', $studentId)
            ->select('a.*', 's.subject_name', 't.teacher_name');

        // Apply filters
        if (request('subject_id')) {
            $query->where('a.subject_id', request('subject_id'));
        }
        if (request('start_date')) {
            $query->where('a.attendance_date', '>=', request('start_date'));
        }
        if (request('end_date')) {
            $query->where('a.attendance_date', '<=', request('end_date'));
        }
        if (request('status')) {
            $query->where('a.attendance_status', request('status'));
        }

        $attendances = $query->orderBy('a.attendance_date', 'DESC')->paginate(25);

        // Calculate statistics
        $total_days = $attendances->count();
        $present_count = $attendances->where('attendance_status', 'present')->count();
        $absent_count = $attendances->where('attendance_status', 'absent')->count();
        $attendance_rate = $total_days > 0 ? ($present_count / $total_days) * 100 : 0;

        // Get all subjects for filter dropdown
        $subjects = Subject::orderBy('subject_name', 'ASC')->get();

        return view('students.viewStudentAttendance', compact(
            'student', 
            'attendances', 
            'total_days', 
            'present_count', 
            'absent_count', 
            'attendance_rate',
            'subjects'
        ));
    }

    public function getStudentsByYear($year)
    {
        $students = DB::table('students as s')
            ->leftJoin('student_subjects as ss', 's.student_id', '=', 'ss.student_id')
            ->leftJoin('subjects as sub', 'ss.subject_id', '=', 'sub.subject_id')
            ->select(
                's.student_id',
                's.bise_roll_no',
                's.reg_no',
                's.name',
                's.father_name',
                's.cnic',
                DB::raw('GROUP_CONCAT(CASE WHEN sub.subject_type = "elective" THEN sub.subject_name ORDER BY sub.subject_name SEPARATOR ", ") as electives')
            )
            ->where('s.admission_year', $year)
            ->groupBy('s.student_id')
            ->orderBy('s.bise_roll_no')
            ->get();

        // Process electives into individual columns
        $processed_students = [];
        foreach ($students as $student) {
            $elective_array = explode(', ', $student->electives ?? '');
            $processed_students[] = [
                'student_id' => $student->student_id,
                'bise_roll_no' => $this->applyRollNumberAdjustment($student->bise_roll_no, $year), // Apply adjustment here
                'reg_no' => $student->reg_no,
                'name' => $student->name,
                'father_name' => $student->father_name,
                'cnic' => $student->cnic,
                'elective_1' => isset($elective_array[0]) ? $elective_array[0] : 'N/A',
                'elective_2' => isset($elective_array[1]) ? $elective_array[1] : 'N/A',
                'elective_3' => isset($elective_array[2]) ? $elective_array[2] : 'N/A',
            ];
        }

        return response()->json(['students' => $processed_students]);
    }

    public function getStudent($id)
    {
        $student = Student::find($id);
        
        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }
        
        // Apply roll number adjustment for single student retrieval
        $student->adjusted_bise_roll_no = $this->applyRollNumberAdjustment($student->bise_roll_no, $student->admission_year);
        
        return response()->json(['student' => $student]);
    }

    public function exportGroupsCSV($year)
    {
        $students = Student::where('admission_year', $year)
            ->with(['subjects' => fn($q) => $q->where('subject_type', 'elective')])
            ->orderBy('bise_roll_no', 'asc')
            ->get();

        $classifyGroup = function ($subjects) {
            $subs = array_map('strtolower', $subjects);
            sort($subs);
            $has = fn($sub) => in_array(true, array_map(fn($s) => strpos($s, strtolower($sub)) !== false, $subs));

            if ($has('biology') && $has('physics') && $has('chemistry')) return 'Pre-Medical';
            if ($has('math') && $has('physics') && $has('chemistry')) return 'Pre-Engineering';
            if ($has('math') && $has('computer')) return 'ICS';
            if ($has('computer')) return 'FA-IT';
            return 'FA';
        };

        $data = [];
        foreach ($students as $student) {
            $subject_names = $student->subjects->pluck('subject_name')->toArray();
            $group = count($subject_names) > 0 ? $classifyGroup($subject_names) : 'Unknown';
            
            // Apply roll number adjustment for CSV export
            $adjusted_roll_no = $this->applyRollNumberAdjustment($student->bise_roll_no, $year);
            
            $data[] = [
                'Group' => $group,
                'BISE Roll No' => $adjusted_roll_no,
                'Name' => $student->name,
                'Father Name' => $student->father_name,
                'Subjects' => implode(', ', $subject_names)
            ];
        }

        $filename = "grouped_students_$year.csv";
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=$filename"];
        $callback = function() use ($data) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Group', 'BISE Roll No', 'Name', 'Father Name', 'Subjects']);
            foreach ($data as $row) fputcsv($output, $row);
            fclose($output);
        };
        return response()->stream($callback, 200, $headers);
    }


    public function showUploadForm()
    {
        $subjects = Subject::orderBy('subject_name', 'ASC')->get();
        return view('students.upload', compact('subjects'));
    }

    public function uploadCSV(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');
        $filePath = $file->getRealPath();

        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0); // Set the header offset to the first row

            foreach ($csv as $record) {
                $name = $record['Student Name'];
                $father_name = $record['Father Name'];
                $cnic = preg_replace('/[^0-9]/', '', $record['CNIC']); // Remove hyphens
                $college_roll_no = $record['College Roll No YYNNNN'];
                $admission_year = $record['Admission Year'];

                $student = Student::create([
                    'name' => $name,
                    'father_name' => $father_name,
                    'cnic' => $cnic,
                    'bise_roll_no' => $college_roll_no,
                    'reg_no' => $college_roll_no, // Assuming reg_no is the same as college_roll_no
                    'admission_year' => $admission_year,
                    'enrollment_status' => 'enrolled',
                ]);

                $subject_ids = [];
                for ($i = 1; $i <= 8; $i++) {
                    $subject_id = $record['Sub' . $i . ' ID'];
                    if ($subject_id) {
                        $subject_ids[] = $subject_id;
                    }
                }

                $student->subjects()->sync($subject_ids);
            }

            return back()->with('success', 'Students uploaded successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading students: ' . $e->getMessage());
        }
    }

    public function downloadSampleCSV()
    {
        $sampleData = [
            ['Student Name', 'Father Name', 'CNIC', 'College Roll No YYNNNN', 'Admission Year', 'Sub1 ID', 'Sub2 ID', 'Sub3 ID', 'Sub4 ID', 'Sub5 ID', 'Sub6 ID', 'Sub7 ID', 'Sub8 ID'],
            ['Ali Raza', 'Ahmed Raza', '3520212345678', '240001', '2024', '1', '2', '3', '4', '5', '6', '8', '13'],
            ['Sara Khan', 'Imran Khan', '3520187654329', '250402', '2024', '1', '2', '3', '4', '5', '6', '7', '8'],
            ['Usman Tariq', 'Tariq Mehmood', '3510312398764', '240102', '2024', '1', '2', '3', '4', '5', '9', '15', '16'],
            ['Fatima Noor', 'Rashid Noor', '3530165432107', '241426', '2024', '1', '2', '3', '4', '5', '11', '13', '20'],
            ['Hamza Ali', 'Shahid Ali', '3520011223345', '242829', '2024', '1', '2', '3', '4', '5', '11', '18', '19'],
        ];

        $filename = 'sample_students.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=$filename"];

        $callback = function() use ($sampleData) {
            $output = fopen('php://output', 'w');
            foreach ($sampleData as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function attachSubjectsToStudent(Student $student, array $subjects)
    {
        foreach ($subjects as $subjectId) {
            StudentSubject::create([
                'student_id' => $student->student_id,
                'subject_id' => $subjectId,
            ]);
        }
    }
}
