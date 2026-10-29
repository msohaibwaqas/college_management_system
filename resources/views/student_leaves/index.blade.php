@extends('layouts.app')

@section('title', 'Student Leaves Management')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                <i class="fas fa-calendar-minus text-warning me-2"></i>Student Leaves
            </h1>
            <p class="text-muted small mb-0">
                Administrative recording of student leaves approved by the Principal. Leaves are synchronized to all class attendances to protect students from absences and strike-offs.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('student-leaves.create') }}" class="btn btn-warning shadow-sm">
                <i class="fas fa-plus-circle me-1"></i> Mark Student Leave
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 border-start border-warning border-4 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Currently On Leave Today</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $stats['onLeaveToday'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-clock fa-2x text-warning opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 border-start border-primary border-4 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Leaves This Month</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $stats['leavesThisMonth'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-primary opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 border-start border-info border-4 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Upcoming Leaves</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $stats['upcomingLeaves'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-plane-departure fa-2x text-info opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 border-start border-success border-4 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Approved Leaves</div>
                            <div class="h4 mb-0 fw-bold text-gray-800">{{ $stats['totalApproved'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('student-leaves.index') }}" method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Search Student / Roll / Reason</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search by name, roll, father, reason..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Class</label>
                        <select name="class" class="form-select">
                            <option value="">All Classes</option>
                            <option value="11" {{ request('class') == '11' ? 'selected' : '' }}>Class 11th</option>
                            <option value="12" {{ request('class') == '12' ? 'selected' : '' }}>Class 12th</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="approved" {{ $status == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="all" {{ $status == 'all' ? 'selected' : '' }}>All Statuses</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Date Filter</label>
                        <select name="date_filter" class="form-select">
                            <option value="">All Dates</option>
                            <option value="today" {{ $dateFilter == 'today' ? 'selected' : '' }}>Active Today</option>
                            <option value="this_week" {{ $dateFilter == 'this_week' ? 'selected' : '' }}>This Week</option>
                            <option value="this_month" {{ $dateFilter == 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="upcoming" {{ $dateFilter == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <a href="{{ route('student-leaves.index') }}" class="btn btn-outline-secondary" title="Reset Filters">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Leaves Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark">
                <i class="fas fa-list me-1"></i> Leave Records
                <span class="badge bg-secondary ms-2">{{ $leaves->total() }}</span>
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 110px;">Roll No</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Leave Period</th>
                            <th>Duration</th>
                            <th>Leave Type &amp; Reason</th>
                            <th>Approved By</th>
                            <th>Recorded By</th>
                            <th>Status</th>
                            <th class="text-end pe-3" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaves as $leave)
                            @php
                                $student = $leave->student;
                                $adm = $student?->admissions?->first();
                                $isActiveToday = $leave->isActiveOn(now());
                            @endphp
                            <tr class="{{ $isActiveToday ? 'table-warning bg-opacity-25' : '' }}">
                                <td class="ps-3 fw-bold">
                                    {{ $adm?->college_roll_no ?? 'N/A' }}
                                    @if($isActiveToday)
                                        <span class="badge bg-warning text-dark d-block mt-1" style="font-size: 0.65rem;">
                                            <i class="fas fa-user-clock"></i> Active Today
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($student)
                                        <a href="{{ route('students.show', $student) }}" class="fw-semibold text-decoration-none text-dark">
                                            {{ $student->full_name }}
                                        </a>
                                        <div class="small text-muted">S/O {{ $student->father?->name ?? 'N/A' }}</div>
                                    @else
                                        <span class="text-muted">Student Record Deleted</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $adm?->current_class ? 'Class ' . $adm->current_class . 'th' : 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $leave->start_date->format('d M Y') }}</div>
                                    <div class="small text-muted">to {{ $leave->end_date->format('d M Y') }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <i class="fas fa-clock me-1"></i>{{ $leave->days_count }} Day{{ $leave->days_count > 1 ? 's' : '' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary text-white mb-1">{{ $leave->leave_type_label }}</span>
                                    <div class="small text-dark text-truncate" style="max-width: 260px;" title="{{ $leave->reason }}">
                                        {{ $leave->reason }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="fas fa-check-double me-1"></i>{{ $leave->approved_by }}
                                    </span>
                                </td>
                                <td>
                                    <div class="small fw-semibold">{{ $leave->markedBy?->name ?? 'Staff' }}</div>
                                    <div class="small text-muted">{{ $leave->created_at->format('d M, h:i A') }}</div>
                                </td>
                                <td>
                                    @if($leave->status === 'approved')
                                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Approved</span>
                                    @else
                                        <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Cancelled</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('student-leaves.show', $leave) }}" class="btn btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($leave->status === 'approved')
                                            <a href="{{ route('student-leaves.edit', $leave) }}" class="btn btn-outline-secondary" title="Edit Leave">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" title="Cancel Leave" data-bs-toggle="modal" data-bs-target="#cancelModal{{ $leave->id }}">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Cancel Modal -->
                                    <div class="modal fade" id="cancelModal{{ $leave->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content text-start">
                                                <form action="{{ route('student-leaves.destroy', $leave) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Cancel Student Leave</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to cancel this approved leave for <strong>{{ $student?->full_name }}</strong>?</p>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Cancellation Reason (Optional)</label>
                                                            <textarea name="cancel_reason" class="form-control" rows="2" placeholder="e.g. Student returned early, application revoked..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fas fa-calendar-times fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                    <h5>No student leaves found</h5>
                                    <p class="small mb-3">No leaves match your current search or filter criteria.</p>
                                    <a href="{{ route('student-leaves.create') }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-plus me-1"></i> Mark First Student Leave
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($leaves->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end py-3">
                {{ $leaves->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
