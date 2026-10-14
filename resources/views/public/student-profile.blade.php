@extends('layouts.public')
@section('title', 'Student Profile')

@section('content')
{{-- Page Header --}}
<div class="bg-primary py-4 text-white mb-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Student Profile</h2>
            <a href="{{ route('public.students-corner') }}" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back to Search
            </a>
        </div>
    </div>
</div>

<div class="container mb-5">
    {{-- Student Basic Info --}}
    <div class="row mb-4">
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body p-4">
                    <img src="{{ $student->getProfilePictureUrl() }}" 
                             alt="{{ $student->full_name }}" 
                             class="img-fluid rounded-circle shadow mb-3"
                             style="width: 200px; height: 200px; object-fit: cover;">
                    <h4 class="fw-bold mb-1">{{ $student->full_name }}</h4>
                    <p class="text-muted mb-0">{{ $admission->current_class ? 'Class ' . $admission->current_class : 'Student' }}</p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Personal Information</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-user text-primary me-3 mt-1"></i>
                                <div>
                                    <small class="text-muted d-block">Student Name</small>
                                    <strong>{{ $student->full_name }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-user-tie text-primary me-3 mt-1"></i>
                                <div>
                                    <small class="text-muted d-block">Father Name</small>
                                    <strong>{{ $student->father->name ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-id-card text-primary me-3 mt-1"></i>
                                <div>
                                    <small class="text-muted d-block">College Roll No</small>
                                    <strong>{{ $admission->college_roll_no ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-calendar text-primary me-3 mt-1"></i>
                                <div>
                                    <small class="text-muted d-block">Admission Year</small>
                                    <strong>{{ $admission->admission_year }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-certificate text-primary me-3 mt-1"></i>
                                <div>
                                    <small class="text-muted d-block">BISE Roll 11</small>
                                    <strong>{{ $admission->bise_roll_11 ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-certificate text-primary me-3 mt-1"></i>
                                <div>
                                    <small class="text-muted d-block">BISE Roll 12</small>
                                    <strong>{{ $admission->bise_roll_12 ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SSC Record --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>SSC Record</h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <small class="text-muted d-block">SSC Roll Number</small>
                    <strong>{{ $student->sscRecord->roll_no ?? 'N/A' }}</strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">SSC Marks</small>
                    <strong>{{ $student->sscRecord ? $student->sscRecord->obtained_mark . ' / ' . $student->sscRecord->total_mark : 'N/A' }}</strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">SSC Percentage</small>
                    <strong>{{ $student->sscRecord->percentage ?? 'N/A' }}{{ $student->sscRecord ? '%' : '' }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Fee Vouchers --}}
    @if($student->vouchers->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Fee Vouchers</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="padding-left: 15px;">Voucher No</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end" style="padding-right: 15px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($student->vouchers->sortByDesc('created_at') as $voucher)
                        <tr>
                            <td style="padding-left: 15px;"><strong>{{ $voucher->voucher_number }}</strong></td>
                            <td>{{ $voucher->issue_date ? $voucher->issue_date->format('d-M-Y') : '-' }}</td>
                            <td>{{ $voucher->due_date ? $voucher->due_date->format('d-M-Y') : '-' }}</td>
                            <td>Rs. {{ number_format($voucher->amount, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $voucher->status_badge }}">{{ ucfirst($voucher->status) }}</span>
                            </td>
                            <td class="text-end" style="padding-right: 15px;">
                                <a href="{{ route('public.students.voucher.download', $voucher) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                    <i class="fas fa-download me-1"></i> Download
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Compulsory Subjects --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-book me-2"></i>Compulsory Subjects</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40%">Subject</th>
                            <th style="width: 30%" class="text-center">Attendance</th>
                            <th style="width: 30%">Teacher</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($compulsorySubjects as $item)
                        <tr>
                            <td><strong>{{ $item['subject']->name }}</strong></td>
                            <td class="text-center">
                                @if($item['delivered'] > 0)
                                    <span class="fw-bold text-primary">{{ $item['attended'] }}</span> / {{ $item['delivered'] }}
                                    <small class="text-muted d-block">{{ number_format(($item['attended'] / $item['delivered']) * 100, 1) }}%</small>
                                @else
                                    <span class="text-muted">No records</span>
                                @endif
                            </td>
                            <td>
                                @if($item['teacher'])
                                    {{ $item['teacher']->full_name }}
                                    <small class="text-muted d-block">{{ $item['teacher']->designation }}</small>
                                @else
                                    <span class="text-muted">Not Assigned</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No compulsory subjects assigned</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Elective Subjects --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-book-open me-2"></i>Elective Subjects</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40%">Subject</th>
                            <th style="width: 30%" class="text-center">Attendance</th>
                            <th style="width: 30%">Teacher</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($electiveSubjects as $item)
                        <tr>
                            <td><strong>{{ $item['subject']->name }}</strong></td>
                            <td class="text-center">
                                @if($item['delivered'] > 0)
                                    <span class="fw-bold text-primary">{{ $item['attended'] }}</span> / {{ $item['delivered'] }}
                                    <small class="text-muted d-block">{{ number_format(($item['attended'] / $item['delivered']) * 100, 1) }}%</small>
                                @else
                                    <span class="text-muted">No records</span>
                                @endif
                            </td>
                            <td>
                                @if($item['teacher'])
                                    {{ $item['teacher']->full_name }}
                                    <small class="text-muted d-block">{{ $item['teacher']->designation }}</small>
                                @else
                                    <span class="text-muted">Not Assigned</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No elective subjects assigned</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- College Test Results --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>College Test Results</h5>
        </div>
        <div class="card-body p-4">
            @php
                $internalTests = $allResults->toBase()->except(['Final']);
            @endphp

            @forelse($internalTests as $examType => $results)
                <div class="mb-4 last-child-mb-0">
                    <h6 class="fw-bold border-bottom pb-2 mb-3">
                        <i class="fas fa-file-signature text-primary me-2"></i>{{ str_replace('_', ' ', strtoupper($examType)) }}
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Subject</th>
                                    <th class="text-center">Exam Name</th>
                                    <th class="text-center">Obtained</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results as $result)
                                    @php
                                        $total = $result->total_obtained_marks;
                                        $max = $result->total_marks > 0 ? $result->total_marks : 100;
                                        $percentage = ($total / $max) * 100;
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $result->subject->name }}</strong></td>
                                        <td class="text-center">{{ $result->exam_name }}</td>
                                        <td class="text-center">
                                            @if($result->is_absent)
                                                <span class="text-danger">Absent</span>
                                            @else
                                                {{ $total }}
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $result->total_marks }}</td>
                                        <td class="text-center">
                                            @if(!$result->is_absent)
                                                {{ number_format($percentage, 1) }}%
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <p class="text-center text-muted py-3 mb-0">No college test results available yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Final Results --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Final Results</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th colspan="2" class="text-center">Part I</th>
                            <th colspan="3" class="text-center">Part II</th>
                            <th rowspan="2" class="text-center align-middle">Percentile<br>Score</th>
                            <th rowspan="2" class="text-center align-middle">Grade</th>
                            <th rowspan="2" class="text-center align-middle">Total</th>
                        </tr>
                        <tr>
                            <th>Subjects</th>
                            <th class="text-center">Theory</th>
                            <th>Subjects</th>
                            <th class="text-center">Theory</th>
                            <th class="text-center">Practical</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $grandTotalObtained = 0;
                            $grandTotalMarks = 0;
                            $totalMarkDeficit = 0;
                            $passThreshold = request('pass_percentage', 33);
                            
                            $groupedResults = $finalResults->groupBy(function($item) {
                                return $item->subject->name;
                            });

                            $evaluationData = [];
                            foreach($groupedResults as $subjectName => $group) {
                                $part1 = $group->where('class_level', '11')->first();
                                $part2 = $group->where('class_level', '12')->first();
                                $subject = $part1 ? $part1->subject : ($part2 ? $part2->subject : null);
                                
                                $isAbsent1 = $part1 ? $part1->is_absent : false;
                                $isAbsent2 = $part2 ? $part2->is_absent : false;
                                
                                $part1Theory = $part1 ? ($part1->obtained_theory ?? 0) : 0;
                                $part2Theory = $part2 ? ($part2->obtained_theory ?? 0) : 0;
                                $totalPractical = ($part1 ? ($part1->obtained_practical ?? 0) : 0) + ($part2 ? ($part2->obtained_practical ?? 0) : 0);
                                
                                $p1Max = ($subject && $subject->total_11 > 0) ? $subject->total_11 : ($part1 && $part1->total_marks > 0 ? $part1->total_marks : 0);
                                $p2TheoryMax = ($subject && $subject->theory_12 > 0) ? $subject->theory_12 : ($part2 ? $part2->total_marks - ($part2->obtained_practical ? $part2->total_marks * 0.15 : 0) : 0); 
                                $pracMax = ($subject && $subject->practical_12 > 0) ? $subject->practical_12 : 0;
                                if (!$subject && $part2) $p2TheoryMax = $part2->total_marks;
                                
                                $subjectMax = $p1Max + $p2TheoryMax + $pracMax;
                                $grandTotalMarks += $subjectMax;
                                
                                $obtainedMarks = 0;
                                if (!$isAbsent1) $obtainedMarks += $part1Theory;
                                if (!$isAbsent2) $obtainedMarks += $part2Theory + $totalPractical;
                                $grandTotalObtained += $obtainedMarks;

                                if ((!$part1 && !$part2) || ($isAbsent1 || $isAbsent2)) {
                                    $evaluationData[$subjectName] = ['is_absent' => true];
                                    continue;
                                }

                                // Thresholds
                                $aggPassMark = round($subjectMax * $passThreshold / 100);
                                $theoryPassMark = round(($p1Max + $p2TheoryMax) * $passThreshold / 100);
                                $pracPassMark = round($pracMax * $passThreshold / 100);
                                $p1PassMark = round($p1Max * $passThreshold / 100);
                                $p2PassMark = round($p2TheoryMax * $passThreshold / 100);
                                
                                $theoryObtained = $part1Theory + $part2Theory;
                                
                                $subjectDeficit = 0;
                                $isPassed = ($theoryObtained >= $theoryPassMark && $obtainedMarks >= $aggPassMark);
                                
                                if (!$isPassed) {
                                    $subjectDeficit = max(0, $theoryPassMark - $theoryObtained, $aggPassMark - $obtainedMarks);
                                }
                                
                                $totalMarkDeficit += $subjectDeficit;
                                $evaluationData[$subjectName] = [
                                    'part1' => $part1, 'part2' => $part2, 'subject' => $subject,
                                    'p1Max' => $p1Max, 'p2TheoryMax' => $p2TheoryMax, 'pracMax' => $pracMax,
                                    'p1Theory' => $part1Theory, 'p2Theory' => $part2Theory, 'totalPractical' => $totalPractical,
                                    'obtained' => $obtainedMarks, 'max' => $subjectMax, 'is_passed' => $isPassed, 'deficit' => $subjectDeficit,
                                    'is_absent' => false, 'p1PassMark' => $p1PassMark, 'p2PassMark' => $p2PassMark,
                                    'pracPassMark' => $pracPassMark
                                ];
                            }
                            
                            $gracePool = ceil($grandTotalMarks * 0.01);
                            $isGraceAwarded = ($totalMarkDeficit > 0 && $totalMarkDeficit <= $gracePool);
                        @endphp
                        @forelse($evaluationData as $subjectName => $data)
                        @php
                            if ($data['is_absent']) {
                                $part1 = null; $part2 = null; $isPassed = false; $pbccStatus = "Absent";
                                $percentileStr = ''; $grade = ''; $totalStr = '';
                            } else {
                                $part1 = $data['part1']; $part2 = $data['part2']; $subject = $data['subject'];
                                $isPassed = $data['is_passed'];
                                $obtained = $data['obtained']; $max = $data['max'];
                                $percentile = $max > 0 ? ($obtained / $max) * 100 : 0;
                                
                                $grade = 'F';
                                if ($percentile >= 80) $grade = 'A+';
                                elseif ($percentile >= 70) $grade = 'C+';
                                elseif ($percentile >= 60) $grade = 'C';
                                elseif ($percentile >= 50) $grade = 'D+';
                                elseif ($percentile >= 40) $grade = 'D';

                                $pbccStatus = "Pass";
                                if (!$isPassed) {
                                    if ($isGraceAwarded) {
                                        $pbccStatus = "Pass (Grace)";
                                        $grade = 'D';
                                    } else {
                                        $failedParts = [];
                                        if ($part1 && $data['p1Theory'] < $data['p1PassMark']) $failedParts[] = ($data['pracMax'] > 0 ? "part-I theory" : "part-I");
                                        if ($part2 && $data['p2Theory'] < $data['p2PassMark']) $failedParts[] = ($data['pracMax'] > 0 ? "part-II theory" : "part-II");
                                        if ($data['pracMax'] > 0 && $data['totalPractical'] < $data['pracPassMark']) $failedParts[] = "practical";
                                        
                                        $pbccStatus = count($failedParts) > 0 ? "Reappear in " . implode(', ', $failedParts) : "Fail";
                                        $grade = 'F';
                                    }
                                }
                                $percentileStr = number_format($percentile, 2);
                                $totalStr = $obtained;
                            }
                            // Variables for display
                            $isAbsent1 = $part1 ? $part1->is_absent : false;
                            $isAbsent2 = $part2 ? $part2->is_absent : false;
                        @endphp
                        <tr>
                            <td>
                                @if($part1 || $part2) {{ $subjectName }} @else &nbsp; @endif
                                @if($pbccStatus !== "Pass" && $pbccStatus !== "Absent")
                                    <br><small class="{{ $pbccStatus === 'Pass (Grace)' ? 'text-info' : 'text-danger' }}">
                                        {{ $pbccStatus }}
                                    </small>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($part1)
                                    @if($isAbsent1 && $data['p1Theory'] == 0) Absent @else {{ $data['p1Theory'] }} @endif
                                @else -- @endif
                            </td>
                            <td>
                                @if($part2) {{ $subjectName }} @else &nbsp; @endif
                            </td>
                            <td class="text-center">
                                @if($part2)
                                    @if($isAbsent2 && $data['p2Theory'] == 0) Absent @else {{ $data['p2Theory'] }} @endif
                                @else -- @endif
                            </td>
                            <td class="text-center">
                                @if(isset($data['pracMax']) && $data['pracMax'] > 0)
                                    @if($isAbsent2 && $data['totalPractical'] == 0) Absent @else {{ $data['totalPractical'] }} @endif
                                @else &nbsp; @endif
                            </td>
                            <td class="text-center">{{ $percentileStr }}</td>
                            <td class="text-center"><strong>{{ $grade }}</strong></td>
                            <td class="text-center"><strong>{{ $totalStr }}</strong></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No final results available</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Personal Weekly Timetable --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>My Weekly Timetable</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 120px;">Lecture</th>
                            @foreach($daysOfWeek as $day)
                                <th>{{ $day }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($timeSlots as $slot)
                            <tr>
                                <td class="table-light">
                                    <strong>{{ $slot->name }}</strong><br>
                                    <small class="text-muted">{{ date('h:i A', strtotime($slot->start_time)) }}</small>
                                </td>
                                @foreach($daysOfWeek as $day)
                                    @php $entries = $timetableGrid[$slot->id][$day] ?? collect(); @endphp
                                    <td>
                                        @if($entries->isNotEmpty())
                                            @foreach($entries as $entry)
                                                <div class="{{ !$loop->last ? 'mb-2 pb-2 border-bottom' : '' }}">
                                                    <span class="d-block fw-bold text-primary">{{ $entry->subject->name }}</span>
                                                    @if($entry->section)
                                                        <span class="badge bg-info text-dark mb-1">Section: {{ $entry->section }}</span>
                                                    @endif
                                                    <small class="d-block text-muted">{{ $entry->staff->full_name ?? 'Teacher N/A' }}</small>
                                                    @if($entry->room)
                                                        <span class="badge bg-light text-dark border"><i class="fas fa-door-open me-1"></i>{{ $entry->room->name }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @if(count($timetableGrid) === 0)
            <div class="card-footer bg-white border-0 py-2">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> No timetable entries scheduled for your enrolled subjects yet.</small>
            </div>
        @endif
    </div>

    {{-- Upcoming Exams --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Upcoming Exams</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Subject</th>
                            <th>Exam Date</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upcomingExams as $exam)
                        <tr>
                            <td><strong>{{ $exam['subject']->name }}</strong></td>
                            <td>
                                <i class="fas fa-calendar text-primary me-2"></i>
                                {{ \Carbon\Carbon::parse($exam['test_date'])->format('l, F j, Y') }}
                            </td>
                            <td>
                                @if($exam['room'])
                                    <i class="fas fa-door-open text-success me-2"></i>
                                    {{ $exam['room']->name }}
                                @else
                                    <span class="text-muted">Not Assigned</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No upcoming exams scheduled</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="text-center">

        <a href="{{ route('public.students-corner') }}" class="btn btn-primary btn-lg rounded-pill px-5">
            <i class="fas fa-search me-2"></i>Search Another Student
        </a>
    </div>
</div>
@endsection
