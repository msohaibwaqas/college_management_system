@extends('layouts.app')

@section('title', 'Review Submissions')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Submission Review</h1>
    </div>

    <div class="card shadow mb-4 border-left-info">
        <div class="card-body">
            <h5 class="font-weight-bold text-info">{{ $assignment->title }}</h5>
            <p class="mb-0 text-muted small"><strong>Due Date:</strong> {{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }} | <strong>Submissions:</strong> {{ $submissions->count() }}</p>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Student Submissions</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Submitted On</th>
                            <th>Score</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($submissions as $sub)
                        <tr>
                            <td>{{ $sub->student->admission_no }}</td>
                            <td>{{ $sub->student->full_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($sub->submitted_at)->format('M d, H:i') }}</td>
                            <td>
                                @if($sub->status == 'graded')
                                    <span class="badge bg-success shadow-sm">{{ $sub->obtained_marks }} / {{ $assignment->total_marks }}</span>
                                @else
                                    <span class="badge bg-warning text-dark">Not Graded</span>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#gradeModal{{ $sub->id }}">
                                    Review & Grade
                                </button>
                                <a href="{{ asset('storage/' . $sub->file_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary ms-1">
                                    <i class="fas fa-download"></i>
                                </a>

                                <!-- Grading Modal -->
                                <div class="modal fade" id="gradeModal{{ $sub->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Grade Submission: {{ $sub->student->full_name }}</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('lms.assignments.grade', $sub->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
                                                    @if($sub->student_notes)
                                                        <div class="mb-3 p-2 bg-light rounded italic small">
                                                            <strong>Student Notes:</strong> {{ $sub->student_notes }}
                                                        </div>
                                                    @endif
                                                    <div class="mb-3">
                                                        <label class="form-label font-weight-bold">Obtained Marks (Max: {{ $assignment->total_marks }})</label>
                                                        <input type="number" name="obtained_marks" class="form-control" value="{{ $sub->obtained_marks ?? '' }}" max="{{ $assignment->total_marks }}" min="0" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label font-weight-bold">Feedback</label>
                                                        <textarea name="teacher_feedback" class="form-control" rows="3">{{ $sub->teacher_feedback ?? '' }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-modal="dismiss">Close</button>
                                                    <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Grade</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No submissions yet for this assignment.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
