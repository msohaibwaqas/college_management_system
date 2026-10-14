@extends('layouts.app')

@section('title', 'Teacher Workload Batch Builder')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-layer-group text-primary me-2"></i>Teacher Workload Batch Builder</h1>
            <p class="text-muted small mb-0">Assign a teacher's full daily/weekly lecture timetable (multiple lectures & sections) in 1 single submission!</p>
        </div>
        <a href="{{ route('admin.timetables.index', ['admission_year' => $filterYear]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Master Timetable Matrix
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Step 1: Select Teacher & Subject Header -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-light d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-tag me-2"></i>Step 1: Select Teacher, Subject & Session</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.timetables.teacher_batch') }}" id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="fw-semibold">Admission Year / Session</label>
                        <select name="admission_year" id="admission_year" class="form-select select2" required>
                            @foreach($allYears as $year)
                                <option value="{{ $year }}" {{ $filterYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">Subject</label>
                        <select name="subject_id" id="subject_id" class="form-select select2" required>
                            <option value="">-- Select Subject --</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ $filterSubjectId == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }} ({{ $subject->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Teacher</label>
                        <select name="staff_id" id="staff_id" class="form-select select2" required>
                            <option value="">-- Select Teacher --</option>
                            @foreach($staffMembers as $staff)
                                <option value="{{ $staff->id }}" {{ $filterStaffId == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->full_name }} ({{ $staff->designation }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Load Schedule</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($filterStaffId && $filterSubjectId && $filterYear)
        @php
            $selectedTeacher = $staffMembers->firstWhere('id', $filterStaffId);
            $selectedSubject = $subjects->firstWhere('id', $filterSubjectId);
        @endphp

        <!-- Step 2: Multi-Lecture Batch Assignment Form -->
        <form action="{{ route('admin.timetables.store_teacher_batch') }}" method="POST">
            @csrf
            <input type="hidden" name="admission_year" value="{{ $filterYear }}">
            <input type="hidden" name="subject_id" value="{{ $filterSubjectId }}">
            <input type="hidden" name="staff_id" value="{{ $filterStaffId }}">

            <div class="card shadow mb-4 border-start border-4 border-primary">
                <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-calendar-check me-2"></i>Step 2: Assign Lectures for {{ $selectedTeacher->full_name ?? 'Teacher' }} ({{ $selectedSubject->name ?? 'Subject' }})
                        </h6>
                        <small class="text-muted">Check the lectures this teacher teaches, pick the sections (e.g. <code>M1, M2, E1</code>) and room for each slot, then save in 1 click!</small>
                    </div>
                    <button type="submit" class="btn btn-success font-weight-bold px-4 shadow-sm">
                        <i class="fas fa-bolt me-1"></i> Save All Assignments in 1 Go
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center" style="width: 70px;">Enable</th>
                                    <th style="width: 200px;">Lecture Period</th>
                                    <th>Assigned Section(s) / Combined Classes</th>
                                    <th style="width: 220px;">Room Assignment</th>
                                    <th style="width: 180px;">Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($timeSlots as $slot)
                                    @php
                                        $existing = $existingAssignments[$slot->id] ?? null;
                                        $isCheck = $existing ? true : false;
                                        $currentSections = $existing ? ($existing['sections'] ?? []) : [];
                                        $currentRoomId = $existing ? ($existing['room_id'] ?? '') : '';
                                        $currentDay = $existing ? ($existing['day_of_week'] ?? 'All Week') : 'All Week';
                                    @endphp
                                    <tr class="slot-row {{ $isCheck ? 'table-primary-subtle' : '' }}" id="row_slot_{{ $slot->id }}">
                                        <!-- Checkbox -->
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input slot-toggle-check" 
                                                       type="checkbox" 
                                                       name="assignments[{{ $slot->id }}][enabled]" 
                                                       value="1" 
                                                       id="check_slot_{{ $slot->id }}"
                                                       {{ $isCheck ? 'checked' : '' }}
                                                       data-slot-id="{{ $slot->id }}">
                                            </div>
                                        </td>
                                        <!-- Lecture Info -->
                                        <td>
                                            <label for="check_slot_{{ $slot->id }}" class="mb-0 cursor-pointer">
                                                <strong class="text-dark d-block">Lecture {{ $slot->slot_number }}</strong>
                                                <small class="text-muted">
                                                    <i class="far fa-clock me-1"></i>{{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('g:i A') }}
                                                </small>
                                            </label>
                                        </td>
                                        <!-- Sections Multi-select / Checkboxes -->
                                        <td>
                                            <div class="slot-field-container" id="field_container_{{ $slot->id }}">
                                                <div class="row g-2">
                                                    @foreach($availableSections as $sec)
                                                        @php
                                                            $isCheckedSec = in_array($sec, $currentSections);
                                                        @endphp
                                                        <div class="col-md-2 col-sm-4 col-6">
                                                            <div class="form-check form-check-inline border rounded px-2 py-1 bg-white w-100">
                                                                <input class="form-check-input section-checkbox" 
                                                                       type="checkbox" 
                                                                       name="assignments[{{ $slot->id }}][sections][]" 
                                                                       value="{{ $sec }}" 
                                                                       id="sec_{{ $slot->id }}_{{ $loop->index }}"
                                                                       {{ $isCheckedSec ? 'checked' : '' }}>
                                                                <label class="form-check-label small text-dark fw-semibold" for="sec_{{ $slot->id }}_{{ $loop->index }}">
                                                                    {{ $sec }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Room Selector -->
                                        <td>
                                            <select name="assignments[{{ $slot->id }}][room_id]" class="form-select form-select-sm">
                                                <option value="">-- No Room / N/A --</option>
                                                @foreach($rooms as $room)
                                                    <option value="{{ $room->id }}" {{ $currentRoomId == $room->id ? 'selected' : '' }}>
                                                        {{ $room->name }} (Cap: {{ $room->class_capacity ?? $room->capacity }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <!-- Day Selector -->
                                        <td>
                                            <select name="assignments[{{ $slot->id }}][day_of_week]" class="form-select form-select-sm">
                                                <option value="All Week" {{ $currentDay == 'All Week' ? 'selected' : '' }}>📅 All Week (Mon-Sat)</option>
                                                @foreach($daysOfWeek as $day)
                                                    <option value="{{ $day }}" {{ $currentDay == $day ? 'selected' : '' }}>{{ $day }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light text-end py-3">
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-bolt me-1"></i> Save All Assignments in 1 Go
                    </button>
                </div>
            </div>
        </form>
    @else
        <div class="alert alert-info shadow-sm text-center py-5">
            <i class="fas fa-info-circle fa-3x text-primary mb-3 d-block"></i>
            <h5>Please select Session, Subject, and Teacher above</h5>
            <p class="text-muted mb-0">Once selected, you can assign all their lecture periods (1st, 2nd, 3rd, 5th lecture etc.) across multiple sections at once!</p>
        </div>
    @endif
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
        }

        // Highlight row on check
        $('.slot-toggle-check').on('change', function() {
            let row = $(this).closest('tr');
            if ($(this).is(':checked')) {
                row.addClass('table-primary-subtle');
            } else {
                row.removeClass('table-primary-subtle');
            }
        });

        // Auto-check slot row if any section checkbox inside it is checked
        $('.section-checkbox').on('change', function() {
            let row = $(this).closest('tr');
            let toggleCheck = row.find('.slot-toggle-check');
            let anySecChecked = row.find('.section-checkbox:checked').length > 0;
            
            if (anySecChecked) {
                toggleCheck.prop('checked', true);
                row.addClass('table-primary-subtle');
            }
        });
    });
</script>
@endsection
