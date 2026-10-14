@extends('layouts.app')

@section('title', 'Manage Assignments')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Assignment Manager</h1>
        <a href="{{ route('lms.assignments.create') }}" class="btn btn-primary btn-sm shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Create New
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>Subject</th>
                            <th>Title</th>
                            <th>Submissions</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                        @php $subCount = $assignment->submissions->count(); @endphp
                        <tr>
                            <td>{{ $assignment->subject_name }}</td>
                            <td>{{ $assignment->title }}</td>
                            <td>
                                <span class="badge bg-info text-white">
                                    {{ $subCount }} Submitted
                                </span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }}</td>
                            <td>
                                @if(\Carbon\Carbon::parse($assignment->due_date)->isPast())
                                    <span class="badge bg-secondary text-white">Closed</span>
                                @else
                                    <span class="badge bg-success text-white">Active</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('lms.assignments.show', $assignment->id) }}" class="btn btn-sm btn-outline-primary">
                                    Review Submissions
                                </a>
                                <a href="{{ route('lms.assignments.edit', $assignment->id) }}" class="btn btn-sm btn-outline-secondary ms-1">
                                    Edit
                                </a>
                                <form action="{{ route('lms.assignments.destroy', $assignment->id) }}" method="POST" class="d-inline ms-1">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this assignment?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">You haven't created any assignments yet.</td>
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
