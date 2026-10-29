@extends('layouts.app')

@section('title', 'Edit Student Leave')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                <i class="fas fa-edit text-warning me-2"></i>Edit Student Leave
            </h1>
            <p class="text-muted small mb-0">
                Update approved leave dates or reason for <strong>{{ $studentLeave->student?->full_name }}</strong>
            </p>
        </div>
        <a href="{{ route('student-leaves.show', $studentLeave) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Leave Details
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-calendar-check text-warning me-2"></i>Edit Leave Form
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('student-leaves.update', $studentLeave) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Student Readonly Overview -->
                        <div class="mb-4 pb-3 border-bottom">
                            <label class="form-label fw-bold">Student Information</label>
                            @php
                                $student = $studentLeave->student;
                                $adm = $student?->admissions?->first();
                            @endphp
                            <div class="card bg-light border p-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-size: 1.25rem;">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-bold text-dark">{{ $student?->full_name }}</h6>
                                        <div class="small text-muted">
                                            Roll No: <strong>{{ $adm?->college_roll_no ?? 'N/A' }}</strong> |
                                            Class: <strong>{{ $adm?->current_class ? 'Class ' . $adm->current_class . 'th' : 'N/A' }}</strong> |
                                            Father: <strong>{{ $student?->father?->name ?? 'N/A' }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date Range Section -->
                        <div class="mb-4 pb-3 border-bottom">
                            <label class="form-label fw-bold">Leave Period &amp; Duration <span class="text-danger">*</span></label>
                            
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold">Start Date (From) <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" id="start_date" class="form-control form-control-lg @error('start_date') is-invalid @enderror" value="{{ old('start_date', $studentLeave->start_date->toDateString()) }}" required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold">End Date (To) <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" id="end_date" class="form-control form-control-lg @error('end_date') is-invalid @enderror" value="{{ old('end_date', $studentLeave->end_date->toDateString()) }}" required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-2 d-flex flex-column justify-content-end">
                                    <div class="card bg-warning-subtle border-warning p-2 text-center">
                                        <div class="small fw-semibold text-warning-emphasis">Total Days</div>
                                        <div class="h4 mb-0 fw-bold text-dark" id="days_count_badge">{{ $studentLeave->days_count }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Leave Type & Reason -->
                        <div class="mb-4 pb-3 border-bottom">
                            <label class="form-label fw-bold">Leave Categorization &amp; Reason <span class="text-danger">*</span></label>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Leave Category <span class="text-danger">*</span></label>
                                    <select name="leave_type" id="leave_type" class="form-select @error('leave_type') is-invalid @enderror" required>
                                        <option value="family_visit" {{ old('leave_type', $studentLeave->leave_type) == 'family_visit' ? 'selected' : '' }}>Family / Relative Visit</option>
                                        <option value="casual" {{ old('leave_type', $studentLeave->leave_type) == 'casual' ? 'selected' : '' }}>Casual Leave</option>
                                        <option value="medical" {{ old('leave_type', $studentLeave->leave_type) == 'medical' ? 'selected' : '' }}>Medical / Sick Leave</option>
                                        <option value="urgent_affair" {{ old('leave_type', $studentLeave->leave_type) == 'urgent_affair' ? 'selected' : '' }}>Urgent Personal Affair</option>
                                        <option value="official" {{ old('leave_type', $studentLeave->leave_type) == 'official' ? 'selected' : '' }}>Official / College Event</option>
                                        <option value="other" {{ old('leave_type', $studentLeave->leave_type) == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('leave_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Approved By <span class="text-danger">*</span></label>
                                    <input type="text" name="approved_by" id="approved_by" class="form-control @error('approved_by') is-invalid @enderror" value="{{ old('approved_by', $studentLeave->approved_by) }}" required>
                                    @error('approved_by')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Reason / Description of Leave <span class="text-danger">*</span></label>
                                <textarea name="reason" id="reason" rows="3" class="form-control @error('reason') is-invalid @enderror" required>{{ old('reason', $studentLeave->reason) }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Attachments & Notes -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Attachment &amp; Additional Remarks (Optional)</label>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Replace Attached Document (Optional)</label>
                                    <input type="file" name="document" class="form-control @error('document') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
                                    @if($studentLeave->document_path)
                                        <div class="form-text small mt-1">
                                            Current document: <a href="{{ $studentLeave->document_url }}" target="_blank">View attached document</a>
                                        </div>
                                    @endif
                                    @error('document')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Internal Remarks / Reference No.</label>
                                    <input type="text" name="remarks" class="form-control" value="{{ old('remarks', $studentLeave->remarks) }}">
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('student-leaves.show', $studentLeave) }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                <i class="fas fa-save me-1"></i> Update Leave &amp; Re-sync Attendance
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
});
</script>
@endsection
