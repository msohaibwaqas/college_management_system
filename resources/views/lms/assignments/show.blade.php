@extends('layouts.app')

@section('title', 'Assignment Details')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('lms.dashboard') }}">LMS</a></li>
            <li class="breadcrumb-item"><a href="{{ route('lms.assignments.index') }}">Assignments</a></li>
            <li class="breadcrumb-item active">{{ $assignment->title }}</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Assignment Info -->
        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">Assignment Details</h6>
                </div>
                <div class="card-body">
                    <h4 class="text-primary font-weight-bold">{{ $assignment->title }}</h4>
                    <p class="text-muted small">
                        <strong>Subject:</strong> {{ $assignment->subject_name }} | 
                        <strong>Teacher:</strong> {{ $assignment->teacher->full_name }} | 
                        <strong>Due:</strong> {{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }}
                    </p>
                    <hr>
                    <div class="assignment-description">
                        {!! nl2br(e($assignment->description)) !!}
                    </div>
                    @if($assignment->file_path)
                    <div class="mt-4 p-3 bg-light rounded d-flex align-items-center">
                        <i class="fas fa-paperclip fa-lg text-primary me-3"></i>
                        <div>
                            <div class="small text-muted">Attachment</div>
                            <a href="{{ asset('storage/' . $assignment->file_path) }}" target="_blank" class="font-weight-bold text-decoration-none">
                                Download Reference Material
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Submission Section -->
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 {{ $submission ? 'bg-success text-white' : 'bg-warning text-dark' }}">
                    <h6 class="m-0 font-weight-bold">
                        {{ $submission ? 'Your Submission' : 'Submit Homework' }}
                    </h6>
                </div>
                <div class="card-body">
                    @if($submission)
                        <div class="text-center py-3">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h5 class="text-success font-weight-bold">Homework Submitted</b></h5>
                            <p class="small text-muted">Submitted on {{ \Carbon\Carbon::parse($submission->submitted_at)->format('M d, Y at H:i') }}</p>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <strong>File:</strong> 
                            <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary ms-2">
                                <i class="fas fa-external-link-alt me-1"></i> View Submitted File
                            </a>
                        </div>
                        @if($submission->status == 'graded')
                            <div class="p-3 bg-light border-left-success rounded mb-3">
                                <h6 class="font-weight-bold text-success">Grade Received</h6>
                                <div class="h4 mb-2">{{ $submission->obtained_marks }} / {{ $assignment->total_marks }}</div>
                                @if($submission->teacher_feedback)
                                    <p class="small text-muted mb-0"><strong>Teacher Feedback:</strong> {{ $submission->teacher_feedback }}</p>
                                @endif
                            </div>
                        @else
                            <div class="alert alert-info py-2 small">
                                <i class="fas fa-info-circle me-1"></i> Your teacher is currently reviewing your submission.
                            </div>
                        @endif
                    @elseif(\Carbon\Carbon::parse($assignment->due_date)->isPast())
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i> The deadline for this assignment has passed.
                        </div>
                    @else
                        <form action="{{ route('lms.assignments.submit', $assignment->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Upload Homework File</label>
                                <input type="file" name="submission_file" class="form-control" required>
                                <div class="form-text small">Accepted formats: PDF, ZIP, JPG, PNG (Max 10MB)</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Additional Notes (Optional)</label>
                                <textarea name="student_notes" class="form-control" rows="3" placeholder="Any comments for your teacher..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 shadow-sm">
                                <i class="fas fa-upload me-2 shadow-sm"></i> Submit Now
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
