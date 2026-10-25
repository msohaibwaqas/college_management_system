@extends('layouts.app')
@section('title', 'Enter Exam Marks')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-pen-alt me-2"></i>Enter Exam Marks</h2>
        
        @php
            $isTeacher = false;
            $activeRole = session('active_role');
            if ($activeRole) {
                $isTeacher = ($activeRole === 'teacher');
            } elseif (Auth::check()) {
                $isTeacher = Auth::user()->hasRole('teacher') && 
                            !Auth::user()->hasRole('admin') && 
                            !Auth::user()->hasRole('principal') && 
                            !Auth::user()->hasRole('clerk') &&
                            !Auth::user()->is_super_admin;
            }
            
            $hasPreSelected = isset($preSelected['subject_id']) && $preSelected['subject_id'];
        @endphp

        @if($isTeacher && $hasPreSelected)
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        @else
            <a href="{{ route('results.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Results
            </a>
        @endif
    </div>

    <!-- Test Selection from Datesheet -->
    <div class="card mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle fw-bold">1</span><h5 class="mb-0 fw-bold text-dark">Select Test Date</h5></div>
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
                    <small class="text-muted">Only dates with scheduled tests are shown</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Select Test <span class="text-danger">*</span></label>
                    <select id="test_selection" class="form-select" required disabled>
                        <option value="">First select a date</option>
                    </select>
                    <small class="text-muted">Choose which test to enter marks for</small>
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
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle fw-bold">2</span><h5 class="mb-0 fw-bold text-dark">Test Details (Auto-filled from Datesheet)</h5></div>
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
                    <select id="exam_type" class="form-select" required {{ $isTeacher ? 'disabled' : '' }}>
                        <option value="Oct_test">October Test</option>
                        <option value="Dec_test">December Test</option>
                        <option value="Send_up">Send Up</option>
                        <option value="Class_test">Class Test</option>
                    </select>
                    @if(!$isTeacher)
                    <small class="text-muted">You can change this</small>
                    @endif
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total Marks <span class="text-danger">*</span></label>
                    <input type="number" id="total_marks" class="form-control" min="1" value="100" required {{ $isTeacher ? 'readonly' : '' }}>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Exam Name <span class="text-danger">*</span></label>
                    <input type="text" id="exam_name" class="form-control" placeholder="e.g. October Test 2024" required>
                    <div id="recentExamsContainer" class="mt-2" style="display:none;">
                        <small class="text-muted d-block mb-1">Pick from recent exams found for this subject:</small>
                        <div id="recentExamsList" class="d-flex flex-wrap gap-2"></div>
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

    <!-- Marks Entry -->
    <div class="card" id="marksSection" style="display: none;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">3. Enter Marks</h5>
            <div>
                <span class="badge bg-info me-2" id="studentCountBadge">0 Students</span>
                <button type="button" id="saveMarksBtn" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Results
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0" id="marksTable">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="15%">Roll No</th>
                            <th width="25%">Student Name</th>
                            <th width="25%">Father Name</th>
                            <th width="30%">Theory Marks</th>
                        </tr>
                    </thead>
                    <tbody id="marksTableBody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white text-end">
            <button type="button" id="saveMarksBtnBottom" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Save Results
            </button>
        </div>
    </div>
</div>

@section('scripts')
<script>
$(document).ready(function() {
    let testsForDate = {};
    
    // When test date is selected,load available tests
    $('#test_date').change(function() {
        const date = $(this).val();
        if (!date) {
            $('#test_selection').prop('disabled', true).html('<option value="">First select a date</option>');
            $('#loadTestBtn').prop('disabled', true);
            return;
        }

        $.post("{{ route('results.get_test_details') }}", {
            test_date: date
        })
        .done(function(response) {
            console.log('Tests loaded for date:', response.tests);
            testsForDate = response.tests;
            const select = $('#test_selection');
            select.empty().append('<option value="">Select a test</option>');
            
            response.tests.forEach((test, index) => {
                select.append(`<option value="${index}">
                    ${test.subject_name} - Class ${test.class_level} (${test.admission_year})
                </option>`);
            });
            
            select.prop('disabled', false);
        })
        .fail(function() {
            alert('Error loading tests for this date');
        });
    });

    // When test is selected, enable load button
    $('#test_selection').change(function() {
        const val = $(this).val();
        console.log('test_selection changed to:', val);
        $('#loadTestBtn').prop('disabled', val === '' || val === null);
        
        // If it was auto-selected, maybe trigger click automatically? 
        // No, let users click it unless it's the dashboard flow.
    });

    // Load test details
    $('#loadTestBtn').click(function() {
        try {
            console.log('Load Test Button Clicked');
            const selectedIndex = $('#test_selection').val();
            console.log('Selected Index:', selectedIndex);
            
            if (selectedIndex === '' || selectedIndex === null) {
                console.log('No test selected');
                return;
            }

            if (!testsForDate || !testsForDate[selectedIndex]) {
                alert('Test data not found for selected index. Please re-select the date.');
                return;
            }

            const test = testsForDate[selectedIndex];
            console.log('Selected Test Data:', test);
            
            // Auto-fill fields
            $('#academic_year').val(test.admission_year || '');
            $('#class_level').val(test.class_level || '');
            $('#subject_id').val(test.subject_id || '');
            $('#subject_name').val(test.subject_name || '');
            
            // Auto-fill Exam Details if available from Attendance/Previous Entry
            if (test.latest_exam_info) {
                $('#exam_type').val(test.latest_exam_info.exam_type).trigger('change');
                $('#exam_name').val(test.latest_exam_info.exam_name);
                $('#total_marks').val(test.latest_exam_info.total_marks);
            } else {
                 // Auto-fill exam name if empty and no info
                const dateText = $('#test_date option:selected').text().split('(')[0].trim();
                $('#exam_name').val(`Test - ${dateText}`);
            }

            // Populate Recent Exams Helper
            const recentList = $('#recentExamsList');
            const recentContainer = $('#recentExamsContainer');
            recentList.empty();
            
            if (test.existing_exams && test.existing_exams.length > 0) {
                test.existing_exams.forEach(exam => {
                    const btn = $(`<button type="button" class="btn btn-sm btn-outline-info">
                        ${exam.exam_name} (${exam.exam_type.replace('_', ' ')})
                    </button>`);
                    
                    btn.click(function() {
                        $('#exam_type').val(exam.exam_type).trigger('change');
                        $('#exam_name').val(exam.exam_name);
                        $('#total_marks').val(exam.total_marks);
                    });
                    
                    recentList.append(btn);
                });
                recentContainer.fadeIn();
            } else {
                recentContainer.hide();
            }

            // Ensure the card is visible
            const card = $('#testDetailsCard');
            card.show(); // Use show() first for debugging, then fadeIn()
            console.log('Test details card visibility after show():', card.is(':visible'));
            
            // Scroll to it
            $('html, body').animate({
                scrollTop: card.offset().top - 20
            }, 500);

        } catch (err) {
            console.error('Error in loadTestBtn click:', err);
            alert('An error occurred while loading test details. Check console.');
        }
    });

    // Auto-fill exam name based on type (Only if not already filled by latest_exam_info logic or user)
    $('#exam_type').change(function() {
        // ... (existing logic kept but less relevant if auto-filled)
        const type = $(this).val();
        const year = $('#academic_year').val();
        const nameInput = $('#exam_name');
        
        if (type && year && !nameInput.val()) {
            let typeName = type.replace('_', ' ');
            typeName = typeName.charAt(0).toUpperCase() + typeName.slice(1);
            nameInput.val(`${typeName} ${year}`);
        }
    });

    // Load Students
    $('#loadStudentsBtn').click(function() {
        const params = {
            academic_year: $('#academic_year').val(),
            class_level: $('#class_level').val(),
            subject_id: $('#subject_id').val(),
            exam_type: $('#exam_type').val(),
            exam_name: $('#exam_name').val()
        };

        if (!params.academic_year || !params.subject_id || !params.exam_type || !params.exam_name) {
            alert('Please fill all required fields.');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Loading...');

        $.post("{{ route('results.fetch_students') }}", params)
            .done(function(response) {
                const tbody = $('#marksTableBody');
                tbody.empty();
                
                $('#studentCountBadge').text(response.students.length + ' Students');
                
                if (response.students.length === 0) {
                    tbody.html('<tr><td colspan="6" class="text-center text-muted py-4">No students found.</td></tr>');
                } else {
                    response.students.forEach((student, index) => {
                        const existing = response.existing_results[student.id];
                        const theoryVal = existing ? (existing.obtained_theory !== null ? existing.obtained_theory : '') : '';
                        const isAbsent = existing ? (existing.is_absent == 1) : false;
                        
                        if (existing && index === 0) {
                            $('#total_marks').val(existing.total_marks);
                        }

                        // Determine if inputs should be disabled
                        // If absent: disabled.
                        const disabledAttr = isAbsent ? 'disabled style="background-color: #f8d7da;"' : '';
                        const absentBadge = isAbsent ? '<span class="badge bg-danger ms-2">Absent</span>' : '';

                        const row = `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${student.college_roll_no || 'N/A'}</td>
                                <td>${student.first_name} ${student.last_name || ''} ${absentBadge}</td>
                                <td>${student.father ? student.father.name : 'N/A'}</td>
                                 <td>
                                    <input type="number" class="form-control mark-input theory-mark" 
                                        name="marks[${student.id}][theory]" 
                                        value="${theoryVal}" 
                                        min="0" 
                                        step="1"
                                        placeholder="${isAbsent ? 'Absent' : 'Theory'}"
                                        ${disabledAttr}>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                }
                
                $('#marksSection').fadeIn();
            })
            .fail(function(xhr) {
                alert('Error loading students: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-users me-2"></i>Load Students');
            });
    });

    // Prevent non-numeric characters in mark inputs
    $(document).on('keypress', '.mark-input', function(e) {
        // Allow only numbers (0-9). Block decimal point (.), e, +, -
        if (e.which < 48 || e.which > 57) {
            e.preventDefault();
        }
    });

    // Validate marks against total marks on blur
    $(document).on('blur', '.mark-input', function() {
        const input = $(this);
        const val = parseInt(input.val());
        const totalMarks = parseInt($('#total_marks').val());

        if (!isNaN(val) && val > totalMarks) {
            input.addClass('is-invalid');
            if (!input.next('.invalid-feedback').length) {
                input.after(`<div class="invalid-feedback">Cannot exceed ${totalMarks}</div>`);
            }
        } else {
            input.removeClass('is-invalid');
            input.next('.invalid-feedback').remove();
        }
    });

    // Save Marks
    $('#saveMarksBtn, #saveMarksBtnBottom').click(function() {
        const formData = {
            academic_year: $('#academic_year').val(),
            class_level: $('#class_level').val(),
            subject_id: $('#subject_id').val(),
            exam_type: $('#exam_type').val(),
            exam_name: $('#exam_name').val(),
            total_marks: $('#total_marks').val(),
            marks: {}
        };

        // Final check before saving
        let hasError = false;
        const totalMarks = parseInt(formData.total_marks);

        $('#marksTableBody tr').each(function() {
            const theoryInput = $(this).find('.theory-mark');
            
            if (theoryInput.length && !theoryInput.prop('disabled')) {
                const val = parseInt(theoryInput.val());
                if (!isNaN(val) && val > totalMarks) {
                    theoryInput.addClass('is-invalid');
                    hasError = true;
                }
            }

            if (theoryInput.length) {
                const nameAttr = theoryInput.attr('name');
                const studentId = nameAttr.match(/marks\[(\d+)\]/)[1];
                
                // If input is disabled (Absent), we don't send marks, or we send what's there?
                // The controller handles is_absent logic if checking checkboxes. 
                // But here we REMOVED checkboxes.
                // We are only sending marks for PRESENT students.
                // Absent students have inputs disabled, so `val()` might still work but we shouldn't update them.
                // However, the controller might expect `is_absent` flag if we want to PERSIST absence?
                // No, the requirement is "teacher COMPLEMENTS marks". Attendance set the Absence.
                // So if we don't send `is_absent`, the controller default behavior?
                // Let's check ResultController::store.
                
                // ResultController::store logic:
                // $isAbsent = isset($mark['is_absent']) ...
                // If not absent and marks empty -> continue.
                // updateOrCreate ... 'is_absent' => $isAbsent.
                
                // ISSUE: If we don't send `is_absent=1` for absent students, the controller might mark them as `is_absent=0`!
                // We must preserve the `is_absent` status.
                
                // So checking if the row has the absent badge? Or checking if input is disabled?
                // Let's check if the input has the 'disabled' attribute coming from the "Absent" logic.
                // Actually, relying on DOM state is fragile. 
                // Better: We know they are absent if the input placeholder says 'Absent' or checking the badge.
                
                // Let's look at the generated row: 
                // <span class="badge bg-danger ms-2">Absent</span> in name column.
                // Input has `disabled`.
                
                const isAbsent = $(this).find('.badge.bg-danger').length > 0;
                
                formData.marks[studentId] = {
                    theory: theoryInput.val(),
                    is_absent: isAbsent ? 1 : 0
                };
            }
        });

        if (hasError) {
            alert('Please fix errors (marks exceeding total marks) before saving.');
            return;
        }

        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');

        $.post("{{ route('results.store') }}", formData)
            .done(function(response) {
                alert(response.message);
                window.location.href = "{{ route('results.index') }}";
            })
            .fail(function(xhr) {
                alert('Error saving results: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                btn.prop('disabled', false).html(originalText);
            });
    });
    // Handle pre-selected data from dashboard
    @if(isset($preSelected['subject_id']))
        const preSubjectId = "{{ $preSelected['subject_id'] }}";
        const preClass = "{{ $preSelected['class'] }}";
        const preYear = "{{ $preSelected['year'] }}";

        // Find the most recent date that has this test
        let foundDateKey = null;
        let foundTestIndex = null;

        @foreach($testDates as $dateKey => $dateInfo)
            @foreach($dateInfo['tests'] as $idx => $test)
                if (!foundDateKey && "{{ $test->subject_id }}" === preSubjectId && 
                    "{{ $test->admission_year }}" === preYear) {
                    foundDateKey = "{{ $dateKey }}";
                }
            @endforeach
        @endforeach

        if (foundDateKey) {
            console.log('Automating pre-selection for date:', foundDateKey);
            $('#test_date').val(foundDateKey).trigger('change');
            
            // Wait for AJAX to load tests, then select the specific test
            let attempts = 0;
            const checkTestsInterval = setInterval(function() {
                attempts++;
                if ($('#test_selection option').length > 1) {
                    clearInterval(checkTestsInterval);
                    
                    $('#test_selection option').each(function(index) {
                        const opt = $(this);
                        const val = opt.val();
                        if (val === '') return;

                        const testData = testsForDate[val];
                        if (testData && testData.subject_id == preSubjectId) {
                             console.log('Auto-selecting test at index:', val);
                             $('#test_selection').val(val).trigger('change');
                             // Small delay before auto-clicking load
                             setTimeout(() => $('#loadTestBtn').click(), 200);
                        }
                    });
                }
                if (attempts > 20) clearInterval(checkTestsInterval);
            }, 500);
        }
    @endif
});
</script>
@endsection
@endsection
