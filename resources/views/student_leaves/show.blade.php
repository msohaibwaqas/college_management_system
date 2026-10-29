@extends('layouts.app')

@section('title', 'Student Leave Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                <i class="fas fa-calendar-check text-warning me-2"></i>Student Leave Details
            </h1>
            <p class="text-muted small mb-0">
                Leave recorded for <strong>{{ $studentLeave->student?->full_name }}</strong> &mdash; {{ $studentLeave->formatted_date_range }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('student-leaves.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
            @if($studentLeave->status === 'approved')
                <a href="{{ route('student-leaves.edit', $studentLeave) }}" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Edit Leave
                </a>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="fas fa-ban me-1"></i> Cancel Leave
                </button>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <!-- Student Info Column -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-user-graduate text-primary me-2"></i>Student Information
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $student = $studentLeave->student;
                        $adm = $student?->admissions?->first();
                    @endphp
                    <div class="text-center mb-3">
                        <div class="avatar-circle mx-auto mb-2 bg-primary text-white d-flex align-items-center justify-content-center rounded-circle" style="width: 72px; height: 72px; font-size: 1.75rem;">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h5 class="fw-bold mb-0 text-dark">{{ $student?->full_name }}</h5>
                        <div class="text-muted small">Roll No: <strong>{{ $adm?->college_roll_no ?? 'N/A' }}</strong></div>
                    </div>

                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Current Class:</span>
                            <span class="fw-semibold">{{ $adm?->current_class ? 'Class ' . $adm->current_class . 'th' : 'N/A' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Admission Year:</span>
                            <span class="fw-semibold">{{ $adm?->admission_year ?? 'N/A' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Father Name:</span>
                            <span class="fw-semibold">{{ $student?->father?->name ?? 'N/A' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Father Contact:</span>
                            <span class="fw-semibold">{{ $student?->father?->contact ?? 'N/A' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Student Contact:</span>
                            <span class="fw-semibold">{{ $student?->contact?->student_contact ?? 'N/A' }}</span>
                        </li>
                    </ul>

                    @if($student)
                        <div class="mt-3 text-center">
                            <a href="{{ route('students.show', $student) }}" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-external-link-alt me-1"></i> View Full Student Profile
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Leave Audit Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-history text-secondary me-2"></i>Audit Trail
                    </h6>
                </div>
                <div class="card-body small">
                    <div class="mb-2">
                        <span class="text-muted">Approved By:</span>
                        <div class="fw-semibold text-dark">{{ $studentLeave->approved_by }}</div>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted">Recorded in System By:</span>
                        <div class="fw-semibold text-dark">{{ $studentLeave->markedBy?->name ?? 'Staff' }} ({{ $studentLeave->markedBy?->email ?? '' }})</div>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted">Recorded On:</span>
                        <div class="fw-semibold text-dark">{{ $studentLeave->created_at->format('d M Y, h:i A') }}</div>
                    </div>
                    @if($studentLeave->updated_at != $studentLeave->created_at)
                        <div>
                            <span class="text-muted">Last Modified:</span>
                            <div class="fw-semibold text-dark">{{ $studentLeave->updated_at->format('d M Y, h:i A') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Leave Details & Impact Column -->
        <div class="col-lg-8">
            <!-- Details Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-info-circle text-warning me-2"></i>Leave Record
                    </h5>
                    <div>
                        @if($studentLeave->status === 'approved')
                            <span class="badge bg-success px-3 py-2"><i class="fas fa-check-circle me-1"></i>Approved Leave</span>
                        @else
                            <span class="badge bg-danger px-3 py-2"><i class="fas fa-times-circle me-1"></i>Cancelled</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded text-center">
                                <div class="text-xs text-muted text-uppercase fw-bold mb-1">Start Date</div>
                                <div class="h5 mb-0 fw-bold text-dark">{{ $studentLeave->start_date->format('d M Y') }}</div>
                                <div class="small text-muted">{{ $studentLeave->start_date->format('l') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded text-center">
                                <div class="text-xs text-muted text-uppercase fw-bold mb-1">End Date</div>
                                <div class="h5 mb-0 fw-bold text-dark">{{ $studentLeave->end_date->format('d M Y') }}</div>
                                <div class="small text-muted">{{ $studentLeave->end_date->format('l') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-warning-subtle border border-warning rounded text-center">
                                <div class="text-xs text-warning-emphasis text-uppercase fw-bold mb-1">Total Duration</div>
                                <div class="h4 mb-0 fw-bold text-dark">{{ $studentLeave->days_count }} Days</div>
                                <div class="small text-muted">{{ $studentLeave->leave_type_label }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold text-muted small text-uppercase">Reason for Leave</label>
                        <div class="p-3 bg-light rounded text-dark fs-6">
                            {{ $studentLeave->reason }}
                        </div>
                    </div>

                    @if($studentLeave->remarks)
                        <div class="mb-3">
                            <label class="fw-bold text-muted small text-uppercase">Additional Remarks / Notes</label>
                            <div class="p-2 border rounded text-secondary small">
                                {{ $studentLeave->remarks }}
                            </div>
                        </div>
                    @endif

                    @if($studentLeave->document_path)
                        <div class="p-3 bg-light rounded border d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-alt fa-2x text-primary me-3"></i>
                                <div>
                                    <div class="fw-bold text-dark">Attached Leave Document</div>
                                    <div class="small text-muted">Original written application / proof</div>
                                </div>
                            </div>
                            <a href="{{ $studentLeave->document_url }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-download me-1"></i> View / Download Document
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Impacted Attendance Records -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-clipboard-check text-success me-2"></i>Synchronized Subject Attendances
                        <span class="badge bg-success ms-2">{{ $attendanceRecords->count() + $heAttendanceRecords->count() }} records</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="p-3 bg-light border-bottom small text-muted">
                        <i class="fas fa-check-circle text-success me-1"></i>
                        These attendance records have been automatically marked as <strong>Leave</strong> across the student's subjects for each day of the leave, ensuring complete protection from strike-offs.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Date</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendanceRecords as $att)
                                    <tr>
                                        <td class="ps-3 fw-semibold">
                                            {{ $att->attendance_date->format('d M Y') }}
                                            <span class="small text-muted d-block">{{ $att->attendance_date->format('l') }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $att->subject?->name ?? 'Unknown' }}</span>
                                            <span class="badge bg-light text-muted border">{{ $att->subject?->code }}</span>
                                        </td>
                                        <td>{{ $att->staff?->first_name }} {{ $att->staff?->last_name }}</td>
                                        <td>
                                            <span class="badge bg-warning text-dark px-2 py-1">
                                                <i class="fas fa-calendar-minus me-1"></i>Leave
                                            </span>
                                        </td>
                                        <td class="small text-muted">{{ $att->remarks }}</td>
                                    </tr>
                                @empty
                                    @if($heAttendanceRecords->isEmpty())
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                No daily attendance records have been processed yet.
                                            </td>
                                        </tr>
                                    @endif
                                @endforelse

                                @foreach($heAttendanceRecords as $heAtt)
                                    <tr>
                                        <td class="ps-3 fw-semibold">
                                            {{ $heAtt->attendance_date->format('d M Y') }}
                                            <span class="small text-muted d-block">{{ $heAtt->attendance_date->format('l') }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $heAtt->bsSubject?->name ?? 'BS Subject' }}</span>
                                            <span class="badge bg-light text-muted border">{{ $heAtt->bsSubject?->code }}</span>
                                        </td>
                                        <td>{{ $heAtt->staff?->first_name }} {{ $heAtt->staff?->last_name }}</td>
                                        <td>
                                            <span class="badge bg-warning text-dark px-2 py-1">
                                                <i class="fas fa-calendar-minus me-1"></i>Leave
                                            </span>
                                        </td>
                                        <td class="small text-muted">{{ $heAtt->remarks }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancellation Modal -->
    @if($studentLeave->status === 'approved')
        <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('student-leaves.destroy', $studentLeave) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Cancel Student Leave</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to cancel this approved leave for <strong>{{ $studentLeave->student?->full_name }}</strong>?</p>
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
    @endif
</div>
@endsection
