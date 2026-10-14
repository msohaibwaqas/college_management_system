@extends('layouts.app')

@section('title', 'Admin Timetable Management')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Master Timetable Builder</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <!-- Add Block Form -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Schedule New Block</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.timetables.store') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label>Admission Year / Session</label>
                            <select name="admission_year" id="admission_year" class="form-select select2" required>
                                <option value="">-- Select Year --</option>
                                @foreach($allYears as $year)
                                    <option value="{{ $year }}" {{ $filterYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Target batch for this timetable.</small>
                        </div>
                        <div class="form-group mb-3">
                            <label>Day of Week</label>
                            <select name="day_of_week" class="form-select" required>
                                <option value="All Week" selected>📅 All Week (Mon-Sat)</option>
                                @foreach($daysOfWeek as $day)
                                    <option value="{{ $day }}">{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Time Slot</label>
                            <select name="time_slot_id" class="form-select" required>
                                @foreach($timeSlots as $slot)
                                    <option value="{{ $slot->id }}">Lecture {{ $slot->slot_number }} ({{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('g:i A') }})</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Subject</label>
                            <select name="subject_id" id="subject_id" class="form-select select2" required>
                                <option value="">-- Select Subject --</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Teacher</label>
                            <select name="staff_id" id="staff_id" class="form-select select2" required>
                                <option value="">-- Select Teacher --</option>
                                @foreach($staffMembers as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->full_name }} ({{ $staff->designation }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Room (Optional)</label>
                            <select name="room_id" class="form-select select2">
                                <option value="">-- Select Room --</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->name }} (Class Cap: {{ $room->class_capacity ?? $room->capacity }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Class capacity might differ from exam seating.</small>
                        </div>
                        <div class="form-group mb-4">
                            <label class="fw-semibold">Section / Batch Name</label>
                            <select name="section" id="section_select" class="form-select" required>
                                <option value="">-- Select Year &amp; Subject First --</option>
                            </select>
                            <small class="text-muted">
                                Loaded from <a href="{{ route('student-affairs.sections.index') }}" target="_blank">Student Affairs Sections</a>. Type to enter custom name.
                            </small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Create Block</button>
                    </form>
                </div>
            </div>
            <!-- Manage Years Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Manage Session ({{ $filterYear }})</h6>
                </div>
                <div class="card-body">
                    @php
                        $isActive = \App\Models\TimetableEntry::where('admission_year', $filterYear)->where('is_active', true)->exists();
                        $hasEntries = \App\Models\TimetableEntry::where('admission_year', $filterYear)->exists();
                    @endphp
                    
                    @if($hasEntries)
                        <p class="small text-muted">You can archive the current year to stop it from causing clashes with other sessions, or copy it to a new year.</p>
                        <form action="{{ route('admin.timetables.toggle_status') }}" method="POST" class="d-grid mb-2">
                            @csrf
                            <input type="hidden" name="admission_year" value="{{ $filterYear }}">
                            <input type="hidden" name="is_active" value="{{ $isActive ? 0 : 1 }}">
                            <button type="submit" class="btn {{ $isActive ? 'btn-outline-danger' : 'btn-success' }} btn-sm">
                                <i class="fas {{ $isActive ? 'fa-archive' : 'fa-check' }}"></i>
                                {{ $isActive ? 'Archive Session '.$filterYear : 'Activate Session '.$filterYear }}
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#cloneModal">
                            <i class="fas fa-copy"></i> Clone to New Year
                        </button>
                    @else
                        <p class="small text-muted">No entries found for {{ $filterYear }}. You can clone from another year to get started quickly.</p>
                        <button type="button" class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#cloneModal">
                            <i class="fas fa-copy"></i> Clone from Another Year
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Master Weekly Timetable Matrix View -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <div>
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-th me-2"></i>Master Weekly Schedule Matrix (Year: {{ $filterYear ?? 'All' }})</h6>
                        @if(isset($isActive) && !$isActive && $hasEntries)
                            <span class="badge bg-secondary">Archived (No Clash Checks)</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <!-- Subject Department Builder Button -->
                        <a href="{{ route('admin.timetables.subject_batch', ['admission_year' => $filterYear]) }}" class="btn btn-sm text-white font-weight-bold" style="background-color: #6f42c1;">
                            <i class="fas fa-book-reader me-1"></i> Subject Dept Builder (1 Go)
                        </a>
                        <!-- Teacher Workload Batch Builder Button -->
                        <a href="{{ route('admin.timetables.teacher_batch', ['admission_year' => $filterYear]) }}" class="btn btn-sm btn-warning font-weight-bold">
                            <i class="fas fa-bolt me-1"></i> Teacher Batch Builder (1 Go)
                        </a>
                        <!-- Combined Builder Button -->
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#combinedModal">
                            <i class="fas fa-layer-group me-1"></i> Combined Section Builder
                        </button>
                        <!-- Show Summary Button -->
                        <a href="{{ route('admin.timetables.summary', ['admission_year' => $filterYear]) }}" class="btn btn-sm btn-success" target="_blank">
                            <i class="fas fa-file-alt"></i> Summary
                        </a>
                        <!-- Year Filter -->
                        <div class="dropdown no-arrow">
                            <button class="btn btn-sm btn-info dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Year: {{ $filterYear ?? 'Select' }} <i class="fas fa-calendar-alt"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                                @foreach($allYears as $year)
                                    <a class="dropdown-item" href="{{ route('admin.timetables.index', ['day' => $filterDay, 'admission_year' => $year]) }}">{{ $year }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 110px;">Lecture</th>
                                    @foreach($daysOfWeek as $day)
                                        <th>{{ $day }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($timeSlots as $slot)
                                    <tr>
                                        <td class="table-light">
                                            <strong>Lec {{ $slot->slot_number }}</strong><br>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }}</small>
                                        </td>
                                        @foreach($daysOfWeek as $day)
                                            @php 
                                                $cellEntries = $matrixGrid[$slot->id][$day] ?? []; 
                                            @endphp
                                            <td class="p-2 align-top text-start style-matrix-cell" data-slot="{{ $slot->id }}" data-day="{{ $day }}">
                                                @if(count($cellEntries) > 0)
                                                    @foreach($cellEntries as $entry)
                                                        <div class="card border border-info mb-1 shadow-sm p-1 bg-light">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="badge bg-primary text-white" style="font-size: 0.75rem;">{{ $entry->section }}</span>
                                                                <button type="button" class="btn btn-xs btn-link text-info p-0 edit-block-btn"
                                                                        data-id="{{ $entry->id }}"
                                                                        data-year="{{ $entry->admission_year }}"
                                                                        data-day="{{ $entry->day_of_week }}"
                                                                        data-slot="{{ $entry->time_slot_id }}"
                                                                        data-subject="{{ $entry->subject_id }}"
                                                                        data-staff="{{ $entry->staff_id }}"
                                                                        data-room="{{ $entry->room_id }}"
                                                                        data-section="{{ $entry->section }}">
                                                                    <i class="fas fa-pencil-alt"></i>
                                                                </button>
                                                            </div>
                                                            <strong class="text-dark d-block text-truncate" style="font-size: 0.82rem;" title="{{ $entry->subject->name ?? '' }}">{{ $entry->subject->name ?? 'N/A' }}</strong>
                                                            <small class="text-muted d-block text-truncate" style="font-size: 0.75rem;">{{ $entry->staff->full_name ?? 'N/A' }}</small>
                                                            @if($entry->room)
                                                                <span class="badge bg-light text-dark border mt-1" style="font-size: 0.7rem;"><i class="fas fa-door-open text-primary me-1"></i>{{ $entry->room->name }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                    <div class="text-center mt-1">
                                                        <button type="button" class="btn btn-xs btn-outline-primary quick-add-btn p-1 w-100" data-slot="{{ $slot->id }}" data-day="{{ $day }}" style="font-size: 0.72rem; border-style: dashed;">
                                                            <i class="fas fa-plus me-1"></i>+ Add Block
                                                        </button>
                                                    </div>
                                                @else
                                                    <div class="text-center py-2">
                                                        <button type="button" class="btn btn-sm btn-outline-dashed text-muted quick-add-btn p-1 w-100" data-slot="{{ $slot->id }}" data-day="{{ $day }}" style="border: 1px dashed #ccc; font-size: 0.75rem;">
                                                            <i class="fas fa-plus-circle me-1"></i>Assign
                                                        </button>
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Single Day Detail View Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-secondary">Detailed List for {{ $filterDay }} (Year: {{ $filterYear ?? 'All' }})</h6>
                    <div class="dropdown no-arrow">
                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Day: {{ $filterDay }} <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                            @foreach($daysOfWeek as $day)
                                <a class="dropdown-item" href="{{ route('admin.timetables.index', ['day' => $day, 'admission_year' => $filterYear]) }}">{{ $day }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Lecture</th>
                                    <th>Teacher</th>
                                    <th>Subject</th>
                                    <th>Section</th>
                                    <th>Room</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entries->sortBy(function($e) { return $e->timeSlot->slot_number; }) as $entry)
                                    <tr class="{{ !$entry->is_active ? 'opacity-50' : '' }}">
                                        <td><strong>Lecture {{ $entry->timeSlot->slot_number }}</strong><br><small class="text-muted">{{ \Carbon\Carbon::parse($entry->timeSlot->start_time)->format('g:i A') }}</small></td>
                                        <td>{{ $entry->staff->full_name ?? 'Unknown' }}</td>
                                        <td>{{ $entry->subject->name ?? 'Unknown' }}</td>
                                        <td><span class="badge bg-info text-dark">{{ $entry->section }}</span></td>
                                        <td>
                                            @if($entry->room)
                                                <span class="text-primary"><i class="fas fa-door-open"></i> {{ $entry->room->name }}</span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info edit-block-btn me-1" 
                                                        data-id="{{ $entry->id }}"
                                                        data-year="{{ $entry->admission_year }}"
                                                        data-day="{{ $entry->day_of_week }}"
                                                        data-slot="{{ $entry->time_slot_id }}"
                                                        data-subject="{{ $entry->subject_id }}"
                                                        data-staff="{{ $entry->staff_id }}"
                                                        data-room="{{ $entry->room_id }}"
                                                        data-section="{{ $entry->section }}"
                                                        title="Edit Block">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('admin.timetables.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this block?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Only Today"><i class="fas fa-trash"></i></button>
                                                </form>
                                                <form action="{{ route('admin.timetables.bulk_destroy', $entry->id) }}" method="POST" onsubmit="return confirm('WARNING: This will delete this lecture for EVERY day of the week. Continue?');" class="ms-1">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-warning" title="Delete For Whole Week"><i class="fas fa-dumpster"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No timetable blocks scheduled for {{ $filterDay }}.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Block Modal -->
<div class="modal fade" id="editBlockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit text-primary"></i> Edit Timetable Block</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editBlockForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="fw-semibold">Admission Year / Session</label>
                        <select name="admission_year" id="edit_admission_year" class="form-select" required>
                            @foreach($allYears as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="fw-semibold">Day of Week</label>
                        <select name="day_of_week" id="edit_day_of_week" class="form-select" required>
                            <option value="All Week">📅 All Week (Mon-Sat)</option>
                            @foreach($daysOfWeek as $day)
                                <option value="{{ $day }}">{{ $day }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="fw-semibold">Time Slot</label>
                        <select name="time_slot_id" id="edit_time_slot_id" class="form-select" required>
                            @foreach($timeSlots as $slot)
                                <option value="{{ $slot->id }}">Lecture {{ $slot->slot_number }} ({{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('g:i A') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="fw-semibold">Subject</label>
                        <select name="subject_id" id="edit_subject_id" class="form-select" required>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="fw-semibold">Teacher</label>
                        <select name="staff_id" id="edit_staff_id" class="form-select" required>
                            <option value="">-- Select Subject First --</option>
                            @foreach($staffMembers as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->full_name }} ({{ $staff->designation }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="fw-semibold">Room (Optional)</label>
                        <select name="room_id" id="edit_room_id" class="form-select">
                            <option value="">-- No Room / N/A --</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="fw-semibold">Section / Batch Name</label>
                        <input type="text" name="section" id="edit_section" class="form-control" required placeholder="e.g. FA-IT or ICS-III">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Clone Modal -->
<div class="modal fade" id="cloneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clone Timetable</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.timetables.clone') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info small">
                        This will copy ALL timetable entries from the Source Year to the Target Year. 
                        Existing entries in the Target Year will NOT be overwritten.
                    </div>
                    <div class="form-group mb-3">
                        <label>Source Admission Year (Copy From)</label>
                        <select name="source_year" class="form-select" required>
                            @foreach($allYears as $year)
                                <option value="{{ $year }}" {{ $filterYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label>Target Admission Year (Copy To)</label>
                        <select name="target_year" class="form-select" required>
                            @foreach($allYears as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                            <option value="{{ date('Y') + 1 }}">{{ date('Y') + 1 }} (Upcoming)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Start Cloning</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Combined Section Builder Modal -->
<div class="modal fade" id="combinedModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-layer-group me-2"></i> Combined Section Timetable Builder</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.timetables.store_combined') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <i class="fas fa-info-circle me-1"></i> Schedule combined section lectures (e.g. <strong>FA-IT</strong> + <strong>ICS-III</strong> + <strong>ICS-IV</strong> for Computer Science under teacher <em>M. Sohaib Waqas</em>). Entries for all selected sections will be created atomically with 3-tier clash checks.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="fw-semibold">Admission Year / Session</label>
                            <select name="admission_year" class="form-select" required>
                                @foreach($allYears as $year)
                                    <option value="{{ $year }}" {{ $filterYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Day of Week</label>
                            <select name="day_of_week" id="combined_day_of_week" class="form-select" required>
                                <option value="All Week" selected>📅 All Week (Mon-Sat)</option>
                                @foreach($daysOfWeek as $day)
                                    <option value="{{ $day }}">{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Time Slot</label>
                            <select name="time_slot_id" id="combined_time_slot_id" class="form-select" required>
                                @foreach($timeSlots as $slot)
                                    <option value="{{ $slot->id }}">Lecture {{ $slot->slot_number }} ({{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('g:i A') }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Subject</label>
                            <select name="subject_id" id="combined_subject_id" class="form-select" required>
                                <option value="">-- Select Subject --</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Teacher (Active Specialists)</label>
                            <select name="staff_id" id="combined_staff_id" class="form-select" required>
                                <option value="">-- Select Subject First --</option>
                                @foreach($staffMembers as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->full_name }} ({{ $staff->designation }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Room (Shared Lecture Room / Lawn)</label>
                            <select name="room_id" class="form-select">
                                <option value="">-- Select Room --</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->name }} (Cap: {{ $room->class_capacity ?? $room->capacity }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="fw-semibold">Select Combined Student Sections</label>
                            <div class="border rounded p-3 bg-light" style="max-height: 160px; overflow-y: auto;">
                                <div class="row g-2">
                                    @foreach($availableSections as $sec)
                                        <div class="col-md-3 col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="section[]" value="{{ $sec }}" id="sec_check_{{ $loop->index }}">
                                                <label class="form-check-label text-dark" for="sec_check_{{ $loop->index }}">
                                                    {{ $sec }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <small class="text-muted">Check all sections to combine into this single lecture period.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-layer-group me-1"></i> Create Combined Block</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        if($.fn.select2) {
            $('.select2').select2({ 
                theme: 'bootstrap-5',
                width: '100%'
            });

            $('#section_select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                tags: true,
                placeholder: 'Select from Student Affairs or type section name...'
            });
        }

        function loadStudentAffairsSections() {
            let year = $('#admission_year').val();
            let subjectId = $('#subject_id').val();
            let sectionSelect = $('#section_select');

            if (!year || !subjectId) {
                sectionSelect.empty().append('<option value="">-- Select Year & Subject First --</option>');
                if ($.fn.select2) sectionSelect.trigger('change');
                return;
            }

            sectionSelect.empty().append('<option value="">-- Loading Sections... --</option>');
            if ($.fn.select2) sectionSelect.trigger('change');

            $.ajax({
                url: "{{ route('staff.get_sections_by_year') }}",
                type: 'GET',
                data: { admission_year: year, subject_id: subjectId },
                success: function(response) {
                    sectionSelect.empty().append('<option value="">-- Select Section --</option>');
                    if (response && response.length > 0) {
                        response.forEach(function(sec) {
                            let label = sec.section + ' (' + sec.student_count + ' student' + (sec.student_count > 1 ? 's' : '') + ')';
                            sectionSelect.append(new Option(label, sec.section, false, false));
                        });
                    } else {
                        sectionSelect.append('<option value="">No sections in Student Affairs yet (type to create)</option>');
                    }
                    if ($.fn.select2) sectionSelect.trigger('change');
                },
                error: function() {
                    sectionSelect.empty().append('<option value="">Error loading sections</option>');
                    if ($.fn.select2) sectionSelect.trigger('change');
                }
            });
        }

        $('#admission_year, #subject_id').on('change', function() {
            loadStudentAffairsSections();
        });

        $('#subject_id').on('change', function() {
            let subjectId = $(this).val();
            let teacherSelect = $('#staff_id');
            
            // Clear current options
            teacherSelect.empty().append('<option value="">-- Loading Teachers... --</option>');
            if($.fn.select2) teacherSelect.trigger('change');

            if (!subjectId) {
                teacherSelect.empty().append('<option value="">-- Select Teacher --</option>');
                if($.fn.select2) teacherSelect.trigger('change');
                return;
            }

            $.ajax({
                url: "{{ route('staff.get_teachers_by_subject') }}",
                type: 'GET',
                data: { subject_id: subjectId },
                success: function(response) {
                    teacherSelect.empty().append('<option value="">-- Select Teacher --</option>');
                    
                    if (response.length === 0) {
                        teacherSelect.append('<option value="">No specialists found</option>');
                    } else {
                        response.forEach(function(teacher) {
                            let fullName = (teacher.first_name + ' ' + (teacher.last_name || '')).trim();
                            teacherSelect.append(`<option value="${teacher.id}">${fullName}</option>`);
                        });
                    }
                    
                    if($.fn.select2) teacherSelect.trigger('change');
                },
                error: function() {
                    teacherSelect.empty().append('<option value="">Error loading teachers</option>');
                    if($.fn.select2) teacherSelect.trigger('change');
                }
            });
        });

        function loadEditTeachers(subjectId, selectedStaffId) {
            let teacherSelect = $('#edit_staff_id');
            teacherSelect.empty().append('<option value="">-- Loading Teachers... --</option>');
            
            if (!subjectId) {
                teacherSelect.empty().append('<option value="">-- Select Subject First --</option>');
                return;
            }

            $.ajax({
                url: "{{ route('staff.get_teachers_by_subject') }}",
                type: 'GET',
                data: { subject_id: subjectId },
                success: function(response) {
                    teacherSelect.empty().append('<option value="">-- Select Teacher --</option>');
                    
                    if (response.length === 0) {
                        teacherSelect.append('<option value="">No active specialists found for this subject</option>');
                    } else {
                        response.forEach(function(teacher) {
                            let fullName = (teacher.first_name + ' ' + (teacher.last_name || '')).trim();
                            let isSelected = (selectedStaffId && teacher.id == selectedStaffId) ? 'selected' : '';
                            teacherSelect.append(`<option value="${teacher.id}" ${isSelected}>${fullName}</option>`);
                        });
                    }
                    if (selectedStaffId) {
                        teacherSelect.val(selectedStaffId);
                    }
                },
                error: function() {
                    teacherSelect.empty().append('<option value="">Error loading teachers</option>');
                }
            });
        }

        $(document).on('click', '.edit-block-btn', function() {
            let btn = $(this);
            let id = btn.data('id');
            let form = $('#editBlockForm');
            
            form.attr('action', "{{ url('admin/timetables') }}/" + id);
            
            let subjectId = btn.data('subject');
            let staffId = btn.data('staff');

            $('#edit_admission_year').val(btn.data('year'));
            $('#edit_day_of_week').val(btn.data('day'));
            $('#edit_time_slot_id').val(btn.data('slot'));
            $('#edit_subject_id').val(subjectId);
            $('#edit_room_id').val(btn.data('room') || '');
            $('#edit_section').val(btn.data('section'));

            // Load active teachers specializing in this subject
            loadEditTeachers(subjectId, staffId);

            let modal = new bootstrap.Modal(document.getElementById('editBlockModal'));
            modal.show();
        });

        $('#edit_subject_id').on('change', function() {
            let subjectId = $(this).val();
            loadEditTeachers(subjectId, null);
        });

        $(document).on('click', '.quick-add-btn', function() {
            let slotId = $(this).data('slot');
            let day = $(this).data('day');

            $('select[name="day_of_week"]').val(day);
            $('select[name="time_slot_id"]').val(slotId);
            $('#combined_day_of_week').val(day);
            $('#combined_time_slot_id').val(slotId);

            let formCard = $('#admission_year').closest('.card');
            formCard.addClass('border-primary shadow-lg');
            setTimeout(function() {
                formCard.removeClass('border-primary shadow-lg');
            }, 1800);

            $('html, body').animate({
                scrollTop: $('#admission_year').offset().top - 100
            }, 300);
        });

        $('#combined_subject_id').on('change', function() {
            let subjectId = $(this).val();
            let teacherSelect = $('#combined_staff_id');
            teacherSelect.empty().append('<option value="">-- Loading Teachers... --</option>');

            if (!subjectId) {
                teacherSelect.empty().append('<option value="">-- Select Subject First --</option>');
                return;
            }

            $.ajax({
                url: "{{ route('staff.get_teachers_by_subject') }}",
                type: 'GET',
                data: { subject_id: subjectId },
                success: function(response) {
                    teacherSelect.empty().append('<option value="">-- Select Teacher --</option>');
                    if (response.length === 0) {
                        teacherSelect.append('<option value="">No active specialists found</option>');
                    } else {
                        response.forEach(function(teacher) {
                            let fullName = (teacher.first_name + ' ' + (teacher.last_name || '')).trim();
                            teacherSelect.append(`<option value="${teacher.id}">${fullName}</option>`);
                        });
                    }
                },
                error: function() {
                    teacherSelect.empty().append('<option value="">Error loading teachers</option>');
                }
            });
        });
    });
</script>
@endsection
