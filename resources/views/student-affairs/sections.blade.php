@extends('layouts.app')
@section('title', 'Manage Student Sections')

@section('styles')
<style>
.section-badge {
    font-size: 0.85rem;
    padding: 6px 16px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-weight: 700;
    letter-spacing: 0.03em;
    transition: all 0.18s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.section-badge:hover {
    box-shadow: 0 4px 14px rgba(0,0,0,0.13);
}
.section-badge-clickable {
    cursor: pointer;
    user-select: none;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.section-badge-clickable:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.18) !important;
}
.section-badge-clickable.active-section-filter {
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.45) !important;
}
.section-pill-clickable {
    cursor: pointer;
    transition: all 0.15s ease;
}
.section-pill-clickable:hover {
    filter: brightness(0.92);
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}
.section-card {
    border-radius: 14px;
    border: none;
    box-shadow: 0 2px 14px rgba(0,0,0,0.07);
    transition: box-shadow 0.2s;
}
.student-table thead th {
    position: sticky;
    top: 0;
    background: #f8f9fa;
    z-index: 2;
    font-size: 0.80rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6c757d;
    border-bottom: 2px solid #dee2e6;
}
.student-table tbody tr { transition: background 0.12s; }
.student-table tbody tr:hover { background: #f0f4ff; }
.table-scroll-container {
    max-height: 520px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0 0 10px 10px;
}
.section-pill {
    display: inline-block;
    padding: 2px 12px;
    border-radius: 12px;
    font-size: 0.78rem;
    font-weight: 700;
    background: #e8f0fe;
    color: #1a56db;
    border: 1px solid #c6d9f7;
    letter-spacing: 0.04em;
}
.section-pill.empty {
    background: #f1f3f5;
    color: #adb5bd;
    border-color: #dee2e6;
    font-weight: 400;
}
#sectionNameInput {
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.08em;
}
.page-header-icon {
    width: 46px; height: 46px;
    border-radius: 12px;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.2rem;
    margin-right: 12px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(79,70,229,0.3);
}
.filter-card .form-select { border-radius: 8px; }
.spinner-overlay {
    display: none;
    position: absolute; inset: 0;
    background: rgba(255,255,255,0.7);
    border-radius: 10px;
    z-index: 10;
    align-items: center;
    justify-content: center;
}
</style>
@endsection

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex align-items-center mb-4">
        <div class="page-header-icon"><i class="fas fa-layer-group"></i></div>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="mb-0 fw-bold" style="font-size:1.5rem;">Student Sections</h2>
                @if(Auth::user()->isHod())
                    <span class="badge bg-primary"><i class="fas fa-user-tie me-1"></i>HOD Department View</span>
                @endif
            </div>
            <p class="text-muted mb-0 small">
                Group enrolled HSSC students into sections <strong>per subject</strong>.
                Each subject can have its own sections (e.g. Math → G1-G5, Computer → ICS-1-ICS-3).
            </p>
        </div>
    </div>

    @if(Auth::user()->isHod() && empty(Auth::user()->getHodSubjectIds()))
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
            <i class="fas fa-exclamation-triangle fa-lg"></i>
            <span>You are logged in as HOD, but no subject department has been assigned to your staff profile yet. Please contact the administrator.</span>
        </div>
    @endif

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">
            <i class="fas fa-check-circle fa-lg"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Year + Subject Selector --}}
    <div class="card section-card filter-card mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold mb-1">
                        <i class="fas fa-calendar-alt me-1 text-primary"></i> Admission Year
                    </label>
                    <select id="yearSelect" class="form-select">
                        <option value="">-- Select Year --</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4" id="subjectContainer" style="{{ $selectedYear ? '' : 'display:none;' }}">
                    <label class="form-label fw-semibold mb-1">
                        <i class="fas fa-book me-1 text-success"></i> Subject
                    </label>
                    <select id="subjectSelect" class="form-select">
                        <option value="">-- Select Subject --</option>
                        @foreach($subjects as $sub)
                            <option value="{{ $sub->id }}" {{ $selectedSubject == $sub->id ? 'selected' : '' }}>
                                {{ $sub->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-none" id="loadBtnContainer">
                    <button type="button" id="loadBtn" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Load Students
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content (shown when year+subject selected) --}}
    <div id="mainContent" style="{{ ($selectedYear && $selectedSubject) ? '' : 'display:none;' }}">

        {{-- Sections Summary --}}
        <div class="card section-card mb-4" id="sectionsSummaryCard">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-sitemap text-primary"></i>
                        <strong>Current Sections — {{ $subjectName }}, {{ $selectedYear }} batch</strong>
                    </div>
                    @if(!$isHod)
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#copySectionsModal">
                        <i class="fas fa-copy me-1"></i>Copy Sections From Subject
                    </button>
                    @endif
                </div>
                @if($sections->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2" id="sectionsSummary">
                        @php
                            $colors = [
                                ['bg'=>'#e8f0fe','text'=>'#1a56db','border'=>'#c6d9f7'],
                                ['bg'=>'#fce8e6','text'=>'#c0392b','border'=>'#f5c6c3'],
                                ['bg'=>'#e6f4ea','text'=>'#1e7e34','border'=>'#c3e6cb'],
                                ['bg'=>'#fef3cd','text'=>'#856404','border'=>'#ffeeba'],
                                ['bg'=>'#f3e8ff','text'=>'#6d28d9','border'=>'#ddd6fe'],
                                ['bg'=>'#e0f7fa','text'=>'#00796b','border'=>'#b2dfdb'],
                            ];
                        @endphp
                        @foreach($sections as $i => $sec)
                            @php $c = $colors[$i % count($colors)]; @endphp
                            <span class="section-badge section-badge-clickable"
                                  data-section-name="{{ $sec->section }}"
                                  title="Click to select all students in section {{ $sec->section }}"
                                  style="background:{{ $c['bg'] }};color:{{ $c['text'] }};border:1.5px solid {{ $c['border'] }};">
                                <i class="fas fa-users" style="font-size:0.72rem;"></i>
                                {{ $sec->section }}
                                <span style="background:{{ $c['text'] }};color:#fff;border-radius:10px;padding:1px 7px;font-size:0.7rem;">
                                    {{ $sec->student_count }}
                                </span>
                            </span>
                        @endforeach
                    </div>
                    <p class="text-muted small mb-0 mt-2">
                        {{ $sections->sum('student_count') }} student(s) in sections ·
                        {{ $students->count() - $sections->sum('student_count') }} unsectioned
                    </p>
                @else
                    @if($isHod)
                        <p class="text-muted small mb-0">
                            No sections assigned to {{ $subjectName }} yet. Select students below to create a section.
                        </p>
                    @else
                        <p class="text-muted small mb-0">
                            No sections assigned to {{ $subjectName }} yet. Click <strong>"Copy Sections From Subject"</strong> above to copy from Computer or Math, or select students below to create a section.
                        </p>
                    @endif
                @endif
            </div>
        </div>

        {{-- Main Assign Form --}}
        <form action="{{ route('student-affairs.sections.store') }}" method="POST" id="sectionsForm">
            @csrf
            <input type="hidden" name="admission_year" value="{{ $selectedYear }}">
            <input type="hidden" name="subject_id"     value="{{ $selectedSubject }}">

            <div class="card section-card">
                <div class="card-header bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                    <div>
                        <h6 class="fw-bold mb-0">
                            <i class="fas fa-users me-1 text-primary"></i>
                            Students enrolled in
                            <span class="text-primary">{{ $subjectName ?: 'selected subject' }}</span>
                            — {{ $selectedYear }}
                        </h6>
                        <small class="text-muted">{{ $students->count() }} student(s). Select students then assign a section name.</small>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        {{-- Select Unsectioned Button --}}
                        <button type="button" id="selectUnsectionedBtn" class="btn btn-sm btn-outline-info" title="Select all students without a section">
                            <i class="fas fa-filter me-1"></i>Select Unsectioned
                        </button>

                        {{-- Section name input --}}
                        <div class="input-group input-group-sm" style="width:auto;">
                            <input type="text" name="section" id="sectionNameInput"
                                   class="form-control" placeholder="Section (e.g. G5)"
                                   maxlength="50" style="width:130px;" autocomplete="off">
                            <button type="submit" class="btn btn-primary" id="assignSectionBtn">
                                <i class="fas fa-tag me-1"></i>Assign
                            </button>
                        </div>

                        {{-- Clear Selected & Clear All --}}
                        <div class="btn-group btn-group-sm">
                            <button type="button" id="clearSectionBtn" class="btn btn-outline-danger">
                                <i class="fas fa-times me-1"></i>Clear Selected
                            </button>
                            <button type="button" id="clearAllSectionsBtn" class="btn btn-outline-danger" title="Clear all sections for this subject">
                                <i class="fas fa-trash me-1"></i>Clear All
                            </button>
                        </div>

                        {{-- Select All --}}
                        <div class="form-check mb-0 ms-1">
                            <input class="form-check-input" type="checkbox" id="selectAllStudents">
                            <label class="form-check-label fw-semibold small" for="selectAllStudents">All</label>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0" style="position:relative;">
                    @if($students->isEmpty())
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-user-slash fa-3x mb-3" style="opacity:0.3;"></i>
                            <p class="mb-0">No enrolled students found for <strong>{{ $subjectName }}</strong> in {{ $selectedYear }}.</p>
                        </div>
                    @else
                        <div class="table-scroll-container">
                            <table class="table table-hover table-sm student-table mb-0">
                                <thead>
                                    <tr>
                                        <th width="42" class="text-center ps-3">☑</th>
                                        <th>Roll No</th>
                                        <th>Student Name</th>
                                        <th>Father Name</th>
                                        <th>Elective Subjects</th>
                                        <th>Section ({{ $subjectName ?: 'subject' }})</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $student)
                                        @php
                                            $admission  = $student->admissions->first();
                                            $secRecord  = $student->subjectSections->first();
                                            $curSection = $secRecord?->section;
                                            $electivesList = $student->electives->sortBy('sequence')->map(fn($e) => $e->subject?->name)->filter()->implode(', ');
                                        @endphp
                                        <tr class="student-row" data-has-section="{{ $curSection ? '1' : '0' }}" data-section="{{ $curSection ?? '' }}">
                                            <td class="text-center ps-3">
                                                <input type="checkbox" name="student_ids[]"
                                                       value="{{ $student->id }}"
                                                       class="student-checkbox form-check-input">
                                            </td>
                                            <td><strong>{{ $admission?->college_roll_no ?? 'N/A' }}</strong></td>
                                            <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                                            <td class="text-muted">{{ $student->father?->name ?? '—' }}</td>
                                            <td>
                                                <small class="text-secondary">{{ $electivesList ?: '—' }}</small>
                                            </td>
                                            <td>
                                                @if($curSection)
                                                    <span class="section-pill section-pill-clickable" data-section-name="{{ $curSection }}" title="Click to select all students in section {{ $curSection }}">{{ $curSection }}</span>
                                                @else
                                                    <span class="section-pill empty">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="px-3 py-2 bg-light border-top d-flex align-items-center justify-content-between">
                            <small class="text-muted">
                                <span id="selectedCount">0</span> selected
                            </small>
                            <small class="text-muted">{{ $students->count() }} total students</small>
                        </div>
                    @endif
                </div>
            </div>
        </form>

        {{-- Hidden clear form --}}
        <form action="{{ route('student-affairs.sections.clear') }}" method="POST" id="clearForm">
            @csrf
            <input type="hidden" name="admission_year" value="{{ $selectedYear }}">
            <input type="hidden" name="subject_id"     value="{{ $selectedSubject }}">
            <div id="clearStudentIds"></div>
        </form>

    </div>{{-- /mainContent --}}

    {{-- Empty state --}}
    @if(!$selectedYear || !$selectedSubject)
    <div class="card section-card">
        <div class="card-body py-5 text-center text-muted">
            <i class="fas fa-layer-group fa-3x mb-3" style="color:#c8d0e0;"></i>
            <h5 class="fw-semibold">Select a year and subject to manage sections</h5>
            <p class="small mb-0">Each subject can have independent sections. A student can be in G5 for Math and ICS-3 for Computer.</p>
        </div>
    </div>
    @endif

    {{-- Copy Sections Modal --}}
    @if($selectedYear && $selectedSubject && !$isHod)
    <div class="modal fade" id="copySectionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('student-affairs.sections.copy') }}" method="POST">
                @csrf
                <input type="hidden" name="admission_year"    value="{{ $selectedYear }}">
                <input type="hidden" name="target_subject_id" value="{{ $selectedSubject }}">

                <div class="modal-content" style="border-radius:12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-copy me-2 text-primary"></i>Copy Sections to {{ $subjectName }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            Copy section assignments from a major subject (e.g. <strong>Computer</strong> or <strong>Math</strong>) to <strong>{{ $subjectName }}</strong> for all students in {{ $selectedYear }}.
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Copy Sections From:</label>
                            <select name="source_subject_id" class="form-select" required>
                                <option value="">-- Select Source Subject --</option>
                                @foreach($allSubjects ?? $subjects as $sub)
                                    @if($sub->id != $selectedSubject)
                                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync me-1"></i>Copy Sections Now
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {

    const subjectsUrl = "{{ route('student-affairs.sections.get-subjects') }}";
    const sectionsUrl = "{{ route('student-affairs.sections.index') }}";

    // ─── Year change → fetch subjects via AJAX ────────────────────────────
    $('#yearSelect').change(function () {
        const year = $(this).val();
        $('#subjectSelect').html('<option value="">Loading...</option>').prop('disabled', true);
        $('#subjectContainer').hide();
        $('#mainContent').hide();
        $('#loadBtnContainer').addClass('d-none');

        if (!year) return;

        $.get(subjectsUrl, { admission_year: year }, function (subjects) {
            let opts = '<option value="">-- Select Subject --</option>';
            subjects.forEach(s => opts += `<option value="${s.id}">${s.name}</option>`);
            $('#subjectSelect').html(opts).prop('disabled', false);
            $('#subjectContainer').fadeIn();
        });
    });

    // ─── Subject change → show Load button ───────────────────────────────
    $('#subjectSelect').change(function () {
        if ($(this).val()) {
            $('#loadBtnContainer').removeClass('d-none');
        } else {
            $('#loadBtnContainer').addClass('d-none');
            $('#mainContent').hide();
        }
    });

    // If both year+subject already selected on page load, show load button
    @if($selectedYear && $selectedSubject)
        $('#subjectContainer').show();
        $('#loadBtnContainer').removeClass('d-none');
    @endif

    // ─── Load button → navigate to page with params ───────────────────────
    $('#loadBtn').click(function () {
        const year    = $('#yearSelect').val();
        const subject = $('#subjectSelect').val();
        if (!year || !subject) { alert('Select year and subject first.'); return; }
        window.location.href = sectionsUrl + '?admission_year=' + year + '&subject_id=' + subject;
    });

    // ─── Select All ───────────────────────────────────────────────────────
    $('#selectAllStudents').change(function () {
        $('.student-checkbox').prop('checked', $(this).is(':checked'));
        updateCount();
    });
    $(document).on('change', '.student-checkbox', function () {
        const total   = $('.student-checkbox').length;
        const checked = $('.student-checkbox:checked').length;
        $('#selectAllStudents')
            .prop('indeterminate', checked > 0 && checked < total)
            .prop('checked', checked === total);
        updateCount();
    });
    function updateCount() {
        $('#selectedCount').text($('.student-checkbox:checked').length);
    }

    // ─── Validate assign form ─────────────────────────────────────────────
    $('#sectionsForm').submit(function (e) {
        if ($('.student-checkbox:checked').length === 0) {
            e.preventDefault();
            alert('Please select at least one student.');
            return;
        }
        const name = $('#sectionNameInput').val().trim();
        if (!name) {
            e.preventDefault();
            alert('Please enter a section name (e.g. G5, ICS-3).');
            $('#sectionNameInput').focus();
        }
    });

    // ─── Clear section ────────────────────────────────────────────────────
    $('#clearSectionBtn').click(function () {
        const checked = $('.student-checkbox:checked');
        if (checked.length === 0) { alert('Select students to clear.'); return; }
        if (!confirm('Clear section for ' + checked.length + ' student(s)?')) return;

        $('#clearStudentIds').empty();
        checked.each(function () {
            $('#clearStudentIds').append('<input type="hidden" name="student_ids[]" value="' + $(this).val() + '">');
        });
        $('#clearForm').submit();
    });

    // ─── Select Unsectioned ─────────────────────────────────────────────
    $('#selectUnsectionedBtn').click(function () {
        $('.student-row').each(function () {
            const hasSec = $(this).data('has-section');
            $(this).find('.student-checkbox').prop('checked', hasSec == 0);
        });
        const total   = $('.student-checkbox').length;
        const checked = $('.student-checkbox:checked').length;
        $('#selectAllStudents')
            .prop('indeterminate', checked > 0 && checked < total)
            .prop('checked', checked === total && total > 0);
        updateCount();
    });

    // ─── Clear All Sections ───────────────────────────────────────────────
    $('#clearAllSectionsBtn').click(function () {
        if (!confirm('Are you sure you want to clear ALL section assignments for {{ $subjectName }} in {{ $selectedYear }}?')) return;
        $('#clearStudentIds').html('<input type="hidden" name="clear_all" value="1">');
        $('#clearForm').submit();
    });

    // ─── Click Section Badge / Pill → Select all students of that section ─────
    $(document).on('click', '.section-badge-clickable, .section-pill-clickable', function () {
        const secName = $(this).data('section-name');
        if (!secName) return;

        // Toggle active visual highlight on badges
        $('.section-badge-clickable').removeClass('active-section-filter');
        $(`.section-badge-clickable[data-section-name="${secName}"]`).addClass('active-section-filter');

        // Select matching students
        $('.student-row').each(function () {
            const rowSec = $(this).data('section');
            const isMatch = (String(rowSec).toUpperCase() === String(secName).toUpperCase());
            $(this).find('.student-checkbox').prop('checked', isMatch);
        });

        // Pre-fill section input box for quick editing/renaming
        $('#sectionNameInput').val(String(secName).toUpperCase()).focus();

        // Update select all checkbox & count
        const total   = $('.student-checkbox').length;
        const checked = $('.student-checkbox:checked').length;
        $('#selectAllStudents')
            .prop('indeterminate', checked > 0 && checked < total)
            .prop('checked', checked === total && total > 0);
        updateCount();
    });

    // ─── Auto uppercase section input ─────────────────────────────────────
    $('#sectionNameInput').on('input', function () {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });
});
</script>
@endsection
