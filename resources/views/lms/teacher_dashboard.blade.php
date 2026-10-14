@extends('layouts.app')

@section('title', 'Teacher LMS Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Learning Management (Teacher)</h1>
        <div>
            <a href="{{ route('lms.assignments.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> New Assignment
            </a>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Assignments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $assignments->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book-open fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Submissions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pendingSubmissionsCount }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-export fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Messenger</div>
                            <a href="{{ route('lms.messages.index') }}" class="btn btn-sm btn-info text-white mt-1">Open Chat</a>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Assignments & Submissions</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Subject</th>
                                    <th>Due Date</th>
                                    <th>Submissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->title }}</td>
                                    <td>{{ $assignment->subject_name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge bg-info text-white">{{ $assignment->submissions->count() }} Submissions</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('lms.assignments.show', $assignment->id) }}" class="btn btn-sm btn-secondary">Review</a>
                                        <a href="{{ route('lms.assignments.edit', $assignment->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">No assignments created yet.</td>
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
@endsection
