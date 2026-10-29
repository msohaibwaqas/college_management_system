@extends('layouts.app')

@section('title', 'Mark Attendance')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Mark Attendance</h1>
        <a href="{{ route('struck-off.teacher') }}" class="btn btn-outline-danger btn-sm">
            <i class="fas fa-user-slash me-1"></i> Struck-Off Students
        </a>
    </div>

    @if(isset($isTodayOff) && $isTodayOff)
        <div class="alert alert-warning border-start border-warning border-4 shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-umbrella-beach fa-2x text-warning me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1 fw-bold">Attendance Closed Today</h5>
                    <p class="mb-0">Today is marked as <strong>{{ $todayOffReason }}</strong>. Classes are not held and attendance marking is suspended for today.</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Select Class to Mark Attendance</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Admission Year</th>
                            <th>Students</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($myClasses as $class)
                        <tr>
                            <td>{{ $class->subject_name }}</td>
                            <td>{{ $class->current_class }}th</td>
                            <td>{{ $class->admission_year }}</td>
                            <td>
                                <span class="badge bg-info text-white me-1" title="Total Students">{{ $class->student_count }}</span>
                                @if(isset($class->section_count) && $class->section_count > 0)
                                    <span class="badge bg-secondary text-white" title="Total Sections">{{ $class->section_count }} Sections</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($isTodayOff) && $isTodayOff)
                                    <button class="btn btn-secondary btn-sm" disabled title="Attendance cannot be marked today ({{ $todayOffReason }})">
                                        <i class="fas fa-ban me-1"></i> Closed ({{ str_contains($todayOffReason, 'Sunday') ? 'Sunday' : (str_contains($todayOffReason, 'Vacation') ? 'Vacation' : 'Holiday') }})
                                    </button>
                                @else
                                    <a href="{{ route('attendance.create', [
                                        'subject_id' => $class->subject_id, 
                                        'class' => $class->current_class, 
                                        'year' => $class->admission_year
                                    ]) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-check-circle me-1"></i> Mark Attendance
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No classes assigned yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
