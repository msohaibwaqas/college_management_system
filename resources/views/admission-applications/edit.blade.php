@extends('layouts.app')
@section('title', 'Edit Application - ' . $application->application_number)

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Application #{{ $application->application_number }}</h4>
        <a href="{{ route('admission-applications.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <form method="POST" action="{{ route('admission-applications.update', $application) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Personal Information --}}
        <div class="card mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle"><i class="fas fa-user fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">Personal Information</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $application->first_name) }}" required pattern="[a-zA-Z\s]+" title="Only alphabets and spaces are allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $application->last_name) }}" required pattern="[a-zA-Z\s]+" title="Only alphabets and spaces are allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $application->date_of_birth->format('Y-m-d')) }}" required>
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                            <option value="">Select</option>
                            <option value="Male" {{ old('gender', $application->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender', $application->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ old('gender', $application->gender) == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">CNIC / B-Form</label>
                        <input type="text" name="cnic" class="form-control cnic-mask @error('cnic') is-invalid @enderror" value="{{ old('cnic', $application->cnic) }}" placeholder="00000-0000000-0" pattern="\d{5}-\d{7}-\d" title="Format: 00000-0000000-0" maxlength="15">
                        @error('cnic')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Religion <span class="text-danger">*</span></label>
                        <select name="religion" id="religion_select" class="form-select @error('religion') is-invalid @enderror" required>
                            @php $religion = old('religion', $application->religion ?? 'Islam') @endphp
                            <option value="Islam" {{ $religion == 'Islam' ? 'selected' : '' }}>Islam</option>
                            <option value="Christianity" {{ $religion == 'Christianity' ? 'selected' : '' }}>Christianity</option>
                            <option value="Hinduism" {{ $religion == 'Hinduism' ? 'selected' : '' }}>Hinduism</option>
                            <option value="Other" {{ $religion == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('religion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nationality <span class="text-danger">*</span></label>
                        <select name="nationality" id="nationality_select" class="form-select @error('nationality') is-invalid @enderror" required>
                            @php $nat = old('nationality', $application->nationality ?? 'Pakistani') @endphp
                            <option value="Pakistani" {{ $nat == 'Pakistani' ? 'selected' : '' }}>Pakistani</option>
                            <option value="Non-Pakistani" {{ $nat == 'Non-Pakistani' ? 'selected' : '' }}>Foreign National</option>
                        </select>
                        @error('nationality')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group_id" class="form-select">
                            <option value="">Select</option>
                            @foreach($bloodGroups as $bg)
                                <option value="{{ $bg->id }}" {{ old('blood_group_id', $application->blood_group_id) == $bg->id ? 'selected' : '' }}>{{ $bg->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quota</label>
                        <select name="quota_id" class="form-select">
                            <option value="">Open Merit</option>
                            @foreach($quotas as $q)
                                <option value="{{ $q->id }}" {{ old('quota_id', $application->quota_id) == $q->id ? 'selected' : '' }}>{{ $q->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Picture</label>
                        <input type="file" name="picture" class="form-control" accept="image/*">
                        @if($application->picture)
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $application->picture) }}" alt="Current Photo" class="img-thumbnail" style="height: 50px;">
                                <small class="text-muted d-block">Current Profile Picture</small>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Dynamic Compulsory Preview --}}
                <div class="alert alert-info mt-4 mb-0 border-0 shadow-sm" id="compulsory_preview_box">
                    <h6 class="fw-bold mb-2"><i class="fas fa-info-circle me-2"></i>Assigned Compulsory Subjects</h6>
                    <p class="small mb-2 text-muted">Based on religion and nationality, the following subjects will be auto-assigned:</p>
                    <div id="compulsory_subjects_list" class="d-flex flex-wrap gap-2">
                        <span class="badge bg-white text-primary border shadow-sm px-3 py-2">Loading defaults...</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Father Information --}}
        <div class="card mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle"><i class="fas fa-male fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">Father / Guardian</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Father Name <span class="text-danger">*</span></label>
                        <input type="text" name="father_name" class="form-control @error('father_name') is-invalid @enderror" value="{{ old('father_name', $application->father_name) }}" required pattern="[a-zA-Z\s]+" title="Only alphabets and spaces are allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                        @error('father_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Father CNIC</label>
                        <input type="text" name="father_cnic" class="form-control cnic-mask @error('father_cnic') is-invalid @enderror" value="{{ old('father_cnic', $application->father_cnic) }}" placeholder="00000-0000000-0" pattern="\d{5}-\d{7}-\d" title="Format: 00000-0000000-0" maxlength="15">
                        @error('father_cnic')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Father Contact</label>
                        <input type="text" name="father_contact" class="form-control phone-mask @error('father_contact') is-invalid @enderror" value="{{ old('father_contact', $application->father_contact) }}" placeholder="0300-0000000" pattern="03\d{2}-\d{7}" title="Format: 03XX-XXXXXXX" maxlength="12">
                        @error('father_contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Occupation</label>
                        <input type="text" name="father_occupation" class="form-control @error('father_occupation') is-invalid @enderror" value="{{ old('father_occupation', $application->father_occupation) }}" pattern="[a-zA-Z\s]*" title="Only alphabets and spaces are allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                        @error('father_occupation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Annual Income (PKR)</label>
                        <input type="number" name="father_annual_income" class="form-control @error('father_annual_income') is-invalid @enderror" value="{{ old('father_annual_income', $application->father_annual_income) }}" min="0">
                        @error('father_annual_income')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact Information --}}
        <div class="card mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle"><i class="fas fa-phone fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">Contact Information</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Student Contact <span class="text-danger">*</span></label>
                        <input type="text" name="student_contact" class="form-control phone-mask @error('student_contact') is-invalid @enderror" value="{{ old('student_contact', $application->student_contact) }}" required placeholder="0300-0000000" pattern="03\d{2}-\d{7}" title="Format: 03XX-XXXXXXX" maxlength="12">
                        @error('student_contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">WhatsApp No</label>
                        <input type="text" name="whatsapp_no" class="form-control phone-mask @error('whatsapp_no') is-invalid @enderror" value="{{ old('whatsapp_no', $application->whatsapp_no) }}" placeholder="0300-0000000" pattern="03\d{2}-\d{7}" title="Format: 03XX-XXXXXXX" maxlength="12">
                        @error('whatsapp_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $application->email) }}" placeholder="student@example.com">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Address <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="1" required>{{ old('address', $application->address) }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- SSC Record --}}
        <div class="card mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle"><i class="fas fa-graduation-cap fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">SSC Record</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">BISE <span class="text-danger">*</span></label>
                        <select name="bise_id" class="form-select @error('bise_id') is-invalid @enderror" required>
                            <option value="">Select BISE</option>
                            @foreach($bises as $bise)
                                <option value="{{ $bise->id }}" {{ old('bise_id', $application->bise_id) == $bise->id ? 'selected' : '' }}>{{ $bise->name }}</option>
                            @endforeach
                        </select>
                        @error('bise_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SSC Roll No <span class="text-danger">*</span></label>
                        <input type="number" name="ssc_roll_no" class="form-control @error('ssc_roll_no') is-invalid @enderror" value="{{ old('ssc_roll_no', $application->ssc_roll_no) }}" required min="0">
                        @error('ssc_roll_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SSC Registration No <span class="text-danger">*</span></label>
                        <input type="text" name="ssc_reg_no" class="form-control @error('ssc_reg_no') is-invalid @enderror" value="{{ old('ssc_reg_no', $application->ssc_reg_no) }}" required>
                        @error('ssc_reg_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">School Name <span class="text-danger">*</span></label>
                        <input type="text" name="ssc_school_name" class="form-control @error('ssc_school_name') is-invalid @enderror" value="{{ old('ssc_school_name', $application->ssc_school_name) }}" required>
                        @error('ssc_school_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Total Marks <span class="text-danger">*</span></label>
                        <input type="number" id="ssc_total_marks" name="ssc_total_marks" class="form-control @error('ssc_total_marks') is-invalid @enderror" value="{{ old('ssc_total_marks', $application->ssc_total_marks) }}" required min="1">
                        @error('ssc_total_marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Obtained Marks <span class="text-danger">*</span></label>
                        <input type="number" id="ssc_obtained_marks" name="ssc_obtained_marks" class="form-control @error('ssc_obtained_marks') is-invalid @enderror" value="{{ old('ssc_obtained_marks', $application->ssc_obtained_marks) }}" required min="0">
                        @error('ssc_obtained_marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Passing Year <span class="text-danger">*</span></label>
                        <input type="number" name="ssc_passing_year" class="form-control @error('ssc_passing_year') is-invalid @enderror" value="{{ old('ssc_passing_year', $application->ssc_passing_year) }}" required min="{{ date('Y') - 10 }}" max="{{ date('Y') }}">
                        @error('ssc_passing_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Passing Session <span class="text-danger">*</span></label>
                        <select name="ssc_passing_session" class="form-select @error('ssc_passing_session') is-invalid @enderror" required>
                            <option value="1st Annual" {{ old('ssc_passing_session', $application->ssc_passing_session) == '1st Annual' ? 'selected' : '' }}>1st Annual</option>
                            <option value="2nd Annual" {{ old('ssc_passing_session', $application->ssc_passing_session) == '2nd Annual' ? 'selected' : '' }}>2nd Annual</option>
                        </select>
                        @error('ssc_passing_session')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SSC Group <span class="text-danger">*</span></label>
                        <select name="ssc_group" class="form-select @error('ssc_group') is-invalid @enderror" required>
                            <option value="Science" {{ old('ssc_group', $application->ssc_group) == 'Science' ? 'selected' : '' }}>Science</option>
                            <option value="Arts" {{ old('ssc_group', $application->ssc_group) == 'Arts' ? 'selected' : '' }}>Arts</option>
                        </select>
                        @error('ssc_group')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Admission Preferences --}}
        <div class="card mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle"><i class="fas fa-book fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">Admission Preferences</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label d-block fw-bold mb-3">Choose Subject Group <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($subjectGroups as $group)
                                <div class="form-check card p-3 border-0 shadow-sm bg-light flex-grow-1" style="min-width: 200px;">
                                    <input class="form-check-input group-radio" type="radio" name="subject_group_id" id="group_{{ $group->id }}" value="{{ $group->id }}" data-subjects='@json($group->subjects)' required {{ old('subject_group_id', $application->subject_group_id) == $group->id ? 'checked' : '' }}>
                                    <label class="form-check-label ps-2" for="group_{{ $group->id }}">
                                        <div class="fw-bold">{{ $group->name }}</div>
                                        <div class="small text-muted">{{ $group->description }}</div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Admission Year <span class="text-danger">*</span></label>
                        <input type="number" name="admission_year" class="form-control @error('admission_year') is-invalid @enderror" value="{{ old('admission_year', $application->admission_year) }}" required>
                        @error('admission_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Elective Subject 1 <span class="text-danger">*</span></label>
                        <select name="elective_subject_1" id="elective_subject_1" class="form-select elective-select @error('elective_subject_1') is-invalid @enderror" required>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}" {{ old('elective_subject_1', $application->elective_subject_1) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('elective_subject_1')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Elective Subject 2 <span class="text-danger">*</span></label>
                        <select name="elective_subject_2" id="elective_subject_2" class="form-select elective-select @error('elective_subject_2') is-invalid @enderror" required>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}" {{ old('elective_subject_2', $application->elective_subject_2) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('elective_subject_2')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Elective Subject 3 <span class="text-danger">*</span></label>
                        <select name="elective_subject_3" id="elective_subject_3" class="form-select elective-select @error('elective_subject_3') is-invalid @enderror" required>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}" {{ old('elective_subject_3', $application->elective_subject_3) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('elective_subject_3')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-warning btn-lg px-5 shadow-sm">
                <i class="fas fa-save me-1"></i> Update Application
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
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
                    data.subjects.forEach(subject => {
                        const span = document.createElement('span');
                        span.className = 'badge bg-white text-primary border shadow-sm px-3 py-2';
                        span.innerHTML = `<i class="fas fa-check-circle text-success me-1"></i> ${subject}`;
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

    // CNIC Input Masking
    const cnicInputs = document.querySelectorAll('.cnic-mask');
    cnicInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let val = e.target.value.replace(/\D/g, '');
            let x = val.match(/(\d{0,5})(\d{0,7})(\d{0,1})/);
            e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    });

    // Phone Input Masking
    const phoneInputs = document.querySelectorAll('.phone-mask');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let val = e.target.value.replace(/\D/g, '');
            if(val.length > 0 && val[0] !== '0') {
                val = '0' + val; // Force leading zero
            }
            if(val.length > 1 && val[1] !== '3') {
                val = '03' + val.substring(2); // Force 03
            }
            let x = val.match(/(\d{0,4})(\d{0,7})/);
            e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2];
        });
    });

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
});
</script>
@endsection
