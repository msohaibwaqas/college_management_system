@extends('layouts.app')
@section('title','Edit Student')

@section('content')
<div class="container mt-4">
    <h1>Edit Student</h1>
    
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    
    <form action="{{ route('students.update', $student) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="redirect_to" value="{{ old('redirect_to', $redirectTo ?? request('redirect_to')) }}">

        {{-- Personal Info --}}
        <div class="card mb-3">
            <div class="card-header">Personal Info</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label>First Name</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" value="{{ old('first_name', $student->first_name) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label>Last Name</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" value="{{ old('last_name', $student->last_name) }}">
                    </div>
                    <div class="col-md-4">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Religion</label>
                        <select name="religion" id="religion_select" class="form-control">
                            @php $religion = old('religion', optional($student->attribute)->religion) @endphp
                            <option value="Islam" {{ $religion == 'Islam' ? 'selected' : '' }}>Islam</option>
                            <option value="Christianity" {{ $religion == 'Christianity' ? 'selected' : '' }}>Christianity</option>
                            <option value="Hinduism" {{ $religion == 'Hinduism' ? 'selected' : '' }}>Hinduism</option>
                            <option value="Other" {{ $religion == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Nationality</label>
                        <select name="nationality" id="nationality_select" class="form-control">
                            @php $nat = old('nationality', optional($student->attribute)->nationality ?? 'Pakistani') @endphp
                            <option value="Pakistani" {{ $nat == 'Pakistani' ? 'selected' : '' }}>Pakistani</option>
                            <option value="Non-Pakistani" {{ $nat == 'Non-Pakistani' ? 'selected' : '' }}>Non-Pakistani</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Domicile</label>
                        <input type="text" name="domicile" class="form-control" value="{{ old('domicile', optional($student->attribute)->domicile) }}" placeholder="e.g. Lahore">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Blood Group</label>
                        <select name="blood_group_id" class="form-control">
                            <option value="">Select Blood Group</option>
                            @foreach($bloodGroups as $bg)
                                <option value="{{ $bg->id }}" {{ old('blood_group_id', optional($student->attribute)->blood_group_id)==$bg->id ? 'selected' : '' }}>{{ $bg->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Quota</label>
                        <select name="quota_id" class="form-control">
                            <option value="">Select Quota</option>
                            @foreach($quotas as $quota)
                                <option value="{{ $quota->id }}" {{ old('quota_id', optional($student->attribute)->quota_id)==$quota->id ? 'selected' : '' }}>{{ $quota->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>CNIC</label>
                        <input type="text" name="student_cnic" class="form-control" value="{{ old('student_cnic', optional($student->attribute)->student_cnic) }}">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Student Picture</label>
                        @if($student->picture)
                            <div class="mb-2">
                                <img src="{{ $student->getProfilePictureUrl() }}" alt="Current Picture" class="img-thumbnail" style="max-width: 150px;">
                                <p class="text-muted small mb-0">Current Picture</p>
                            </div>
                        @endif
                        <input type="file" name="picture" class="form-control" accept="image/*">
                        <small class="text-muted">Optional - JPG, PNG (Max 2MB). Leave empty to keep current picture.</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Father Info --}}
        <div class="card mb-3">
            <div class="card-header">Father Info</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label>Father Name</label>
                        <input type="text" name="father_name" class="form-control" value="{{ old('father_name', optional($student->father)->name) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label>CNIC</label>
                        <input type="text" name="father_cnic" class="form-control" value="{{ old('father_cnic', optional($student->father)->cnic) }}">
                    </div>
                    <div class="col-md-4">
                        <label>Contact</label>
                        <input type="text" name="father_contact" class="form-control" value="{{ old('father_contact', optional($student->father)->contact) }}">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <label>Occupation</label>
                        <input type="text" name="father_occupation" class="form-control" value="{{ old('father_occupation', optional($student->father)->occupation) }}">
                    </div>
                    <div class="col-md-6">
                        <label>Annual Income</label>
                        <input type="number" name="father_annual_income" class="form-control" value="{{ old('father_annual_income', optional($student->father)->annual_income) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact Info --}}
        <div class="card mb-3">
            <div class="card-header">Contact Info</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label>Student Contact</label>
                        <input type="text" name="student_contact" class="form-control" value="{{ old('student_contact', optional($student->contact)->student_contact) }}">
                    </div>
                    <div class="col-md-4">
                        <label>WhatsApp No</label>
                        <input type="text" name="whatsapp_no" class="form-control" value="{{ old('whatsapp_no', optional($student->contact)->whatsapp_no) }}">
                    </div>
                    <div class="col-md-4">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', optional($student->contact)->email) }}">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <label>Address</label>
                        <textarea name="address" class="form-control">{{ old('address', optional($student->contact)->address) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Program Information --}}
        <div class="card mb-3">
            <div class="card-header">Program Information</div>
            <div class="card-body">
                @php $admission = $student->admissions->first(); @endphp
                <div class="row">
                    <div class="col-md-3">
                        <label>Program Type</label>
                        <select name="program_type" id="program_type" class="form-control" required>
                            <option value="Inter" {{ old('program_type', $admission->program_type ?? 'Inter') == 'Inter' ? 'selected' : '' }}>Intermediate (FSc/FA)</option>
                            <option value="ADP" {{ old('program_type', $admission->program_type ?? '') == 'ADP' ? 'selected' : '' }}>ADP (Associate Degree)</option>
                            <option value="BS" {{ old('program_type', $admission->program_type ?? '') == 'BS' ? 'selected' : '' }}>BS (4 Years)</option>
                        </select>
                    </div>
                    <div class="col-md-3 program-specific" style="display: none;">
                        <label>Program Name</label>
                        <input type="text" name="program_name" id="program_name" class="form-control" placeholder="e.g. BS English" value="{{ old('program_name', $admission->program_name ?? '') }}">
                    </div>
                    <div class="col-md-3 program-specific" style="display: none;">
                        <label>Shift</label>
                        <select name="shift" id="shift" class="form-control">
                            <option value="">Select Shift</option>
                            <option value="Morning" {{ old('shift', $admission->shift ?? '') == 'Morning' ? 'selected' : '' }}>Morning</option>
                            <option value="Evening" {{ old('shift', $admission->shift ?? '') == 'Evening' ? 'selected' : '' }}>Evening</option>
                        </select>
                    </div>
                    <div class="col-md-3 program-specific" style="display: none;">
                        <label>Semester</label>
                        <input type="number" name="semester" id="semester" class="form-control" min="1" max="8" value="{{ old('semester', $admission->semester ?? 1) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Admission Info --}}
        <div class="card mb-3">
            <div class="card-header">Admission Info</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label>Admission Year</label>
                        <input type="number" name="admission_year" class="form-control" value="{{ old('admission_year', $admission->admission_year ?? '') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label>Academic Year</label>
                        <input type="number" name="academic_year" class="form-control" value="{{ old('academic_year', $admission->academic_year ?? '') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label>College Roll No</label>
                        <input type="text" name="college_roll_no" class="form-control" value="{{ old('college_roll_no', $admission->college_roll_no ?? '') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label>HSSC Reg No</label>
                        <input type="text" name="hssc_reg_no" class="form-control" value="{{ old('hssc_reg_no', $admission->hssc_reg_no ?? '') }}" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3" id="current_class_div">
                        <label>Current Class</label>
                        <select name="current_class" id="current_class" class="form-control">
                            <option value="11" {{ old('current_class', $admission->current_class ?? '')=='11' ? 'selected':'' }}>11</option>
                            <option value="12" {{ old('current_class', $admission->current_class ?? '')=='12' ? 'selected':'' }}>12</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Status</label>
                        <select name="status" class="form-control" required>
                            <option value="Applied" {{ old('status', $admission->status ?? '')=='Applied' ? 'selected':'' }}>Applied</option>
                            <option value="Admitted" {{ old('status', $admission->status ?? '')=='Admitted' ? 'selected':'' }}>Admitted</option>
                            <option value="Active" {{ old('status', $admission->status ?? '')=='Active' ? 'selected':'' }}>Active</option>
                            <option value="Suspended" {{ old('status', $admission->status ?? '')=='Suspended' ? 'selected':'' }}>Suspended</option>
                            <option value="Dropped" {{ old('status', $admission->status ?? '')=='Dropped' ? 'selected':'' }}>Dropped</option>
                            <option value="Graduated" {{ old('status', $admission->status ?? '')=='Graduated' ? 'selected':'' }}>Graduated</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- SSC Record --}}
        <div class="card mb-3">
            <div class="card-header">SSC Record</div>
            <div class="card-body">
                @php $ssc = $student->sscRecord; @endphp
                <div class="row">
                    <div class="col-md-2">
                        <label>Bise</label>
                        <select name="ssc[bise_id]" class="form-control" required>
                            <option value="">Select BISE</option>
                            @foreach($bises as $bise)
                                <option value="{{ $bise->id }}" {{ old('ssc.bise_id', $ssc->bise_id ?? '') == $bise->id ? 'selected' : '' }}>
                                    {{ $bise->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Roll No</label>
                        <input type="text" name="ssc[roll_no]" class="form-control" value="{{ old('ssc.roll_no', $ssc->roll_no ?? '') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label>Reg No</label>
                        <input type="text" name="ssc[reg_no]" class="form-control" value="{{ old('ssc.reg_no', $ssc->reg_no ?? '') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label>School Name</label>
                        <input type="text" name="ssc[school_name]" class="form-control" value="{{ old('ssc.school_name', $ssc->school_name ?? '') }}" required>
                    </div>
                    <div class="col-md-1">
                        <label>Total Marks</label>
                        <input type="number" name="ssc[total_mark]" class="form-control" value="{{ old('ssc.total_mark', $ssc->total_mark ?? '') }}" required>
                    </div>
                    <div class="col-md-1">
                        <label>Obtained</label>
                        <input type="number" name="ssc[obtained_mark]" class="form-control" value="{{ old('ssc.obtained_mark', $ssc->obtained_mark ?? '') }}" required>
                    </div>
                    <div class="col-md-1">
                        <label>Passing Year</label>
                        <input type="number" name="ssc[passing_year]" class="form-control" value="{{ old('ssc.passing_year', $ssc->passing_year ?? '') }}" required>
                    </div>
                    <div class="col-md-2 mt-2">
                        <label>Session</label>
                        <select name="ssc[passing_session]" class="form-control" required>
                            <option value="1st Annual" {{ old('ssc.passing_session', $ssc->passing_session ?? '')=='1st Annual' ? 'selected':'' }}>1st Annual</option>
                            <option value="2nd Annual" {{ old('ssc.passing_session', $ssc->passing_session ?? '')=='2nd Annual' ? 'selected':'' }}>2nd Annual</option>
                        </select>
                    </div>
                    <div class="col-md-2 mt-2">
                        <label>Group</label>
                        <select name="ssc[ssc_group]" class="form-control" required>
                            <option value="Science" {{ old('ssc.ssc_group', $ssc->ssc_group ?? '')=='Science' ? 'selected':'' }}>Science</option>
                            <option value="Arts" {{ old('ssc.ssc_group', $ssc->ssc_group ?? '')=='Arts' ? 'selected':'' }}>Arts</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Subjects --}}
        <div class="card mb-3">
            <div class="card-header">Subjects</div>
            <div class="card-body">
                {{-- Dynamic Compulsory Preview --}}
                <div class="alert alert-info mb-4 border-0 shadow-sm" id="compulsory_preview_box">
                    <h6 class="fw-bold mb-2"><i class="fas fa-info-circle me-2"></i>Assigned Compulsory Subjects</h6>
                    <p class="small mb-2 text-muted">Based on religion and nationality, the following subjects will be auto-assigned:</p>
                    <div id="compulsory_subjects_list" class="d-flex flex-wrap gap-2">
                        <span class="badge bg-white text-primary border shadow-sm px-3 py-2">Loading defaults...</span>
                    </div>
                </div>

                <div id="subject_group_section">
                    <h5 class="mt-4">Subject Group <span class="text-danger">*</span></h5>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($subjectGroups as $group)
                                    <div class="form-check card p-3 border-0 shadow-sm bg-light flex-grow-1" style="min-width: 200px;">
                                        <input class="form-check-input group-radio" type="radio" name="subject_group_id" id="group_{{ $group->id }}" value="{{ $group->id }}" data-subjects='@json($group->subjects)' required {{ old('subject_group_id', $currentSubjectGroupId) == $group->id ? 'checked' : '' }}>
                                        <label class="form-check-label ps-2" for="group_{{ $group->id }}">
                                            <div class="fw-bold">{{ $group->name }}</div>
                                            <div class="small text-muted">{{ $group->description }}</div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('subject_group_id')<div class="invalid-feedback d-block mt-2">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="alert alert-info border-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Subject Management:</strong> To change individual elective subjects, please use the 
                        <a href="{{ route('students.change-subjects', $student) }}" class="fw-bold">Change Subjects</a> 
                        feature. Changing the <strong>Subject Group</strong> above will reset electives to that group's defaults.
                    </div>

                    <div class="row mt-3" style="display: none;" id="elective_preview_row">
                        @for($i=0; $i<3; $i++)
                            <div class="col-md-4">
                                <label>Elective {{ $i+1 }}</label>
                                <select name="elective_subjects[{{ $i }}]" class="form-control elective-select" data-index="{{ $i }}">
                                    <option value="">Select Subject</option>
                                    @foreach($subjects as $subject)
                                        @php 
                                            $currentSubjectId = $student->electives->where('sequence', $i+1)->first()?->subject_id;
                                        @endphp
                                        <option value="{{ $subject->id }}" {{ old("elective_subjects.$i", $currentSubjectId) == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>

        @php
            $backUrl = old('redirect_to', $redirectTo ?? request('redirect_to') ?? route('students.index'));
        @endphp
        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Student</button>
            <a href="{{ $backUrl }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Cancel & Back</a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {

    // Dynamic Compulsory Subjects Preview
    const religion_select = document.getElementById('religion_select');
    const nationality_select = document.getElementById('nationality_select');
    const compulsory_list = document.getElementById('compulsory_subjects_list');

    function fetchCompulsorySubjects() {
        if (!religion_select || !nationality_select || !compulsory_list) return;

        const religion = religion_select.value;
        const nationality = nationality_select.value;
        
        compulsory_list.innerHTML = '<span class="text-muted small">Updating...</span>';

        fetch(`{{ route('apply.compulsory-subjects') }}?religion=${religion}&nationality=${nationality}`)
            .then(response => response.json())
            .then(data => {
                compulsory_list.innerHTML = '';
                if (data.subjects && data.subjects.length > 0) {
                    data.subjects.forEach((subject, idx) => {
                        const subjectId = (data.subject_ids && data.subject_ids[idx]) ? data.subject_ids[idx] : '';
                        const span = document.createElement('span');
                        span.className = 'badge bg-white text-primary border shadow-sm px-3 py-2';
                        span.innerHTML = `<i class="fas fa-check-circle text-success me-1"></i> ${subject} <input type="hidden" name="compulsory_subjects[]" value="${subjectId}">`;
                        compulsory_list.appendChild(span);
                    });
                } else {
                    compulsory_list.innerHTML = '<span class="text-muted small">No specific rules found. Default subjects will apply.</span>';
                }
            })
            .catch(error => {
                console.error('Error fetching compulsory subjects:', error);
                compulsory_list.innerHTML = '<span class="text-danger small">Failed to load subjects.</span>';
            });
    }

    if (religion_select) religion_select.addEventListener('change', fetchCompulsorySubjects);
    if (nationality_select) nationality_select.addEventListener('change', fetchCompulsorySubjects);
    fetchCompulsorySubjects(); // Initial load

    // SUBJECT GROUP and ELECTIVE CASCADE Logic
    const groupRadios = document.querySelectorAll('.group-radio');
    const electiveSelects = document.querySelectorAll('.elective-select');
    const electivePreviewRow = document.getElementById('elective_preview_row');

    function applySubjectRules() {
        const selectedGroup = document.querySelector('.group-radio:checked');
        if (!selectedGroup) return;

        if (electivePreviewRow) electivePreviewRow.style.display = 'flex';

        const groupSubjects = JSON.parse(selectedGroup.dataset.subjects || '[]');
        const mandatorySubjects = groupSubjects.filter(s => s.pivot.is_mandatory);

        electiveSelects.forEach((select, index) => {
            const currentValue = select.value;
            const slotNum = index + 1; // 1, 2, 3

            if (!select.dataset.originalOptions) {
                select.dataset.originalOptions = select.innerHTML;
            }

            // 1. Reset options from backup
            select.innerHTML = select.dataset.originalOptions;

            // 2. Filter by SLOT
            const slotKey = `is_slot${slotNum}`;
            const allowedForThisSlot = groupSubjects.filter(s => {
                const isS1 = s.pivot.is_slot1 == 1;
                const isS2 = s.pivot.is_slot2 == 1;
                const isS3 = s.pivot.is_slot3 == 1;
                const isThisSlot = s.pivot[slotKey] == 1;
                const hasSpecificSlot = isS1 || isS2 || isS3;
                return isThisSlot || !hasSpecificSlot;
            });
            const allowedIdsForThisSlot = allowedForThisSlot.map(s => s.id.toString());

            // Remove options not allowed for this slot
            Array.from(select.options).forEach(option => {
                if (option.value && !allowedIdsForThisSlot.includes(option.value)) {
                    option.remove();
                }
            });

            // 3. Apply MANDATORY rules
            if (mandatorySubjects[index]) {
                const mandatoryId = mandatorySubjects[index].id.toString();
                select.value = mandatoryId;
                select.setAttribute('readonly', true);
                select.style.pointerEvents = 'none';
                select.style.backgroundColor = '#e9ecef';
            } else {
                select.removeAttribute('readonly');
                select.style.pointerEvents = 'auto';
                select.style.backgroundColor = '#fff';

                // 4. CASCADE filter — remove subjects selected in OTHER dropdowns
                const otherSelections = Array.from(electiveSelects)
                    .filter((s, i) => i !== index && s.value)
                    .map(s => s.value);

                Array.from(select.options).forEach(option => {
                    if (option.value && otherSelections.includes(option.value)) {
                        option.remove();
                    }
                });

                // 5. Restore previous value if still available
                const stillValid = Array.from(select.options).some(o => o.value === currentValue);
                select.value = stillValid ? currentValue : '';
            }
        });
    }

    groupRadios.forEach(radio => radio.addEventListener('change', applySubjectRules));
    electiveSelects.forEach(select => select.addEventListener('change', applySubjectRules));

    // Initial trigger — filters dropdowns by slot and restores saved values
    if (document.querySelector('.group-radio:checked')) {
        applySubjectRules();
    }

    // Handle Program Type Change
    function toggleProgramFields() {
        const type = $('#program_type').val();
        if (type === 'Inter') {
            $('.program-specific').hide();
            $('#program_name').prop('required', false);
            $('#shift').prop('required', false);
            $('#semester').prop('required', false);
            
            $('#current_class_div').show();
            $('#current_class').prop('required', true);

            $('#subject_group_section').show();
            $('.group-radio').prop('required', true);
        } else {
            $('.program-specific').show();
            $('#program_name').prop('required', true);
            $('#shift').prop('required', true);
            $('#semester').prop('required', true);
            
            $('#current_class_div').hide();
            $('#current_class').prop('required', false);

            $('#subject_group_section').hide();
            $('.group-radio').prop('required', false);
        }
    }

    $('#program_type').on('change', toggleProgramFields);
    toggleProgramFields(); // Run on load

    // Auto-fill and auto-split name script for quick editing
    function autoFillStudentDefaults() {
        const firstNameInput = $('#first_name');
        const lastNameInput = $('#last_name');
        const domicileInput = $('input[name="domicile"]');
        const studentContactInput = $('input[name="student_contact"]');
        const hsscRegNoInput = $('input[name="hssc_reg_no"]');
        const collegeRollNoInput = $('input[name="college_roll_no"]');
        
        const sscBiseSelect = $('select[name="ssc[bise_id]"]');
        const sscRollInput = $('input[name="ssc[roll_no]"]');
        const sscRegInput = $('input[name="ssc[reg_no]"]');
        const sscSchoolInput = $('input[name="ssc[school_name]"]');
        const sscTotalInput = $('input[name="ssc[total_mark]"]');
        const sscObtainedInput = $('input[name="ssc[obtained_mark]"]');
        const sscYearInput = $('input[name="ssc[passing_year]"]');

        function handleNameSplit() {
            let fullName = $.trim(firstNameInput.val() || '');
            let currentLastName = $.trim(lastNameInput.val() || '');
            if (fullName !== '' && currentLastName === '') {
                let parts = fullName.split(/\s+/);
                if (parts.length > 1) {
                    let last = parts.pop();
                    let first = parts.join(' ');
                    firstNameInput.val(first);
                    lastNameInput.val(last);
                } else {
                    lastNameInput.val(fullName);
                }
            }
        }

        handleNameSplit();
        firstNameInput.on('blur change', function() {
            if ($.trim(lastNameInput.val()) === '') {
                handleNameSplit();
            }
        });

        if ($.trim(domicileInput.val()) === '') {
            domicileInput.val('Bahawalnagar');
        }

        if ($.trim(studentContactInput.val()) === '' && $.trim(hsscRegNoInput.val()) !== '') {
            studentContactInput.val($.trim(hsscRegNoInput.val()));
        }

        hsscRegNoInput.on('input change', function() {
            let regVal = $.trim($(this).val());
            if ($.trim(studentContactInput.val()) === '' || studentContactInput.data('auto')) {
                studentContactInput.val(regVal).data('auto', true);
            }
            if ($.trim(sscRegInput.val()) === '' || sscRegInput.data('auto')) {
                sscRegInput.val(regVal).data('auto', true);
            }
        });

        collegeRollNoInput.on('input change', function() {
            let rollVal = $.trim($(this).val());
            if ($.trim(sscRollInput.val()) === '' || sscRollInput.data('auto')) {
                sscRollInput.val(rollVal).data('auto', true);
            }
        });

        if (!sscBiseSelect.val()) {
            sscBiseSelect.find('option').filter(function() {
                return $(this).text().indexOf('Bahawalpur') !== -1;
            }).prop('selected', true);
        }

        if ($.trim(sscRollInput.val()) === '' && $.trim(collegeRollNoInput.val()) !== '') {
            sscRollInput.val($.trim(collegeRollNoInput.val()));
        }
        if ($.trim(sscRegInput.val()) === '' && $.trim(hsscRegNoInput.val()) !== '') {
            sscRegInput.val($.trim(hsscRegNoInput.val()));
        }
        if ($.trim(sscSchoolInput.val()) === '') {
            sscSchoolInput.val('Govt. High School Chishtian');
        }
        if ($.trim(sscTotalInput.val()) === '') {
            sscTotalInput.val(1200);
        }
        if ($.trim(sscObtainedInput.val()) === '') {
            sscObtainedInput.val(600);
        }
        if ($.trim(sscYearInput.val()) === '') {
            sscYearInput.val(new Date().getFullYear());
        }
    }
    autoFillStudentDefaults();
});
</script>
@endsection

