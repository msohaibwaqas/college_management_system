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
                                <option value="{{ $sec }}" {{ isset($section) && $section == $sec ? 'selected' : '' }}>
                                    Section {{ $sec }}
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
                                    $status = $existingAttendance[$student->id]->status ?? 'absent';
                                    $remarks = $existingAttendance[$student->id]->remarks ?? '';
                                @endphp
                                <tr style="{{ $student->row_bg_style ?? '' }}">
                                    <td class="text-nowrap fw-bold">{{ $student->roll_number }}</td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <input type="radio" class="btn-check attendance-radio" name="attendance[{{ $student->id }}]" id="present_{{ $student->id }}" value="present" {{ $status == 'present' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-success btn-sm" for="present_{{ $student->id }}">P</label>

                                            <input type="radio" class="btn-check attendance-radio" name="attendance[{{ $student->id }}]" id="absent_{{ $student->id }}" value="absent" {{ $status == 'absent' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-danger btn-sm" for="absent_{{ $student->id }}">A</label>

                                            <input type="radio" class="btn-check attendance-radio" name="attendance[{{ $student->id }}]" id="leave_{{ $student->id }}" value="leave" {{ $status == 'leave' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-warning btn-sm" for="leave_{{ $student->id }}">L</label>

                                            <input type="radio" class="btn-check attendance-radio" name="attendance[{{ $student->id }}]" id="late_{{ $student->id }}" value="late" {{ $status == 'late' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-info btn-sm" for="late_{{ $student->id }}">Late</label>
                                        </div>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        @if(isset($student->recent_status_label))
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
        $('input[value="' + status + '"].attendance-radio').prop('checked', true);
    });
});
</script>
@endsection
