@extends('layouts.app')

@section('title', 'Mark Staff Attendance')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-clipboard-user me-2"></i>Daily Staff Attendance</h1>
            <p class="page-subtitle">Mark present/absent statuses for all administrative and teaching staff.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Date Selector -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('staff.attendance.index') }}" method="GET" class="row gx-3 gy-2 align-items-center">
                <div class="col-sm-3">
                    <label class="visually-hidden" for="dateFilter">Date</label>
                    <input type="date" class="form-control form-control-lg" id="dateFilter" name="date" value="{{ $date }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-lg">Load Roster</button>
                    @if($date == date('Y-m-d'))
                        <span class="badge bg-info ms-2">Today</span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($holiday)
        <div class="alert alert-info border-info shadow-sm d-flex align-items-center">
            <i class="fas fa-calendar-star fa-2x me-3"></i>
            <div>
                <h5 class="mb-0 fw-bold">{{ $holiday->title }}</h5>
                <p class="mb-0 small">The selected date is marked as a {{ $holiday->type }} holiday. Attendance is automatically bypassed.</p>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('staff.attendance.store') }}" method="POST">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%">Staff Member</th>
                                <th style="width: 25%">Status</th>
                                <th style="width: 15%">Check In</th>
                                <th style="width: 15%">Check Out</th>
                                <th style="width: 20%">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($staffMembers as $staff)
                                @php
                                    $record = $existingAttendance->get($staff->id);
                                    $leave = $leaves->get($staff->id);
                                    
                                    // Determine default status
                                    $defaultStatus = 'present';
                                    if ($holiday) {
                                        $defaultStatus = 'holiday';
                                    } elseif ($leave) {
                                        $defaultStatus = 'leave';
                                    } elseif ($record) {
                                        $defaultStatus = $record->status;
                                    }

                                    $isLocked = ($leave || $holiday);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar bg-primary text-white rounded-circle text-center d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                                {{ substr($staff->first_name, 0, 1) }}
                                            </div>
                                            <div>
                                                <strong>{{ $staff->first_name }} {{ $staff->last_name }}</strong>
                                                <div class="small text-muted">{{ $staff->designation ?? 'Staff' }} | {{ $staff->employee_id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($leave)
                                            <span class="badge bg-warning text-dark mb-1"><i class="fas fa-suitcase-rolling me-1"></i>Approved Leave</span>
                                            <input type="hidden" name="attendance[{{ $staff->id }}][status]" value="leave">
                                            <input type="hidden" name="attendance[{{ $staff->id }}][leave_application_id]" value="{{ $leave->id }}">
                                        @elseif($holiday)
                                            <span class="badge bg-info text-dark mb-1"><i class="fas fa-gift me-1"></i>Holiday</span>
                                            <input type="hidden" name="attendance[{{ $staff->id }}][status]" value="holiday">
                                        @else
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="attendance[{{ $staff->id }}][status]" id="status_p_{{ $staff->id }}" value="present" {{ $defaultStatus === 'present' ? 'checked' : '' }}>
                                                <label class="btn btn-outline-success btn-sm w-25" for="status_p_{{ $staff->id }}">P</label>

                                                <input type="radio" class="btn-check" name="attendance[{{ $staff->id }}][status]" id="status_a_{{ $staff->id }}" value="absent" {{ $defaultStatus === 'absent' ? 'checked' : '' }}>
                                                <label class="btn btn-outline-danger btn-sm w-25" for="status_a_{{ $staff->id }}">A</label>

                                                <input type="radio" class="btn-check" name="attendance[{{ $staff->id }}][status]" id="status_l_{{ $staff->id }}" value="late" {{ $defaultStatus === 'late' ? 'checked' : '' }}>
                                                <label class="btn btn-outline-warning btn-sm w-25" for="status_l_{{ $staff->id }}">L</label>
                                                
                                                <input type="radio" class="btn-check" name="attendance[{{ $staff->id }}][status]" id="status_h_{{ $staff->id }}" value="half_day" {{ $defaultStatus === 'half_day' ? 'checked' : '' }}>
                                                <label class="btn btn-outline-secondary btn-sm w-25" for="status_h_{{ $staff->id }}">HD</label>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="time" name="attendance[{{ $staff->id }}][check_in_time]" class="form-control form-control-sm" value="{{ $record->check_in_time ?? '' }}" {{ $isLocked ? 'readonly' : '' }}>
                                    </td>
                                    <td>
                                        <input type="time" name="attendance[{{ $staff->id }}][check_out_time]" class="form-control form-control-sm" value="{{ $record->check_out_time ?? '' }}" {{ $isLocked ? 'readonly' : '' }}>
                                    </td>
                                    <td>
                                        <input type="text" name="attendance[{{ $staff->id }}][remarks]" class="form-control form-control-sm" value="{{ $record->remarks ?? '' }}" placeholder="Remarks..." {{ $isLocked ? 'readonly' : '' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save me-2"></i>Save Attendance Records
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
