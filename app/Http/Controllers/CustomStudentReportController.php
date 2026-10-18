<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admissions;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\Staff;
use App\Models\Result;
use App\Exports\CustomReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class CustomStudentReportController extends Controller
{
    /**
     * Display the dynamic custom report & query builder.
     */
    public function index(Request $request)
    {
        $context = $this->buildReportContext($request);
        return view('reports.custom-student-report.index', $context);
    }

    /**
     * Export the generated report to Excel (.xlsx).
     */
    public function export(Request $request)
    {
        $context = $this->buildReportContext($request);
        $viewMode = $context['viewMode'];

        if ($viewMode === 'summary') {
            $headings = [
                'Category / Group',
                'Total Students',
                'Male',
                'Female',
                'Struck Off',
                'Avg Attendance %',
                'Passed Count',
                'Appeared Count',
                'Pass Rate %',
                'Fee Billed (PKR)',
                'Fee Collected (PKR)',
                'Balance Due (PKR)',
                'Recovery %'
            ];

            $exportRows = [];
            foreach ($context['summaryRows'] as $row) {
                $exportRows[] = [
                    $row['label'],
                    $row['total_students'],
                    $row['male_count'],
                    $row['female_count'],
                    $row['struck_off_count'],
                    $row['avg_attendance'] . '%',
                    $row['pass_count'],
                    $row['appeared_count'],
                    $row['pass_rate'] . '%',
                    number_format($row['total_billed']),
                    number_format($row['total_paid']),
                    number_format($row['total_balance']),
                    $row['recovery_rate'] . '%'
                ];
            }

            $fileName = 'Custom_Summary_Report_' . ($context['selectedYear'] ?? 'All') . '_' . date('Ymd_His') . '.xlsx';
            return Excel::download(new CustomReportExport($exportRows, $headings, 'Summary Report'), $fileName);
        } else {
            // Detailed View Export
            $selectedColumns = $context['selectedColumns'];
            $columnDefinitions = $this->getColumnDefinitions();

            $headings = ['#'];
            foreach ($selectedColumns as $colKey) {
                $headings[] = $columnDefinitions[$colKey]['label'] ?? ucfirst(str_replace('_', ' ', $colKey));
            }

            $exportRows = [];
            foreach ($context['detailedRows'] as $index => $row) {
                $exportRow = [$index + 1];
                foreach ($selectedColumns as $colKey) {
                    $val = $row[$colKey] ?? '--';
                    if (is_array($val)) {
                        $val = implode(', ', $val);
                    }
                    $exportRow[] = (string) $val;
                }
                $exportRows[] = $exportRow;
            }

            $fileName = 'Custom_Detailed_Report_' . ($context['selectedYear'] ?? 'All') . '_' . date('Ymd_His') . '.xlsx';
            return Excel::download(new CustomReportExport($exportRows, $headings, 'Detailed Report'), $fileName);
        }
    }

    /**
     * Shared context builder for web view and export.
     */
    private function buildReportContext(Request $request): array
    {
        // 1. Available Filter Lookups
        $instituteSettings = \App\Models\InstituteSetting::getSettings();
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->whereNotNull('admission_year')
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        $subjectGroups = SubjectGroup::whereNull('deleted_at')->orderBy('name')->get(['id', 'name']);
        $subjects = Subject::whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'type']);
        $teachers = Staff::whereNull('deleted_at')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $sections = DB::table('student_subject_sections')
            ->whereNotNull('section')
            ->whereNull('deleted_at')
            ->distinct()
            ->orderBy('section')
            ->pluck('section');

        $admissionStatuses = Admissions::select('status')
            ->distinct()
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');

        // Defaults
        $selectedYear = $request->input('admission_year', $admissionYears->first() ?? null);
        $viewMode = $request->input('view_mode', 'summary'); // 'summary' or 'detailed'
        $groupBy = $request->input('group_by', 'group'); // 'group', 'class', 'section', 'status', 'result_status', 'fee_status', 'teacher'
        $passPercentage = (int) $request->input('pass_percentage', 33);

        $defaultColumns = [
            'college_roll_no', 'name', 'father_name', 'class_level',
            'subject_group', 'status', 'attendance_pct', 'result_status', 'fee_status'
        ];
        $selectedColumns = $request->input('columns', $defaultColumns);
        if (empty($selectedColumns)) {
            $selectedColumns = $defaultColumns;
        }

        $columnDefinitions = $this->getColumnDefinitions();

        // 2. Fetch and Assemble Student Records if year is selected
        $rawStudents = [];
        $kpi = [
            'total_students' => 0,
            'active_students' => 0,
            'struck_off_students' => 0,
            'pass_count' => 0,
            'appeared_count' => 0,
            'pass_percentage' => 0,
            'avg_attendance' => 0,
            'low_attendance_count' => 0,
            'struck_off_risk_count' => 0,
            'total_billed' => 0,
            'total_paid' => 0,
            'total_balance' => 0,
            'fee_recovery_rate' => 0,
        ];
        $summaryRows = [];
        $detailedRows = [];

        if ($selectedYear) {
            $rawStudents = $this->fetchStudentDataset($request, $selectedYear, $passPercentage);

            // Calculate KPIs
            $total = count($rawStudents);
            if ($total > 0) {
                $activeCount = 0;
                $struckOffCount = 0;
                $passCount = 0;
                $appearedCount = 0;
                $totalAttPct = 0;
                $attRecordCount = 0;
                $lowAttCount = 0;
                $riskCount = 0;
                $totBilled = 0;
                $totPaid = 0;
                $totBal = 0;

                foreach ($rawStudents as $st) {
                    if ($st['status'] === 'Active') $activeCount++;
                    if ($st['is_struck_off']) $struckOffCount++;
                    if (in_array($st['result_status'], ['Pass', 'Pass (Grace)'])) $passCount++;
                    if (!in_array($st['result_status'], ['Missing', 'None'])) $appearedCount++;

                    if ($st['attendance_total'] > 0) {
                        $totalAttPct += $st['attendance_pct'];
                        $attRecordCount++;
                        if ($st['attendance_pct'] < 75) $lowAttCount++;
                    }
                    if ($st['struck_off_risk'] && !$st['is_struck_off']) $riskCount++;

                    $totBilled += $st['fee_billed'];
                    $totPaid += $st['fee_paid'];
                    $totBal += $st['fee_balance'];
                }

                $kpi['total_students'] = $total;
                $kpi['active_students'] = $activeCount;
                $kpi['struck_off_students'] = $struckOffCount;
                $kpi['pass_count'] = $passCount;
                $kpi['appeared_count'] = $appearedCount;
                $kpi['pass_percentage'] = $appearedCount > 0 ? round(($passCount / $appearedCount) * 100, 1) : 0;
                $kpi['avg_attendance'] = $attRecordCount > 0 ? round($totalAttPct / $attRecordCount, 1) : 0;
                $kpi['low_attendance_count'] = $lowAttCount;
                $kpi['struck_off_risk_count'] = $riskCount;
                $kpi['total_billed'] = $totBilled;
                $kpi['total_paid'] = $totPaid;
                $kpi['total_balance'] = $totBal;
                $kpi['fee_recovery_rate'] = $totBilled > 0 ? round(($totPaid / $totBilled) * 100, 1) : 0;

                // Build Summary Table
                $summaryRows = $this->buildSummaryAggregation($rawStudents, $groupBy);
                $detailedRows = $rawStudents;
            }
        }

        return compact(
            'admissionYears',
            'subjectGroups',
            'subjects',
            'teachers',
            'sections',
            'admissionStatuses',
            'selectedYear',
            'viewMode',
            'groupBy',
            'passPercentage',
            'selectedColumns',
            'columnDefinitions',
            'kpi',
            'summaryRows',
            'detailedRows',
            'instituteSettings'
        );
    }

    /**
     * Column Registry with Labels & Categories.
     */
    private function getColumnDefinitions(): array
    {
        return [
            // Personal & Contact
            'college_roll_no' => ['label' => 'Roll No', 'category' => 'Personal & Contact'],
            'name'            => ['label' => 'Student Name', 'category' => 'Personal & Contact'],
            'father_name'     => ['label' => 'Father Name', 'category' => 'Personal & Contact'],
            'gender'          => ['label' => 'Gender', 'category' => 'Personal & Contact'],
            'cnic'            => ['label' => 'CNIC / B-Form', 'category' => 'Personal & Contact'],
            'contact'         => ['label' => 'Contact Number', 'category' => 'Personal & Contact'],
            'religion'        => ['label' => 'Religion', 'category' => 'Personal & Contact'],
            'blood_group'     => ['label' => 'Blood Group', 'category' => 'Personal & Contact'],
            'dob'             => ['label' => 'Date of Birth', 'category' => 'Personal & Contact'],

            // Board & Academic
            'class_level'     => ['label' => 'Class Level', 'category' => 'Board & Academics'],
            'subject_group'   => ['label' => 'Subject Group / Program', 'category' => 'Board & Academics'],
            'section'         => ['label' => 'Section', 'category' => 'Board & Academics'],
            'bise_roll_11'    => ['label' => 'BISE 11 Roll', 'category' => 'Board & Academics'],
            'bise_roll_12'    => ['label' => 'BISE 12 Roll', 'category' => 'Board & Academics'],
            'school_name'     => ['label' => 'SSC School', 'category' => 'Board & Academics'],
            'ssc_marks'       => ['label' => 'SSC Marks', 'category' => 'Board & Academics'],
            'electives'       => ['label' => 'Elective Subjects', 'category' => 'Board & Academics'],
            'status'          => ['label' => 'Admission Status', 'category' => 'Board & Academics'],

            // Exams & Results
            'oct_test'        => ['label' => 'Oct Test Marks', 'category' => 'Exams & Performance'],
            'dec_test'        => ['label' => 'Dec Test Marks', 'category' => 'Exams & Performance'],
            'total_11'        => ['label' => '11th Annual Marks', 'category' => 'Exams & Performance'],
            'total_12'        => ['label' => '12th Annual Marks', 'category' => 'Exams & Performance'],
            'combined_marks'  => ['label' => 'Total Marks', 'category' => 'Exams & Performance'],
            'result_status'   => ['label' => 'Result Status', 'category' => 'Exams & Performance'],
            'reappear'        => ['label' => 'Reappear In', 'category' => 'Exams & Performance'],

            // Attendance & Conduct
            'attendance_total'   => ['label' => 'Total Classes', 'category' => 'Attendance & Conduct'],
            'attendance_present' => ['label' => 'Present Count', 'category' => 'Attendance & Conduct'],
            'attendance_absent'  => ['label' => 'Absent Count', 'category' => 'Attendance & Conduct'],
            'attendance_pct'     => ['label' => 'Attendance %', 'category' => 'Attendance & Conduct'],
            'struck_off_risk'    => ['label' => 'Risk (>10 Absents)', 'category' => 'Attendance & Conduct'],
            'struck_off_info'    => ['label' => 'Struck Off Details', 'category' => 'Attendance & Conduct'],

            // Fees & Dues
            'fee_billed'  => ['label' => 'Fee Billed (PKR)', 'category' => 'Fees & Collections'],
            'fee_paid'    => ['label' => 'Fee Paid (PKR)', 'category' => 'Fees & Collections'],
            'fee_balance' => ['label' => 'Balance Due (PKR)', 'category' => 'Fees & Collections'],
            'fee_status'  => ['label' => 'Fee Status', 'category' => 'Fees & Collections'],

            // Teacher Assignments
            'assigned_teachers' => ['label' => 'Assigned Teachers', 'category' => 'Teacher Assignments'],
        ];
    }

    /**
     * Efficiently queries and aggregates data across all 5 modules for the selected criteria.
     */
    private function fetchStudentDataset(Request $request, int $admissionYear, int $passThreshold): array
    {
        $admissionsQuery = Admissions::where('admission_year', $admissionYear)
            ->whereHas('student');

        // Apply URL Filters
        if ($request->filled('class_level')) {
            $admissionsQuery->where('current_class', $request->class_level);
        }

        if ($request->filled('status')) {
            $admissionsQuery->where('status', $request->status);
        }

        if ($request->filled('subject_group_id')) {
            $groupId = $request->subject_group_id;
            $admissionsQuery->whereHas('student.electives', function ($eq) use ($groupId) {
                $eq->where('subject_group_id', $groupId);
            });
        }

        if ($request->filled('subject_id')) {
            $subId = $request->subject_id;
            $admissionsQuery->whereHas('student.electives', function ($eq) use ($subId) {
                $eq->where('subject_id', $subId);
            });
        }

        if ($request->filled('staff_id')) {
            $staffId = $request->staff_id;
            $admissionsQuery->whereHas('student.studentTeacherSubjects', function ($tq) use ($staffId) {
                $tq->where('staff_id', $staffId);
            });
        }

        if ($request->filled('section')) {
            $sec = $request->section;
            $admissionsQuery->whereHas('student.subjectSections', function ($sq) use ($sec) {
                $sq->where('section', $sec);
            });
        }

        if ($request->filled('search')) {
            $term = trim($request->search);
            $admissionsQuery->where(function ($q) use ($term) {
                $q->where('college_roll_no', 'like', "%{$term}%")
                  ->orWhere('bise_roll_11', 'like', "%{$term}%")
                  ->orWhere('bise_roll_12', 'like', "%{$term}%")
                  ->orWhereHas('student', function ($sq) use ($term) {
                      $sq->where('first_name', 'like', "%{$term}%")
                         ->orWhere('last_name', 'like', "%{$term}%")
                         ->orWhereHas('father', fn($fq) => $fq->where('name', 'like', "%{$term}%"));
                  });
            });
        }

        $admissions = $admissionsQuery->with([
            'student.father',
            'student.contact',
            'student.attribute.bloodGroup',
            'student.sscRecord',
            'student.struckOffLogs.struckOffBy',
        ])->get();

        $studentIds = $admissions->pluck('student_id')->toArray();
        if (empty($studentIds)) {
            return [];
        }

        // 1. Batch Load Attendance Aggregates
        $attendanceMap = DB::table('attendances')
            ->whereIn('student_id', $studentIds)
            ->select(
                'student_id',
                DB::raw('COUNT(*) as total_classes'),
                DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count'),
                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count'),
                DB::raw('SUM(CASE WHEN status = "leave" THEN 1 ELSE 0 END) as leave_count')
            )
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        // 2. Batch Load Fee Voucher Aggregates
        $feeMap = DB::table('fee_vouchers')
            ->whereIn('student_id', $studentIds)
            ->whereNull('deleted_at')
            ->select(
                'student_id',
                DB::raw('SUM(amount) as total_billed'),
                DB::raw('SUM(paid_amount) as total_paid'),
                DB::raw('COUNT(*) as total_vouchers'),
                DB::raw('SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_vouchers'),
                DB::raw('SUM(CASE WHEN status = "unpaid" THEN 1 ELSE 0 END) as unpaid_vouchers')
            )
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        // 3. Batch Load Electives & Groups
        $studentElectives = DB::table('student_elective_subjects')
            ->join('subjects', 'student_elective_subjects.subject_id', '=', 'subjects.id')
            ->leftJoin('subject_groups', 'student_elective_subjects.subject_group_id', '=', 'subject_groups.id')
            ->whereIn('student_elective_subjects.student_id', $studentIds)
            ->whereNull('student_elective_subjects.deleted_at')
            ->select(
                'student_elective_subjects.student_id',
                'student_elective_subjects.subject_group_id',
                'subject_groups.name as group_name',
                'subjects.name as subject_name'
            )
            ->get()
            ->groupBy('student_id');

        // 4. Batch Load Sections
        $studentSections = DB::table('student_subject_sections')
            ->whereIn('student_id', $studentIds)
            ->whereNull('deleted_at')
            ->whereNotNull('section')
            ->select('student_id', 'section')
            ->distinct()
            ->get()
            ->groupBy('student_id');

        // 5. Batch Load Teacher Assignments
        $studentTeachers = DB::table('student_teacher_subjects')
            ->join('staff', 'student_teacher_subjects.staff_id', '=', 'staff.id')
            ->join('subjects', 'student_teacher_subjects.subject_id', '=', 'subjects.id')
            ->whereIn('student_teacher_subjects.student_id', $studentIds)
            ->whereNull('student_teacher_subjects.deleted_at')
            ->select(
                'student_teacher_subjects.student_id',
                DB::raw("TRIM(CONCAT(staff.first_name, ' ', COALESCE(staff.last_name, ''))) as staff_name"),
                'subjects.name as subject_name'
            )
            ->get()
            ->groupBy('student_id');

        // 6. Batch Load Exam Results & Pre-calculate Grace/Reappear
        $allResults = Result::whereIn('student_id', $studentIds)
            ->whereNull('deleted_at')
            ->with('subject')
            ->get()
            ->groupBy('student_id');

        $graceStatusMap = $this->calculateGraceStatusMap($studentIds, $allResults, $passThreshold);

        // 7. Assemble Normalized Records
        $records = [];
        foreach ($admissions as $adm) {
            $student = $adm->student;
            if (!$student) continue;

            $sId = $student->id;

            // Attendance calculations
            $att = $attendanceMap->get($sId);
            $totalClasses = $att ? (int) $att->total_classes : 0;
            $presentCount = $att ? (int) $att->present_count : 0;
            $absentCount = $att ? (int) $att->absent_count : 0;
            $attPct = $totalClasses > 0 ? round(($presentCount / $totalClasses) * 100, 1) : 0;
            $isStruckOffRisk = $absentCount > 10;

            // Struck-off info
            $isStruckOff = ($adm->status === 'Struck Off');
            $activeLog = $student->struckOffLogs ? $student->struckOffLogs->where('status', 'struck_off')->sortByDesc('struck_off_at')->first() : null;
            $struckOffInfo = '--';
            if ($isStruckOff && $activeLog) {
                $teacherName = $activeLog->struckOffBy ? $activeLog->struckOffBy->name : 'Staff';
                $struckDate = $activeLog->struck_off_at ? \Carbon\Carbon::parse($activeLog->struck_off_at)->format('d M Y') : '';
                $struckOffInfo = "Struck off by {$teacherName} ({$struckDate})";
            } elseif ($isStruckOff) {
                $struckOffInfo = 'Struck Off';
            }

            // Fee calculations
            $fee = $feeMap->get($sId);
            $feeBilled = $fee ? (float) $fee->total_billed : 0;
            $feePaid = $fee ? (float) $fee->total_paid : 0;
            $feeBalance = max(0, $feeBilled - $feePaid);
            if (!$fee || $fee->total_vouchers == 0) {
                $feeStatus = 'No Voucher';
            } elseif ($feeBalance <= 0) {
                $feeStatus = 'Paid';
            } elseif ($feePaid > 0) {
                $feeStatus = 'Partially Paid';
            } else {
                $feeStatus = 'Unpaid';
            }

            // Electives & Group
            $electivesColl = $studentElectives->get($sId, collect());
            $groupName = $electivesColl->first() && $electivesColl->first()->group_name ? $electivesColl->first()->group_name : ($adm->program_name ?: 'General / Other');
            $electiveNames = $electivesColl->pluck('subject_name')->filter()->unique()->implode(', ') ?: '--';

            // Sections
            $secColl = $studentSections->get($sId, collect());
            $sectionNames = $secColl->pluck('section')->filter()->unique()->implode(', ') ?: '--';

            // Teachers
            $teachColl = $studentTeachers->get($sId, collect());
            $teacherList = $teachColl->map(fn($t) => "{$t->subject_name}: {$t->staff_name}")->unique()->implode(' | ') ?: '--';
            $primaryTeacher = $teachColl->first() ? $teachColl->first()->staff_name : 'Unassigned';

            // Results
            $sResults = $allResults->get($sId, collect());
            $octSum = $sResults->where('exam_type', 'Oct_test')->sum(fn($r) => ($r->obtained_theory ?? 0) + ($r->obtained_practical ?? 0));
            $decSum = $sResults->where('exam_type', 'Dec_test')->sum(fn($r) => ($r->obtained_theory ?? 0) + ($r->obtained_practical ?? 0));

            $p11 = $sResults->where('exam_type', 'Final')->where('class_level', '11')->sum(fn($r) => ($r->obtained_theory ?? 0) + ($r->obtained_practical ?? 0));
            $p12 = $sResults->where('exam_type', 'Final')->where('class_level', '12')->sum(fn($r) => ($r->obtained_theory ?? 0) + ($r->obtained_practical ?? 0));
            $combinedMarks = $p11 + $p12;

            $statusData = $this->calculateStudentStats($sId, $sResults, $graceStatusMap[$sId] ?? false, $passThreshold);
            $resultStatus = $statusData['status'];
            $reappearIn = $statusData['reappear'];

            $rec = [
                'student_id'          => $sId,
                'admission_id'        => $adm->id,
                'college_roll_no'     => $adm->college_roll_no ?: '--',
                'name'                => $student->full_name,
                'father_name'         => $student->father ? $student->father->name : '--',
                'gender'              => $student->gender ?: '--',
                'cnic'                => $student->cnic ?: '--',
                'contact'             => $student->contact ? $student->contact->student_contact : '--',
                'religion'            => $student->attribute ? $student->attribute->religion : '--',
                'blood_group'         => ($student->attribute && $student->attribute->bloodGroup) ? $student->attribute->bloodGroup->name : '--',
                'dob'                 => $student->date_of_birth ? $student->date_of_birth->format('d M Y') : '--',
                'class_level'         => $adm->current_class ? ($adm->current_class . 'th') : '--',
                'raw_class'           => (string) $adm->current_class,
                'subject_group'       => $groupName,
                'section'             => $sectionNames,
                'raw_section'         => $secColl->first() ? $secColl->first()->section : 'None',
                'bise_roll_11'        => $adm->bise_roll_11 ?: '--',
                'bise_roll_12'        => $adm->bise_roll_12 ?: '--',
                'school_name'         => $student->sscRecord ? $student->sscRecord->school_name : '--',
                'ssc_marks'           => $student->sscRecord ? ($student->sscRecord->obtained_mark . '/' . $student->sscRecord->total_mark) : '--',
                'electives'           => $electiveNames,
                'status'              => $adm->status ?: 'Active',
                'is_struck_off'       => $isStruckOff,
                'struck_off_risk'     => $isStruckOffRisk,
                'struck_off_info'     => $struckOffInfo,
                'attendance_total'    => $totalClasses,
                'attendance_present'  => $presentCount,
                'attendance_absent'   => $absentCount,
                'attendance_pct'      => $attPct,
                'fee_billed'          => $feeBilled,
                'fee_paid'            => $feePaid,
                'fee_balance'         => $feeBalance,
                'fee_status'          => $feeStatus,
                'oct_test'            => $octSum > 0 ? $octSum : '--',
                'dec_test'            => $decSum > 0 ? $decSum : '--',
                'total_11'            => $p11 > 0 ? $p11 : '--',
                'total_12'            => $p12 > 0 ? $p12 : '--',
                'combined_marks'      => $combinedMarks > 0 ? $combinedMarks : '--',
                'result_status'       => $resultStatus,
                'reappear'            => $reappearIn,
                'assigned_teachers'   => $teacherList,
                'primary_teacher'     => $primaryTeacher,
            ];

            // In-Memory Filter Checks
            if ($request->filled('fee_status')) {
                $targetFee = $request->fee_status;
                if ($targetFee === 'has_dues' && $rec['fee_balance'] <= 0) continue;
                if ($targetFee === 'paid' && $rec['fee_status'] !== 'Paid') continue;
                if ($targetFee === 'unpaid' && $rec['fee_status'] !== 'Unpaid') continue;
                if ($targetFee === 'partial' && $rec['fee_status'] !== 'Partially Paid') continue;
            }

            if ($request->filled('attendance_filter')) {
                $attFilter = $request->attendance_filter;
                if ($attFilter === 'low' && ($rec['attendance_pct'] >= 75 || $rec['attendance_total'] == 0)) continue;
                if ($attFilter === 'risk' && !$rec['struck_off_risk']) continue;
            }

            if ($request->filled('result_status_filter')) {
                $resFilter = $request->result_status_filter;
                if ($resFilter === 'pass' && !in_array($rec['result_status'], ['Pass', 'Pass (Grace)'])) continue;
                if ($resFilter === 'reappear' && $rec['result_status'] !== 'Reappear') continue;
                if ($resFilter === 'absent' && $rec['result_status'] !== 'Absent') continue;
            }

            $records[] = $rec;
        }

        return $records;
    }

    /**
     * Aggregates normalized student records into summary rows by the chosen dimension.
     */
    private function buildSummaryAggregation(array $students, string $groupBy): array
    {
        $grouped = [];

        foreach ($students as $st) {
            $key = match ($groupBy) {
                'class'         => $st['class_level'] ?: 'Unassigned Class',
                'section'       => $st['raw_section'] ?: 'No Section',
                'status'        => $st['status'] ?: 'Unknown Status',
                'result_status' => $st['result_status'] ?: 'No Result',
                'fee_status'    => $st['fee_status'] ?: 'No Fee',
                'teacher'       => $st['primary_teacher'] ?: 'Unassigned Teacher',
                default         => $st['subject_group'] ?: 'General',
            };

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'label'             => $key,
                    'total_students'    => 0,
                    'male_count'        => 0,
                    'female_count'      => 0,
                    'struck_off_count'  => 0,
                    'tot_att_pct'       => 0,
                    'att_count'         => 0,
                    'pass_count'        => 0,
                    'appeared_count'    => 0,
                    'total_billed'      => 0,
                    'total_paid'        => 0,
                    'total_balance'     => 0,
                ];
            }

            $grouped[$key]['total_students']++;
            $gender = strtolower($st['gender']);
            if (str_contains($gender, 'm') || $gender === 'male') {
                $grouped[$key]['male_count']++;
            } elseif (str_contains($gender, 'f') || $gender === 'female') {
                $grouped[$key]['female_count']++;
            }

            if ($st['is_struck_off']) {
                $grouped[$key]['struck_off_count']++;
            }

            if ($st['attendance_total'] > 0) {
                $grouped[$key]['tot_att_pct'] += $st['attendance_pct'];
                $grouped[$key]['att_count']++;
            }

            if (in_array($st['result_status'], ['Pass', 'Pass (Grace)'])) {
                $grouped[$key]['pass_count']++;
            }
            if (!in_array($st['result_status'], ['Missing', 'None'])) {
                $grouped[$key]['appeared_count']++;
            }

            $grouped[$key]['total_billed'] += $st['fee_billed'];
            $grouped[$key]['total_paid'] += $st['fee_paid'];
            $grouped[$key]['total_balance'] += $st['fee_balance'];
        }

        // Format calculated rows
        $summary = [];
        foreach ($grouped as $key => $data) {
            $data['avg_attendance'] = $data['att_count'] > 0 ? round($data['tot_att_pct'] / $data['att_count'], 1) : 0;
            $data['pass_rate'] = $data['appeared_count'] > 0 ? round(($data['pass_count'] / $data['appeared_count']) * 100, 1) : 0;
            $data['recovery_rate'] = $data['total_billed'] > 0 ? round(($data['total_paid'] / $data['total_billed']) * 100, 1) : 0;
            $summary[] = $data;
        }

        // Sort descending by total students
        usort($summary, fn($a, $b) => $b['total_students'] <=> $a['total_students']);

        return $summary;
    }

    /**
     * Pre-calculate Grace status map for all students in the batch.
     */
    private function calculateGraceStatusMap(array $studentIds, $allResultsGrouped, int $passThreshold): array
    {
        $map = [];
        foreach ($studentIds as $id) {
            $results = $allResultsGrouped->get($id, collect())->where('exam_type', 'Final');
            if ($results->isEmpty()) {
                $map[$id] = false;
                continue;
            }

            $grouped = $results->groupBy(fn($item) => $item->subject ? $item->subject->name : 'Unknown');
            $totalMarks = 0;
            $totalDeficit = 0;

            foreach ($grouped as $g) {
                $sP1 = $g->where('class_level', '11')->first();
                $sP2 = $g->where('class_level', '12')->first();
                $sub = $sP1 ? $sP1->subject : ($sP2 ? $sP2->subject : null);

                $max1 = ($sub && $sub->total_11 > 0) ? $sub->total_11 : ($sP1 ? $sP1->total_marks : 0);
                $max2Th = ($sub && $sub->theory_12 > 0) ? $sub->theory_12 : ($sP2 ? $sP2->total_marks - ($sP2->obtained_practical ? $sP2->total_marks * 0.15 : 0) : 0);
                $maxPrac = ($sub && $sub->practical_12 > 0) ? $sub->practical_12 : 0;
                $subMax = $max1 + $max2Th + $maxPrac;
                $totalMarks += $subMax;

                $obt = ($sP1 ? $sP1->obtained_theory : 0) + ($sP2 ? $sP2->obtained_theory : 0) + ($sP1 ? $sP1->obtained_practical : 0) + ($sP2 ? $sP2->obtained_practical : 0);
                $thObt = ($sP1 ? $sP1->obtained_theory : 0) + ($sP2 ? $sP2->obtained_theory : 0);

                $aggPass = round($subMax * $passThreshold / 100);
                $thPass = round(($max1 + $max2Th) * $passThreshold / 100);

                if ($thObt < $thPass || $obt < $aggPass) {
                    $totalDeficit += max(0, $thPass - $thObt, $aggPass - $obt);
                }
            }

            $limit = ceil($totalMarks * 0.01);
            $map[$id] = ($totalDeficit > 0 && $totalDeficit <= $limit);
        }

        return $map;
    }

    /**
     * Calculate individual student exam status and reappear subjects.
     */
    private function calculateStudentStats(int $id, $results, bool $canUseGrace, int $passThreshold): array
    {
        $finalResults = $results->where('exam_type', 'Final');
        if ($finalResults->isEmpty()) {
            return ['status' => 'Missing', 'reappear' => '--'];
        }

        $anyAbsent = $finalResults->contains('is_absent', true);
        if ($anyAbsent) {
            return ['status' => 'Absent', 'reappear' => 'Absent'];
        }

        $reappears = [];
        $isOverallPass = true;

        $grouped = $finalResults->groupBy(fn($item) => $item->subject ? $item->subject->name : 'Unknown');
        foreach ($grouped as $name => $g) {
            $sP1 = $g->where('class_level', '11')->first();
            $sP2 = $g->where('class_level', '12')->first();
            $sub = $sP1 ? $sP1->subject : ($sP2 ? $sP2->subject : null);

            $max1 = ($sub && $sub->total_11 > 0) ? $sub->total_11 : ($sP1 ? $sP1->total_marks : 0);
            $max2Th = ($sub && $sub->theory_12 > 0) ? $sub->theory_12 : ($sP2 ? $sP2->total_marks - ($sP2->obtained_practical ? $sP2->total_marks * 0.15 : 0) : 0);
            $subMax = $max1 + $max2Th + ($sub && $sub->practical_12 > 0 ? $sub->practical_12 : 0);

            $thObt = ($sP1 ? $sP1->obtained_theory : 0) + ($sP2 ? $sP2->obtained_theory : 0);
            $totObt = $thObt + ($sP1 ? $sP1->obtained_practical : 0) + ($sP2 ? $sP2->obtained_practical : 0);

            $aggPass = round($subMax * $passThreshold / 100);
            $thPass = round(($max1 + $max2Th) * $passThreshold / 100);

            if ($thObt < $thPass || $totObt < $aggPass) {
                if (!$canUseGrace) {
                    $isOverallPass = false;
                    $reappears[] = $name;
                }
            }
        }

        if ($isOverallPass) {
            return ['status' => $canUseGrace ? 'Pass (Grace)' : 'Pass', 'reappear' => '--'];
        }

        return ['status' => 'Reappear', 'reappear' => implode(', ', array_unique($reappears))];
    }
}
