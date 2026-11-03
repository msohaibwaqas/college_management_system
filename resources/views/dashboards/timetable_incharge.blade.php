@extends('layouts.app')

@section('title', 'Timetable Incharge Dashboard')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                <i class="fas fa-calendar-alt text-warning me-2"></i>Timetable Incharge Dashboard
            </h1>
            <p class="text-muted small mb-0">Academic scheduling, lecture periods, time slots, and room allocations</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @php
                $user = Auth::user();
                $hasTeacher = $user && $user->hasRole('teacher');
                $hasHod = $user && ($user->hasRole('hod') || !empty($user->getHodSubjectIds()));
            @endphp
            @if($hasTeacher)
                <span class="badge bg-success"><i class="fas fa-chalkboard-teacher me-1"></i> Faculty Teacher</span>
            @endif
            @if($hasHod)
                <span class="badge bg-primary"><i class="fas fa-user-tie me-1"></i> Head of Department</span>
            @endif
            <a href="{{ route('admin.timetables.summary') }}" target="_blank" class="btn btn-sm btn-outline-success shadow-sm">
                <i class="fas fa-print me-1"></i> Print Timetable
            </a>
            <a href="{{ route('admin.timetables.index') }}" class="btn btn-sm btn-warning text-dark font-weight-bold shadow-sm">
                <i class="fas fa-table me-1"></i> Master Timetable
            </a>
        </div>
    </div>

    {{-- Metrics Row --}}
    <div class="row">
        <!-- HSSC Timetable Entries -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                HSSC Scheduled Lectures
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalEntries ?? 0 }}</div>
                            <small class="text-muted">Active periods in schedule</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BS / HE Timetable Entries -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                BS / ADP Scheduled Slots
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalHeEntries ?? 0 }}</div>
                            <small class="text-muted">Semester timetable slots</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Time Slots Configured -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Configured Time Slots
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalTimeSlots ?? 0 }}</div>
                            <small class="text-muted">Daily lecture periods</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lecture Rooms Available -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Lecture Rooms & Blocks
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalRooms ?? 0 }}</div>
                            <small class="text-muted">{{ $activeTeachersCount ?? 0 }} teachers scheduled</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-door-open fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Action Hub --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 bg-light d-flex align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt text-warning me-2"></i>Timetable Management Actions
                    </h6>
                    <span class="badge bg-secondary">Authorized as Timetable Incharge</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 border rounded h-100 bg-light">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-primary p-2 me-2"><i class="fas fa-table fa-lg"></i></span>
                                    <h6 class="mb-0 fw-bold">Class Timetable Matrix</h6>
                                </div>
                                <p class="text-muted small mb-3">View and edit daily lecture schedule by admission year and day of the week.</p>
                                <a href="{{ route('admin.timetables.index') }}" class="btn btn-sm btn-primary w-100">
                                    Open Master Matrix <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 border rounded h-100 bg-light">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-warning text-dark p-2 me-2"><i class="fas fa-chalkboard-teacher fa-lg"></i></span>
                                    <h6 class="mb-0 fw-bold">Teacher Batch Builder</h6>
                                </div>
                                <p class="text-muted small mb-3">Assign a teacher's full weekly schedule across all slots in a single fast interface.</p>
                                <a href="{{ route('admin.timetables.teacher_batch') }}" class="btn btn-sm btn-warning text-dark font-weight-bold w-100">
                                    Teacher Batch Grid <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 border rounded h-100 bg-light">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge p-2 me-2 text-white" style="background-color: #6f42c1;"><i class="fas fa-book fa-lg"></i></span>
                                    <h6 class="mb-0 fw-bold">Subject Dept Builder</h6>
                                </div>
                                <p class="text-muted small mb-3">Schedule subject sections across teachers and class slots in one consolidated view.</p>
                                <a href="{{ route('admin.timetables.subject_batch') }}" class="btn btn-sm text-white w-100" style="background-color: #6f42c1;">
                                    Subject Batch Grid <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-md-4 mt-3">
                            <div class="p-3 border rounded h-100 bg-light">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-success p-2 me-2"><i class="fas fa-file-alt fa-lg"></i></span>
                                    <h6 class="mb-0 fw-bold">Master Summary View</h6>
                                </div>
                                <p class="text-muted small mb-3">View the complete college weekly timetable grid for teachers, rooms, and sections.</p>
                                <a href="{{ route('admin.timetables.summary') }}" target="_blank" class="btn btn-sm btn-success w-100">
                                    View Full Grid <i class="fas fa-external-link-alt ms-1"></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-md-4 mt-3">
                            <div class="p-3 border rounded h-100 bg-light">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-info p-2 me-2"><i class="fas fa-clock fa-lg"></i></span>
                                    <h6 class="mb-0 fw-bold">Time Slots & Periods</h6>
                                </div>
                                <p class="text-muted small mb-3">Create and manage bell timings, period duration, numbers, and daily breaks.</p>
                                <a href="{{ route('time-slots.index') }}" class="btn btn-sm btn-info text-white w-100">
                                    Configure Time Slots <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-md-4 mt-3">
                            <div class="p-3 border rounded h-100 bg-light">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-secondary p-2 me-2"><i class="fas fa-graduation-cap fa-lg"></i></span>
                                    <h6 class="mb-0 fw-bold">BS / ADP Timetables</h6>
                                </div>
                                <p class="text-muted small mb-3">Manage semester-wise higher education timetables, credit hours, and labs.</p>
                                <a href="{{ route('he.timetables.index') }}" class="btn btn-sm btn-secondary w-100">
                                    Manage HE Timetable <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Multi-Role Context Card --}}
    @if($hasTeacher || $hasHod)
    <div class="row">
        <div class="col-12">
            <div class="card shadow border-left-info mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="font-weight-bold text-info mb-1">
                                <i class="fas fa-users-cog me-2"></i>Multi-Role Profile Active
                            </h6>
                            <p class="text-muted small mb-0">
                                Your account is configured with multiple concurrent roles:
                                <strong>Timetable Incharge</strong>
                                @if($hasHod) + <strong>Head of Department (HOD)</strong> @endif
                                @if($hasTeacher) + <strong>Teacher</strong> @endif.
                                You can switch your active session view anytime using the top role switcher in the navbar.
                            </p>
                        </div>
                        @if($hasTeacher)
                            <form action="{{ route('switch.role') }}" method="POST">
                                @csrf
                                <input type="hidden" name="role" value="teacher">
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-chalkboard-teacher me-1"></i> Switch to Teacher View
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
