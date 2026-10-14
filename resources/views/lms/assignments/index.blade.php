@extends('layouts.app')

@section('title', 'My Assignments')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Assignments</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th>Subject</th>
                            <th>Title</th>
                            <th>Teacher</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                        @php 
                            $submission = auth()->user()->student->assignmentSubmissions->where('assignment_id', $assignment->id)->first();
                            $isOverdue = \Carbon\Carbon::parse($assignment->due_date)->isPast() && !$submission;
                        @endphp
                        <tr>
                            <td><strong>{{ $assignment->subject_name }}</strong></td>
                            <td>{{ $assignment->title }}</td>
                            <td>{{ $assignment->teacher->full_name }}</td>
                            <td>
                                <span class="{{ $isOverdue ? 'text-danger font-weight-bold' : '' }}">
                                    {{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }}
                                </span>
                            </td>
                            <td>
                                @if($submission)
                                    <span class="badge bg-success">
                                        {{ $submission->status == 'graded' ? 'Graded ('.$submission->obtained_marks.'/'.$assignment->total_marks.')' : 'Submitted' }}
                                    </span>
                                @elseif($isOverdue)
                                    <span class="badge bg-danger">Overdue</span>
                                @else
                                    <span class="badge bg-warning">Pending</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('lms.assignments.show', $assignment->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye shadow-sm"></i> {{ $submission ? 'View' : 'Open' }}
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">No assignments assigned to you yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $assignments->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
