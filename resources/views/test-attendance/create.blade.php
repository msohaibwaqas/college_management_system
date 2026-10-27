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

    <!-- Test Selection from Datesheet -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">1. Select Test Date</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Test Date <span class="text-danger">*</span></label>
                    <select id="test_date" class="form-select" required>
                        <option value="">Select Test Date</option>
                        @foreach($testDates as $dateKey => $dateInfo)
                            <option value="{{ $dateKey }}">
                                {{ $dateInfo['date']->format('l, d F Y') }} 
                                ({{ $dateInfo['tests']->count() }} test{{ $dateInfo['tests']->count() > 1 ? 's' : '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Select Test <span class="text-danger">*</span></label>
                    <select id="test_selection" class="form-select" required disabled>
                        <option value="">First select a date</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" id="loadTestBtn" class="btn btn-primary w-100" disabled>
                        <i class="fas fa-check me-2"></i>Load Test Details
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Details (Auto-filled) -->
    <div class="card mb-4" id="testDetailsCard" style="display: none;">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">2. Test Details (Auto-filled)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Admission Year</label>
                    <input type="text" id="academic_year" class="form-control" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Class Level</label>
                    <input type="text" id="class_level" class="form-control" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Subject</label>
                    <input type="text" id="subject_name" class="form-control" readonly>
                    <input type="hidden" id="subject_id">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Exam Type <span class="text-danger">*</span></label>
                    <select id="exam_type" class="form-select" required>
                        <option value="Oct_test">October Test</option>
                        <option value="Dec_test">December Test</option>
                        <option value="Send_up">Send Up</option>
                        <option value="Class_test">Class Test</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total Marks <span class="text-danger">*</span></label>
                    <input type="number" id="total_marks" class="form-control" min="1" value="100" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Exam Name <span class="text-danger">*</span></label>
                    <input type="text" id="exam_name" class="form-control" placeholder="e.g. October Test 2024" required>
                </div>
                <!-- Existing Attendances Section -->
                <div id="existingAttendancesDiv" class="col-md-12" style="display: none;">
                    <div class="alert alert-info py-2 mb-0">
                        <small class="fw-bold d-block mb-1"><i class="fas fa-history me-1"></i> Existing attendances found for this subject:</small>
                        <div id="existingAttendancesList" class="d-flex flex-wrap gap-2">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>
                <div class="col-12 text-end">
                    <button type="button" id="loadStudentsBtn" class="btn btn-success">
                        <i class="fas fa-users me-2"></i>Load Students
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Attendance List -->
    <div class="card" id="attendanceSection" style="display: none;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">3. Mark Attendance (Check if Absent)</h5>
            <div>
                <span class="badge bg-info me-2" id="studentCountBadge">0 Students</span>
                <button type="button" id="saveAttendanceBtn" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Attendance
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0 attendance-table" id="attendanceTable">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="15%" class="text-nowrap">Roll No</th>
                            <th width="30%" class="col-name-father">Student Name</th>
                            <th width="25%" class="col-name-father">Father Name</th>
                            <th width="15%" class="text-center">Is Absent?</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white text-end">
            <button type="button" id="saveAttendanceBtnBottom" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Save Attendance
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let testsForDate = {};
    let originalExamType = null;
    let originalExamName = null;
    
    // Load Test Details when date selected
    $('#test_date').change(function() {
        const date = $(this).val();
        if (!date) {
            $('#test_selection').prop('disabled', true).html('<option value="">First select a date</option>');
            $('#loadTestBtn').prop('disabled', true);
            return;
        }

        // Reuse the route from ResultController as logic is identical for fetching datesheet details
        $.post("{{ route('results.get_test_details') }}", {
            test_date: date,
            _token: "{{ csrf_token() }}"
        })
        .done(function(response) {
            testsForDate = response.tests;
            const select = $('#test_selection');
            select.empty().append('<option value="">Select a test</option>');
            
            response.tests.forEach((test, index) => {
                const examTag = test.exam_name ? ` [${test.exam_name}]` : '';
                select.append(`<option value="${index}">
                    ${test.subject_name} - Class ${test.class_level} (${test.admission_year})${examTag}
                </option>`);
            });
            
            select.prop('disabled', false);
        })
        .fail(function() {
            alert('Error loading tests for this date');
        });
    });

    $('#test_selection').change(function() {
        $('#loadTestBtn').prop('disabled', !$(this).val());
    });

    $('#loadTestBtn').click(function() {
        const selectedIndex = $('#test_selection').val();
        if (selectedIndex === '') return;

        const test = testsForDate[selectedIndex];
        
        $('#academic_year').val(test.admission_year);
        $('#class_level').val(test.class_level);
        $('#subject_id').val(test.subject_id);
        $('#subject_name').val(test.subject_name);
        
        // Handle Existing Attendances
        const existingList = $('#existingAttendancesList');
        existingList.empty();
        if (test.existing_exams && test.existing_exams.length > 0) {
            test.existing_exams.forEach(exam => {
                const btn = $(`<button type="button" class="btn btn-sm btn-outline-info">
                    ${exam.exam_name} (${exam.exam_type}) [${exam.total_marks}]
                </button>`);
                btn.click(function() {
                    $('#exam_type').val(exam.exam_type);
                    $('#exam_name').val(exam.exam_name);
                    $('#total_marks').val(exam.total_marks);
                    originalExamType = exam.exam_type;
                    originalExamName = exam.exam_name;
                    // Also trigger load students if they wish? Or let them click
                });
                existingList.append(btn);
            });
            $('#existingAttendancesDiv').show();
        } else {
            $('#existingAttendancesDiv').hide();
        }

        // Auto-fill preset exam name from datesheet
        if (test.exam_name) {
            $('#exam_name').val(test.exam_name);
            const nameLower = test.exam_name.toLowerCase();
            if (nameLower.includes('oct')) {
                $('#exam_type').val('Oct_test');
            } else if (nameLower.includes('dec')) {
                $('#exam_type').val('Dec_test');
            } else if (nameLower.includes('send') || nameLower.includes('pre-board') || nameLower.includes('pre board')) {
                $('#exam_type').val('Send_up');
            } else if (nameLower.includes('class')) {
                $('#exam_type').val('Class_test');
            }
        } else {
            const date = $('#test_date option:selected').text().split('(')[0].trim();
            if (!$('#exam_name').val()) {
                $('#exam_name').val(`Test - ${date}`);
            }
        }

        $('#testDetailsCard').fadeIn();
    });
    
    // Auto-fill exam name logic
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

    // Load Students for Attendance
    $('#loadStudentsBtn').click(function() {
        const params = {
            academic_year: $('#academic_year').val(),
            class_level: $('#class_level').val(),
            subject_id: $('#subject_id').val(),
            exam_type: $('#exam_type').val(),
            exam_name: $('#exam_name').val(),
            _token: "{{ csrf_token() }}"
        };

        if (!params.academic_year || !params.subject_id || !params.exam_type || !params.exam_name) {
            alert('Please fill all required fields.');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Loading...');

        // We use a new route for fetching students specific to attendance if needed, 
        // or reuse if logic fits. But let's use the dedicated controller route.
        $.post("{{ route('test-attendance.fetch_students') }}", params)
            .done(function(response) {
                const tbody = $('#attendanceTableBody');
                tbody.empty();
                
                $('#studentCountBadge').text(response.students.length + ' Students');
                
                if (response.students.length === 0) {
                    tbody.html('<tr><td colspan="5" class="text-center text-muted py-4">No students found.</td></tr>');
                } else {
                    response.students.forEach((student, index) => {
                        const existing = response.existing_results[student.id];
                        const isAbsent = existing ? (existing.is_absent == 1) : false;
                        
                        if (existing && index === 0) {
                            $('#total_marks').val(existing.total_marks);
                        }

                        const row = `
                            <tr>
                                <td>${index + 1}</td>
                                <td class="text-nowrap">${student.college_roll_no || 'N/A'}</td>
                                <td class="col-name-father">${student.first_name} ${student.last_name || ''}</td>
                                <td class="col-name-father">${student.father ? student.father.name : 'N/A'}</td>
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center">
                                        <input type="checkbox" class="form-check-input attendance-checkbox" 
                                            style="transform: scale(1.5);"
                                            name="attendance[${student.id}]" 
                                            value="1"
                                            ${isAbsent ? 'checked' : ''}>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                }
                
                $('#attendanceSection').fadeIn();
            })
            .fail(function(xhr) {
                alert('Error loading students: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-users me-2"></i>Load Students');
            });
    });

    // Save Attendance
    $('#saveAttendanceBtn, #saveAttendanceBtnBottom').click(function() {
        const formData = {
            academic_year: $('#academic_year').val(),
            class_level: $('#class_level').val(),
            subject_id: $('#subject_id').val(),
            exam_type: $('#exam_type').val(),
            exam_name: $('#exam_name').val(),
            old_exam_type: originalExamType,
            old_exam_name: originalExamName,
            total_marks: $('#total_marks').val(),
            attendance: {},
            _token: "{{ csrf_token() }}"
        };

        // Collect checked students
        $('.attendance-checkbox:checked').each(function() {
            const nameAttr = $(this).attr('name');
            const studentId = nameAttr.match(/attendance\[(\d+)\]/)[1];
            formData.attendance[studentId] = 1;
        });

        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');

        $.post("{{ route('test-attendance.store') }}", formData)
            .done(function(response) {
                alert(response.message);
                // Maybe reload specific part or redirect?
                // window.location.reload(); 
                // Or just keep on page to allow edits?
                // Let's redirect to self (reload)
                location.reload();
            })
            .fail(function(xhr) {
                alert('Error saving attendance: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                btn.prop('disabled', false).html(originalText);
            });
    });
});
</script>
@endsection
