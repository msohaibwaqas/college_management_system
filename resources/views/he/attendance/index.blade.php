@extends('layouts.app')
@section('title', 'My BS/ADP Classes')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-chalkboard-teacher me-2"></i>My BS/ADP Classes</h2>
            <p class="text-muted">Select a class to mark attendance.</p>
        </div>
    </div>

    @if($myClasses->count() > 0)
        <div class="row">
            @foreach($myClasses as $class)
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-book me-2"></i>{{ $class->subject_name }}
                            </h5>
                            <small class="opacity-75">({{ $class->subject_code }})</small>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="fas fa-graduation-cap text-muted me-2"></i>
                                    <strong>Program:</strong> {{ $class->program_name }}
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-layer-group text-muted me-2"></i>
                                    <strong>Semester:</strong> {{ $class->current_semester }}
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-calendar-alt text-muted me-2"></i>
                                    <strong>Year:</strong> {{ $class->admission_year }}
                                </li>
                                <li>
                                    <i class="fas fa-users text-muted me-2"></i>
                                    <strong>Students:</strong> 
                                    <span class="badge bg-info">{{ $class->student_count }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="{{ route('he.attendance.create', [
                                'subject_id' => $class->subject_id,
                                'program_id' => $class->program_id,
                                'semester' => $class->current_semester,
                                'year' => $class->admission_year
                            ]) }}" class="btn btn-success w-100">
                                <i class="fas fa-clipboard-check me-1"></i>Mark Attendance
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                <h4>No Classes Assigned</h4>
                <p class="text-muted">You don't have any BS/ADP classes assigned yet.<br>
                Please contact the administrator to get classes assigned.</p>
            </div>
        </div>
    @endif
</div>
@endsection
