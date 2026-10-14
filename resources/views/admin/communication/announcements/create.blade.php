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
                                <div class="form-check form-switch bg-warning bg-opacity-10 p-3 border border-warning rounded">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="is_holiday" value="1" id="isHolidaySwitch" {{ old('is_holiday') ? 'checked' : '' }} style="transform: scale(1.2);" onchange="document.getElementById('holidayDateBox').style.display = this.checked ? 'block' : 'none';">
                                    <label class="form-check-label fw-bold text-dark" for="isHolidaySwitch">
                                        <i class="fas fa-umbrella-beach text-warning me-1"></i> Mark as Holiday / Off Day Announcement
                                    </label>
                                    <div class="form-text small text-muted ms-4">Announcing a holiday automatically disables attendance reminder dispatches for that date.</div>
                                </div>
                            </div>

                            <div class="col-md-12" id="holidayDateBox" style="display: {{ old('is_holiday') ? 'block' : 'none' }};">
                                <label class="form-label fw-bold text-warning"><i class="fas fa-calendar-day me-1"></i>Holiday Date <span class="text-danger">*</span></label>
                                <input type="date" name="holiday_date" class="form-control border-warning" value="{{ old('holiday_date', date('Y-m-d')) }}">
                                <div class="form-text">Specify the exact date on which classes will be suspended.</div>
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
