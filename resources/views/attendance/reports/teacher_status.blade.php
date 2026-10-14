@extends('layouts.app')

@section('title', 'Teacher Attendance Status')

@section('styles')
<style>
    .kpi-card {
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
    }
    .status-pill-marked {
        background-color: #d1e7dd;
        color: #0f5132;
        border: 1px solid #badbcc;
        font-weight: 600;
        padding: 0.35em 0.65em;
        border-radius: 50rem;
    }
    .status-pill-pending {
        background-color: #f8d7da;
        color: #842029;
        border: 1px solid #f5c2c7;
        font-weight: 600;
        padding: 0.35em 0.65em;
        border-radius: 50rem;
    }
    .col-teacher-name {
        white-space: nowrap !important;
    }
    @media (max-width: 768px) {
        .col-teacher-name {
            white-space: nowrap !important;
            font-size: 0.85rem !important;
        }
        .status-table th, .status-table td {
            padding: 0.45rem 0.4rem !important;
            font-size: 0.85rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Teacher Attendance Status
            </h1>
            <p class="text-muted small mb-0">Track which teachers have marked attendance for classes scheduled on <strong>{{ $dayOfWeek }}</strong> ({{ \Carbon\Carbon::parse($date)->format('d F Y') }}) <span class="badge bg-light text-primary border ms-1"><i class="fas fa-calendar-week me-1"></i>Timetable Filter Active</span></p>
        </div>
        <div class="mt-2 mt-sm-0 d-flex gap-2">
            <a href="{{ route('reports.teacher_workload') }}" class="btn btn-outline-info btn-sm text-dark">
                <i class="fas fa-briefcase me-1"></i> Teacher Workload Report
            </a>
            <a href="{{ route('attendance.reports.form') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Student Attendance Reports
            </a>
        </div>
    </div>

    {{-- Filter Form & Controls --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-light rounded-3 p-3">
            <form action="{{ route('reports.teacher_attendance_status') }}" method="GET" id="statusFilterForm" class="row g-3 align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label for="date" class="form-label fw-bold text-dark small"><i class="fas fa-calendar-alt text-primary me-1"></i>Select Date</label>
                    <input type="date" name="date" id="date" class="form-control form-control-sm" value="{{ $date }}" onchange="document.getElementById('statusFilterForm').submit();">
                </div>

                <div class="col-md-3 col-sm-6">
                    <label for="class_filter" class="form-label fw-bold text-dark small"><i class="fas fa-layer-group text-primary me-1"></i>Class Level</label>
                    <select name="class_filter" id="class_filter" class="form-select form-select-sm" onchange="document.getElementById('statusFilterForm').submit();">
                        <option value="" {{ $classFilter == '' ? 'selected' : '' }}>All Classes (11th & 12th)</option>
                        <option value="11" {{ $classFilter == '11' ? 'selected' : '' }}>11th Class</option>
                        <option value="12" {{ $classFilter == '12' ? 'selected' : '' }}>12th Class</option>
                    </select>
                </div>

                <div class="col-md-4 col-sm-8">
                    <label class="form-label fw-bold text-dark small"><i class="fas fa-filter text-primary me-1"></i>Status Filter</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="status_filter" id="status_all" value="all" {{ $statusFilter == 'all' ? 'checked' : '' }} onchange="document.getElementById('statusFilterForm').submit();">
                        <label class="btn btn-outline-primary btn-sm" for="status_all">All ({{ $totalClasses }})</label>

                        <input type="radio" class="btn-check" name="status_filter" id="status_marked" value="marked" {{ $statusFilter == 'marked' ? 'checked' : '' }} onchange="document.getElementById('statusFilterForm').submit();">
                        <label class="btn btn-outline-success btn-sm" for="status_marked">Marked ({{ $markedClasses }})</label>

                        <input type="radio" class="btn-check" name="status_filter" id="status_pending" value="pending" {{ $statusFilter == 'pending' ? 'checked' : '' }} onchange="document.getElementById('statusFilterForm').submit();">
                        <label class="btn btn-outline-danger btn-sm" for="status_pending">Pending ({{ $pendingClasses }})</label>
                    </div>
                </div>

                <div class="col-md-2 col-sm-4 text-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-sync me-1"></i>Refresh
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="card kpi-card shadow-sm border-0 border-start border-4 border-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted fw-bold small">Total Scheduled Classes</span>
                            <h2 class="mb-0 font-weight-bold text-dark mt-1">{{ $totalClasses }}</h2>
                        </div>
                        <div class="rounded-circle bg-primary-subtle p-3 text-primary">
                            <i class="fas fa-chalkboard-teacher fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="card kpi-card shadow-sm border-0 border-start border-4 border-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted fw-bold small">Attendance Marked</span>
                            <div class="d-flex align-items-baseline gap-2 mt-1">
                                <h2 class="mb-0 font-weight-bold text-success">{{ $markedClasses }}</h2>
                                <span class="badge bg-success-subtle text-success border border-success fw-bold">{{ $markedPercentage }}%</span>
                            </div>
                        </div>
                        <div class="rounded-circle bg-success-subtle p-3 text-success">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $markedPercentage }}%;" aria-valuenow="{{ $markedPercentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12">
            <div class="card kpi-card shadow-sm border-0 border-start border-4 border-danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted fw-bold small">Pending / Not Marked</span>
                            <h2 class="mb-0 font-weight-bold text-danger mt-1">{{ $pendingClasses }}</h2>
                        </div>
                        <div class="rounded-circle bg-danger-subtle p-3 text-danger">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        @if($pendingClasses > 0)
                            <span class="text-danger"><i class="fas fa-clock me-1"></i>{{ $pendingClasses }} class(es) still waiting for attendance marking.</span>
                        @else
                            <span class="text-success"><i class="fas fa-check me-1"></i>All scheduled classes marked for today!</span>
                        @endif
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Status Table --}}
    @if(!empty($isHolidayOrSunday))
        <div class="alert alert-warning border border-warning shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-calendar-times fa-2x text-warning me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1 fw-bold"><i class="fas fa-ban me-1"></i>Attendance Reminders Disabled for Selected Date</h5>
                    <p class="mb-0"><strong>{{ $holidayReason }}</strong> — No classes are scheduled and attendance marking is not required on Sundays or announced holidays. Sending reminders is disabled for this date.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Main Status Table --}}
    <form action="{{ route('attendance.reports.send_reminders') }}" method="POST" id="sendRemindersForm">
        @csrf
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="m-0 font-weight-bold text-dark d-inline-block me-3">
                        <i class="fas fa-list-alt text-primary me-2"></i>Class Attendance Marking List
                    </h6>
                    <span class="badge bg-secondary">Showing {{ $reportItems->count() }} Record(s)</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-warning btn-sm text-dark" id="btnSelectAllPending" {{ !empty($isHolidayOrSunday) ? 'disabled' : '' }}>
                        <i class="fas fa-check-square me-1"></i>Select All Pending
                    </button>
                    <button type="button" class="btn btn-warning btn-sm fw-bold shadow-sm" id="btnOpenReminderModal" data-bs-toggle="modal" data-bs-target="#sendReminderModal" disabled>
                        <i class="fas fa-paper-plane me-1"></i>Send Reminder (<span id="selectedReminderCount">0</span>)
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0 status-table">
                        <thead class="table-light">
                            <tr>
                                <th width="3%" class="text-center">
                                    <input type="checkbox" id="selectAllCheckbox" class="form-check-input" title="Select All Pending" {{ !empty($isHolidayOrSunday) ? 'disabled' : '' }}>
                                </th>
                                <th width="4%" class="text-center">#</th>
                                <th class="col-teacher-name">Teacher Name</th>
                                <th>Subject</th>
                                <th class="text-center">Class / Year</th>
                                <th class="text-center">Section</th>
                                <th class="text-center">Assigned Students</th>
                                <th class="text-center">Marking Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportItems as $index => $item)
                                <tr>
                                    <td class="text-center">
                                        @if(!$item->is_marked && empty($isHolidayOrSunday))
                                            <input type="checkbox" class="form-check-input reminder-checkbox" 
                                                   name="reminders[{{ $index }}][staff_id]" value="{{ $item->staff_id }}"
                                                   data-subject-id="{{ $item->subject_id }}"
                                                   data-class-level="{{ $item->class_level }}"
                                                   data-admission-year="{{ $item->admission_year }}"
                                                   data-section="{{ $item->section }}"
                                                   data-date="{{ $date }}">
                                        @else
                                            <i class="fas fa-check text-muted opacity-50"></i>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                                    <td class="col-teacher-name">
                                        <div class="fw-bold text-dark">{{ $item->teacher_name }}</div>
                                        @if(!empty($item->employee_id))
                                            <small class="text-muted">ID: {{ $item->employee_id }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-primary">{{ $item->subject_name }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-outline-secondary text-dark border">
                                            {{ $item->class_level }}th Class ({{ $item->admission_year }})
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if(!empty($item->section))
                                            <span class="badge bg-info text-dark">{{ $item->section }}</span>
                                        @else
                                            <span class="text-muted small">All Sections</span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold">
                                        {{ $item->assigned_students }}
                                    </td>
                                    <td class="text-center">
                                        @if($item->is_marked)
                                            <span class="status-pill-marked d-inline-flex align-items-center gap-1">
                                                <i class="fas fa-check-circle"></i> Marked ({{ $item->marked_students }} marked)
                                            </span>
                                            @if($item->last_marked_at)
                                                <div class="text-muted style-italic" style="font-size: 0.72rem;">
                                                    At {{ \Carbon\Carbon::parse($item->last_marked_at)->format('h:i A') }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="status-pill-pending d-inline-flex align-items-center gap-1">
                                                <i class="fas fa-times-circle"></i> Not Marked
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($item->is_marked)
                                            <a href="{{ route('attendance.reports.generate', ['admission_year' => $item->admission_year, 'current_class' => $item->class_level, 'subject_id' => $item->subject_id, 'start_date' => $date, 'end_date' => $date]) }}" class="btn btn-sm btn-outline-primary" title="View Detailed Attendance Report">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                        @else
                                            <a href="{{ route('attendance.create', ['subject_id' => $item->subject_id, 'class' => $item->class_level, 'year' => $item->admission_year, 'date' => $date, 'section' => $item->section]) }}" class="btn btn-sm btn-outline-success" title="Mark Attendance Now">
                                                <i class="fas fa-pen me-1"></i>Mark
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="fas fa-info-circle fa-2x mb-2 d-block text-secondary"></i>
                                        No teacher class assignments found for the selected criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Send Reminder Confirmation Modal --}}
        <div class="modal fade" id="sendReminderModal" tabindex="-1" aria-labelledby="sendReminderModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold" id="sendReminderModalLabel">
                            <i class="fas fa-paper-plane me-2"></i>Send Attendance Reminders
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info border-0 shadow-sm small mb-3">
                            <i class="fas fa-shield-alt text-primary me-2 fa-lg"></i>
                            <strong>One-Time Attendance Access Permission:</strong><br>
                            Sending a reminder will notify teacher(s) on login and grant them <strong>one-time permission</strong> to mark attendance for date <strong>{{ \Carbon\Carbon::parse($date)->format('d F Y') }}</strong> (bypassing the 11:00 PM cutoff). Once submitted, permission expires automatically.
                        </div>

                        <div class="mb-3">
                            <label for="custom_message" class="form-label fw-bold small text-dark">Note / Message from Principal (Optional)</label>
                            <textarea class="form-control form-control-sm" id="custom_message" name="custom_message" rows="3" placeholder="e.g. Please mark pending class attendance immediately for today's roster."></textarea>
                        </div>

                        <div id="hiddenInputsContainer"></div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning btn-sm fw-bold" id="btnSubmitReminders">
                            <i class="fas fa-paper-plane me-1"></i>Send <span id="modalReminderCount">0</span> Reminder(s)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const btnSelectAllPending = document.getElementById('btnSelectAllPending');
    const reminderCheckboxes = document.querySelectorAll('.reminder-checkbox');
    const btnOpenReminderModal = document.getElementById('btnOpenReminderModal');
    const selectedReminderCount = document.getElementById('selectedReminderCount');
    const modalReminderCount = document.getElementById('modalReminderCount');
    const hiddenInputsContainer = document.getElementById('hiddenInputsContainer');
    const btnSubmitReminders = document.getElementById('btnSubmitReminders');
    const sendRemindersForm = document.getElementById('sendRemindersForm');

    function updateSelectionState() {
        let count = 0;
        reminderCheckboxes.forEach(cb => {
            if (cb.checked) count++;
        });

        selectedReminderCount.textContent = count;
        modalReminderCount.textContent = count;
        btnOpenReminderModal.disabled = (count === 0);

        if (selectAllCheckbox) {
            selectAllCheckbox.checked = (reminderCheckboxes.length > 0 && count === reminderCheckboxes.length);
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            reminderCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateSelectionState();
        });
    }

    if (btnSelectAllPending) {
        btnSelectAllPending.addEventListener('click', function() {
            const allChecked = Array.from(reminderCheckboxes).every(cb => cb.checked);
            reminderCheckboxes.forEach(cb => {
                cb.checked = !allChecked;
            });
            updateSelectionState();
        });
    }

    reminderCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectionState);
    });

    btnSubmitReminders.addEventListener('click', function() {
        hiddenInputsContainer.innerHTML = '';
        let index = 0;

        reminderCheckboxes.forEach(cb => {
            if (cb.checked) {
                const staffId = cb.value;
                const subjectId = cb.getAttribute('data-subject-id');
                const classLevel = cb.getAttribute('data-class-level');
                const admissionYear = cb.getAttribute('data-admission-year');
                const section = cb.getAttribute('data-section');
                const attDate = cb.getAttribute('data-date');

                hiddenInputsContainer.innerHTML += `
                    <input type="hidden" name="reminders[${index}][staff_id]" value="${staffId}">
                    <input type="hidden" name="reminders[${index}][subject_id]" value="${subjectId}">
                    <input type="hidden" name="reminders[${index}][class_level]" value="${classLevel}">
                    <input type="hidden" name="reminders[${index}][admission_year]" value="${admissionYear}">
                    <input type="hidden" name="reminders[${index}][section]" value="${section}">
                    <input type="hidden" name="reminders[${index}][attendance_date]" value="${attDate}">
                `;
                index++;
            }
        });

        if (index > 0) {
            btnSubmitReminders.disabled = true;
            btnSubmitReminders.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...';
            sendRemindersForm.submit();
        }
    });
});
</script>
@endsection
