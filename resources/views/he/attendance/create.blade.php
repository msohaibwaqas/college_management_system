@extends('layouts.app')
@section('title', 'Mark Attendance')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-clipboard-check me-2"></i>Mark Attendance</h2>
            <p class="text-muted">
                <strong>{{ $subject->name ?? 'Unknown Subject' }}</strong> | 
                Date: <strong>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</strong>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('he.attendance.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Classes
            </a>
        </div>
    </div>

    <form action="{{ route('he.attendance.store') }}" method="POST">
        @csrf
        <input type="hidden" name="subject_id" value="{{ $subject->id }}">
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Attendance Date</h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <input type="date" name="attendance_date" class="form-control form-control-lg" 
                               value="{{ $date }}" required>
                    </div>
                    <div class="col-md-8">
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
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>Students 
                    <span class="badge bg-primary">{{ $students->count() }}</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Roll No</th>
                                <th>Student Name</th>
                                <th class="text-center" style="width: 250px;">Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $index => $student)
                                @php
                                    $existing = $existingAttendance[$student->he_admission_id] ?? null;
                                    $currentStatus = $existing ? $existing->status : 'present';
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $student->roll_no }}</strong></td>
                                    <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <input type="radio" class="btn-check attendance-radio" 
                                                   name="attendance[{{ $student->he_admission_id }}]" 
                                                   id="present_{{ $student->he_admission_id }}" 
                                                   value="present" 
                                                   {{ $currentStatus == 'present' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-success btn-sm" for="present_{{ $student->he_admission_id }}">
                                                <i class="fas fa-check"></i> P
                                            </label>

                                            <input type="radio" class="btn-check attendance-radio" 
                                                   name="attendance[{{ $student->he_admission_id }}]" 
                                                   id="absent_{{ $student->he_admission_id }}" 
                                                   value="absent"
                                                   {{ $currentStatus == 'absent' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-danger btn-sm" for="absent_{{ $student->he_admission_id }}">
                                                <i class="fas fa-times"></i> A
                                            </label>

                                            <input type="radio" class="btn-check attendance-radio" 
                                                   name="attendance[{{ $student->he_admission_id }}]" 
                                                   id="leave_{{ $student->he_admission_id }}" 
                                                   value="leave"
                                                   {{ $currentStatus == 'leave' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-warning btn-sm" for="leave_{{ $student->he_admission_id }}">
                                                <i class="fas fa-calendar-minus"></i> L
                                            </label>

                                            <input type="radio" class="btn-check attendance-radio" 
                                                   name="attendance[{{ $student->he_admission_id }}]" 
                                                   id="late_{{ $student->he_admission_id }}" 
                                                   value="late"
                                                   {{ $currentStatus == 'late' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-info btn-sm" for="late_{{ $student->he_admission_id }}">
                                                <i class="fas fa-clock"></i> Lt
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="remarks[{{ $student->he_admission_id }}]" 
                                               class="form-control form-control-sm" 
                                               placeholder="Optional remarks"
                                               value="{{ $existing->remarks ?? '' }}">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                        No students found for this class.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($students->count() > 0)
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-1"></i>Save Attendance
                    </button>
                </div>
            @endif
        </div>
    </form>
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
