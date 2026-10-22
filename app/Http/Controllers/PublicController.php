<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Datesheet;
use App\Models\Admissions;
use App\Models\HeAdmission;
use App\Models\Program;
use App\Models\Subject;
use App\Models\BsSubject;
use App\Models\Semester;
use App\Models\BannerSlide;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function index()
    {
        // Load institute settings
        $instituteSettings = \App\Models\InstituteSetting::getSettings();

        // Statistics for the home page
        $stats = [
            'students' => Admissions::whereIn('status', ['Active', 'Admitted'])->count() + HeAdmission::whereIn('status', ['Active', 'Admitted'])->count(),
            'teachers' => Staff::where('category', 'teaching')->where('status', 'active')->count(),
            'graduated' => Admissions::where('status', 'Graduated')->count() + HeAdmission::where('status', 'Graduated')->count(),
            'years_active' => date('Y') - 1964 // Assuming established in 1964
        ];

        // Recent downloads (limit to 3 for ticker, keep 6 for section)
        $downloads = \App\Models\Download::orderBy('published_date', 'desc')->take(6)->get();
        $tickerDownloads = $downloads->take(3);

        // Recent news/updates
        $recentUpdates = \App\Models\Update::where('status', 1)
            ->orderBy('date', 'desc')
            ->take(3)
            ->get();

        // Upcoming Exams Summary
        $upcomingInter = \App\Models\Datesheet::where('test_date', '>=', now()->toDateString())
            ->orderBy('test_date', 'asc')
            ->get();
        
        $upcomingHe = \App\Models\HeDatesheet::where('exam_date', '>=', now()->toDateString())
            ->orderBy('exam_date', 'asc')
            ->get();
            
        $examSummary = null;
        if ($upcomingInter->isNotEmpty() || $upcomingHe->isNotEmpty()) {
            $allDates = $upcomingInter->pluck('test_date')
                ->merge($upcomingHe->pluck('exam_date'))
                ->sort();
            
            $start = $allDates->first();
            $end = $allDates->last();
            
            if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
                $examSummary = "Upcoming Exam: " . $start->format('d M, Y');
            } else {
                $examSummary = "Upcoming Exams: " . $start->format('d M') . " to " . $end->format('d M, Y');
            }
        }

        // Admission Status for Ticker
        $admissionTicker = null;
        $activeAdmission = \App\Models\AdmissionSetting::where('is_open', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if ($activeAdmission) {
            $admissionTicker = [
                'label' => "Admissions Open for " . $activeAdmission->program_type . " (" . $activeAdmission->admission_year . ") - Apply Now!",
                'url' => route('apply.form', ['program' => $activeAdmission->program_type])
            ];
        } else {
            $futureAdmission = \App\Models\AdmissionSetting::where('start_date', '>', now())
                ->orderBy('start_date', 'asc')
                ->first();
            if ($futureAdmission) {
                $admissionTicker = [
                    'label' => "Admissions opening for " . $futureAdmission->program_type . " on " . $futureAdmission->start_date->format('d M, Y'),
                    'url' => route('public.programs')
                ];
            }
        }

        // Featured Staff...
        $featuredStaff = Staff::where('category', 'teaching')
            ->where('status', 'active')
            ->inRandomOrder()
            ->take(8)
            ->get();

        // Alumni (Graduated Students)
        $alumni = Admissions::with('student')
            ->where('status', 'Graduated')
            ->inRandomOrder()
            ->get();

        // Current Principal
        $currentPrincipal = \App\Models\Principal::where('status', 1)
            ->where(function ($query) {
                $query->whereNull('to_date')
                      ->orWhere('to_date', '>=', now());
            })
            ->orderBy('from_date', 'desc')
            ->first();

        // Banner slides for the hero carousel
        $bannerSlides = BannerSlide::getActiveSlides();

        return view('public.home', compact('stats', 'downloads', 'tickerDownloads', 'featuredStaff', 'alumni', 'instituteSettings', 'currentPrincipal', 'recentUpdates', 'examSummary', 'admissionTicker', 'bannerSlides'));
    }

    public function staff()
    {
        $staffMembers = Staff::where('status', 'active')
            ->orderByRaw("FIELD(category, 'teaching', 'non_teaching')") // Teaching first
            ->orderBy('bps', 'desc') // Seniority by BPS
            ->orderBy('entry_date_govt_service', 'asc') // Then by seniority
            ->paginate(24);

        return view('public.staff', compact('staffMembers'));
    }

    public function programs()
    {
        // HSSC Compulsory Subjects
        $compulsorySubjects = Subject::where('type', 'Compulsory')
            ->whereIn('class_level', ['11', '12', 'both'])
            ->orderBy('name')
            ->get();

        // HSSC Subject Groups (Electives)
        $subjectGroups = \App\Models\SubjectGroup::with('subjects')->orderBy('name')->get();

        // HE Programs with Semester Subjects
        $programs = Program::with(['semesters.bsSubject'])
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        return view('public.programs', compact('compulsorySubjects', 'subjectGroups', 'programs'));
    }

    public function department(\App\Models\Subject $subject)
    {
        $staffMembers = Staff::where('category', 'teaching')
            ->whereHas('subjectSpecializations', function($q) use ($subject) {
                $q->where('subject_id', $subject->id);
            })
            ->orderByRaw("CASE WHEN status = 'active' THEN 1 ELSE 2 END")
            ->orderBy('bps', 'desc')
            ->orderBy('entry_date_govt_service', 'asc')
            ->paginate(12);

        $departmentName = $subject->name;

        // Calculate Statistics
        $stats = [
            'enrolled_by_year' => [],
            'graduated' => 0,
            'class_11' => 0,
            'class_12' => 0,
            'pass_percentage' => 0,
            'fail_percentage' => 0,
        ];

        // Helper to get student IDs for this subject
        $compulsoryStudentIds = $subject->studentCompulsorySubjects()->pluck('student_id');
        $electiveStudentIds = $subject->studentElectives()->pluck('student_id');
        $allStudentIds = $compulsoryStudentIds->merge($electiveStudentIds)->unique();

        if ($allStudentIds->isNotEmpty()) {
            // Enrolled by Admission Year
            $stats['enrolled_by_year'] = \App\Models\Admissions::whereIn('student_id', $allStudentIds)
                ->selectRaw('admission_year, count(*) as count')
                ->groupBy('admission_year')
                ->orderBy('admission_year', 'desc')
                ->pluck('count', 'admission_year')
                ->toArray();

            // Current Class 11 & 12
            $stats['class_11'] = \App\Models\Admissions::whereIn('student_id', $allStudentIds)
                ->where('current_class', '11')
                ->whereIn('status', ['Admitted', 'Active'])
                ->count();

            $stats['class_12'] = \App\Models\Admissions::whereIn('student_id', $allStudentIds)
                ->where('current_class', '12')
                ->whereIn('status', ['Admitted', 'Active'])
                ->count();

            // Graduated
            $stats['graduated'] = \App\Models\Admissions::whereIn('student_id', $allStudentIds)
                ->where('status', 'Graduated')
                ->count();

            // Results (Pass/Fail)
            // Assuming we have a Result model linked to subject
            $results = \App\Models\Result::where('subject_id', $subject->id)->get();
            $totalResults = $results->count();
            if ($totalResults > 0) {
                $passed = $results->where('marks_obtained', '>=', $subject->getRequiredMarksForPassing())->count();
                $stats['pass_percentage'] = round(($passed / $totalResults) * 100, 1);
                $stats['fail_percentage'] = round(100 - $stats['pass_percentage'], 1);
            }
        }

        $teacherStatsData = \App\Models\StudentTeacherSubject::where('subject_id', $subject->id)
            ->select('staff_id', 'academic_year', \DB::raw('count(distinct student_id) as student_count'))
            ->groupBy('staff_id', 'academic_year')
            ->orderBy('academic_year', 'desc')
            ->get();

        $stats['teacher_stats'] = [];
        foreach ($teacherStatsData as $data) {
            if ($data->academic_year) {
                $stats['teacher_stats'][$data->staff_id][$data->academic_year] = $data->student_count;
            }
        }

        return view('public.staff', compact('staffMembers', 'departmentName', 'stats'));
    }

    public function downloads(Request $request)
    {
        $query = \App\Models\Download::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $downloads = $query->orderBy('published_date', 'desc')->paginate(50);
        return view('public.downloads', compact('downloads'));
    }

    public function datesheets()
    {
        $datesheets = \App\Models\Datesheet::where('status', 'active')
            ->orderBy('publish_date', 'desc')
            ->paginate(10);
            
        return view('public.datesheets', compact('datesheets'));
    }

    public function gallery()
    {
        $albums = \App\Models\Album::where('status', 1)
            ->with(['photos' => function($query) {
                $query->where('status', 1)->limit(1);
            }])
            ->withCount(['photos' => function($query) {
                $query->where('status', 1);
            }])
            ->orderBy('date', 'desc')
            ->paginate(12);
            
        return view('public.gallery', compact('albums'));
    }

    public function album($id)
    {
        $album = \App\Models\Album::where('status', 1)->with(['photos' => function($query) {
            $query->where('status', 1);
        }])->findOrFail($id);
        
        return view('public.album', compact('album'));
    }

    public function news()
    {
        $updates = \App\Models\Update::where('status', 1)
            ->orderBy('date', 'desc')
            ->paginate(10);
            
        return view('public.news', compact('updates'));
    }

    public function principals()
    {
        $principals = \App\Models\Principal::orderBy('is_current', 'desc')
            ->orderBy('from_date', 'desc')
            ->get();
            
        return view('public.principals', compact('principals'));
    }

    public function principalShow($id)
    {
        $principal = \App\Models\Principal::findOrFail($id);
        return view('public.principal-show', compact('principal'));
    }


    public function studentsCorner()
    {
        // Get distinct admission years for dropdown
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');
            
        // Get HE Admission Years too
        $heAdmissionYears = \App\Models\HeAdmission::select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');
            
        $admissionYears = $admissionYears->merge($heAdmissionYears)->unique()->sortDesc();

        $programs = \App\Models\Program::orderBy('name')->get();

        // Generate a simple math captcha
        $n1 = rand(1, 10);
        $n2 = rand(1, 10);
        session(['captcha_answer' => $n1 + $n2]);
        $captcha_question = "$n1 + $n2";
            
        return view('public.students-corner', compact('admissionYears', 'programs', 'captcha_question'));
    }

    public function searchStudent(Request $request)
    {
        // Handle GET request by redirecting to the search form
        if ($request->isMethod('get')) {
            return redirect()->route('public.students-corner');
        }

        $request->validate([
            'admission_year' => 'required|integer',
            'search_value' => 'required|string',
            'program_type' => 'required|string', // 'hssc' or program_id
            'captcha' => 'required|integer',
        ]);

        if ($request->captcha != session('captcha_answer')) {
            return back()->with('error', 'Incorrect CAPTCHA answer. Please try again.')->withInput();
        }

        $admissionYear = $request->admission_year;
        $searchValue = $request->search_value;
        $programType = $request->program_type;

        if ($programType === 'hssc') {
            return $this->searchHsscStudent($admissionYear, $searchValue);
        } else {
            return $this->searchHeStudent($admissionYear, $searchValue, $programType);
        }
    }

    private function searchHsscStudent($admissionYear, $searchValue)
    {
        $admission = Admissions::withTrashed()
            ->where('admission_year', $admissionYear)
            ->where(function($query) use ($searchValue) {
                // Exact match for roll numbers
                $query->where('college_roll_no', $searchValue)
                      ->orWhere('bise_roll_11', $searchValue)
                      ->orWhere('bise_roll_12', $searchValue)
                      // LIKE search for names
                      ->orWhereHas('student', function($q) use ($searchValue) {
                          $q->withTrashed()->where(function($sq) use ($searchValue) {
                              $sq->where('first_name', 'LIKE', "%{$searchValue}%")
                                 ->orWhere('last_name', 'LIKE', "%{$searchValue}%")
                                 ->orWhereHas('father', function($fq) use ($searchValue) {
                                     $fq->where('name', 'LIKE', "%{$searchValue}%");
                                 });
                          });
                      });
            })
            ->with(['student' => function($q) {
                $q->withTrashed()->with(['father', 'sscRecord', 'compulsorySubjects.subject', 'electives.subject', 'vouchers', 'fines.fineRule']);
            }])
            ->first();

        if (!$admission) {
            return redirect()->route('public.students-corner')
                ->with('error', 'No HSSC student found with the provided information in admission year ' . $admissionYear);
        }

        $student = $admission->student;

        // Check if records are soft-deleted
        if ($admission->trashed() || ($student && $student->trashed())) {
            return redirect()->route('public.students-corner')
                ->with('error', 'Your records are deleted, contact college administration.');
        }

        if (!$student) {
            return redirect()->route('public.students-corner')
                ->with('error', 'No HSSC student found with the provided information in admission year ' . $admissionYear);
        }

        // Get compulsory subjects with their assigned teachers
        $compulsorySubjects = $student->compulsorySubjects->map(function($scs) use ($student) {
            // Get teacher assigned to this student for this subject
            $studentTeacher = \App\Models\StudentTeacherSubject::where('student_id', $student->id)
                ->where('subject_id', $scs->subject_id)
                ->with('staff')
                ->first();
            
            // Get attendance stats
            $attendanceStats = \App\Models\Attendance::where('student_id', $student->id)
                ->where('subject_id', $scs->subject_id)
                ->selectRaw('count(*) as delivered, sum(case when status in ("present", "late") then 1 else 0 end) as attended')
                ->first();

            return [
                'subject' => $scs->subject,
                'teacher' => $studentTeacher ? $studentTeacher->staff : null,
                'delivered' => $attendanceStats->delivered ?? 0,
                'attended' => $attendanceStats->attended ?? 0
            ];
        });

        // Get elective subjects with their assigned teachers
        $electiveSubjects = $student->electives->map(function($se) use ($student) {
            // Get teacher assigned to this student for this subject
            $studentTeacher = \App\Models\StudentTeacherSubject::where('student_id', $student->id)
                ->where('subject_id', $se->subject_id)
                ->with('staff')
                ->first();
            
            // Get attendance stats
            $attendanceStats = \App\Models\Attendance::where('student_id', $student->id)
                ->where('subject_id', $se->subject_id)
                ->selectRaw('count(*) as delivered, sum(case when status in ("present", "late") then 1 else 0 end) as attended')
                ->first();

            return [
                'subject' => $se->subject,
                'teacher' => $studentTeacher ? $studentTeacher->staff : null,
                'delivered' => $attendanceStats->delivered ?? 0,
                'attended' => $attendanceStats->attended ?? 0
            ];
        });

        // Get all test results
        $allResults = \App\Models\Result::where('student_id', $student->id)
            ->with(['subject'])
            ->orderByDesc('academic_year')
            ->orderBy('exam_type')
            ->get()
            ->groupBy('exam_type');

        // Extract final results for the existing final results section if needed
        $finalResults = \App\Models\Result::where('student_id', $student->id)
            ->where('exam_type', 'Final')
            ->with(['subject'])
            ->get();

        // Get upcoming exams for this student
        // First, get all subject IDs the student is enrolled in (compulsory + elective)
        $studentSubjectIds = $student->compulsorySubjects->pluck('subject_id')
            ->merge($student->electives->pluck('subject_id'))
            ->unique();

        $upcomingExams = \App\Models\Datesheet::where('admission_year', $admission->admission_year)
            ->where('test_date', '>=', now('Asia/Karachi')->toDateString())
            ->whereIn('subject_id', $studentSubjectIds) // Only subjects student is enrolled in
            ->with(['subject'])
            ->orderBy('test_date', 'asc')
            ->get()
            ->map(function($datesheet) use ($student) {
                // Get room assignment from seating plan details
                $seatingDetail = \App\Models\SeatingPlanDetail::whereHas('seatingPlan', function($query) use ($datesheet) {
                    $query->where('test_date', $datesheet->test_date);
                })
                ->where('student_id', $student->id)
                ->where('subject_id', $datesheet->subject_id)
                ->with('room')
                ->first();

                return [
                    'subject' => $datesheet->subject,
                    'test_date' => $datesheet->test_date,
                    'room' => $seatingDetail ? $seatingDetail->room : null,
                ];
            });

        // Personal Timetable Logic
        $compulsorySubjectIds = $student->compulsorySubjects->pluck('subject_id');
        $electiveSubjectIds = $student->electives->pluck('subject_id');
        $studentSubjectIds = $compulsorySubjectIds->merge($electiveSubjectIds)->filter()->unique()->values();

        $subjectSectionsMap = [];
        foreach ($studentSubjectIds as $subId) {
            // From Student Affairs
            $saSec = \App\Models\StudentSubjectSection::where('student_id', $student->id)
                ->where('subject_id', $subId)
                ->whereNotNull('section')
                ->value('section');
                
            if ($saSec) {
                $subjectSectionsMap[$subId] = [
                    'sa_section' => $saSec,
                    'all_sections' => [$saSec]
                ];
            }
        }

        $timetableEntries = \App\Models\TimetableEntry::with(['subject', 'staff', 'timeSlot', 'room'])
            ->where('admission_year', $admission->admission_year)
            ->whereIn('subject_id', $studentSubjectIds)
            ->where(function ($query) use ($studentSubjectIds, $subjectSectionsMap) {
                if ($studentSubjectIds->isEmpty()) {
                    $query->whereRaw('1=0');
                } else {
                    foreach ($studentSubjectIds as $subId) {
                        $query->orWhere(function ($q) use ($subId, $subjectSectionsMap) {
                            $q->where('subject_id', $subId);
                            if (isset($subjectSectionsMap[$subId])) {
                                $q->where(function($sq) use ($subjectSectionsMap, $subId) {
                                    $sq->whereIn('section', $subjectSectionsMap[$subId]['all_sections'])
                                       ->orWhereNull('section');
                                });
                            }
                        });
                    }
                }
            })
            ->get();

        // Organize into grid
        $timeSlots = \App\Models\TimeSlot::orderBy('slot_number')->get();
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $timetableGrid = [];

        foreach ($timetableEntries as $entry) {
            $slotId = $entry->time_slot_id;
            $day = $entry->day_of_week;
            $subId = $entry->subject_id;
            $saSec = $subjectSectionsMap[$subId]['sa_section'] ?? null;

            if (!isset($timetableGrid[$slotId][$day])) {
                $timetableGrid[$slotId][$day] = collect();
            }

            $currentForSubject = $timetableGrid[$slotId][$day]->where('subject_id', $subId);
            if ($currentForSubject->isNotEmpty() && $saSec) {
                $hasExactSaMatch = $currentForSubject->contains(function($item) use ($saSec) {
                    return $item->section === $saSec;
                });
                if ($hasExactSaMatch && $entry->section !== $saSec) {
                    continue;
                }
                if (!$hasExactSaMatch && $entry->section === $saSec) {
                    $timetableGrid[$slotId][$day] = $timetableGrid[$slotId][$day]->reject(function($item) use ($subId) {
                        return $item->subject_id === $subId;
                    });
                }
            }

            $timetableGrid[$slotId][$day]->push($entry);
        }

        return view('public.student-profile', compact(
            'student', 
            'admission', 
            'compulsorySubjects', 
            'electiveSubjects', 
            'finalResults',
            'allResults',
            'upcomingExams',
            'timeSlots',
            'daysOfWeek',
            'timetableGrid'
        ));
    }

    private function searchHeStudent($admissionYear, $searchValue, $programId)
    {
        // Search in HE Admissions
        $admission = \App\Models\HeAdmission::withTrashed()
            ->where('admission_year', $admissionYear)
            ->where('program_id', $programId)
            ->where(function($query) use ($searchValue) {
                // Exact match for roll number
                $query->where('roll_no', $searchValue)
                      // LIKE search for names (via Student relationship)
                      ->orWhereHas('student', function($q) use ($searchValue) {
                          $q->withTrashed()->where(function($sq) use ($searchValue) {
                              $sq->where('first_name', 'LIKE', "%{$searchValue}%")
                                ->orWhere('last_name', 'LIKE', "%{$searchValue}%")
                                ->orWhereHas('father', function($fq) use ($searchValue) {
                                    $fq->where('name', 'LIKE', "%{$searchValue}%");
                                });
                          });
                      });
            })
            ->with(['student' => function($q) {
                $q->withTrashed()->with(['father', 'vouchers', 'fines.fineRule']);
            }, 'program'])
            ->first();

        if (!$admission) {
             return redirect()->route('public.students-corner')
                ->with('error', 'No student found in specified program for admission year ' . $admissionYear);
        }

        $student = $admission->student;

        // Check if records are soft-deleted
        if ($admission->trashed() || ($student && $student->trashed())) {
            return redirect()->route('public.students-corner')
                ->with('error', 'Your records are deleted, contact college administration.');
        }

        if (!$student) {
            return redirect()->route('public.students-corner')
                ->with('error', 'No student found in specified program for admission year ' . $admissionYear);
        }
        $program = $admission->program;

        // Get Enrolled Subjects & Results
        // Current Semester Subjects
        $currentSubjects = \App\Models\HeStudentSubject::where('he_admission_id', $admission->id)
            ->where('semester_no', $admission->current_semester)
            ->with('bsSubject')
            ->get()
            ->map(function($ss) use ($admission) {
                $attendanceStats = \App\Models\HeAttendance::where('he_admission_id', $admission->id)
                    ->where('bs_subject_id', $ss->bs_subject_id)
                    ->selectRaw('count(*) as delivered, sum(case when status in ("present", "late") then 1 else 0 end) as attended')
                    ->first();
                
                return [
                    'bsSubject' => $ss->bsSubject,
                    'delivered' => $attendanceStats->delivered ?? 0,
                    'attended' => $attendanceStats->attended ?? 0
                ];
            });

        // Get All Results (Transcript style?)
        $allResults = \App\Models\HeResult::where('he_admission_id', $admission->id)
            ->orderBy('semester_no')
            ->with('bsSubject')
            ->get()
            ->groupBy('semester_no');

        $upcomingExams = \App\Models\HeDatesheet::where('program_id', $program->id)
            ->where('semester_no', $admission->current_semester)
            ->where('academic_year', $admission->academic_year ?? date('Y')) // approximate
            ->where('exam_date', '>=', now())
            ->with('bsSubject')
            ->orderBy('exam_date')
            ->get();

        // HE Timetable
        $heTimetable = \App\Models\HeTimetable::where('program_id', $admission->program_id)
            ->where('semester_no', $admission->current_semester)
            ->with(['subject', 'room', 'staff'])
            ->get();
            
        $heGrid = [];
        foreach ($heTimetable as $t) {
            $heGrid[$t->period_number][$t->day_of_week] = $t;
        }
        $heDays = \App\Models\HeTimetable::getDaysOfWeek();
        $hePeriods = \App\Models\HeTimetable::getPeriodTimes();

        return view('public.student-profile-he', compact(
            'student',
            'admission',
            'program',
            'currentSubjects',
            'allResults',
            'upcomingExams',
            'heGrid',
            'heDays',
            'hePeriods'
        ));
    }

    public function downloadStudentVoucher(\App\Models\FeeVoucher $voucher)
    {
        $voucher->load(['items', 'student.admissions', 'student.heAdmissions', 'student.attribute', 'student.contact', 'application']);
        $settings = \App\Models\InstituteSetting::getSettings();
        $printable = \App\Services\Voucher\VoucherFactory::create($voucher, $settings);
        
        return view('pdfs.fee-voucher', ['voucher' => $printable, 'settings' => $settings]);
    }

    public function downloadFineVoucher(\App\Models\StudentFine $fine)
    {
        $fine->load(['student.father', 'student.attribute', 'student.contact', 'student.admissions', 'student.vouchers', 'admission.student', 'fineRule']);
        $settings = \App\Models\InstituteSetting::getSettings();
        $printable = \App\Services\Voucher\VoucherFactory::create($fine, $settings);
        
        return view('pdfs.fee-voucher', ['voucher' => $printable, 'settings' => $settings]);
    }


    public function about()
    {
        return view('public.about');
    }

    public function contact()
    {
        return view('public.contact');
    }

    public function dutyRosterLookup(Request $request)
    {
        $assignments = [];
        $selectedStaff = null;
        
        $staffId = auth()->user()->staff_id;
        
        if ($staffId) {
            $selectedStaff = Staff::find($staffId);
            if ($selectedStaff) {
                $assignments = \App\Models\InvigilatorAssignment::where('staff_id', $staffId)
                    ->where('exam_date', '>=', now('Asia/Karachi')->toDateString())
                    ->with('room')
                    ->orderBy('exam_date')
                    ->get();
            }
        }
        
        return view('public.duty_roster', compact('assignments', 'selectedStaff'));
    }

    public function serveImage($path)
    {
        $storagePath = storage_path('app/public');
        $fullPath = $storagePath . '/' . $path;

        // Security check: ensure the file is inside the public storage directory
        if (!file_exists($fullPath) || strpos(realpath($fullPath), realpath($storagePath)) !== 0 || is_dir($fullPath)) {
            abort(404);
        }

        $file = file_get_contents($fullPath);
        $type = mime_content_type($fullPath);

        return response($file)->header('Content-Type', $type)
                              ->header('Cache-Control', 'public, max-age=86400');
    }
}
