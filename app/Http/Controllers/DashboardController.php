<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\Admissions;
use App\Models\HeAdmission;
use App\Models\Program;
use App\Models\BsSubject;
use App\Models\FeeVoucher;
use App\Models\AdmissionApplication;
use App\Models\StudentTeacherSubject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function clearCache()
    {
        Artisan::call('optimize:clear');
        return redirect()->back()->with('success', 'System cache cleared successfully!');
    }
    public function index()
    {
        $user = Auth::user();
        
        $activeRole = session('active_role');

        if (!$activeRole) {
            if ($user->is_super_admin) {
                $activeRole = 'admin';
            } elseif ($user->roles->isNotEmpty()) {
                // Default priority if multiple roles exist and no active role selected
                if ($user->hasRole('admin')) $activeRole = 'admin';
                elseif ($user->hasRole('principal')) $activeRole = 'principal';
                elseif ($user->hasRole('clerk')) $activeRole = 'clerk';
                elseif ($user->hasRole('teacher')) $activeRole = 'teacher';
                else $activeRole = $user->roles->first()->name;
            } else {
                $activeRole = 'student';
            }
            session(['active_role' => $activeRole]);
        }

        if ($activeRole === 'admin' || $activeRole === 'principal') {
            return $this->adminDashboard();
        } elseif ($activeRole === 'teacher') {
            $staff = $user->staff;
            if (!$staff) {
                return view('dashboards.teacher', ['error' => 'No staff record linked to this user.']);
            }

            // Get subjects and student counts for this teacher
            $myClasses = DB::table('student_teacher_subjects')
                ->join('subjects', 'student_teacher_subjects.subject_id', '=', 'subjects.id')
                ->join('admissions', 'student_teacher_subjects.student_id', '=', 'admissions.student_id')
                ->join('students', 'student_teacher_subjects.student_id', '=', 'students.id')
                ->where('student_teacher_subjects.staff_id', $staff->id)
                ->whereIn('admissions.status', ['active', 'admitted'])
                ->whereNull('student_teacher_subjects.deleted_at')
                ->whereNull('admissions.deleted_at')
                ->whereNull('students.deleted_at')
                ->select(
                    'subjects.id as subject_id',
                    'subjects.name as subject_name',
                    'admissions.admission_year',
                    'admissions.current_class',
                    DB::raw('count(distinct student_teacher_subjects.student_id) as student_count')
                )
                ->groupBy('subjects.id', 'subjects.name', 'admissions.admission_year', 'admissions.current_class')
                ->orderBy('admissions.admission_year', 'desc')
                ->get();

            $myHeClasses = DB::table('he_teacher_assignments')
                ->join('bs_subjects', 'he_teacher_assignments.bs_subject_id', '=', 'bs_subjects.id')
                ->join('he_admissions', 'he_teacher_assignments.he_admission_id', '=', 'he_admissions.id')
                ->join('programs', 'he_admissions.program_id', '=', 'programs.id')
                ->join('students', 'he_admissions.student_id', '=', 'students.id')
                ->where('he_teacher_assignments.staff_id', $staff->id)
                ->whereIn('he_admissions.status', ['Active', 'Admitted'])
                ->whereNull('he_admissions.deleted_at')
                ->whereNull('students.deleted_at')
                ->select(
                    'bs_subjects.id as subject_id',
                    'bs_subjects.name as subject_name',
                    'bs_subjects.code as subject_code',
                    'he_admissions.admission_year',
                    'he_admissions.current_semester',
                    'programs.name as program_name',
                    'programs.id as program_id',
                    DB::raw('count(distinct he_teacher_assignments.he_admission_id) as student_count')
                )
                ->groupBy(
                    'bs_subjects.id', 
                    'bs_subjects.name', 
                    'bs_subjects.code', 
                    'he_admissions.admission_year', 
                    'he_admissions.current_semester', 
                    'programs.name', 
                    'programs.id'
                )
                ->orderBy('he_admissions.admission_year', 'desc')
                ->get();

            return view('dashboards.teacher', compact('myClasses', 'myHeClasses', 'staff'));
        } elseif ($activeRole === 'clerk') {
            $hsscStudents = Admissions::whereIn('status', ['Active', 'Admitted'])->count();
            $bsStudents = HeAdmission::whereIn('status', ['Active', 'Admitted'])->count();
            $newHssc = Admissions::where('admission_year', date('Y'))->count();
            $newBs = HeAdmission::where('admission_year', date('Y'))->count();
            return view('dashboards.clerk', compact('hsscStudents', 'bsStudents', 'newHssc', 'newBs'));
        } elseif ($activeRole === 'accountant') {
            $totalCollected = FeeVoucher::where('status', 'paid')->sum('paid_amount');
            $pendingCount = FeeVoucher::where('status', 'pending')->count();
            $recentVouchers = FeeVoucher::with('student')->latest()->limit(5)->get();
            return view('dashboards.accountant', compact('totalCollected', 'pendingCount', 'recentVouchers'));
        } elseif ($activeRole === 'hssc_clerk') {
            $studentCount = Admissions::whereIn('status', ['Active', 'Admitted'])->count();
            $newAdmissions = Admissions::where('admission_year', date('Y'))->count();
            return view('dashboards.hssc_clerk', compact('studentCount', 'newAdmissions'));
        } elseif ($activeRole === 'hssc_admin') {
            $studentCount = Admissions::whereIn('status', ['Active', 'Admitted'])->count();
            $newAdmissions = Admissions::where('admission_year', date('Y'))->count();
            $subjectCount = Subject::count();
            $systemHealth = $this->getSystemHealth();
            return view('dashboards.hssc_admin', compact('studentCount', 'newAdmissions', 'subjectCount', 'systemHealth'));
        } elseif ($activeRole === 'hssc_accountant') {
            $collected = FeeVoucher::where('status', 'paid')
                ->whereHas('application', function($q) { $q->whereNotIn('program_type', ['BS', 'ADP', 'DIT']); })
                ->sum('paid_amount');
            $pending = FeeVoucher::where('status', 'pending')
                ->whereHas('application', function($q) { $q->whereNotIn('program_type', ['BS', 'ADP', 'DIT']); })
                ->count();
            return view('dashboards.hssc_accountant', compact('collected', 'pending'));
        } elseif ($activeRole === 'bs_clerk') {
            $studentCount = HeAdmission::whereIn('status', ['Active', 'Admitted'])->count();
            $newAdmissions = HeAdmission::where('admission_year', date('Y'))->count();
            return view('dashboards.bs_clerk', compact('studentCount', 'newAdmissions'));
        } elseif ($activeRole === 'bs_admin') {
            $studentCount = HeAdmission::whereIn('status', ['Active', 'Admitted'])->count();
            $programCount = Program::count();
            $subjectCount = BsSubject::count();
            $systemHealth = $this->getSystemHealth();
            return view('dashboards.bs_admin', compact('studentCount', 'programCount', 'subjectCount', 'systemHealth'));
        } elseif ($activeRole === 'bs_accountant') {
            $collected = FeeVoucher::where('status', 'paid')
                ->whereHas('application', function($q) { $q->whereIn('program_type', ['BS', 'ADP', 'DIT']); })
                ->sum('paid_amount');
            $pending = FeeVoucher::where('status', 'pending')
                ->whereHas('application', function($q) { $q->whereIn('program_type', ['BS', 'ADP', 'DIT']); })
                ->count();
            return view('dashboards.bs_accountant', compact('collected', 'pending'));
        } elseif ($activeRole === 'student') {
            return redirect()->route('lms.dashboard');
        } else {
            return redirect()->route('lms.dashboard');
        }
    }

    private function adminDashboard()
    {
        // Optimization: Cache dashboard statistics for 5 minutes
        $dashboardData = \Illuminate\Support\Facades\Cache::remember('admin_dashboard_stats', 300, function () {
            // Students Admitted Each Year
            $studentsPerYear = Admissions::select('admission_year', DB::raw('count(*) as total'))
                ->groupBy('admission_year')
                ->orderBy('admission_year', 'desc')
                ->get();

            // Active Students by Class
            $activeStudentsByClass = Admissions::whereIn('status', ['Active', 'Admitted'])
                ->select('current_class', DB::raw('count(*) as total'))
                ->groupBy('current_class')
                ->get();

            // Total Statistics
            // Only count students who have at least one valid admission and are not soft-deleted
            $totalStudents = Student::whereHas('admissions', function($q) {
                $q->whereIn('status', ['Active', 'Admitted', 'Graduated']);
            })->orWhereHas('heAdmissions', function($q) {
                $q->whereIn('status', ['Active', 'Admitted', 'Graduated']);
            })->count();

            $activeHssc = Admissions::whereIn('status', ['Active', 'Admitted'])
                ->whereHas('student')
                ->count();
            $activeHe = HeAdmission::whereIn('status', ['Active', 'Admitted'])
                ->whereHas('student')
                ->count();
            $activeStudents = $activeHssc + $activeHe;

            $graduatedHssc = Admissions::where('status', 'Graduated')
                ->whereHas('student')
                ->count();
            $graduatedHe = HeAdmission::where('status', 'Graduated')
                ->whereHas('student')
                ->count();
            $graduatedStudents = $graduatedHssc + $graduatedHe;

            $totalStaff = Staff::count();
            $activeTeachers = Staff::where('category', 'teaching')->where('status', 'active')->count();
            $totalSubjects = Subject::count() + BsSubject::count();
            
            // Pending Leaves for Principal / Admin Approval
            $pendingLeavesCount = \App\Models\LeaveApplication::whereIn('status', ['pending', 'hod_approved'])->count();
            $recentPendingLeaves = \App\Models\LeaveApplication::with(['staff', 'leaveType'])
                ->whereIn('status', ['pending', 'hod_approved'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Active Teachers per Subject
            $teachersPerSubject = Subject::select('subjects.id', 'subjects.name', 'subjects.code')
                ->leftJoin('staff_subject_specializations', 'subjects.id', '=', 'staff_subject_specializations.subject_id')
                ->leftJoin('staff', function($join) {
                    $join->on('staff_subject_specializations.staff_id', '=', 'staff.id')
                         ->where('staff.category', '=', 'teaching')
                         ->where('staff.status', '=', 'active');
                })
                ->groupBy('subjects.id', 'subjects.name', 'subjects.code')
                ->selectRaw('COUNT(DISTINCT staff.id) as teacher_count')
                ->orderBy('subjects.name')
                ->get();

            // Students in Each Subject by Year (Class 11 and 12)
            // Optimization: Fetch all counts in a single query instead of N+1 queries
            
            // 1. Get Compulsory Subject Counts
            $compulsoryCounts = DB::table('student_compulsory_subjects')
                ->join('admissions', 'student_compulsory_subjects.student_id', '=', 'admissions.student_id')
                ->join('students', 'student_compulsory_subjects.student_id', '=', 'students.id')
                ->whereIn('admissions.status', ['Active', 'Admitted'])
                ->whereIn('admissions.current_class', ['11', '12'])
                ->whereNull('student_compulsory_subjects.deleted_at')
                ->whereNull('admissions.deleted_at')
                ->whereNull('students.deleted_at')
                ->select(
                    'student_compulsory_subjects.subject_id',
                    'admissions.current_class',
                    DB::raw('COUNT(DISTINCT student_compulsory_subjects.student_id) as count')
                )
                ->groupBy('student_compulsory_subjects.subject_id', 'admissions.current_class')
                ->get()
                ->groupBy('subject_id');

            // 2. Get Elective Subject Counts
            $electiveCounts = DB::table('student_elective_subjects')
                ->join('admissions', 'student_elective_subjects.student_id', '=', 'admissions.student_id')
                ->join('students', 'student_elective_subjects.student_id', '=', 'students.id')
                ->whereIn('admissions.status', ['Active', 'Admitted'])
                ->whereIn('admissions.current_class', ['11', '12'])
                ->whereNull('student_elective_subjects.deleted_at')
                ->whereNull('admissions.deleted_at')
                ->whereNull('students.deleted_at')
                ->select(
                    'student_elective_subjects.subject_id',
                    'admissions.current_class',
                    DB::raw('COUNT(DISTINCT student_elective_subjects.student_id) as count')
                )
                ->groupBy('student_elective_subjects.subject_id', 'admissions.current_class')
                ->get()
                ->groupBy('subject_id');

            $studentsPerSubjectByYear = Subject::select('subjects.id', 'subjects.name', 'subjects.code', 'subjects.type')
                ->orderBy('subjects.name') // Add ordering for better presentation
                ->get()
                ->map(function($subject) use ($compulsoryCounts, $electiveCounts) {
                    $class11Count = 0;
                    $class12Count = 0;

                    if ($subject->type === 'Compulsory') {
                        $counts = $compulsoryCounts->get($subject->id);
                        if ($counts) {
                            $class11Count = $counts->where('current_class', '11')->first()?->count ?? 0;
                            $class12Count = $counts->where('current_class', '12')->first()?->count ?? 0;
                        }
                    } else {
                        $counts = $electiveCounts->get($subject->id);
                        if ($counts) {
                            $class11Count = $counts->where('current_class', '11')->first()?->count ?? 0;
                            $class12Count = $counts->where('current_class', '12')->first()?->count ?? 0;
                        }
                    }

                    return [
                        'subject_name' => $subject->name,
                        'subject_code' => $subject->code,
                        'type' => $subject->type,
                        'class_11' => $class11Count,
                        'class_12' => $class12Count,
                        'total' => $class11Count + $class12Count
                    ];
                });

            // Student Status Distribution
            $studentStatusDistribution = Admissions::whereHas('student')
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get();

            // Program and Gender Statistics
            $hsscGender = Admissions::join('students', 'admissions.student_id', '=', 'students.id')
                ->whereIn('admissions.status', ['Active', 'Admitted'])
                ->whereNull('admissions.deleted_at')
                ->whereNull('students.deleted_at')
                ->select('students.gender', DB::raw('count(*) as total'))
                ->groupBy('students.gender')
                ->get()
                ->pluck('total', 'gender')
                ->toArray();

            $heGenderStats = HeAdmission::join('students', 'he_admissions.student_id', '=', 'students.id')
                ->join('programs', 'he_admissions.program_id', '=', 'programs.id')
                ->whereIn('he_admissions.status', ['Active', 'Admitted'])
                ->whereNull('he_admissions.deleted_at')
                ->whereNull('students.deleted_at')
                ->select('programs.type', 'students.gender', DB::raw('count(*) as total'))
                ->groupBy('programs.type', 'students.gender')
                ->get();

            $bsGender = $heGenderStats->where('type', 'BS')->pluck('total', 'gender')->toArray();
            $adpGender = $heGenderStats->where('type', 'ADP')->pluck('total', 'gender')->toArray();

            // Gender Distribution (Staff)
            $staffGenderDistribution = Staff::select('gender', DB::raw('count(*) as total'))
                ->groupBy('gender')
                ->get();

            // Recent Admissions (Last 5)
            $recentAdmissions = Admissions::with('student')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Religion Statistics by Admission Year
            $religionStats = DB::table('admissions')
                ->join('student_attributes', 'admissions.student_id', '=', 'student_attributes.student_id')
                ->whereNull('admissions.deleted_at')
                ->whereNull('student_attributes.deleted_at')
                ->select(
                    'admissions.admission_year',
                    DB::raw("COUNT(CASE WHEN student_attributes.religion IN ('Islam', 'Muslim', 'muslim', 'islam') THEN 1 END) as muslims"),
                    DB::raw("COUNT(CASE WHEN student_attributes.religion IN ('Christianity', 'Christian', 'christianity', 'christian') THEN 1 END) as christians"),
                    DB::raw("COUNT(CASE WHEN student_attributes.religion IN ('Sikh', 'sikh') THEN 1 END) as sikhs"),
                    DB::raw("COUNT(CASE WHEN student_attributes.religion IN ('Hindu', 'hindu') THEN 1 END) as hindus")
                )
                ->groupBy('admissions.admission_year')
                ->orderBy('admissions.admission_year', 'desc')
                ->get();

            // System Health & Data Aggregate
            $systemHealth = $this->getSystemHealth();

            return compact(
                'studentsPerYear',
                'activeStudentsByClass',
                'totalStudents',
                'activeStudents',
                'graduatedStudents',
                'totalStaff',
                'activeTeachers',
                'totalSubjects',
                'teachersPerSubject',
                'studentsPerSubjectByYear',
                'studentStatusDistribution',
                'staffGenderDistribution',
                'recentAdmissions',
                'hsscGender',
                'bsGender',
                'adpGender',
                'religionStats',
                'systemHealth',
                'pendingLeavesCount',
                'recentPendingLeaves'
            );
        });

        return view('dashboards.admin', $dashboardData);
    }

    private function getSystemHealth()
    {
        $totalDataPoints = Student::count() + Staff::count() + Subject::count() + BsSubject::count() +
                           FeeVoucher::count() + AdmissionApplication::count() + 
                           \DB::table('results')->count() + \DB::table('he_results')->count();

        $systemOk = file_exists(base_path('vendor/Sohaib/CmsCore/ProtectionService.php'))
            ? \Sohaib\CmsCore\ProtectionService::checkSystemStatus()
            : true;

        return [
            'status' => $systemOk ? 'Healthy' : 'Compromised',
            'integrity' => $systemOk,
            'total_data' => $totalDataPoints,
            'version' => '1.2.5',
            'uptime' => '99.9%',
            'heartbeat' => now()->format('H:i')
        ];
    }
}
