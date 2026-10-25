<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\Student;
use App\Models\Datesheet;
use App\Models\HeDatesheet;
use App\Models\Admissions;
use App\Models\HeAdmission;
use App\Models\Program;
use App\Models\Subject;
use App\Models\BsSubject;
use App\Models\BannerSlide;
use App\Models\Download;
use App\Models\Update;
use App\Models\AdmissionSetting;
use App\Models\Principal;
use App\Models\InstituteSetting;
use App\Models\StudentTeacherSubject;
use App\Models\StudentSubjectSection;
use App\Models\Attendance;
use App\Models\HeAttendance;
use App\Models\Result;
use App\Models\HeResult;
use App\Models\HeStudentSubject;
use App\Models\SeatingPlanDetail;
use App\Models\TimetableEntry;
use App\Models\TimeSlot;
use App\Models\HeTimetable;
use App\Models\InvigilatorAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PublicPortalService
{
    /**
     * Compile homepage metrics, announcements, upcoming exams, and sliders.
     */
    public function getHomeData(): array
    {
        $instituteSettings = InstituteSetting::getSettings();

        $stats = [
            'students' => Admissions::whereIn('status', ['Active', 'Admitted'])->count() + HeAdmission::whereIn('status', ['Active', 'Admitted'])->count(),
            'teachers' => Staff::where('category', 'teaching')->where('status', 'active')->count(),
            'graduated' => Admissions::where('status', 'Graduated')->count() + HeAdmission::where('status', 'Graduated')->count(),
            'years_active' => date('Y') - 1964,
        ];

        $downloads = Download::orderBy('published_date', 'desc')->take(6)->get();
        $tickerDownloads = $downloads->take(3);

        $recentUpdates = Update::where('status', 1)
            ->orderBy('date', 'desc')
            ->take(3)
            ->get();

        $upcomingInter = Datesheet::where('test_date', '>=', now()->toDateString())
            ->orderBy('test_date', 'asc')
            ->get();

        $upcomingHe = HeDatesheet::where('exam_date', '>=', now()->toDateString())
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

        $admissionTicker = null;
        $activeAdmission = AdmissionSetting::where('is_open', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if ($activeAdmission) {
            $admissionTicker = [
                'label' => "Admissions Open for " . $activeAdmission->program_type . " (" . $activeAdmission->admission_year . ") - Apply Now!",
                'url' => route('apply.form', ['program' => $activeAdmission->program_type])
            ];
        } else {
            $futureAdmission = AdmissionSetting::where('start_date', '>', now())
                ->orderBy('start_date', 'asc')
                ->first();
            if ($futureAdmission) {
                $admissionTicker = [
                    'label' => "Admissions opening for " . $futureAdmission->program_type . " on " . $futureAdmission->start_date->format('d M, Y'),
                    'url' => route('public.programs')
                ];
            }
        }

        $featuredStaff = Staff::where('category', 'teaching')
            ->where('status', 'active')
            ->inRandomOrder()
            ->take(8)
            ->get();

        $alumni = Admissions::with('student')
            ->where('status', 'Graduated')
            ->inRandomOrder()
            ->get();

        $currentPrincipal = Principal::where('status', 1)
            ->where(function ($query) {
                $query->whereNull('to_date')
                      ->orWhere('to_date', '>=', now());
            })
            ->orderBy('from_date', 'desc')
            ->first();

        $bannerSlides = BannerSlide::getActiveSlides();

        return compact(
            'stats',
            'downloads',
            'tickerDownloads',
            'featuredStaff',
            'alumni',
            'instituteSettings',
            'currentPrincipal',
            'recentUpdates',
            'examSummary',
            'admissionTicker',
            'bannerSlides'
        );
    }

    /**
     * Get department staff and enrollment/results statistics.
     */
    public function getDepartmentData(Subject $subject): array
    {
        $staffMembers = Staff::where('category', 'teaching')
            ->whereHas('subjectSpecializations', function ($q) use ($subject) {
                $q->where('subject_id', $subject->id);
            })
            ->orderByRaw("CASE WHEN status = 'active' THEN 1 ELSE 2 END")
            ->orderBy('bps', 'desc')
            ->orderBy('entry_date_govt_service', 'asc')
            ->paginate(12);

        $departmentName = $subject->name;

        $stats = [
            'enrolled_by_year' => [],
            'graduated' => 0,
            'class_11' => 0,
            'class_12' => 0,
            'pass_percentage' => 0,
            'fail_percentage' => 0,
        ];

        $compulsoryStudentIds = $subject->studentCompulsorySubjects()->pluck('student_id');
        $electiveStudentIds = $subject->studentElectives()->pluck('student_id');
        $allStudentIds = $compulsoryStudentIds->merge($electiveStudentIds)->unique();

        if ($allStudentIds->isNotEmpty()) {
            $stats['enrolled_by_year'] = Admissions::whereIn('student_id', $allStudentIds)
                ->selectRaw('admission_year, count(*) as count')
                ->groupBy('admission_year')
                ->orderBy('admission_year', 'desc')
                ->pluck('count', 'admission_year')
                ->toArray();

            $stats['class_11'] = Admissions::whereIn('student_id', $allStudentIds)
                ->where('current_class', '11')
                ->whereIn('status', ['Admitted', 'Active'])
                ->count();

            $stats['class_12'] = Admissions::whereIn('student_id', $allStudentIds)
                ->where('current_class', '12')
                ->whereIn('status', ['Admitted', 'Active'])
                ->count();

            $stats['graduated'] = Admissions::whereIn('student_id', $allStudentIds)
                ->where('status', 'Graduated')
                ->count();

            $results = Result::where('subject_id', $subject->id)->get();
            $totalResults = $results->count();
            if ($totalResults > 0) {
                $passed = $results->where('marks_obtained', '>=', $subject->getRequiredMarksForPassing())->count();
                $stats['pass_percentage'] = round(($passed / $totalResults) * 100, 1);
                $stats['fail_percentage'] = round(100 - $stats['pass_percentage'], 1);
            }
        }

        $teacherStatsData = StudentTeacherSubject::where('subject_id', $subject->id)
            ->select('staff_id', 'academic_year', DB::raw('count(distinct student_id) as student_count'))
            ->groupBy('staff_id', 'academic_year')
            ->orderBy('academic_year', 'desc')
            ->get();

        $stats['teacher_stats'] = [];
        foreach ($teacherStatsData as $data) {
            if ($data->academic_year) {
                $stats['teacher_stats'][$data->staff_id][$data->academic_year] = $data->student_count;
            }
        }

        return compact('staffMembers', 'departmentName', 'stats');
    }

    /**
     * Prepare student corner search form data.
     */
    public function getStudentsCornerData(): array
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        $heAdmissionYears = HeAdmission::select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        $admissionYears = $admissionYears->merge($heAdmissionYears)->unique()->sortDesc();
        $programs = Program::orderBy('name')->get();

        $n1 = rand(1, 10);
        $n2 = rand(1, 10);
        session(['captcha_answer' => $n1 + $n2]);
        $captcha_question = "$n1 + $n2";

        return compact('admissionYears', 'programs', 'captcha_question');
    }

    /**
     * Search HSSC student and build complete public profile data.
     */
    public function findHsscStudentProfile($admissionYear, $searchValue): array
    {
        $admission = Admissions::withTrashed()
            ->where('admission_year', $admissionYear)
            ->where(function ($query) use ($searchValue) {
                $query->where('college_roll_no', $searchValue)
                      ->orWhere('bise_roll_11', $searchValue)
                      ->orWhere('bise_roll_12', $searchValue)
                      ->orWhereHas('student', function ($q) use ($searchValue) {
                          $q->withTrashed()->where(function ($sq) use ($searchValue) {
                              $sq->where('first_name', 'LIKE', "%{$searchValue}%")
                                 ->orWhere('last_name', 'LIKE', "%{$searchValue}%")
                                 ->orWhereHas('father', function ($fq) use ($searchValue) {
                                     $fq->where('name', 'LIKE', "%{$searchValue}%");
                                 });
                          });
                      });
            })
            ->with(['student' => function ($q) {
                $q->withTrashed()->with(['father', 'sscRecord', 'compulsorySubjects.subject', 'electives.subject', 'vouchers', 'fines.fineRule']);
            }])
            ->first();

        if (!$admission) {
            return ['error' => 'No HSSC student found with the provided information in admission year ' . $admissionYear];
        }

        $student = $admission->student;

        if ($admission->trashed() || ($student && $student->trashed())) {
            return ['error' => 'Your records are deleted, contact college administration.'];
        }

        if (!$student) {
            return ['error' => 'No HSSC student found with the provided information in admission year ' . $admissionYear];
        }

        $compulsorySubjects = $student->compulsorySubjects->map(function ($scs) use ($student) {
            $studentTeacher = StudentTeacherSubject::where('student_id', $student->id)
                ->where('subject_id', $scs->subject_id)
                ->with('staff')
                ->first();

            $attendanceStats = Attendance::where('student_id', $student->id)
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

        $electiveSubjects = $student->electives->map(function ($se) use ($student) {
            $studentTeacher = StudentTeacherSubject::where('student_id', $student->id)
                ->where('subject_id', $se->subject_id)
                ->with('staff')
                ->first();

            $attendanceStats = Attendance::where('student_id', $student->id)
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

        $allResults = Result::where('student_id', $student->id)
            ->with(['subject'])
            ->orderByDesc('academic_year')
            ->orderBy('exam_type')
            ->get()
            ->groupBy('exam_type');

        $finalResults = Result::where('student_id', $student->id)
            ->where('exam_type', 'Final')
            ->with(['subject'])
            ->get();

        $studentSubjectIds = $student->compulsorySubjects->pluck('subject_id')
            ->merge($student->electives->pluck('subject_id'))
            ->unique();

        $upcomingExams = Datesheet::where('admission_year', $admission->admission_year)
            ->where('test_date', '>=', now('Asia/Karachi')->toDateString())
            ->whereIn('subject_id', $studentSubjectIds)
            ->with(['subject'])
            ->orderBy('test_date', 'asc')
            ->get()
            ->map(function ($datesheet) use ($student) {
                $seatingDetail = SeatingPlanDetail::whereHas('seatingPlan', function ($query) use ($datesheet) {
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

        $compulsorySubjectIds = $student->compulsorySubjects->pluck('subject_id');
        $electiveSubjectIds = $student->electives->pluck('subject_id');
        $studentSubjectIds = $compulsorySubjectIds->merge($electiveSubjectIds)->filter()->unique()->values();

        $subjectSectionsMap = [];
        foreach ($studentSubjectIds as $subId) {
            $saSec = StudentSubjectSection::where('student_id', $student->id)
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

        $timetableEntries = TimetableEntry::with(['subject', 'staff', 'timeSlot', 'room'])
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
                                $q->where(function ($sq) use ($subjectSectionsMap, $subId) {
                                    $sq->whereIn('section', $subjectSectionsMap[$subId]['all_sections'])
                                       ->orWhereNull('section');
                                });
                            }
                        });
                    }
                }
            })
            ->get();

        $timeSlots = TimeSlot::orderBy('slot_number')->get();
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
                $hasExactSaMatch = $currentForSubject->contains(function ($item) use ($saSec) {
                    return $item->section === $saSec;
                });
                if ($hasExactSaMatch && $entry->section !== $saSec) {
                    continue;
                }
                if (!$hasExactSaMatch && $entry->section === $saSec) {
                    $timetableGrid[$slotId][$day] = $timetableGrid[$slotId][$day]->reject(function ($item) use ($subId) {
                        return $item->subject_id === $subId;
                    });
                }
            }

            $timetableGrid[$slotId][$day]->push($entry);
        }

        return [
            'data' => compact(
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
            )
        ];
    }

    /**
     * Search Higher Education (BS/ADP) student and build complete public profile data.
     */
    public function findHeStudentProfile($admissionYear, $searchValue, $programId): array
    {
        $admission = HeAdmission::withTrashed()
            ->where('admission_year', $admissionYear)
            ->where('program_id', $programId)
            ->where(function ($query) use ($searchValue) {
                $query->where('roll_no', $searchValue)
                      ->orWhereHas('student', function ($q) use ($searchValue) {
                          $q->withTrashed()->where(function ($sq) use ($searchValue) {
                              $sq->where('first_name', 'LIKE', "%{$searchValue}%")
                                ->orWhere('last_name', 'LIKE', "%{$searchValue}%")
                                ->orWhereHas('father', function ($fq) use ($searchValue) {
                                    $fq->where('name', 'LIKE', "%{$searchValue}%");
                                });
                          });
                      });
            })
            ->with(['student' => function ($q) {
                $q->withTrashed()->with(['father', 'vouchers', 'fines.fineRule']);
            }, 'program'])
            ->first();

        if (!$admission) {
            return ['error' => 'No student found in specified program for admission year ' . $admissionYear];
        }

        $student = $admission->student;

        if ($admission->trashed() || ($student && $student->trashed())) {
            return ['error' => 'Your records are deleted, contact college administration.'];
        }

        if (!$student) {
            return ['error' => 'No student found in specified program for admission year ' . $admissionYear];
        }
        $program = $admission->program;

        $currentSubjects = HeStudentSubject::where('he_admission_id', $admission->id)
            ->where('semester_no', $admission->current_semester)
            ->with('bsSubject')
            ->get()
            ->map(function ($ss) use ($admission) {
                $attendanceStats = HeAttendance::where('he_admission_id', $admission->id)
                    ->where('bs_subject_id', $ss->bs_subject_id)
                    ->selectRaw('count(*) as delivered, sum(case when status in ("present", "late") then 1 else 0 end) as attended')
                    ->first();

                return [
                    'bsSubject' => $ss->bsSubject,
                    'delivered' => $attendanceStats->delivered ?? 0,
                    'attended' => $attendanceStats->attended ?? 0
                ];
            });

        $allResults = HeResult::where('he_admission_id', $admission->id)
            ->orderBy('semester_no')
            ->with('bsSubject')
            ->get()
            ->groupBy('semester_no');

        $upcomingExams = HeDatesheet::where('program_id', $program->id)
            ->where('semester_no', $admission->current_semester)
            ->where('academic_year', $admission->academic_year ?? date('Y'))
            ->where('exam_date', '>=', now())
            ->with('bsSubject')
            ->orderBy('exam_date')
            ->get();

        $heTimetable = HeTimetable::where('program_id', $admission->program_id)
            ->where('semester_no', $admission->current_semester)
            ->with(['subject', 'room', 'staff'])
            ->get();

        $heGrid = [];
        foreach ($heTimetable as $t) {
            $heGrid[$t->period_number][$t->day_of_week] = $t;
        }
        $heDays = HeTimetable::getDaysOfWeek();
        $hePeriods = HeTimetable::getPeriodTimes();

        return [
            'data' => compact(
                'student',
                'admission',
                'program',
                'currentSubjects',
                'allResults',
                'upcomingExams',
                'heGrid',
                'heDays',
                'hePeriods'
            )
        ];
    }

    /**
     * Get duty roster invigilation assignments for a staff member.
     */
    public function getDutyRosterForStaff(?int $staffId): array
    {
        $assignments = [];
        $selectedStaff = null;

        if ($staffId) {
            $selectedStaff = Staff::find($staffId);
            if ($selectedStaff) {
                $assignments = InvigilatorAssignment::where('staff_id', $staffId)
                    ->where('exam_date', '>=', now('Asia/Karachi')->toDateString())
                    ->with('room')
                    ->orderBy('exam_date')
                    ->get();
            }
        }

        return compact('assignments', 'selectedStaff');
    }
}
