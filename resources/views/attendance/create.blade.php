@extends('layouts.app')

@section('title', 'Mark Attendance')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Mark Attendance: {{ $subject->name }} ({{ $class }}th Class)</h1>
        <a href="{{ route('attendance.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Classes
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('attendance.create') }}" method="GET" class="mb-4">
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                <input type="hidden" name="class" value="{{ $class }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" name="date" id="date" class="form-control" value="{{ $date }}" required>
                    </div>
                    @if(isset($availableSections) && count($availableSections) > 0)
                    <div class="col-md-4">
                        <label for="section" class="form-label">Filter by Section</label>
                        <select name="section" id="section" class="form-select">
                            <option value="">All Students ({{ count($students) + (isset($section) && $section ? \App\Models\StudentTeacherSubject::where('staff_id', auth()->user()->staff_id)->where('subject_id', $subject->id)->where('class_level', $class)->where('academic_year', $year)->count() - count($students) : 0) }})</option>
                            @foreach($availableSections as $sec)
                                @php
                                    $isSecToday = isset($todayTtSections) && $todayTtSections->isNotEmpty() && $todayTtSections->contains($sec);
                                @endphp
                                <option value="{{ $sec }}" {{ isset($section) && $section == $sec ? 'selected' : '' }}>
                                    Section {{ $sec }}{{ $isSecToday ? ' (Scheduled Today)' : '' }}
                                </option>
                            @endforeach
                            <option value="unassigned" {{ isset($section) && $section == 'unassigned' ? 'selected' : '' }}>
                                Unassigned Students
                            </option>
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary d-block w-100">Apply Filter</button>
                    </div>
                </div>
            </form>

            <form action="{{ route('attendance.store') }}" method="POST">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                <input type="hidden" name="attendance_date" value="{{ $date }}">
                <input type="hidden" name="class" value="{{ $class }}">
                <input type="hidden" name="year" value="{{ $year }}">
                @if(isset($section) && $section)
                    <input type="hidden" name="section" value="{{ $section }}">
                @endif

                @if(isset($scheduledTest) && $scheduledTest)
                    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 5px solid #2563eb !important;">
                        <div class="card-body p-3">
                            <div class="row align-items-center">
                                <div class="col-lg-7 mb-3 mb-lg-0">
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="badge bg-primary px-2.5 py-1.5 me-2" style="font-size: 0.8rem;">
                                            <i class="fas fa-file-signature me-1"></i> Scheduled Test
                                        </span>
                                        <h5 class="mb-0 fw-bold text-dark">{{ $scheduledTest->exam_name }}</h5>
                                    </div>
                                    <p class="mb-0 text-muted small">
                                        A test is scheduled for <strong>{{ $subject->name }}</strong> on {{ \Carbon\Carbon::parse($date)->format('l, d M Y') }}.
                                        Marking attendance will automatically sync to <strong>Test Attendance &amp; Results</strong>:
                                        absent/leave students are recorded as absent (0 marks), and present students remain empty ready for entering obtained marks.
                                    </p>
                                </div>
                                <div class="col-lg-5">
                                    <div class="bg-white p-3 rounded-3 border shadow-sm">
                                        <label for="test_total_marks" class="form-label fw-bold text-dark small mb-1 d-flex justify-content-between align-items-center">
                                            <span><i class="fas fa-award text-warning me-1"></i> Test Total Marks <span class="text-danger">*</span></span>
                                            <span class="badge bg-light text-secondary border">Teacher Specific</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted fw-bold">Max Marks</span>
                                            <input type="number" 
                                                   name="test_total_marks" 
                                                   id="test_total_marks" 
                                                   class="form-control form-control-lg fw-bold text-primary" 
                                                   min="1" 
                                                   max="500" 
                                                   value="{{ old('test_total_marks', $existingTestMarks ?? 25) }}" 
                                                   placeholder="e.g. 25, 30, 50" 
                                                   required>
                                        </div>
                                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                            Enter the total marks prepared for this test (e.g. 25, 30, 50).
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="datesheet_id" value="{{ $scheduledTest->id }}">
                    <input type="hidden" name="exam_name" value="{{ $scheduledTest->exam_name }}">
                @endif

                @if(isset($activeLeaves) && $activeLeaves->isNotEmpty())
                    <div class="alert alert-warning border-start border-warning border-4 mb-3 d-flex align-items-center shadow-sm">
                        <i class="fas fa-calendar-check fa-2x text-warning me-3"></i>
                        <div>
                            <strong>Institutional Approved Leave Active ({{ $activeLeaves->count() }} Student{{ $activeLeaves->count() > 1 ? 's' : '' }}):</strong><br>
                            <span class="small">
                                Approved leave has been marked for student(s) below by the Principal/Administration. Their status is set to <strong>Leave (L)</strong> and protected from mass actions ("All Present" / "All Absent") to prevent accidental strike-offs.
                            </span>
                        </div>
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-success mark-all" data-status="present">
                                <i class="fas fa-check me-1"></i>All Present
                            </button>
                            <button type="button" class="btn btn-outline-danger mark-all" data-status="absent">
                                <i class="fas fa-times me-1"></i>All Absent
                            </button>
                        </div>
                    </div>
                </div>

                @section('styles')
<style>
    .col-name-father {
        white-space: nowrap !important;
    }
    @media (max-width: 768px) {
        .col-name-father {
            white-space: nowrap !important;
            font-size: 0.8rem !important;
            vertical-align: middle;
        }
        .attendance-table th,
        .attendance-table td {
            padding: 0.45rem 0.4rem !important;
        }
        .attendance-table .col-name-father .badge {
            font-size: 0.65rem !important;
            padding: 0.25em 0.4em !important;
        }
        .attendance-table .btn-group .btn {
            padding: 0.25rem 0.4rem !important;
            font-size: 0.75rem !important;
        }
    }
</style>
@endsection

               
                <div class="table-responsive">
                    <table class="table table-bordered align-middle attendance-table">
                        <thead>
                            <tr class="table-light">
                                <th class="text-nowrap">Roll No</th>
                                <th class="text-center">Status</th>
                                <th class="text-center text-nowrap">History</th>
                                <th class="col-name-father">Name</th>
                                <th class="col-name-father">Father Name</th>
                                <th class="text-nowrap">Contact No.</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                @php
                                    $leave = $activeLeaves[$student->id] ?? null;
                                    $isOnLeave = !is_null($leave);
                                    $existing = $existingAttendance[$student->id] ?? null;
                                    $status = $existing ? $existing->status : ($isOnLeave ? 'leave' : 'absent');
                                    if ($isOnLeave && (!$existing || $existing->status === 'absent')) {
                                        $status = 'leave';
                                    }
                                    $remarks = $existing ? $existing->remarks : ($isOnLeave ? "Approved Leave: {$leave->reason}" : '');
                                @endphp
                                <tr data-on-leave="{{ $isOnLeave ? 'true' : 'false' }}" style="{{ $isOnLeave ? 'background-color: rgba(255, 193, 7, 0.15) !important;' : ($student->row_bg_style ?? '') }}">
                                    <td class="text-nowrap fw-bold">
                                        {{ $student->roll_number }}
                                        @if($isOnLeave)
                                            <span class="badge bg-warning text-dark d-block mt-1" style="font-size: 0.7rem;" title="{{ $leave->reason }}">
                                                <i class="fas fa-calendar-check"></i> On Leave
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <input type="radio" class="btn-check attendance-radio" name="attendance[{{ $student->id }}]" id="present_{{ $student->id }}" value="present" {{ $status == 'present' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-success btn-sm" for="present_{{ $student->id }}">P</label>

                                            <input type="radio" class="btn-check attendance-radio" name="attendance[{{ $student->id }}]" id="absent_{{ $student->id }}" value="absent" {{ $status == 'absent' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-danger btn-sm" for="absent_{{ $student->id }}">A</label>

                                            <input type="radio" class="btn-check attendance-radio" name="attendance[{{ $student->id }}]" id="leave_{{ $student->id }}" value="leave" {{ $status == 'leave' ? 'checked' : '' }}>
                                            <label class="btn {{ $isOnLeave ? 'btn-warning fw-bold' : 'btn-outline-warning' }} btn-sm" for="leave_{{ $student->id }}">L</label>

                                            <input type="radio" class="btn-check attendance-radio" name="attendance[{{ $student->id }}]" id="late_{{ $student->id }}" value="late" {{ $status == 'late' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-info btn-sm" for="late_{{ $student->id }}">Late</label>
                                        </div>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        @if($isOnLeave)
                                            <span class="badge bg-warning text-dark px-2 py-1 fw-bold" title="Approved by: {{ $leave->approved_by }} ({{ $leave->formatted_date_range }})">
                                                <i class="fas fa-umbrella-beach me-1"></i>Approved Leave
                                            </span>
                                        @elseif(isset($student->recent_status_label))
                                            <span class="badge px-2 py-1 fw-normal" style="{{ $student->recent_badge_style }}">
                                                {{ $student->recent_status_label }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary text-white">No Record</span>
                                        @endif
                                    </td>
                                    <td class="col-name-father">
                                        {{ $student->first_name }} {{ $student->last_name }}
                                        @if(!empty($student->effective_section))
                                            <span class="badge bg-secondary text-white ms-2">{{ $student->effective_section }}</span>
                                        @endif
                                        @if($isOnLeave)
                                            <div class="small text-muted fst-italic mt-1" style="font-size: 0.75rem;">
                                                <i class="fas fa-info-circle text-warning"></i> Leave: {{ \Illuminate\Support\Str::limit($leave->reason, 35) }} ({{ $leave->days_count }}d)
                                            </div>
                                        @endif
                                    </td>
                                    <td class="col-name-father">{{ $student->father_name }}</td>
                                    <td class="text-nowrap small text-muted">{{ $student->hssc_reg_no ?? '-' }}</td>
                                    <td>
                                        <input type="text" name="remarks[{{ $student->id }}]" class="form-control form-control-sm" value="{{ $remarks }}" placeholder="Optional">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No students found for this class.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($students->count() > 0)
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-1"></i> Save Attendance
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
$(document).ready(function() {
    $('.mark-all').on('click', function() {
        var status = $(this).data('status');
        $('tbody tr').each(function() {
            // Protect students on approved leave from being overwritten by mass actions
            if ($(this).attr('data-on-leave') !== 'true') {
                $(this).find('input[value="' + status + '"].attendance-radio').prop('checked', true);
            }
        });
    });
});
</script>
@endsection
