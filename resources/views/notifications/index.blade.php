@extends('layouts.app')

@section('title', 'Notification Center')

@section('content')
<div class="container-fluid py-4">
    {{-- Header Section --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-gray-800 mb-1 d-flex align-items-center gap-2">
                <i class="fas fa-bell text-primary"></i>
                Notification Center
            </h1>
            <p class="text-muted small mb-0">Manage attendance marking reminders, system notices, and student updates.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @if($unreadNotificationsCount > 0)
                <form action="{{ route('notifications.markAllAsRead') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm shadow-sm fw-semibold">
                        <i class="fas fa-check-double me-1"></i> Mark All Read
                    </button>
                </form>
            @endif
            @if($notifications->isNotEmpty())
                <form action="{{ route('notifications.destroyAll') }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete all system notifications?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm shadow-sm fw-semibold">
                        <i class="fas fa-trash-alt me-1"></i> Clear Notifications
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Filter Navigation Pills --}}
    <div class="card border-0 shadow-sm mb-4 bg-light">
        <div class="card-body p-2 d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('notifications.index', ['filter' => 'all']) }}" 
               class="btn btn-sm rounded-pill {{ $filter === 'all' ? 'btn-primary' : 'btn-white text-dark border' }} fw-semibold px-3">
                <i class="fas fa-layer-group me-1"></i> All
            </a>
            @if($pendingReminders->isNotEmpty() || $usedReminders->isNotEmpty())
                <a href="#attendanceRemindersCard" 
                   class="btn btn-sm rounded-pill btn-warning text-dark fw-bold px-3 shadow-sm">
                    <i class="fas fa-calendar-check me-1"></i> Attendance Reminders
                    @if($pendingReminders->isNotEmpty())
                        <span class="badge bg-danger rounded-pill ms-1">{{ $pendingReminders->count() }}</span>
                    @endif
                </a>
            @endif
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" 
               class="btn btn-sm rounded-pill {{ $filter === 'unread' ? 'btn-primary' : 'btn-white text-dark border' }} fw-semibold px-3">
                <i class="fas fa-envelope me-1"></i> Unread Only
                @if($unreadNotificationsCount > 0)
                    <span class="badge bg-danger rounded-pill ms-1">{{ $unreadNotificationsCount }}</span>
                @endif
            </a>
        </div>
    </div>

    {{-- Section 1: Attendance Reminders for Teachers (Dedicated Card) --}}
    @if(Auth::user()->staff && ($pendingReminders->isNotEmpty() || $usedReminders->isNotEmpty()))
        <div class="card border-0 shadow-sm mb-4" id="attendanceRemindersCard">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <span class="p-2 rounded-circle bg-warning bg-opacity-10 text-warning d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fas fa-clipboard-user fs-6"></i>
                    </span>
                    Principal Attendance Marking Permissions & Reminders
                </h5>
                @if($pendingReminders->isNotEmpty())
                    <span class="badge bg-danger rounded-pill px-3 py-2">
                        <i class="fas fa-exclamation-triangle me-1"></i> {{ $pendingReminders->count() }} Action Required
                    </span>
                @else
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-semibold">
                        <i class="fas fa-check-circle me-1"></i> All Marked
                    </span>
                @endif
            </div>
            <div class="card-body p-3">
                {{-- Pending / Unused Reminders --}}
                @if($pendingReminders->isNotEmpty())
                    <h6 class="text-uppercase text-muted fw-bold small mb-3">
                        <i class="fas fa-hourglass-half text-warning me-1"></i> Active Reminders (One-Time Past Date Permission Active)
                    </h6>
                    <div class="row g-3 mb-4">
                        @foreach($pendingReminders as $reminder)
                            <div class="col-12">
                                <div class="p-3 border rounded-3 bg-warning bg-opacity-10 border-warning shadow-sm">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="badge bg-warning text-dark fw-bold">
                                                    <i class="fas fa-calendar-day me-1"></i> Date: {{ \Carbon\Carbon::parse($reminder->attendance_date)->format('d F Y (l)') }}
                                                </span>
                                                <span class="badge bg-dark">
                                                    {{ $reminder->class_level }}th Class ({{ $reminder->admission_year }})
                                                </span>
                                                @if($reminder->section)
                                                    <span class="badge bg-info text-dark">Section: {{ $reminder->section }}</span>
                                                @endif
                                            </div>
                                            <h6 class="fw-bold mb-1 text-dark">
                                                {{ $reminder->subject->name ?? 'Subject' }}
                                            </h6>
                                            <p class="small text-muted mb-1">
                                                Permission granted by <strong>{{ $reminder->creator->full_name ?? $reminder->creator->username ?? 'Principal' }}</strong>.
                                                <span class="text-danger fw-semibold">Bypasses past-date lockout for this class. Permission automatically expires once submitted.</span>
                                            </p>
                                            @if(!empty($reminder->message))
                                                <div class="alert alert-light border py-1 px-2 mb-0 small text-dark fst-italic mt-1">
                                                    <i class="fas fa-quote-left text-muted me-1"></i>"{{ $reminder->message }}"
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <a href="{{ route('attendance.create', [
                                                'subject_id' => $reminder->subject_id,
                                                'class' => $reminder->class_level,
                                                'year' => $reminder->admission_year,
                                                'date' => \Carbon\Carbon::parse($reminder->attendance_date)->toDateString(),
                                                'section' => $reminder->section,
                                                'reminder_id' => $reminder->id
                                            ]) }}" class="btn btn-warning fw-bold px-4 py-2 shadow-sm text-dark">
                                                <i class="fas fa-pen-fancy me-1"></i> Mark Attendance Now
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Used / Past Reminders --}}
                @if($usedReminders->isNotEmpty())
                    <div class="mt-2">
                        <button class="btn btn-sm btn-link text-muted p-0 text-decoration-none fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#pastRemindersCollapse">
                            <i class="fas fa-history me-1"></i> View Previously Completed Reminders ({{ $usedReminders->count() }}) <i class="fas fa-chevron-down ms-1 small"></i>
                        </button>
                        <div class="collapse mt-2" id="pastRemindersCollapse">
                            <div class="list-group list-group-flush border rounded-3">
                                @foreach($usedReminders as $past)
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 bg-light">
                                        <div class="small">
                                            <span class="badge bg-success bg-opacity-25 text-success me-2"><i class="fas fa-check me-1"></i> Marked</span>
                                            <strong>{{ $past->subject->name ?? 'Subject' }}</strong> - {{ $past->class_level }}th Class ({{ $past->admission_year }})
                                            for date <strong>{{ \Carbon\Carbon::parse($past->attendance_date)->format('d M Y') }}</strong>
                                        </div>
                                        <div class="small text-muted">
                                            Completed: {{ $past->used_at ? $past->used_at->diffForHumans() : 'Consumed' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Section 2: General & System Notifications --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="fas fa-bell text-secondary"></i>
                System & Academic Notifications
            </h5>
            <span class="badge bg-light text-muted border px-2 py-1">
                Showing {{ $notifications->count() }} of {{ $notifications->total() }}
            </span>
        </div>
        <div class="card-body p-0">
            @if($notifications->isEmpty())
                <div class="text-center py-5">
                    <div class="mb-3">
                        <span class="p-3 bg-light rounded-circle text-muted d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                            <i class="fas fa-bell-slash fs-3 text-secondary"></i>
                        </span>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">No notifications here</h6>
                    <p class="text-muted small mb-0">You're all caught up with announcements and system alerts.</p>
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach($notifications as $notification)
                        @php
                            $data = $notification->data;
                            $isUnread = $notification->unread();
                            $type = $data['type'] ?? '';
                        @endphp
                        <div class="list-group-item list-group-item-action p-3 {{ $isUnread ? 'bg-primary bg-opacity-10 border-start border-4 border-primary' : '' }}">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div class="d-flex align-items-start gap-3">
                                    {{-- Notification Icon --}}
                                    <div class="mt-1">
                                        @if($type === 'mark_change')
                                            <span class="p-2 rounded-circle bg-warning bg-opacity-25 text-warning-emphasis d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                <i class="fas fa-pen-to-square"></i>
                                            </span>
                                        @elseif(!empty($data['action']) && in_array($data['action'], ['Assigned', 'Unassigned']))
                                            <span class="p-2 rounded-circle {{ $data['action'] === 'Assigned' ? 'bg-success bg-opacity-25 text-success' : 'bg-danger bg-opacity-25 text-danger' }} d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                <i class="fas fa-user-tag"></i>
                                            </span>
                                        @else
                                            <span class="p-2 rounded-circle bg-info bg-opacity-25 text-info-emphasis d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                <i class="fas fa-bullhorn"></i>
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Notification Content --}}
                                    <div>
                                        @if($type === 'mark_change')
                                            <div class="fw-bold text-dark mb-1">
                                                <span class="badge bg-warning text-dark me-1">Marks Changed</span>
                                                {{ $data['student_name'] ?? 'Student' }} 
                                                @if(!empty($data['student_roll_no']))
                                                    <span class="text-muted fw-normal">(Roll: {{ $data['student_roll_no'] }})</span>
                                                @endif
                                                <span class="text-muted fw-normal">—</span> {{ $data['subject'] ?? 'Subject' }}
                                            </div>
                                            <div class="small text-secondary mb-1">
                                                <strong>Theory:</strong> {{ $data['prev_theory'] ?? 'N/A' }} → <span class="fw-bold text-dark">{{ $data['new_theory'] ?? 'N/A' }}</span> |
                                                <strong>Practical:</strong> {{ $data['prev_practical'] ?? 'N/A' }} → <span class="fw-bold text-dark">{{ $data['new_practical'] ?? 'N/A' }}</span> |
                                                <strong>Changed By:</strong> {{ $data['user_name'] ?? 'System' }}
                                            </div>
                                        @elseif(!empty($data['action']) && in_array($data['action'], ['Assigned', 'Unassigned']))
                                            <div class="fw-bold text-dark mb-1">
                                                <span class="badge {{ $data['action'] === 'Assigned' ? 'bg-success' : 'bg-danger' }} me-1">
                                                    {{ $data['action'] }}
                                                </span>
                                                {{ $data['student_name'] ?? 'Student' }}
                                                <span class="text-muted fw-normal">to</span>
                                                {{ $data['teacher_name'] ?? 'Teacher' }}
                                            </div>
                                            <div class="small text-secondary mb-1">
                                                <strong>Roll No:</strong> {{ $data['student_roll_no'] ?? 'N/A' }} |
                                                <strong>Adm Year:</strong> {{ $data['admission_year'] ?? 'N/A' }} |
                                                <strong>Subject:</strong> {{ $data['subject_name'] ?? 'N/A' }} |
                                                <strong>By:</strong> {{ $data['performed_by'] ?? 'System' }}
                                            </div>
                                        @else
                                            <div class="fw-bold text-dark mb-1">
                                                {{ $data['subject'] ?? $data['title'] ?? 'Notification' }}
                                            </div>
                                            <div class="small text-secondary mb-1">
                                                {{ $data['body'] ?? $data['message'] ?? '' }}
                                            </div>
                                        @endif
                                        <div class="small text-muted">
                                            <i class="far fa-clock me-1"></i>{{ $notification->created_at->diffForHumans() }} ({{ $notification->created_at->format('d M Y, h:i A') }})
                                        </div>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="d-flex align-items-center gap-1">
                                    @if($isUnread)
                                        <form action="{{ route('notifications.markAsRead', $notification->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-light border text-primary" title="Mark as Read">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" onsubmit="return confirm('Delete this notification?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger" title="Delete">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        @if($notifications->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
