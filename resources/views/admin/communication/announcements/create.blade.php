@extends('layouts.app')

@section('title', 'Compose Announcement')

@section('content')
<div class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-pen-nib me-2"></i>Compose Announcement</h1>
        <p class="page-subtitle">Send unified messages across Email, SMS, and the In-App portal</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>Please fix the following errors:
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('announcements.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Announcement Title / Subject <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required placeholder="e.g. Eid Holidays Notification">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Message Layout <span class="text-danger">*</span></label>
                                <textarea name="body" class="form-control" rows="6" required placeholder="Write the complete announcement text here...">{{ old('body') }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Recipient Groups <span class="text-danger">*</span></label>
                                <div class="bg-light p-3 rounded border">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="recipient_group[]" value="students" id="grpStudents" {{ in_array('students', old('recipient_group', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="grpStudents">All Active Students</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="recipient_group[]" value="teaching_staff" id="grpTeaching" {{ in_array('teaching_staff', old('recipient_group', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="grpTeaching">Teaching Staff (Faculty)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="recipient_group[]" value="non_teaching_staff" id="grpNonTeaching" {{ in_array('non_teaching_staff', old('recipient_group', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="grpNonTeaching">Non-Teaching Staff</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Delivery Channels <span class="text-danger">*</span></label>
                                <div class="bg-light p-3 rounded border">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="channels[]" value="in-app" id="chInApp" {{ in_array('in-app', old('channels', ['in-app'])) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold text-warning" for="chInApp"><i class="fas fa-bell me-1"></i> In-App Notification (Portal)</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="chEmail" {{ in_array('email', old('channels', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label text-primary" for="chEmail"><i class="fas fa-envelope me-1"></i> Email Gateway</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="channels[]" value="sms" id="chSms" {{ in_array('sms', old('channels', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label text-info" for="chSms"><i class="fas fa-sms me-1"></i> SMS Gateway (Costs Apply)</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 mt-3">
                                <div class="card border-warning border-opacity-50 bg-warning bg-opacity-10 mb-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="is_holiday" value="1" id="isHolidaySwitch" {{ old('is_holiday') ? 'checked' : '' }} style="transform: scale(1.25);" onchange="toggleHolidayBox(this.checked)">
                                            <label class="form-check-label fw-bold text-dark fs-6" for="isHolidaySwitch">
                                                <i class="fas fa-umbrella-beach text-warning me-1"></i> Mark as Holiday / Vacation Period Announcement
                                            </label>
                                            <div class="form-text small text-muted ms-4">Announcing a holiday or multi-day vacation locks attendance marking across all modules (Student Intermediate, BS/ADP, Staff Daily, and Gate attendance) for the entire duration.</div>
                                        </div>

                                        <div id="holidayDateBox" style="display: {{ old('is_holiday') ? 'block' : 'none' }};">
                                            <!-- Quick Preset Templates -->
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-magic me-1"></i>Quick Vacation Presets</label>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyPreset('winter')">
                                                        <i class="fas fa-snowflake me-1"></i> Winter Break (23 Dec 2026 - 31 Dec 2026)
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-warning text-dark" onclick="applyPreset('summer')">
                                                        <i class="fas fa-sun me-1"></i> Summer Break (01 Jun 2027 - 14 Aug 2027)
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyPreset('singleday')">
                                                        <i class="fas fa-calendar-day me-1"></i> Single Day Off
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold text-dark"><i class="fas fa-calendar-day me-1 text-primary"></i>Vacation / Holiday Start Date <span class="text-danger">*</span></label>
                                                    <input type="date" name="holiday_date" id="holiday_date" class="form-control border-warning bg-white" value="{{ old('holiday_date', date('Y-m-d')) }}" onchange="updateDurationCalculation()">
                                                    <div class="form-text small">First day of the vacation/holiday closure.</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold text-dark"><i class="fas fa-calendar-check me-1 text-success"></i>Vacation End Date <span class="text-muted small fw-normal">(Optional for single day)</span></label>
                                                    <input type="date" name="holiday_end_date" id="holiday_end_date" class="form-control border-warning bg-white" value="{{ old('holiday_end_date') }}" onchange="updateDurationCalculation()">
                                                    <div class="form-text small">Leave empty if classes/offices are closed for only 1 day.</div>
                                                </div>
                                            </div>

                                            <!-- Duration & Attendance Restriction Callout -->
                                            <div class="mt-3 p-3 bg-white rounded border border-warning shadow-sm" id="durationPreviewBox">
                                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                    <div>
                                                        <span class="badge bg-warning text-dark fs-6" id="durationBadge"><i class="fas fa-clock me-1"></i> 1 Day Off</span>
                                                        <span class="ms-2 fw-semibold text-secondary" id="durationDateRangeText">Single Day Holiday</span>
                                                    </div>
                                                    <div class="text-danger small fw-bold">
                                                        <i class="fas fa-shield-alt me-1"></i> Attendance Strictly Blocked
                                                    </div>
                                                </div>
                                                <div class="small text-muted mt-2">
                                                    <i class="fas fa-info-circle me-1 text-primary"></i> During this period, all staff and faculty attendance marking will be locked. Teachers, clerks, and administrators will not be able to mark attendance on these dates to prevent erroneous data insertion and calculation mismatches.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 mt-3">
                                <label class="form-label fw-bold">Scheduled Delivery <span class="text-muted small italic">(Optional)</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                    <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}" min="{{ date('Y-m-d\TH:i') }}">
                                </div>
                                <div class="form-text small">Leave blank to dispatch immediately. Future scheduling requires the system task scheduler to be active.</div>
                            </div>

                        </div>

                        <div class="mt-4 text-end border-top pt-3">
                            <a href="{{ route('announcements.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to broadcast this announcement? This action cannot be undone.')">
                                <i class="fas fa-paper-plane me-2"></i>Dispatch Announcement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h5 class="text-primary mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Caution</h5>
                    <p class="small text-muted mb-2">
                        <strong>Bulk Sending:</strong> Broadcasts are added to the background queue and processed progressively to avoid server timeout.
                    </p>
                    <p class="small text-muted mb-2">
                        <strong>SMS Costs:</strong> Selecting all students via SMS may dispatch thousands of messages simultaneously. Please ensure sufficient gateway credit before selecting the SMS channel.
                    </p>
                    <p class="small text-muted mb-0">
                        <strong>Vacation Lock:</strong> Once an announcement with holiday/vacation dates is dispatched, all attendance operations (Student HSSC, Higher Education BS, Daily Staff, Gate scanning) are locked for those calendar days.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleHolidayBox(checked) {
    const box = document.getElementById('holidayDateBox');
    if (box) {
        box.style.display = checked ? 'block' : 'none';
        if (checked) {
            updateDurationCalculation();
        }
    }
}

function updateDurationCalculation() {
    const startInput = document.getElementById('holiday_date');
    const endInput = document.getElementById('holiday_end_date');
    const badge = document.getElementById('durationBadge');
    const rangeText = document.getElementById('durationDateRangeText');

    if (!startInput || !badge || !rangeText) return;

    const startDateVal = startInput.value;
    const endDateVal = endInput ? endInput.value : '';

    if (!startDateVal) {
        badge.innerHTML = '<i class="fas fa-calendar me-1"></i> Select Date';
        rangeText.innerText = 'Please select a start date';
        return;
    }

    const start = new Date(startDateVal + 'T00:00:00');
    let days = 1;

    if (endDateVal && endDateVal >= startDateVal) {
        const end = new Date(endDateVal + 'T00:00:00');
        const diffTime = Math.abs(end - start);
        days = Math.round(diffTime / (1000 * 60 * 60 * 24)) + 1;
        badge.className = 'badge bg-primary text-white fs-6';
        badge.innerHTML = `<i class="fas fa-umbrella-beach me-1"></i> ${days} Days Vacation`;
        rangeText.innerText = `Vacation Period: ${formatDate(start)} to ${formatDate(end)}`;
    } else {
        badge.className = 'badge bg-warning text-dark fs-6';
        badge.innerHTML = '<i class="fas fa-calendar-day me-1"></i> 1 Day Holiday';
        rangeText.innerText = `Single Day Off: ${formatDate(start)}`;
    }
}

function formatDate(dateObj) {
    const options = { day: '2-digit', month: 'short', year: 'numeric' };
    return dateObj.toLocaleDateString('en-GB', options);
}

function applyPreset(type) {
    const switchEl = document.getElementById('isHolidaySwitch');
    if (switchEl && !switchEl.checked) {
        switchEl.checked = true;
        toggleHolidayBox(true);
    }

    const titleInput = document.querySelector('input[name="title"]');
    const bodyInput = document.querySelector('textarea[name="body"]');
    const startInput = document.getElementById('holiday_date');
    const endInput = document.getElementById('holiday_end_date');

    if (type === 'winter') {
        if (startInput) startInput.value = '2026-12-23';
        if (endInput) endInput.value = '2026-12-31';
        if (titleInput && (!titleInput.value || titleInput.value.includes('Holiday') || titleInput.value.includes('Break') || titleInput.value.includes('Vacation'))) {
            titleInput.value = 'Winter Vacation Notification (23 Dec 2026 - 31 Dec 2026)';
        }
        if (bodyInput && (!bodyInput.value || bodyInput.value.includes('classes') || bodyInput.value.includes('closed') || bodyInput.value.includes('Winter'))) {
            bodyInput.value = 'This is to notify all faculty, administrative staff, and students that the college will observe Winter Break from 23-12-2026 to 31-12-2026. All academic and administrative operations will remain suspended. Attendance marking is disabled during this period. The college will reopen on 01-01-2027.';
        }
    } else if (type === 'summer') {
        if (startInput) startInput.value = '2027-06-01';
        if (endInput) endInput.value = '2027-08-14';
        if (titleInput && (!titleInput.value || titleInput.value.includes('Holiday') || titleInput.value.includes('Break') || titleInput.value.includes('Vacation'))) {
            titleInput.value = 'Summer Vacation Notification (01 Jun 2027 - 14 Aug 2027)';
        }
        if (bodyInput && (!bodyInput.value || bodyInput.value.includes('classes') || bodyInput.value.includes('closed') || bodyInput.value.includes('Summer'))) {
            bodyInput.value = 'All faculty members, staff, and students are hereby informed that Summer Vacation will commence from 01-06-2027 and conclude on 14-08-2027. Attendance marking across all faculties is locked for this vacation period. Normal academic activities will resume on 15-08-2027.';
        }
    } else if (type === 'singleday') {
        const today = new Date().toISOString().split('T')[0];
        if (startInput) startInput.value = today;
        if (endInput) endInput.value = '';
        if (titleInput && (!titleInput.value || titleInput.value.includes('Vacation'))) {
            titleInput.value = 'Public Holiday Notification';
        }
    }

    updateDurationCalculation();
}

document.addEventListener('DOMContentLoaded', function() {
    updateDurationCalculation();
});
</script>
@endsection

