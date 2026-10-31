@extends('layouts.app')

@section('title', 'Teacher Dashboard')

@section('content')
@php
    $currentUser = Auth::user();
    $userIsHod = (isset($isHod) && $isHod) || ($currentUser && $currentUser->isHod());
    $departmentSubjects = (isset($hodSubjects) && $hodSubjects->isNotEmpty()) 
        ? $hodSubjects 
        : ($userIsHod && $currentUser && $currentUser->staff ? \App\Models\Subject::where('hod_staff_id', $currentUser->staff->id)->withCount(['staffSubjectSpecializations as teachers_count'])->get() : collect());
@endphp

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                @if($userIsHod)
                    Head of Department & Teacher Dashboard
                @else
                    Teacher Dashboard
                @endif
            </h1>
            @if($userIsHod)
                <p class="text-muted mb-0 small">Intermediate Department Management, Subject Sections, Faculty Allocations & Teaching Schedule</p>
            @endif
        </div>
        @if($userIsHod)
            <div class="d-none d-sm-flex gap-2">
                <a href="{{ route('student-affairs.sections.index') }}" class="btn btn-outline-primary btn-sm shadow-sm fw-bold">
                    <i class="fas fa-layer-group me-1"></i> Subject Sections
                </a>
                <a href="{{ route('staff.student_assignment') }}" class="btn btn-primary btn-sm shadow-sm fw-bold">
                    <i class="fas fa-user-plus me-1"></i> Assign Students
                </a>
            </div>
        @endif
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

    @if($userIsHod)
    {{-- HOD Department Management Hub Banner --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="border-radius: 14px; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: #ffffff;">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <div class="d-flex align-items-center mb-2 flex-wrap gap-2">
                        <span class="badge bg-warning text-dark px-3 py-1 font-weight-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem; border-radius: 20px;">
                            <i class="fas fa-crown me-1"></i> Head of Department (HOD)
                        </span>
                        <span class="badge bg-white bg-opacity-25 text-white px-2 py-1 small" style="border-radius: 20px;">
                            Intermediate / HSSC
                        </span>
                    </div>
                    <h2 class="h3 font-weight-bold text-white mb-2">
                        Department Management Hub
                    </h2>
                    <p class="text-white text-opacity-75 mb-3" style="font-size: 0.95rem; line-height: 1.5; max-width: 600px;">
                        Manage your department's subject sections, configure quotas, and assign intermediate students across your department's teaching faculty.
                    </p>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="small text-white text-opacity-75 me-1 font-weight-bold">Headed Subjects:</span>
                        @forelse($departmentSubjects as $deptSub)
                            <span class="badge bg-white text-primary px-3 py-2 shadow-sm font-weight-bold d-inline-flex align-items-center" style="font-size: 0.85rem; border-radius: 8px;">
                                <i class="fas fa-book me-1 text-primary"></i> {{ $deptSub->name }}
                                <span class="badge bg-primary text-white ms-2">{{ $deptSub->teachers_count }} Teachers</span>
                            </span>
                        @empty
                            <span class="badge bg-light text-dark">Subject HOD</span>
                        @endforelse
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <a href="{{ route('student-affairs.sections.index') }}" class="card text-decoration-none border-0 shadow h-100 bg-white text-dark hod-action-card" style="border-radius: 12px; transition: all 0.25s ease;">
                                <div class="card-body p-3 text-center d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex mb-2">
                                            <i class="fas fa-layer-group fa-2x"></i>
                                        </div>
                                        <h6 class="font-weight-bold mb-1 text-gray-800">Subject Sections</h6>
                                        <p class="text-muted small mb-3">Group students into subject sections & manage limits</p>
                                    </div>
                                    <span class="btn btn-sm btn-primary w-100 font-weight-bold shadow-sm">
                                        Manage Sections <i class="fas fa-arrow-right ms-1"></i>
                                    </span>
                                </div>
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <a href="{{ route('staff.student_assignment') }}" class="card text-decoration-none border-0 shadow h-100 bg-white text-dark hod-action-card" style="border-radius: 12px; transition: all 0.25s ease;">
                                <div class="card-body p-3 text-center d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-inline-flex mb-2">
                                            <i class="fas fa-user-plus fa-2x"></i>
                                        </div>
                                        <h6 class="font-weight-bold mb-1 text-gray-800">Assign Students</h6>
                                        <p class="text-muted small mb-3">Assign sections & students to department teachers</p>
                                    </div>
                                    <span class="btn btn-sm btn-success w-100 font-weight-bold shadow-sm">
                                        Assign Students <i class="fas fa-arrow-right ms-1"></i>
                                    </span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        @if(isset($error))
            <div class="col-12">
                <div class="alert alert-danger">{{ $error }}</div>
            </div>
        @endif

        {{-- My Classes Card --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 hover-scale">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">My Assigned Classes (Total)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $myClasses->count() + $myHeClasses->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Students Card --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 hover-scale">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Students Assigned</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $myClasses->sum('student_count') + $myHeClasses->sum('student_count') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-success opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($userIsHod)
        {{-- Department Faculty Card --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 hover-scale">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Department Faculty (Teachers)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $departmentSubjects->sum('teachers_count') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-friends fa-2x text-info opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Department Headed Subjects Card --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 hover-scale">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Department Subjects</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $departmentSubjects->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book-reader fa-2x text-warning opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="row">
        {{-- HSSC Classes --}}
        <div class="col-lg-12 mb-4">
            <div class="card shadow mb-4 border-left-primary">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">My Assigned HSSC Classes</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Class</th>
                                    <th>Year</th>
                                    <th>Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($myClasses as $class)
                                <tr>
                                    <td>{{ $class->subject_name }}</td>
                                    <td>{{ $class->current_class }}th</td>
                                    <td>{{ $class->admission_year }}</td>
                                    <td><span class="badge bg-info text-white">{{ $class->student_count }}</span></td>
                                    <td>
                                        @if(isset($isTodayOff) && $isTodayOff)
                                            <button class="btn btn-sm btn-secondary" disabled title="Attendance closed today ({{ $todayOffReason }})">
                                                <i class="fas fa-ban me-1"></i> Closed
                                            </button>
                                        @else
                                            <a href="{{ route('attendance.create', [
                                            'subject_id' => $class->subject_id, 
                                            'class' => $class->current_class, 
                                            'year' => $class->admission_year
                                        ]) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-user-check me-1"></i> Attendance
                                        </a>
                                        @endif
                                        <a href="{{ route('attendance.report.generate', [
                                            'subject_id' => $class->subject_id,
                                            'current_class' => $class->current_class,
                                            'admission_year' => $class->admission_year,
                                            'start_date' => date('Y-m-01'),
                                            'end_date' => date('Y-m-t')
                                        ]) }}" class="btn btn-sm btn-info text-white">
                                            <i class="fas fa-file-alt me-1"></i> Report
                                        </a>
                                        <a href="{{ route('results.create', [
                                            'subject_id' => $class->subject_id,
                                            'class' => $class->current_class,
                                            'year' => $class->admission_year
                                        ]) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-marker me-1"></i> Marks
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">No HSSC classes assigned yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- BS/ADP Classes --}}
        <div class="col-lg-12 mb-4">
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">My BS/ADP Assigned Classes</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Program</th>
                                    <th>Subject</th>
                                    <th>Semester</th>
                                    <th>Year</th>
                                    <th>Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($myHeClasses as $class)
                                <tr>
                                    <td>{{ $class->program_name }}</td>
                                    <td>{{ $class->subject_name }} ({{ $class->subject_code }})</td>
                                    <td>{{ $class->current_semester }}<sup>{{ match((int)$class->current_semester) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }}</sup></td>
                                    <td>{{ $class->admission_year }}</td>
                                    <td><span class="badge bg-success text-white">{{ $class->student_count }}</span></td>
                                    <td>
                                        @if(isset($isTodayOff) && $isTodayOff)
                                            <button class="btn btn-sm btn-secondary" disabled title="Attendance closed today ({{ $todayOffReason }})">
                                                <i class="fas fa-ban me-1"></i> Closed
                                            </button>
                                        @else
                                            <a href="{{ route('he.attendance.create', [
                                            'subject_id' => $class->subject_id, 
                                            'program_id' => $class->program_id,
                                            'semester' => $class->current_semester,
                                            'year' => $class->admission_year
                                        ]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-user-check me-1"></i> Attendance
                                        </a>
                                        @endif
                                        <a href="{{ route('he.attendance.report', [
                                            'subject_id' => $class->subject_id,
                                            'program_id' => $class->program_id,
                                            'semester' => $class->current_semester,
                                            'start_date' => date('Y-m-01'),
                                            'end_date' => date('Y-m-t')
                                        ]) }}" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-file-alt me-1"></i> Report
                                        </a>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-marker me-1"></i> Marks
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="{{ route('he.results.create', [
                                                    'bs_subject_id' => $class->subject_id, 
                                                    'program_id' => $class->program_id,
                                                    'semester_no' => $class->current_semester,
                                                    'academic_year' => $class->admission_year,
                                                    'exam_type' => 'Mid'
                                                ]) }}">Mid Term</a></li>
                                                <li><a class="dropdown-item" href="{{ route('he.results.create', [
                                                    'bs_subject_id' => $class->subject_id, 
                                                    'program_id' => $class->program_id,
                                                    'semester_no' => $class->current_semester,
                                                    'academic_year' => $class->admission_year,
                                                    'exam_type' => 'Sessional'
                                                ]) }}">Sessional</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No BS/ADP classes assigned yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hod-action-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.2) !important;
}
.hover-scale:hover {
    transform: scale(1.02);
    transition: transform 0.2s ease-in-out;
}
</style>
@endsection
