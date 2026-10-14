@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Student Promotion & Graduation Management</h2>
            <p class="text-muted">Automatically manage student class promotions and graduations</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('output'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <strong>Command Output:</strong>
            <pre class="mb-0 mt-2">{{ session('output') }}</pre>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Class 11 Students</h6>
                    <h2 class="mb-0 text-primary">{{ number_format($class11Students) }}</h2>
                    <small class="text-muted">Currently enrolled</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Class 12 Students</h6>
                    <h2 class="mb-0 text-info">{{ number_format($class12Students) }}</h2>
                    <small class="text-muted">Currently enrolled</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Eligible for Promotion</h6>
                    <h2 class="mb-0 text-warning">{{ number_format($eligibleForPromotion) }}</h2>
                    <small class="text-muted">Class 11 → Class 12</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Eligible for Graduation</h6>
                    <h2 class="mb-0 text-success">{{ number_format($eligibleForGraduation) }}</h2>
                    <small class="text-muted">Class 12 → Graduated</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Promotion Rules -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Automatic Promotion Rules</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary"><i class="fas fa-arrow-up me-2"></i>Class 11 → Class 12 Promotion</h6>
                    <ul class="list-unstyled ms-3">
                        <li><i class="fas fa-check text-success me-2"></i>Admission starts: <strong>July of admission year</strong></li>
                        <li><i class="fas fa-check text-success me-2"></i>Exams held: <strong>May of (admission year + 1)</strong></li>
                        <li><i class="fas fa-check text-success me-2"></i>Promotion date: <strong>June 1st of (admission year + 1)</strong></li>
                    </ul>
                    <div class="alert alert-info mb-0">
                        <small><strong>Example:</strong> Student admitted in July 2025 → Promoted to Class 12 on June 1, 2026</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-success"><i class="fas fa-user-graduate me-2"></i>Class 12 → Graduation</h6>
                    <ul class="list-unstyled ms-3">
                        <li><i class="fas fa-check text-success me-2"></i>Final exams: <strong>April of (admission year + 2)</strong></li>
                        <li><i class="fas fa-check text-success me-2"></i>Graduation date: <strong>April 30th of (admission year + 2)</strong></li>
                        <li><i class="fas fa-check text-success me-2"></i>Status changes to: <strong>Graduated</strong></li>
                    </ul>
                    <div class="alert alert-success mb-0">
                        <small><strong>Example:</strong> Student admitted in July 2025 → Graduated on April 30, 2027</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Update Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Manual Update</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">
                The system automatically updates student status daily at 1:00 AM. 
                You can also manually trigger the update process below.
            </p>
            <div class="row">
                <div class="col-md-6">
                    <form action="{{ route('students.promotion.update') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-sync-alt me-2"></i>Update Student Status (Date-Based)
                        </button>
                        <small class="text-muted d-block mt-2">
                            This will only update students whose promotion/graduation date has passed.
                        </small>
                    </form>
                </div>
                <div class="col-md-6">
                    <form action="{{ route('students.promotion.update') }}" method="POST" class="d-inline" 
                          onsubmit="return confirm('This will force update ALL eligible students regardless of date. Continue?');">
                        @csrf
                        <input type="hidden" name="force" value="1">
                        <button type="submit" class="btn btn-warning btn-lg w-100">
                            <i class="fas fa-bolt me-2"></i>Force Update All Eligible
                        </button>
                        <small class="text-muted d-block mt-2">
                            This will update all students regardless of the current date.
                        </small>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Information -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Automated Schedule</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Automatic Updates:</strong> The system runs daily at <strong>1:00 AM</strong> to check and update student status.
                <br>
                <small class="text-muted">
                    Current server time: <strong>{{ $currentDate->format('F d, Y h:i A') }}</strong>
                </small>
            </div>
            <hr>
            <h6 class="mb-3">To enable automatic scheduling on your server:</h6>
            <ol>
                <li>Add this cron entry to your server's crontab:</li>
            </ol>
            <div class="bg-dark text-white p-3 rounded">
                <code>* * * * * cd /home/main/Music/scratch && php artisan schedule:run >> /dev/null 2>&1</code>
            </div>
            <small class="text-muted mt-2 d-block">
                This cron job runs every minute and Laravel will execute scheduled tasks at their designated times.
            </small>
        </div>
    </div>

    <!-- Recent Updates -->
    @if($recentUpdates->count() > 0)
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Updates (Last 30 Days)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student Name</th>
                            <th>Current Class</th>
                            <th>Status</th>
                            <th>Admission Year</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentUpdates as $update)
                        <tr>
                            <td>{{ $update->student->full_name ?? 'N/A' }}</td>
                            <td><span class="badge bg-primary">Class {{ $update->current_class }}</span></td>
                            <td>
                                <span class="badge {{ $update->status == 'Graduated' ? 'bg-success' : 'bg-info' }}">
                                    {{ $update->status }}
                                </span>
                            </td>
                            <td>{{ $update->admission_year }}</td>
                            <td>{{ $update->updated_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
