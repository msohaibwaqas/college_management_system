@extends('layouts.app')
@section('title', 'Student Assignment')

@section('styles')
<style>
.section-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.18s;
    user-select: none;
}
.section-chip:hover {
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
}
.section-chip .badge-count {
    background: rgba(255,255,255,0.85);
    color: inherit;
    border-radius: 10px;
    padding: 1px 7px;
    font-size: 0.75rem;
}
#sectionsPanel {
    border-radius: 10px;
    background: var(--col-light-bg);
    border: 1px solid var(--col-border);
    padding: 14px 16px;
    margin-bottom: 16px;
}
#sectionsPanel h6 {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6c757d;
    margin-bottom: 10px;
}
.chip-colors {
    --c0-bg: var(--col-primary-subtle); --c0-text: var(--col-primary); --c0-border: color-mix(in srgb, var(--col-primary) 30%, transparent);
    --c1-bg: var(--col-secondary-subtle); --c1-text: var(--col-secondary); --c1-border: color-mix(in srgb, var(--col-secondary) 30%, transparent);
    --c2-bg: #f1f5f9; --c2-text: #334155; --c2-border: #cbd5e1;
    --c3-bg: var(--col-primary-subtle); --c3-text: var(--col-dark); --c3-border: color-mix(in srgb, var(--col-dark) 30%, transparent);
    --c4-bg: var(--col-secondary-subtle); --c4-text: var(--col-active); --c4-border: color-mix(in srgb, var(--col-active) 30%, transparent);
    --c5-bg: #f8fafc; --c5-text: #0f172a; --c5-border: #94a3b8;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-user-plus me-2"></i>Assign Students to Teachers</h2>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Select Criteria</h5>
        </div>
        <div class="card-body">
            <form id="assignmentForm" class="row g-3">
                <div class="col-md-3">
                    <label for="admission_year" class="form-label fw-semibold">Admission Year</label>
                    <select name="admission_year" id="admission_year" class="form-select" required>
                        <option value="">Select Year</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3" id="subject_container" style="display: none;">
                    <label for="subject_id" class="form-label fw-semibold">Subject</label>
                    <select name="subject_id" id="subject_id" class="form-select" required disabled>
                        <option value="">Select Subject</option>
                    </select>
                </div>

                <div class="col-md-3" id="staff_container" style="display: none;">
                    <label for="staff_id" class="form-label fw-semibold">Teacher</label>
                    <select name="staff_id" id="staff_id" class="form-select" required disabled>
                        <option value="">Select Teacher</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end" id="load_btn_container" style="display: none;">
                    <div class="d-flex w-100 gap-2">
                        <button type="button" id="loadStudentsBtn" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-sync me-1"></i>Load
                        </button>
                        <button type="button" id="downloadAwardListBtn" class="btn btn-outline-danger flex-grow-1" title="Download Empty Award List">
                            <i class="fas fa-file-pdf me-1"></i>Award List
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sections Panel (shown after load) -->
    <div id="sectionsPanel" class="chip-colors" style="display:none;">
        <h6><i class="fas fa-layer-group me-1"></i> Quick Assign / Unassign by Section</h6>
        <div id="sectionChipsContainer" class="d-flex flex-wrap gap-2">
            <span class="text-muted small">No sections found for this year &amp; subject.</span>
        </div>
        <p class="text-muted small mb-0 mt-2">
            <i class="fas fa-info-circle me-1"></i>
            Click a section chip to select all its students in the list below, then use Assign / Unassign buttons.
            <span id="sectionDirectAssignHint" class="ms-1 text-primary" style="display:none;">
                Or use <strong>Assign Section →</strong> buttons to assign in one click.
            </span>
        </p>
    </div>

    <div class="row">
        <!-- Unassigned Students -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-user-slash text-secondary me-2"></i>Unassigned Students</h5>
                    <button type="button" id="assignBtn" class="btn btn-sm btn-primary" disabled>
                        Assign Selected <i class="fas fa-arrow-right ms-1"></i>
                    </button>
                </div>
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    <div id="unassignedStudentsContainer" class="p-3 text-center text-muted">
                        Please select filters and click "Load"
                    </div>
                </div>
            </div>
        </div>

        <!-- Assigned Students -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <button type="button" id="unassignBtn" class="btn btn-sm btn-outline-danger" disabled>
                        <i class="fas fa-arrow-left me-1"></i> Unassign Selected
                    </button>
                    <h5 class="mb-0 fw-bold text-dark">Assigned Students <i class="fas fa-user-check text-primary ms-2"></i></h5>
                </div>
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    <div id="assignedStudentsContainer" class="p-3 text-center text-muted">
                        Please select filters and click "Load"
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
$(document).ready(function() {

    // Section chip color palette (cycling)
    const CHIP_COLORS = [
        { bg: 'var(--col-primary-subtle)', text: 'var(--col-primary)', border: 'color-mix(in srgb, var(--col-primary) 30%, transparent)' },
        { bg: 'var(--col-secondary-subtle)', text: 'var(--col-secondary)', border: 'color-mix(in srgb, var(--col-secondary) 30%, transparent)' },
        { bg: '#f1f5f9', text: '#334155', border: '#cbd5e1' },
        { bg: 'var(--col-primary-subtle)', text: 'var(--col-dark)', border: 'color-mix(in srgb, var(--col-dark) 30%, transparent)' },
        { bg: 'var(--col-secondary-subtle)', text: 'var(--col-active)', border: 'color-mix(in srgb, var(--col-active) 30%, transparent)' },
        { bg: '#f8fafc', text: '#0f172a', border: '#94a3b8' },
    ];

    // Store sections data
    let sectionsData = [];

    // ─── Admission Year Change ───────────────────────────────────────────────
    $('#admission_year').change(function() {
        const year = $(this).val();
        resetSubsequentFields();
        clearLists();

        if (year) {
            $.get("{{ route('staff.get_subjects_by_year') }}", { admission_year: year }, function(subjects) {
                let options = '<option value="">Select Subject</option>';
                subjects.forEach(s => options += `<option value="${s.id}">${s.name}</option>`);
                $('#subject_id').html(options).prop('disabled', false);
                $('#subject_container').fadeIn();
            });
        }
    });

    // ─── Subject Change ──────────────────────────────────────────────────────
    $('#subject_id').change(function() {
        const subjectId = $(this).val();
        $('#staff_id').html('<option value="">Select Teacher</option>').prop('disabled', true);
        $('#staff_container').hide();
        $('#load_btn_container').hide();
        clearLists();

        if (subjectId) {
            $.get("{{ route('staff.get_teachers_by_subject') }}", { subject_id: subjectId }, function(teachers) {
                let options = '<option value="">Select Teacher</option>';
                teachers.forEach(t => options += `<option value="${t.id}">${t.first_name} ${t.last_name || ''}</option>`);
                $('#staff_id').html(options).prop('disabled', false);
                $('#staff_container').fadeIn();
            });
        }
    });

    // ─── Staff Change ────────────────────────────────────────────────────────
    $('#staff_id').change(function() {
        if ($(this).val()) {
            $('#load_btn_container').fadeIn();
        } else {
            $('#load_btn_container').hide();
        }
    });

    // ─── Load Button ─────────────────────────────────────────────────────────
    $('#loadStudentsBtn').click(function() {
        loadStudents();
    });

    // ─── Download Award List ─────────────────────────────────────────────────
    $('#downloadAwardListBtn').click(function() {
        const year    = $('#admission_year').val();
        const subject = $('#subject_id').val();
        const staff   = $('#staff_id').val();

        if (!year || !subject || !staff) { alert('Please select all fields'); return; }

        const url = "{{ route('staff.download_award_list') }}"
            + "?admission_year=" + year
            + "&subject_id=" + subject
            + "&staff_id=" + staff;
        window.location.href = url;
    });

    // ─── Load Students + Sections ────────────────────────────────────────────
    function loadStudents() {
        const year    = $('#admission_year').val();
        const subject = $('#subject_id').val();
        const staff   = $('#staff_id').val();

        if (!year || !subject || !staff) { alert('Please select all fields'); return; }

        // Loading spinners
        $('#unassignedStudentsContainer').html('<div class="p-3 text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        $('#assignedStudentsContainer').html('<div class="p-3 text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        $('#sectionsPanel').hide();

        // Load Unassigned
        $.get("{{ route('staff.unassigned_students') }}", { admission_year: year, subject_id: subject }, function(response) {
            $('#unassignedStudentsContainer').html(response.html);
            $('#assignBtn').prop('disabled', false);
        });

        // Load Assigned
        $.get("{{ route('staff.assigned_students') }}", { admission_year: year, subject_id: subject, staff_id: staff }, function(response) {
            $('#assignedStudentsContainer').html(response.html);
            $('#unassignBtn').prop('disabled', false);
        });

        // Load Sections
        $.get("{{ route('staff.get_sections_by_year') }}", { admission_year: year, subject_id: subject }, function(sections) {
            sectionsData = sections;
            renderSectionChips(sections, staff, year, subject);
        });
    }

    // ─── Render Section Chips ────────────────────────────────────────────────
    function renderSectionChips(sections, staffId, year, subjectId) {
        if (!sections || sections.length === 0) {
            $('#sectionChipsContainer').html('<span class="text-muted small fst-italic">No sections found. <a href="{{ route('student-affairs.sections.index') }}" target="_blank">Create sections here &rarr;</a></span>');
            $('#sectionDirectAssignHint').hide();
            $('#sectionsPanel').fadeIn();
            return;
        }

        let html = '';
        sections.forEach((sec, i) => {
            const color = CHIP_COLORS[i % CHIP_COLORS.length];
            html += `
            <div class="d-inline-flex align-items-center gap-1" style="margin-bottom:6px;margin-right:6px;">
                <span class="section-chip"
                    data-section="${sec.section}"
                    data-student-ids='${JSON.stringify(sec.student_ids)}'
                    style="background:${color.bg};color:${color.text};border-color:${color.border};"
                    title="Click to select Section ${sec.section} students in both tables">
                    <i class="fas fa-users" style="font-size:0.7rem;"></i>
                    Section ${sec.section}
                    <span class="badge-count">${sec.student_count}</span>
                </span>
                <button class="btn btn-sm assign-section-btn"
                    data-student-ids='${JSON.stringify(sec.student_ids)}'
                    data-section="${sec.section}"
                    style="font-size:0.72rem;padding:3px 9px;background:${color.bg};color:${color.text};border:1.5px solid ${color.border};border-radius:8px;"
                    title="Assign all of Section ${sec.section} to this teacher">
                    Assign → </button>
                <button class="btn btn-sm unassign-section-btn"
                    data-student-ids='${JSON.stringify(sec.student_ids)}'
                    data-section="${sec.section}"
                    style="font-size:0.72rem;padding:3px 9px;background:#fff0f0;color:#c0392b;border:1.5px solid #f5c6c3;border-radius:8px;"
                    title="Unassign all of Section ${sec.section} from this teacher">
                    ← Unassign </button>
            </div>`;
        });

        $('#sectionChipsContainer').html(html);
        $('#sectionDirectAssignHint').show();
        $('#sectionsPanel').fadeIn();

        // Section chip click → select matching checkboxes in BOTH unassigned and assigned lists
        $('.section-chip').click(function () {
            const studentIds = $(this).data('student-ids');
            const section    = $(this).data('section');

            // Select matching checkboxes in both lists
            let foundUnassigned = 0;
            let foundAssigned   = 0;

            $('.unassigned-checkbox').each(function () {
                if (studentIds.includes(parseInt($(this).val()))) {
                    $(this).prop('checked', true);
                    foundUnassigned++;
                }
            });

            $('.assigned-checkbox').each(function () {
                if (studentIds.includes(parseInt($(this).val()))) {
                    $(this).prop('checked', true);
                    foundAssigned++;
                }
            });

            let msg = `Section ${section}: `;
            if (foundUnassigned > 0 || foundAssigned > 0) {
                let parts = [];
                if (foundUnassigned > 0) parts.push(`${foundUnassigned} unassigned`);
                if (foundAssigned > 0) parts.push(`${foundAssigned} assigned`);
                msg += parts.join(' and ') + ' student(s) selected.';
                showToast(msg, 'success');
            } else {
                showToast(`No Section ${section} students found in current view.`, 'info');
            }
        });

        // Assign section btn → directly assign all students of that section
        $(document).off('click', '.assign-section-btn').on('click', '.assign-section-btn', function () {
            const studentIds = $(this).data('student-ids');
            const section    = $(this).data('section');
            const year2      = $('#admission_year').val();
            const subjectId2 = $('#subject_id').val();
            const staffId2   = $('#staff_id').val();

            if (!staffId2) { alert('Please select a teacher first.'); return; }
            if (!studentIds || studentIds.length === 0) { alert('No students in this section.'); return; }

            $.post("{{ route('staff.assign_students') }}", {
                _token:      "{{ csrf_token() }}",
                student_ids: studentIds,
                staff_id:    staffId2,
                subject_id:  subjectId2,
                academic_year: year2
            }, function(response) {
                if (response.success) {
                    showToast(`Section ${section} students assigned successfully!`, 'success');
                    loadStudents();
                } else {
                    alert('Error assigning students');
                }
            });
        });

        // Unassign section btn → directly unassign all students of that section
        $(document).off('click', '.unassign-section-btn').on('click', '.unassign-section-btn', function () {
            const studentIds = $(this).data('student-ids');
            const section    = $(this).data('section');
            const subjectId2 = $('#subject_id').val();
            const staffId2   = $('#staff_id').val();

            if (!staffId2) { alert('Please select a teacher first.'); return; }
            if (!studentIds || studentIds.length === 0) { alert('No students in this section.'); return; }

            $.post("{{ route('staff.unassign_students') }}", {
                _token:      "{{ csrf_token() }}",
                student_ids: studentIds,
                staff_id:    staffId2,
                subject_id:  subjectId2
            }, function(response) {
                if (response.success) {
                    showToast(`Section ${section} students unassigned successfully!`, 'success');
                    loadStudents();
                } else {
                    alert('Error unassigning students');
                }
            });
        });
    }

    // ─── Assign Selected ─────────────────────────────────────────────────────
    $('#assignBtn').click(function() {
        const studentIds = [];
        $('.unassigned-checkbox:checked').each(function() { studentIds.push($(this).val()); });
        if (studentIds.length === 0) { alert('Please select students to assign'); return; }

        $.post("{{ route('staff.assign_students') }}", {
            _token:       "{{ csrf_token() }}",
            student_ids:  studentIds,
            staff_id:     $('#staff_id').val(),
            subject_id:   $('#subject_id').val(),
            academic_year: $('#admission_year').val()
        }, function(response) {
            if (response.success) { loadStudents(); }
            else { alert('Error assigning students'); }
        });
    });

    // ─── Unassign Selected ───────────────────────────────────────────────────
    $('#unassignBtn').click(function() {
        const studentIds = [];
        $('.assigned-checkbox:checked').each(function() { studentIds.push($(this).val()); });
        if (studentIds.length === 0) { alert('Please select students to unassign'); return; }

        $.post("{{ route('staff.unassign_students') }}", {
            _token:      "{{ csrf_token() }}",
            student_ids: studentIds,
            staff_id:    $('#staff_id').val(),
            subject_id:  $('#subject_id').val()
        }, function(response) {
            if (response.success) { loadStudents(); }
            else { alert('Error unassigning students'); }
        });
    });

    // ─── Helpers ─────────────────────────────────────────────────────────────
    function resetSubsequentFields() {
        $('#subject_id').html('<option value="">Select Subject</option>').prop('disabled', true);
        $('#subject_container').hide();
        $('#staff_id').html('<option value="">Select Teacher</option>').prop('disabled', true);
        $('#staff_container').hide();
        $('#load_btn_container').hide();
    }

    function clearLists() {
        const placeholder = '<div class="p-3 text-center text-muted">Please select filters and click "Load"</div>';
        $('#unassignedStudentsContainer').html(placeholder);
        $('#assignedStudentsContainer').html(placeholder);
        $('#assignBtn, #unassignBtn').prop('disabled', true);
        $('#sectionsPanel').hide();
        sectionsData = [];
    }

    function showToast(message, type = 'success') {
        // Simple inline toast / alert at top of page
        const colors = { success: '#198754', info: '#0d6efd', warning: '#ffc107' };
        const color  = colors[type] || colors.success;
        const toast  = $(`<div style="position:fixed;bottom:20px;right:20px;z-index:9999;
            background:${color};color:#fff;padding:10px 18px;border-radius:8px;
            font-size:0.88rem;box-shadow:0 4px 16px rgba(0,0,0,0.2);max-width:280px;">${message}</div>`);
        $('body').append(toast);
        setTimeout(() => toast.fadeOut(400, function () { $(this).remove(); }), 3000);
    }
});
</script>
@endsection
@endsection
