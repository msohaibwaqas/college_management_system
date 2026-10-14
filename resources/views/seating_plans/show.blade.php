<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Seating Plan - {{ $seatingPlan->test_date->format('d M Y') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
</head>
<body class="p-3 seating-plan-page">
    <div class="no-print mb-3 text-center">
        <button onclick="window.print()" class="btn btn-primary btn-lg me-2">
            <i class="fas fa-print me-2"></i>Print Seating Plan
        </button>
        <a href="{{ route('seating-plans.attendance', $seatingPlan) }}" class="btn btn-success btn-lg me-2">
            <i class="fas fa-clipboard-list me-2"></i>Print Attendance Sheet
        </a>
        <a href="{{ route('seating-plans.index') }}" class="btn btn-secondary btn-lg">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    @php
        $roomChunks = array_chunk($data, 2, true);
        $totalPages = count($roomChunks);
    @endphp

    @foreach($roomChunks as $pageIndex => $roomsOnPage)
        <div class="{{ $pageIndex < $totalPages - 1 ? 'page-break' : '' }}">
            <div class="page-header">
                Seating Plan - {{ $seatingPlan->test_date->format('d F Y') }}
                <span class="page-number">Page {{ $pageIndex + 1 }}/{{ $totalPages }}</span>
            </div>

            @foreach($roomsOnPage as $roomData)
                <div class="room-section">
                    <div class="room-title">{{ $roomData['room_name'] }}</div>
                    <div class="text-center mb-3">
                        <div class="mb-1">
                            <strong>Invigilators:</strong>
                            @forelse($roomData['invigilators'] as $invigilator)
                                {{ $invigilator->staff->first_name }} {{ $invigilator->staff->last_name }} 
                                ({{ $invigilator->staff->designationModel->name ?? 'N/A' }}){{ !$loop->last ? ',' : '' }}
                            @empty
                                <span class="text-danger">Not Assigned</span>
                            @endforelse
                        </div>
                        <div class="small text-muted">
                            @foreach($roomData['subject_counts'] as $subject)
                                <span class="badge bg-light text-dark border me-1">
                                    {{ $subject['name'] }} = {{ $subject['count'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="roll-grid">
                        @foreach($roomData['students_by_class'] as $class => $students)
                            <div class="year-divider">Class {{ $class }}</div>
                             @foreach($students as $detail)
                                 @php
                                     $collegeRoll = '-';
                                     $studentName = 'Empty / Vacant';
                                     $subjectName = $detail->subject ? $detail->subject->name : '';
                                     
                                     if ($detail->student) {
                                         $admission = $detail->student->admissions()
                                             ->where('admission_year', $detail->admission_year)
                                             ->first();
                                         $collegeRoll = $admission->college_roll_no ?? $detail->student->id;
                                         $studentName = $detail->student->full_name;
                                     }
                                 @endphp
                                 <div class="roll-cell {{ !$detail->student ? 'bg-light text-muted' : '' }}">
                                     <div>{{ $collegeRoll }}</div>
                                     <div class="student-name">{{ $studentName }}</div>
                                     @if($subjectName)
                                         <div class="student-name" style="color: #007bff;">{{ $subjectName }}</div>
                                     @endif
                                 </div>
                             @endforeach
                        @endforeach
                    </div>
                    
                    <div class="room-stats">
                        Total: {{ $roomData['students_by_class']->flatten()->count() }} students
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

    @if(empty($data))
        <div class="alert alert-warning text-center">No seating details found for this plan.</div>
    @endif

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <div class="d-print-none text-center pb-3">
        @cmsCredits
    </div>
</body>
</html>
