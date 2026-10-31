@extends('layouts.app')
@section('title', 'Test Attendance')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-check-square me-2"></i>Test Attendance</h2>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <!-- Step 1: Sequential Selection: Exam Name -> Range Date -> Select Test -->
    <div class="card mb-4 shadow-sm border-primary">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-calendar-check me-2"></i>1. Select Test from Date Sheet
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                {{-- 1. Select Exam Name --}}
                <div class="col-md-4">
                    <label class="form-label fw-bold text-dark">
                        <i class="fas fa-tag text-primary me-1"></i>Exam / Test Name <span class="text-danger">*</span>
                    </label>
                    <select id="exam_selection" class="form-select border-primary" required>
                        <option value="">-- Choose Exam Name --</option>
                        @foreach($examGroups as $exam)
                            <option value="{{ $exam['key'] }}"
                                    data-name="{{ $exam['name'] }}"
                                    data-start="{{ $exam['start_date'] }}"
                                    data-end="{{ $exam['end_date'] }}"
                                    {{ $loop->first ? 'selected' : '' }}>
                                {{ $exam['display_label'] }} ({{ $exam['total_tests'] }} tests)
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Preset datesheet exam range</small>
                </div>

                {{-- 2. Range Test Date (Appears after Exam Name) --}}
                <div class="col-md-3" id="dateColumn">
                    <label class="form-label fw-bold text-dark">
                        <i class="fas fa-calendar-day text-success me-1"></i>Test Date <span class="text-danger">*</span>
                    </label>
                    <select id="test_date" class="form-select border-success" required>
                        <option value="">Select a date</option>
                    </select>
                    <small class="text-muted">Dates in this exam range</small>
                </div>

                {{-- 3. Select Test (Appears after Date is selected) --}}
                <div class="col-md-3" id="testColumn">
                    <label class="form-label fw-bold text-dark">
                        <i class="fas fa-book text-info me-1"></i>Select Test <span class="text-danger">*</span>
                    </label>
                    <select id="test_selection" class="form-select border-info" required disabled>
                        <option value="">First select a date</option>
                    </select>
                    <small class="text-muted">Subject &amp; Class on this date</small>
                </div>

                {{-- 4. Load Action Button --}}
                <div class="col-md-2" id="loadCol">
                    <button type="button" id="loadTestBtn" class="btn btn-primary w-100 py-2 shadow-sm" disabled>
                        <i class="fas fa-check-circle me-1"></i>Load Details
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Test Details (Auto-filled) -->
    <div class="card mb-4 shadow-sm border-success" id="testDetailsCard" style="display: none;">
        <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-clipboard-list me-2"></i>2. Test Details (Auto-filled)
            </h5>
            <span class="badge bg-white text-success fw-bold px-3 py-1 fs-6" id="loadedSubjectBadge">Subject</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark">Admission Year (Batch)</label>
                    <input type="text" id="academic_year" class="form-control bg-light" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-dark">Class Level</label>
                    <input type="text" id="class_level" class="form-control bg-light" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark">Subject</label>
                    <input type="text" id="subject_name" class="form-control bg-light" readonly>
                    <input type="hidden" id="subject_id">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-dark">Exam Type <span class="text-danger">*</span></label>
                    <select id="exam_type" class="form-select" required>
                        <option value="Oct_test">October Test</option>
                        <option value="Dec_test">December Test</option>
                        <option value="Send_up">Send Up</option>
                        <option value="Class_test">Class Test</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-dark">Total Marks <span class="text-danger">*</span></label>
                    <input type="number" id="total_marks" class="form-control" min="1" value="100" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold small text-dark">
                        <i class="fas fa-bookmark text-primary me-1"></i>Exam Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="exam_name" class="form-control fw-bold text-primary" placeholder="e.g. October Test 2026" required>
                    <small class="text-muted">Auto-loaded from preset datesheet exam name.</small>
                </div>

                <!-- Existing Attendances Section -->
                <div id="existingAttendancesDiv" class="col-md-12" style="display: none;">
                    <div class="alert alert-info py-2 mb-0">
                        <small class="fw-bold d-block mb-1">
                            <i class="fas fa-history me-1"></i> Existing attendances found for this subject:
                        </small>
                        <div id="existingAttendancesList" class="d-flex flex-wrap gap-2">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>

                <div class="col-12 text-end pt-2 border-top">
                    <button type="button" id="loadStudentsBtn" class="btn btn-success px-4 shadow-sm">
                        <i class="fas fa-users me-2"></i>Load Students
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Student Attendance List -->
    <div class="card shadow-sm" id="attendanceSection" style="display: none;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-user-check text-primary me-2"></i>3. Mark Attendance (Check if Absent)
            </h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-info px-3 py-2 fs-6" id="studentCountBadge">0 Students</span>
                <button type="button" class="btn btn-primary btn-sm saveAttendanceBtn shadow-sm">
                    <i class="fas fa-save me-1"></i>Save Attendance
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0 align-middle attendance-table" id="attendanceTable">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">#</th>
                            <th width="15%" class="text-nowrap">Roll No</th>
                            <th width="35%">Student Name</th>
                            <th width="30%">Father Name</th>
                            <th width="15%" class="text-center">Is Absent?</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white text-end py-3">
            <button type="button" class="btn btn-primary px-4 saveAttendanceBtn shadow-sm">
                <i class="fas fa-save me-2"></i>Save Attendance
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    const examGroups = @json($examGroups);
    let testsForDate = [];
    let originalExamType = null;
    let originalExamName = null;

    // Helper: auto-detect exam type from exam name
    function detectExamType(name) {
        if (!name) return 'Oct_test';
        const lower = name.toLowerCase();
        if (lower.includes('oct')) return 'Oct_test';
        if (lower.includes('dec')) return 'Dec_test';
        if (lower.includes('send') || lower.includes('pre-board') || lower.includes('pre board')) return 'Send_up';
        if (lower.includes('class')) return 'Class_test';
        return 'Oct_test';
    }

    // 1. When Exam Name is selected -> Populate and show Test Date dropdown
    $('#exam_selection').change(function() {
        const selectedKey = $(this).val();
        const testDateSelect = $('#test_date');
        const testSelect = $('#test_selection');
        const loadBtn = $('#loadTestBtn');

        testDateSelect.empty().append('<option value="">Select a date</option>');
        testSelect.empty().append('<option value="">First select a date</option>').prop('disabled', true);
        loadBtn.prop('disabled', true);
        $('#testDetailsCard').hide();
        $('#attendanceSection').hide();

        if (!selectedKey) {
            testDateSelect.prop('disabled', true);
            $('#dateColumn').fadeOut();
            $('#testColumn').fadeOut();
            return;
        }

        const group = examGroups.find(g => g.key === selectedKey);
        if (group && group.dates && group.dates.length > 0) {
            group.dates.forEach(d => {
                testDateSelect.append(`<option value="${d.date_key}">${d.formatted} (${d.test_count} test${d.test_count > 1 ? 's' : ''})</option>`);
            });
            testDateSelect.prop('disabled', false);
            $('#dateColumn').fadeIn();
        } else {
            testDateSelect.append('<option value="">No scheduled dates found</option>').prop('disabled', true);
        }
    });

    // 2. When Test Date is selected -> Fetch and show Select Test dropdown
    $('#test_date').change(function() {
        const date = $(this).val();
        const selectedKey = $('#exam_selection').val();
        const testSelect = $('#test_selection');
        const loadBtn = $('#loadTestBtn');

        testSelect.empty().append('<option value="">Loading tests...</option>').prop('disabled', true);
        loadBtn.prop('disabled', true);
        $('#testDetailsCard').hide();
        $('#attendanceSection').hide();

        if (!date) {
            testSelect.html('<option value="">First select a date</option>');
            $('#testColumn').fadeOut();
            return;
        }

        const selectedExam = examGroups.find(g => g.key === selectedKey);
        const examNameParam = (selectedExam && !selectedExam.is_other) ? selectedExam.name : '';

        $.post("{{ route('results.get_test_details') }}", {
            test_date: date,
            exam_name: examNameParam,
            _token: "{{ csrf_token() }}"
        })
        .done(function(response) {
            testsForDate = response.tests || [];
            testSelect.empty().append('<option value="">-- Choose a Subject / Test --</option>');

            if (testsForDate.length === 0) {
                testSelect.append('<option value="">No tests found for this date</option>');
                testSelect.prop('disabled', true);
            } else {
                testsForDate.forEach((test, index) => {
                    const examTag = test.exam_name ? ` [${test.exam_name}]` : '';
                    testSelect.append(`<option value="${index}">
                        ${test.subject_name} — Class ${test.class_level} (Batch ${test.admission_year})${examTag}
                    </option>`);
                });
                testSelect.prop('disabled', false);
                $('#testColumn').fadeIn();

                // If only 1 test on this date, auto-select it
                if (testsForDate.length === 1) {
                    testSelect.val(0).trigger('change');
                }
            }
        })
        .fail(function() {
            alert('Error loading tests for selected date.');
            testSelect.empty().append('<option value="">Error loading tests</option>').prop('disabled', true);
        });
    });

    // 3. When Test is chosen -> enable Load button and optionally auto-load
    $('#test_selection').change(function() {
        const val = $(this).val();
        $('#loadTestBtn').prop('disabled', val === '');
        if (val !== '') {
            loadSelectedTestDetails(parseInt(val));
        }
    });

    $('#loadTestBtn').click(function() {
        const val = $('#test_selection').val();
        if (val !== '') {
            loadSelectedTestDetails(parseInt(val));
        }
    });

    function loadSelectedTestDetails(selectedIndex) {
        const test = testsForDate[selectedIndex];
        if (!test) return;

        $('#academic_year').val(test.admission_year);
        $('#class_level').val(test.class_level);
        $('#subject_id').val(test.subject_id);
        $('#subject_name').val(test.subject_name);
        $('#loadedSubjectBadge').text(test.subject_name + ' (Class ' + test.class_level + ')');

        // Resolve Exam Name
        const selectedKey = $('#exam_selection').val();
        const selectedExam = examGroups.find(g => g.key === selectedKey);
        let resolvedExamName = test.exam_name;
        if (!resolvedExamName && selectedExam && !selectedExam.is_other) {
            resolvedExamName = selectedExam.name;
        }
        if (!resolvedExamName) {
            const dateStr = $('#test_date option:selected').text().split('(')[0].trim();
            resolvedExamName = `Test - ${dateStr}`;
        }

        $('#exam_name').val(resolvedExamName);
        $('#exam_type').val(detectExamType(resolvedExamName));

        // Existing attendances badge/buttons
        const existingList = $('#existingAttendancesList');
        existingList.empty();
        if (test.existing_exams && test.existing_exams.length > 0) {
            test.existing_exams.forEach(exam => {
                const btn = $(`<button type="button" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-tag me-1"></i>${exam.exam_name} (${exam.exam_type}) [${exam.total_marks} Marks]
                </button>`);
                btn.click(function() {
                    $('#exam_type').val(exam.exam_type);
                    $('#exam_name').val(exam.exam_name);
                    $('#total_marks').val(exam.total_marks);
                    originalExamType = exam.exam_type;
                    originalExamName = exam.exam_name;
                });
                existingList.append(btn);
            });
            $('#existingAttendancesDiv').show();
        } else {
            $('#existingAttendancesDiv').hide();
        }

        $('#attendanceSection').hide();
        $('#testDetailsCard').slideDown();
    }

    // Auto-fill exam name if exam type changes manually
    $('#exam_type').change(function() {
        const type = $(this).val();
        const year = $('#academic_year').val();
        const nameInput = $('#exam_name');

        if (type && year && !nameInput.val()) {
            let typeName = type.replace('_', ' ');
            typeName = typeName.charAt(0).toUpperCase() + typeName.slice(1);
            nameInput.val(`${typeName} ${year}`);
        }
    });

    // 4. Load Students for Attendance
    $('#loadStudentsBtn').click(function() {
        const params = {
            academic_year: $('#academic_year').val(),
            class_level: $('#class_level').val(),
            subject_id: $('#subject_id').val(),
            exam_type: $('#exam_type').val(),
            exam_name: $('#exam_name').val(),
            test_date: $('#test_date').val(),
            _token: "{{ csrf_token() }}"
        };

        if (!params.academic_year || !params.subject_id || !params.exam_type || !params.exam_name) {
            alert('Please fill all required test details.');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Loading Students...');

        $.post("{{ route('test-attendance.fetch_students') }}", params)
            .done(function(response) {
                const tbody = $('#attendanceTableBody');
                tbody.empty();

                $('#studentCountBadge').text(response.students.length + ' Students');

                if (response.students.length === 0) {
                    tbody.html('<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-info-circle me-1"></i>No students found for this subject and class.</td></tr>');
                } else {
                    response.students.forEach((student, index) => {
                        const existing = response.existing_results[student.id];
                        const isAbsent = existing ? (existing.is_absent == 1) : false;
                        const isStruckOff = student.is_struck_off || (existing && existing.is_struck_off);

                        if (existing && index === 0 && existing.total_marks) {
                            $('#total_marks').val(existing.total_marks);
                        }

                        const struckOffBadge = isStruckOff
                            ? '<span class="badge bg-danger ms-2"><i class="fas fa-ban me-1"></i>Struck Off (Absent)</span>'
                            : '';

                        const row = `
                            <tr class="${isStruckOff ? 'table-danger bg-opacity-10' : ''}">
                                <td class="text-center text-muted">${index + 1}</td>
                                <td class="text-nowrap fw-bold">${student.college_roll_no || 'N/A'}</td>
                                <td class="fw-semibold">
                                    ${student.first_name} ${student.last_name || ''}
                                    ${struckOffBadge}
                                </td>
                                <td class="text-muted">${student.father ? student.father.name : 'N/A'}</td>
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center m-0">
                                        <input type="checkbox" class="form-check-input attendance-checkbox" 
                                            style="transform: scale(1.4); cursor: pointer;"
                                            name="attendance[${student.id}]" 
                                            value="1"
                                            ${(isAbsent || isStruckOff) ? 'checked' : ''}>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                }

                $('#attendanceSection').slideDown();
            })
            .fail(function(xhr) {
                alert('Error loading students: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-users me-2"></i>Load Students');
            });
    });

    // 5. Save Attendance
    $('.saveAttendanceBtn').click(function() {
        const formData = {
            academic_year: $('#academic_year').val(),
            class_level: $('#class_level').val(),
            subject_id: $('#subject_id').val(),
            exam_type: $('#exam_type').val(),
            exam_name: $('#exam_name').val(),
            old_exam_type: originalExamType,
            old_exam_name: originalExamName,
            total_marks: $('#total_marks').val(),
            test_date: $('#test_date').val(),
            attendance: {},
            _token: "{{ csrf_token() }}"
        };

        $('.attendance-checkbox:checked').each(function() {
            const nameAttr = $(this).attr('name');
            const studentId = nameAttr.match(/attendance\[(\d+)\]/)[1];
            formData.attendance[studentId] = 1;
        });

        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving Attendance...');

        $.post("{{ route('test-attendance.store') }}", formData)
            .done(function(response) {
                alert(response.message || 'Attendance saved successfully!');
                location.reload();
            })
            .fail(function(xhr) {
                alert('Error saving attendance: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                btn.prop('disabled', false).html(originalText);
            });
    });

    // Auto-trigger first exam selection on page load if present
    if ($('#exam_selection').val()) {
        $('#exam_selection').trigger('change');
    }
});
</script>
@endsection
