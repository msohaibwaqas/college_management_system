@extends('layouts.app')

@section('title', 'Mark Student Leave')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                <i class="fas fa-calendar-plus text-warning me-2"></i>Mark Student Leave
            </h1>
            <p class="text-muted small mb-0">
                Record an approved leave for a student. All subject teachers will see this leave during attendance marking.
            </p>
        </div>
        <a href="{{ route('student-leaves.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Leaves
        </a>
    </div>

    <!-- Informational Card -->
    <div class="alert alert-info border-start border-info border-4 mb-4 shadow-sm">
        <div class="d-flex align-items-center">
            <i class="fas fa-shield-alt fa-2x text-info me-3"></i>
            <div>
                <strong>Automatic Multi-Teacher Attendance Sync &amp; Strike-Off Protection</strong><br>
                <span class="small">
                    When you save this leave, the system immediately records <strong>Leave</strong> across all of the student's enrolled subjects for the specified date range. Teachers will see the approved leave on their attendance screen, and any existing absences on those days are automatically converted to Leave, preventing the student from being struck off.
                </span>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-file-signature text-warning me-2"></i>Leave Application Details
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('student-leaves.store') }}" method="POST" enctype="multipart/form-data" id="leaveForm">
                        @csrf

                        <!-- Student Selection Section -->
                        <div class="mb-4 pb-3 border-bottom">
                            <label class="form-label fw-bold">1. Select Student <span class="text-danger">*</span></label>
                            
                            @if(isset($selectedStudent) && $selectedStudent)
                                <input type="hidden" name="student_id" value="{{ $selectedStudent->id }}">
                                <input type="hidden" name="redirect_to_student" value="1">
                                
                                <div class="card bg-light border p-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-size: 1.25rem;">
                                                <i class="fas fa-user-graduate"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-bold text-dark">{{ $selectedStudent->full_name }}</h6>
                                                <div class="small text-muted">
                                                    Roll No: <strong>{{ $selectedStudent->admissions->first()?->college_roll_no ?? 'N/A' }}</strong> |
                                                    Class: <strong>{{ $selectedStudent->admissions->first()?->current_class ? 'Class ' . $selectedStudent->admissions->first()->current_class . 'th' : 'N/A' }}</strong> |
                                                    Father: <strong>{{ $selectedStudent->father?->name ?? 'N/A' }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ route('student-leaves.create') }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-exchange-alt me-1"></i> Change Student
                                        </a>
                                    </div>
                                </div>
                            @else
                                <select name="student_id" id="student_select" class="form-select @error('student_id') is-invalid @enderror" required style="width: 100%;">
                                    <option value="">Search by Roll No, Student Name, or Father Name...</option>
                                </select>
                                @error('student_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text small">Start typing student's Roll Number or Name to search active students.</div>
                            @endif
                        </div>

                        <!-- Date Range Section -->
                        <div class="mb-4 pb-3 border-bottom">
                            <label class="form-label fw-bold">2. Leave Period &amp; Duration <span class="text-danger">*</span></label>
                            
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold">Start Date (From) <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" id="start_date" class="form-control form-control-lg @error('start_date') is-invalid @enderror" value="{{ old('start_date', now()->toDateString()) }}" required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold">End Date (To) <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" id="end_date" class="form-control form-control-lg @error('end_date') is-invalid @enderror" value="{{ old('end_date', now()->addDays(4)->toDateString()) }}" required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-2 d-flex flex-column justify-content-end">
                                    <div class="card bg-warning-subtle border-warning p-2 text-center">
                                        <div class="small fw-semibold text-warning-emphasis">Total Days</div>
                                        <div class="h4 mb-0 fw-bold text-dark" id="days_count_badge">5</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Leave Type & Reason -->
                        <div class="mb-4 pb-3 border-bottom">
                            <label class="form-label fw-bold">3. Leave Categorization &amp; Reason <span class="text-danger">*</span></label>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Leave Category <span class="text-danger">*</span></label>
                                    <select name="leave_type" id="leave_type" class="form-select @error('leave_type') is-invalid @enderror" required>
                                        <option value="family_visit" {{ old('leave_type') == 'family_visit' ? 'selected' : '' }}>Family / Relative Visit (e.g. 5 days)</option>
                                        <option value="casual" {{ old('leave_type') == 'casual' ? 'selected' : '' }}>Casual Leave</option>
                                        <option value="medical" {{ old('leave_type') == 'medical' ? 'selected' : '' }}>Medical / Sick Leave</option>
                                        <option value="urgent_affair" {{ old('leave_type') == 'urgent_affair' ? 'selected' : '' }}>Urgent Personal Affair</option>
                                        <option value="official" {{ old('leave_type') == 'official' ? 'selected' : '' }}>Official / College Event</option>
                                        <option value="other" {{ old('leave_type') == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('leave_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Approved By <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-user-check"></i></span>
                                        <input type="text" name="approved_by" id="approved_by" class="form-control @error('approved_by') is-invalid @enderror" value="{{ old('approved_by', 'Principal Office') }}" placeholder="e.g. Principal Office, Vice Principal" required>
                                    </div>
                                    <div class="mt-1 d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 quick-approver" data-val="Principal Office">Principal Office</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 quick-approver" data-val="Principal">Principal</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 quick-approver" data-val="Vice Principal">Vice Principal</button>
                                    </div>
                                    @error('approved_by')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Reason / Description of Leave <span class="text-danger">*</span></label>
                                <textarea name="reason" id="reason" rows="3" class="form-control @error('reason') is-invalid @enderror" placeholder="Detailed reason for leave (e.g. Student submitted written application approved by Principal to visit relatives for 5 days)..." required>{{ old('reason') }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Attachments & Notes -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">4. Attachment &amp; Additional Remarks (Optional)</label>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Attach Approved Application / Document (Optional)</label>
                                    <input type="file" name="document" class="form-control @error('document') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
                                    <div class="form-text small">Scanned handwritten application, medical certificate, etc. (PDF, JPG, PNG - Max 5MB).</div>
                                    @error('document')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Internal Remarks / Reference No.</label>
                                    <input type="text" name="remarks" class="form-control" placeholder="e.g. Application diary #142 approved on Oct 28" value="{{ old('remarks') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('student-leaves.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-success btn-lg shadow-sm">
                                <i class="fas fa-check-circle me-1"></i> Mark Leave &amp; Synchronize Attendance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 1. Select2 for AJAX Student Search
    if ($('#student_select').length) {
        $('#student_select').select2({
            theme: 'bootstrap-5',
            ajax: {
                url: "{{ route('student-leaves.search-students') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            },
            placeholder: 'Search by Roll No, Student Name, or Father Name...',
            minimumInputLength: 1
        });
    }

    // 2. Live day count calculator
    function updateDaysCount() {
        var start = new Date($('#start_date').val());
        var end = new Date($('#end_date').val());

        if (!isNaN(start.getTime()) && !isNaN(end.getTime())) {
            var diffTime = end.getTime() - start.getTime();
            var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            if (diffDays < 1) {
                $('#days_count_badge').text('Invalid');
            } else {
                $('#days_count_badge').text(diffDays);
            }
        }
    }

    $('#start_date, #end_date').on('change', updateDaysCount);
    updateDaysCount();

    // 3. Quick Approver Pills
    $('.quick-approver').on('click', function() {
        $('#approved_by').val($(this).data('val'));
    });
});
</script>
@endsection
