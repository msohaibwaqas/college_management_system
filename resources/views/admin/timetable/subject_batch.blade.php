@extends('layouts.app')

@section('title', 'Subject Department Master Schedule Builder')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-book-reader text-primary me-2"></i>Subject Department Schedule Builder</h1>
            <p class="text-muted small mb-0">Manage all parallel teachers, sections, and room assignments for a subject department across all 8 lectures in 1 go!</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.timetables.teacher_batch', ['admission_year' => $filterYear]) }}" class="btn btn-outline-warning btn-sm fw-bold">
                <i class="fas fa-user-tie me-1"></i> Teacher Batch Builder
            </a>
            <a href="{{ route('admin.timetables.index', ['admission_year' => $filterYear]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Master Timetable
            </a>
        </div>
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

    <!-- Step 1: Select Subject & Session Header -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-light">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter me-2"></i>Step 1: Select Subject Department & Session</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.timetables.subject_batch') }}" id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="fw-semibold">Admission Year / Session</label>
                        <select name="admission_year" id="admission_year" class="form-select select2" required>
                            @foreach($allYears as $year)
                                <option value="{{ $year }}" {{ $filterYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="fw-semibold">Subject Department</label>
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
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Load Department Schedule</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($filterSubjectId && $filterYear)
        @php
            $selectedSubject = $subjects->firstWhere('id', $filterSubjectId);
        @endphp

        <!-- Step 2: Department Master Schedule Form -->
        <form action="{{ route('admin.timetables.store_subject_batch') }}" method="POST" id="subjectBatchForm">
            @csrf
            <input type="hidden" name="admission_year" value="{{ $filterYear }}">
            <input type="hidden" name="subject_id" value="{{ $filterSubjectId }}">

            <div class="card shadow mb-4 border-start border-4 border-primary">
                <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-layer-group me-2"></i>Department Master Schedule for {{ $selectedSubject->name ?? 'Subject' }} (Session: {{ $filterYear }})
                        </h6>
                        <small class="text-muted">Add multiple parallel teachers and sections for any lecture slot (e.g. <em>Lecture 3: Mudassar (M1, E1, Room 20) + Abdullah (A1, A2, Room 7)</em>), then save all in 1 click!</small>
                    </div>
                    <button type="submit" class="btn btn-success font-weight-bold px-4 shadow-sm">
                        <i class="fas fa-save me-1"></i> Save Entire Subject Department Schedule in 1 Go
                    </button>
                </div>
                <div class="card-body bg-light">
                    @foreach($timeSlots as $slot)
                        <div class="card mb-3 border shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-dark me-2 fs-6">Lecture {{ $slot->slot_number }}</span>
                                    <small class="text-muted fw-semibold me-3">
                                        <i class="far fa-clock me-1"></i>{{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('g:i A') }}
                                    </small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary add-row-btn" data-slot-id="{{ $slot->id }}">
                                    <i class="fas fa-plus-circle me-1"></i> Add Parallel Class to Lecture {{ $slot->slot_number }}
                                </button>
                            </div>
                            <div class="card-body p-3">
                                <div class="parallel-rows-container" id="rows_container_{{ $slot->id }}">
                                    <!-- Dynamic rows will be rendered here via JS -->
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="card-footer bg-white text-end py-3">
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save me-1"></i> Save Entire Subject Department Schedule in 1 Go
                    </button>
                </div>
            </div>
        </form>
    @else
        <div class="alert alert-info shadow-sm text-center py-5">
            <i class="fas fa-book-open fa-3x text-primary mb-3 d-block"></i>
            <h5>Please select Admission Year and Subject above</h5>
            <p class="text-muted mb-0">Once selected, you can set up all parallel teachers, sections, and rooms for that subject across all lecture periods in one screen!</p>
        </div>
    @endif
</div>

<!-- Template data for JavaScript initialization -->
<script>
    const availableSectionsList = @json($availableSections);
    const roomsList = @json($rooms);
    const teachersList = @json($subjectTeachers->values());
    const existingAllocationsData = @json($existingSubjectAllocations);
    const teacherSectionsMap = @json($teacherSectionsMap);
    let rowCounter = {};

    function filterRowSections(rowElement) {
        let teacherId = rowElement.find('.teacher-select').val();
        let allowedSecs = (teacherId && teacherSectionsMap[teacherId]) ? teacherSectionsMap[teacherId] : null;
        
        let sectionCols = rowElement.find('.section-item-col');
        let hintBadge = rowElement.find('.teacher-sections-badge');

        if (allowedSecs && allowedSecs.length > 0) {
            hintBadge.html(`<i class="fas fa-filter me-1"></i>Showing ${allowedSecs.length} section(s) assigned to teacher`).removeClass('bg-secondary').addClass('bg-info text-dark');
            sectionCols.each(function() {
                let sec = $(this).data('section');
                if (allowedSecs.includes(sec)) {
                    $(this).show();
                } else {
                    $(this).hide();
                    $(this).find('input[type="checkbox"]').prop('checked', false);
                }
            });
        } else {
            hintBadge.html('<i class="fas fa-list me-1"></i>Showing all subject sections').removeClass('bg-info text-dark').addClass('bg-secondary text-white');
            sectionCols.show();
        }
    }

    function renderParallelRow(slotId, data = null) {
        if (!rowCounter[slotId]) rowCounter[slotId] = 0;
        let index = rowCounter[slotId]++;
        
        let container = $('#rows_container_' + slotId);
        
        let selectedStaffId = data ? data.staff_id : '';
        let selectedRoomId = data ? data.room_id : '';
        let selectedDay = data ? (data.day_of_week || 'All Week') : 'All Week';
        let currentSections = data ? (data.sections || []) : [];

        let teacherOptionsHTML = '<option value="">-- Select Teacher --</option>';
        teachersList.forEach(function(t) {
            let selected = (selectedStaffId == t.id) ? 'selected' : '';
            teacherOptionsHTML += `<option value="${t.id}" ${selected}>${t.first_name} ${t.last_name || ''} (${t.designation})</option>`;
        });

        let roomOptionsHTML = '<option value="">-- Select Room --</option>';
        roomsList.forEach(function(r) {
            let selected = (selectedRoomId == r.id) ? 'selected' : '';
            let cap = r.class_capacity || r.capacity;
            roomOptionsHTML += `<option value="${r.id}" ${selected}>${r.name} (Cap: ${cap})</option>`;
        });

        let sectionsCheckboxesHTML = '';
        availableSectionsList.forEach(function(sec, sIdx) {
            let isChecked = currentSections.includes(sec) ? 'checked' : '';
            let checkId = `sec_${slotId}_${index}_${sIdx}`;
            sectionsCheckboxesHTML += `
                <div class="col-md-2 col-sm-4 col-6 section-item-col" data-section="${sec}">
                    <div class="form-check form-check-inline border rounded px-2 py-1 bg-white w-100 shadow-sm">
                        <input class="form-check-input" type="checkbox" name="assignments[${slotId}][${index}][sections][]" value="${sec}" id="${checkId}" ${isChecked}>
                        <label class="form-check-label small text-dark fw-semibold" for="${checkId}">${sec}</label>
                    </div>
                </div>
            `;
        });

        let rowHTML = `
            <div class="parallel-class-card border rounded p-3 mb-2 bg-white shadow-sm" id="row_${slotId}_${index}">
                <div class="row g-2 align-items-center mb-2">
                    <div class="col-md-4">
                        <label class="small text-muted fw-bold d-block">Teacher</label>
                        <select name="assignments[${slotId}][${index}][staff_id]" class="form-select form-select-sm teacher-select" required>
                            ${teacherOptionsHTML}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted fw-bold d-block">Room</label>
                        <select name="assignments[${slotId}][${index}][room_id]" class="form-select form-select-sm">
                            ${roomOptionsHTML}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted fw-bold d-block">Days</label>
                        <select name="assignments[${slotId}][${index}][day_of_week]" class="form-select form-select-sm">
                            <option value="All Week" ${selectedDay === 'All Week' ? 'selected' : ''}>📅 All Week (Mon-Sat)</option>
                            <option value="Monday" ${selectedDay === 'Monday' ? 'selected' : ''}>Monday</option>
                            <option value="Tuesday" ${selectedDay === 'Tuesday' ? 'selected' : ''}>Tuesday</option>
                            <option value="Wednesday" ${selectedDay === 'Wednesday' ? 'selected' : ''}>Wednesday</option>
                            <option value="Thursday" ${selectedDay === 'Thursday' ? 'selected' : ''}>Thursday</option>
                            <option value="Friday" ${selectedDay === 'Friday' ? 'selected' : ''}>Friday</option>
                            <option value="Saturday" ${selectedDay === 'Saturday' ? 'selected' : ''}>Saturday</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-end">
                        <label class="small text-muted fw-bold d-block">&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" onclick="$('#row_${slotId}_${index}').remove();">
                            <i class="fas fa-trash me-1"></i> Remove
                        </button>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="small text-muted fw-bold mb-0">Select Section(s) for this Teacher:</label>
                        <span class="badge bg-secondary teacher-sections-badge" style="font-size: 0.75rem;">Showing all subject sections</span>
                    </div>
                    <div class="row g-2 sections-grid">
                        ${sectionsCheckboxesHTML}
                    </div>
                </div>
            </div>
        `;

        let newRow = $(rowHTML);
        container.append(newRow);

        // Apply section filter immediately for row
        filterRowSections(newRow);
    }

    $(document).ready(function() {
        if($.fn.select2) {
            $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });
        }

        // Filter sections when teacher selection changes in any row
        $(document).on('change', '.teacher-select', function() {
            let row = $(this).closest('.parallel-class-card');
            filterRowSections(row);
        });

        // Pre-populate existing allocations from backend
        if (typeof existingAllocationsData === 'object' && existingAllocationsData !== null) {
            Object.keys(existingAllocationsData).forEach(function(slotId) {
                let allocations = existingAllocationsData[slotId];
                if (Array.isArray(allocations)) {
                    allocations.forEach(function(alloc) {
                        renderParallelRow(slotId, alloc);
                    });
                }
            });
        }

        // Add blank row when "+ Add Parallel Class" is clicked
        $('.add-row-btn').on('click', function() {
            let slotId = $(this).data('slot-id');
            renderParallelRow(slotId, null);
        });
    });
</script>
@endsection
