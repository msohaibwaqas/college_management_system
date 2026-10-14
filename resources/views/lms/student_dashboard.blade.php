@extends('layouts.app')

@section('title', 'Student LMS Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Learning Portal (Student)</h1>
        <div class="text-muted small">Welcome back, <strong>{{ $student->full_name }}</strong></div>
    </div>

    <!-- Stats Row -->
    <div class="row">
        <!-- Admission Info -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Current Class</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $student->current_class }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-id-card fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Assignments -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">New Assignments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $assignments->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Support -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">My Performance</div>
                            <a href="{{ route('lms.performance') }}" class="btn btn-sm btn-success mt-1">View Result</a>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Assignments -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Assignments</h6>
                    <a href="{{ route('lms.assignments.index') }}" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Title</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignments as $assignment)
                                @php 
                                    $sub = $recentSubmissions->where('assignment_id', $assignment->id)->first();
                                    $isOverdue = \Carbon\Carbon::parse($assignment->due_date)->isPast() && !$sub;
                                @endphp
                                <tr>
                                    <td>{{ $assignment->subject_name }}</td>
                                    <td>{{ $assignment->title }}</td>
                                    <td>
                                        <span class="{{ $isOverdue ? 'text-danger font-weight-bold' : '' }}">
                                            {{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($sub)
                                            <span class="badge bg-success">Submitted</span>
                                        @elseif($isOverdue)
                                            <span class="badge bg-danger">Overdue</span>
                                        @else
                                            <span class="badge bg-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('lms.assignments.show', $assignment->id) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">No assignments found for your subjects.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messaging Sidebar -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Chat (Teachers)</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('lms.messages.index') }}" class="list-group-item list-group-item-action text-center text-primary font-weight-bold">
                            <i class="fas fa-comments me-2"></i>Open Messenger
                        </a>
                    </div>
                    <p class="small text-muted mt-3">Click to start a discussion with your subject teachers regarding assignments or queries.</p>
                </div>
            </div>
            
            <!-- Quick Tips -->
            <div class="card shadow mb-4 border-bottom-info">
                <div class="card-body">
                    <h6 class="font-weight-bold">LMS Tip</h6>
                    <p class="small mb-0">You can upload scanned copies of your homework in JPG, PNG or PDF format directly through the assignment portal.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
