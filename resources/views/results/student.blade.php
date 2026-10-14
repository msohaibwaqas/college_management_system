@extends('layouts.app')
@section('title', 'Student Results')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-graduate me-2"></i>Student Results</h2>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>

    <!-- Student Info -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 text-center">
                    @if($student->contact && $student->contact->picture)
                        <img src="{{ asset($student->contact->picture) }}" class="img-thumbnail rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                    @else
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px;">
                            <i class="fas fa-user fa-3x text-secondary"></i>
                        </div>
                    @endif
                </div>
                <div class="col-md-10">
                    <h4 class="mb-1">{{ $student->first_name }} {{ $student->last_name }}</h4>
                    <p class="text-muted mb-2">
                        <strong>Roll No:</strong> {{ $student->admissions->first()->college_roll_no ?? 'N/A' }} | 
                        <strong>Class:</strong> {{ $student->admissions->first()->current_class ?? 'N/A' }}th
                    </p>
                    <div class="d-flex gap-3">
                        <span class="badge bg-primary">Father: {{ $student->father->name ?? 'N/A' }}</span>
                        <span class="badge bg-info">Admission Year: {{ $student->admissions->first()->admission_year ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    {{-- College Test Results --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>College Test Results</h5>
        </div>
        <div class="card-body p-4">
            @php
                $internalTests = $allResults->toBase()->except(['Final']);
            @endphp

            @forelse($internalTests as $examType => $examResults)
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
                                @foreach($examResults as $result)
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
</div>
@endsection
