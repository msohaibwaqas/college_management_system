@extends('layouts.app')
@section('title','Add Student')

@section('content')
<div class="container mt-4">
    <h1>Add Student</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Personal Info --}}
        <div class="card mb-3">
            <div class="card-header">Personal Info</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}">
                    </div>
                    <div class="col-md-3">
                        <label>Gender</label>
                        <select name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Religion</label>
                        <select name="religion" id="religion_select" class="form-control">
                            <option value="Islam" {{ old('religion') == 'Islam' ? 'selected' : '' }}>Islam</option>
                            <option value="Christianity" {{ old('religion') == 'Christianity' ? 'selected' : '' }}>Christianity</option>
                            <option value="Hinduism" {{ old('religion') == 'Hinduism' ? 'selected' : '' }}>Hinduism</option>
                            <option value="Other" {{ old('religion') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Nationality</label>
                        <select name="nationality" id="nationality_select" class="form-control">
                            <option value="Pakistani" {{ old('nationality', 'Pakistani') == 'Pakistani' ? 'selected' : '' }}>Pakistani</option>
                            <option value="Non-Pakistani" {{ old('nationality') == 'Non-Pakistani' ? 'selected' : '' }}>Non-Pakistani</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Domicile</label>
                        <input type="text" name="domicile" class="form-control" value="{{ old('domicile') }}" placeholder="e.g. Lahore">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Blood Group</label>
                        <select name="blood_group_id" class="form-control">
                            <option value="">Select Blood Group</option>
                            @foreach($bloodGroups as $bg)
                                <option value="{{ $bg->id }}" {{ old('blood_group_id')==$bg->id ? 'selected' : '' }}>{{ $bg->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Quota</label>
                        <select name="quota_id" class="form-control">
                            <option value="">Select Quota</option>
                            @foreach($quotas as $quota)
                                <option value="{{ $quota->id }}" {{ old('quota_id')==$quota->id ? 'selected' : '' }}>{{ $quota->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>CNIC</label>
                        <input type="text" name="student_cnic" class="form-control" value="{{ old('student_cnic') }}">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Student Picture</label>
                        <input type="file" name="picture" class="form-control" accept="image/*">
                        <small class="text-muted">Optional - JPG, PNG (Max 2MB)</small>
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
                        <input type="text" name="father_name" class="form-control" value="{{ old('father_name') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label>CNIC</label>
                        <input type="text" name="father_cnic" class="form-control" value="{{ old('father_cnic') }}">
                    </div>
                    <div class="col-md-4">
                        <label>Contact</label>
                        <input type="text" name="father_contact" class="form-control" value="{{ old('father_contact') }}">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <label>Occupation</label>
                        <input type="text" name="father_occupation" class="form-control" value="{{ old('father_occupation') }}">
                    </div>
                    <div class="col-md-6">
                        <label>Annual Income</label>
                        <input type="number" name="father_annual_income" class="form-control" value="{{ old('father_annual_income') }}">
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
                        <input type="text" name="student_contact" class="form-control" value="{{ old('student_contact') }}">
                    </div>
                    <div class="col-md-4">
                        <label>WhatsApp No</label>
                        <input type="text" name="whatsapp_no" class="form-control" value="{{ old('whatsapp_no') }}">
                    </div>
                    <div class="col-md-4">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <label>Address</label>
                        <textarea name="address" class="form-control">{{ old('address') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Program Information --}}
        <div class="card mb-3">
            <div class="card-header">Program Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label>Program Type</label>
                        <select name="program_type" id="program_type" class="form-control" required readonly>
                            <option value="Inter" selected>Intermediate (FSc/FA)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Shift</label>
                        <select name="shift" id="shift" class="form-control" required>
                            <option value="">Select Shift</option>
                            <option value="Morning" {{ old('shift') == 'Morning' ? 'selected' : '' }}>Morning</option>
                            <option value="Evening" {{ old('shift') == 'Evening' ? 'selected' : '' }}>Evening</option>
                        </select>
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
                        <input type="number" name="admission_year" class="form-control" value="{{ date('Y') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label>Academic Year</label>
                        <input type="number" name="academic_year" class="form-control" value="{{ date('Y') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label>College Roll No</label>
                        <input type="text" name="college_roll_no" class="form-control" value="{{ old('college_roll_no') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label>HSSC Reg No</label>
                        <input type="text" name="hssc_reg_no" class="form-control" value="{{ old('hssc_reg_no') }}" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3" id="current_class_div">
                        <label>Current Class</label>
                        <select name="current_class" id="current_class" class="form-control">
                            <option value="11" {{ old('current_class')=='11' ? 'selected':'' }}>11</option>
                            <option value="12" {{ old('current_class')=='12' ? 'selected':'' }}>12</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- SSC Records --}}
        <div class="card mb-3">
            <div class="card-header">
                SSC Records
            </div>
            <div class="card-body">
                <div id="sscWrapper">
                    <div class="sscRow row mb-2">
                        <div class="col-md-2">
                            <label>Bise</label>
                            <select name="ssc_records[0][bise_id]" class="form-control" required>
                                <option value="">Select BISE</option>
                                @foreach($bises as $bise)
                                    <option value="{{ $bise->id }}" {{ $bise->name == 'Board of Intermediate and Secondary Education Bahawalpur' ? 'selected' : '' }}>
                                        {{ $bise->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Roll No</label>
                            <input type="text" name="ssc_records[0][roll_no]" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label>Reg No</label>
                            <input type="text" name="ssc_records[0][reg_no]" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label>School Name</label>
                            <input type="text" name="ssc_records[0][school_name]" class="form-control" value="Govt. Model High School No. 01 Chishtian" required>
                        </div>
                        <div class="col-md-1">
                            <label>Total Mark</label>
                            <input type="number" name="ssc_records[0][total_mark]" class="form-control" value="1200" required>
                        </div>
                        <div class="col-md-1">
                            <label>Obtained</label>
                            <input type="number" name="ssc_records[0][obtained_mark]" class="form-control" required>
                        </div>
                        <div class="col-md-2 mt-2">
                            <label>Passing Year</label>
                            <input type="number" name="ssc_records[0][passing_year]" class="form-control" value="{{ date('Y') }}" required>
                        </div>
                        <div class="col-md-2 mt-2">
                            <label>Session</label>
                            <select name="ssc_records[0][passing_session]" class="form-control" required>
                                <option value="1st Annual">1st Annual</option>
                                <option value="2nd Annual">2nd Annual</option>
                            </select>
                        </div>
                        <div class="col-md-2 mt-2">
                            <label>Group</label>
                            <select name="ssc_records[0][ssc_group]" class="form-control" required>
                                <option value="Science">Science</option>
                                <option value="Arts">Arts</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- Subjects --}}
        <div class="card mb-3">
            <div class="card-header">Subjects</div>
            <div class="card-body">
                <h5>Compulsory Subjects</h5>
                <div class="row" id="compulsory_subjects_container">
                    @foreach($subjects->where('type','Compulsory') as $subject)
                        <div class="col-md-3">
                            <input type="checkbox" name="compulsory_subjects[]" value="{{ $subject->id }}" data-name="{{ $subject->name }}" {{ is_array(old('compulsory_subjects')) && in_array($subject->id, old('compulsory_subjects')) ? 'checked' : (old('compulsory_subjects') ? '' : 'checked') }} onclick="return false;" style="pointer-events: none; opacity: 0.8;">
                            {{ $subject->name }}
                        </div>
                    @endforeach
                </div>

                <h5 class="mt-4">Choose Subject Group <span class="text-danger">*</span></h5>
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($subjectGroups as $group)
                                <div class="form-check card p-3 border-0 shadow-sm bg-light flex-grow-1" style="min-width: 200px;">
                                    <input class="form-check-input group-radio" type="radio" name="subject_group_id" id="group_{{ $group->id }}" value="{{ $group->id }}" data-subjects='@json($group->subjects)' required {{ old('subject_group_id') == $group->id ? 'checked' : '' }}>
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

                <h5 class="mt-3">Elective Subjects (Select Sequence)</h5>
                <div class="row">
                    @for($i=0; $i<3; $i++)
                        <div class="col-md-4">
                            <label>Elective {{ $i+1 }}</label>
                            <select name="elective_subjects[{{ $i }}]" class="form-control elective-select" data-index="{{ $i }}" required>
                                <option value="">Select Subject</option>
                                @foreach($subjects->where('type','Elective')->sortBy('name') as $subject)
                                    <option value="{{ $subject->id }}" {{ old("elective_subjects.$i")==$subject->id ? 'selected':'' }}>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Save Student</button>
    </form>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {

    // Consolidated SUBJECT GROUP and ELECTIVE CASCADE Logic
    const groupRadios = document.querySelectorAll('.group-radio');
    const electiveSelects = document.querySelectorAll('.elective-select');

    function applySubjectRules() {
        const selectedGroup = document.querySelector('.group-radio:checked');
        if (!selectedGroup) return;

        const groupSubjects = JSON.parse(selectedGroup.dataset.subjects);
        const mandatorySubjects = groupSubjects.filter(s => s.pivot.is_mandatory);
        const possibleSubjectIds = groupSubjects.map(s => s.id.toString());

        electiveSelects.forEach((select, index) => {
            const currentValue = select.value;
            const slotNum = index + 1; // 1, 2, 3
            
            if (!select.dataset.originalOptions) {
                select.dataset.originalOptions = select.innerHTML;
            }

            // 1. Reset options from backup
            select.innerHTML = select.dataset.originalOptions;

            // 1.5 Filter by SLOT if slots are defined for this group
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

            // 2. Filter by GROUP (only show subjects belonging to this group/slot)
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

                // 4. CASCADE filter (don't show subjects selected in OTHER dropdowns)
                const otherSelections = Array.from(electiveSelects)
                    .filter((s, i) => i !== index && s.value)
                    .map(s => s.value);

                Array.from(select.options).forEach(option => {
                    const optVal = option.value;
                    if (optVal && otherSelections.includes(optVal)) {
                        option.remove();
                    }
                });

                // Restore previous value if it's still an option, otherwise reset
                const stillValid = Array.from(select.options).some(o => o.value === currentValue);
                select.value = stillValid ? currentValue : '';
            }
        });
    }

    groupRadios.forEach(radio => radio.addEventListener('change', applySubjectRules));
    electiveSelects.forEach(select => select.addEventListener('change', applySubjectRules));
    
    // Initial trigger
    if (document.querySelector('.group-radio:checked')) {
        applySubjectRules();
    }

    // Automatic Compulsory Subject Selection
    const religionSelect = document.getElementById('religion_select');
    const nationalitySelect = document.getElementById('nationality_select');
    const compulsoryCheckboxes = document.querySelectorAll('#compulsory_subjects_container input[type="checkbox"]');

    function fetchCompulsorySubjects() {
        if (!religionSelect || !nationalitySelect) return;

        const religion = religionSelect.value;
        const nationality = nationalitySelect.value;

        fetch(`{{ route('apply.compulsory-subjects') }}?religion=${religion}&nationality=${nationality}&program=Inter`)
            .then(response => response.json())
            .then(data => {
                const subjectIds = data.subject_ids.map(id => id.toString());
                
                compulsoryCheckboxes.forEach(checkbox => {
                    if (subjectIds.includes(checkbox.value)) {
                        checkbox.checked = true;
                    } else {
                        checkbox.checked = false;
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching compulsory subjects:', error);
            });
    }

    if (religionSelect) religionSelect.addEventListener('change', fetchCompulsorySubjects);
    if (nationalitySelect) nationalitySelect.addEventListener('change', fetchCompulsorySubjects);

    // Initial load only if religion/nationality was changed manually or it's a fresh page
    @if(!old('compulsory_subjects'))
        fetchCompulsorySubjects();
    @endif
});
</script>
@endsection
