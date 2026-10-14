@extends('layouts.public')
@section('title', 'Apply Online')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10" data-aos="fade-up">
            
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-2">
                    @if(isset($application))
                        Correct Your Application
                    @else
                        {{ $program }} Admission Form
                    @endif
                </h2>
                <p class="text-muted">
                    @if(isset($application))
                        Please correct the information as per the remarks: <strong class="text-danger">{{ $application->review_remarks }}</strong>
                    @else
                        Fill out the application form carefully. All fields marked with an asterisk (<span class="text-danger">*</span>) are required.
                    @endif
                </p>
            </div>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-body p-4 p-md-5">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <strong><i class="fas fa-exclamation-circle me-2"></i>Please check the form below for errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ isset($application) ? route('apply.update', $application) : route('apply.store') }}" enctype="multipart/form-data" id="admissionForm">
                        @csrf
                        @if(isset($application))
                            @method('PUT')
                            <input type="hidden" name="search_term" value="{{ $searchTerm }}">
                        @endif
                        <input type="hidden" name="program_type" value="{{ $program }}">
                        
                        {{-- Multi-step Progress Bar --}}
                        <div class="progress mb-4" style="height: 10px;">
                            <div id="formProgress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 50%;"></div>
                        </div>
                        <div class="step-indicators d-flex justify-content-between mb-4">
                            <span class="badge rounded-pill bg-primary" id="badgeStep1">1. Personal &amp; Academic</span>
                            <span class="badge rounded-pill bg-secondary" id="badgeStep2">2. Documents Upload</span>
                        </div>

                        {{-- STEP 1 --}}
                        <div id="step1">
                            {{-- Personal Information --}}
                            <h4 class="text-primary mb-3 border-bottom pb-2"><i class="fas fa-user me-2"></i>Personal Information</h4>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $application->first_name ?? '') }}" required pattern="[a-zA-Z\s]+" title="Only alphabets and spaces are allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $application->last_name ?? '') }}" required pattern="[a-zA-Z\s]+" title="Only alphabets and spaces are allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                    <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', isset($application->date_of_birth) ? $application->date_of_birth->format('Y-m-d') : '') }}" required>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                                        <option value="">Select Gender</option>
                                        @php $gender = old('gender', $application->gender ?? '') @endphp
                                        <option value="Male" {{ $gender == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ $gender == 'Female' ? 'selected' : '' }}>Female</option>
                                        <option value="Other" {{ $gender == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">CNIC / B-Form</label>
                                    <input type="text" name="cnic" class="form-control cnic-mask @error('cnic') is-invalid @enderror" value="{{ old('cnic', $application->cnic ?? '') }}" placeholder="00000-0000000-0" pattern="\d{5}-\d{7}-\d" title="Format: 00000-0000000-0" maxlength="15">
                                    <small class="text-muted">Optional</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Applicant Picture <span class="text-danger">*</span></label>
                                    <input type="file" name="picture" class="form-control @error('picture') is-invalid @enderror" accept="image/*" {{ isset($application) ? '' : 'required' }}>
                                    <small class="text-muted">Passport size. JPG/PNG. {{ isset($application) ? 'Leave blank to keep current.' : '' }}</small>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Religion <span class="text-danger">*</span></label>
                                    <select name="religion" id="religion_select" class="form-select @error('religion') is-invalid @enderror" required>
                                        @php $religion = old('religion', $application->religion ?? 'Islam') @endphp
                                        <option value="Islam" {{ $religion == 'Islam' ? 'selected' : '' }}>Islam</option>
                                        <option value="Christianity" {{ $religion == 'Christianity' ? 'selected' : '' }}>Christianity</option>
                                        <option value="Hinduism" {{ $religion == 'Hinduism' ? 'selected' : '' }}>Hinduism</option>
                                        <option value="Other" {{ $religion == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nationality <span class="text-danger">*</span></label>
                                    <select name="nationality" id="nationality_select" class="form-select @error('nationality') is-invalid @enderror" required>
                                        @php $nat = old('nationality', $application->nationality ?? 'Pakistani') @endphp
                                        <option value="Pakistani" {{ $nat == 'Pakistani' ? 'selected' : '' }}>Pakistani</option>
                                        <option value="Non-Pakistani" {{ $nat == 'Non-Pakistani' ? 'selected' : '' }}>Foreign National</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Domicile <span class="text-danger">*</span></label>
                                    <input type="text" name="domicile" class="form-control @error('domicile') is-invalid @enderror" value="{{ old('domicile', $application->domicile ?? '') }}" required placeholder="e.g. Lahore">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Blood Group</label>
                                    <select name="blood_group_id" class="form-select">
                                        <option value="">Select</option>
                                        @php $bgId = old('blood_group_id', $application->blood_group_id ?? '') @endphp
                                        @foreach($bloodGroups as $bg)
                                            <option value="{{ $bg->id }}" {{ $bgId == $bg->id ? 'selected' : '' }}>{{ $bg->code ?? $bg->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Admission Quota</label>
                                    <select name="quota_id" class="form-select">
                                        <option value="">Open Merit</option>
                                        @php $qId = old('quota_id', $application->quota_id ?? '') @endphp
                                        @foreach($quotas as $q)
                                            <option value="{{ $q->id }}" {{ $qId == $q->id ? 'selected' : '' }}>{{ $q->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- ADP/BS: Program Selection --}}
                            @if(in_array($program, ['ADP', 'BS']))
                            <div class="bg-light p-4 rounded-3 border mb-4">
                                <h4 class="text-primary mb-3"><i class="fas fa-graduation-cap me-2"></i>Program Selection</h4>
                                <p class="text-muted small mb-3">Select the {{ $program }} program you wish to apply for. You will be admitted into Semester 1 of the selected program.</p>
                                <div class="row g-3">
                                    @php $selectedProgramId = old('program_id', $application->program_id ?? '') @endphp
                                    @forelse($programs as $prog)
                                        <div class="col-md-4">
                                            <div class="form-check card h-100 p-3 border shadow-sm bg-white">
                                                <input class="form-check-input" type="radio" name="program_id"
                                                    id="prog_{{ $prog->id }}" value="{{ $prog->id }}" required
                                                    {{ $selectedProgramId == $prog->id ? 'checked' : '' }}>
                                                <label class="form-check-label ps-2" for="prog_{{ $prog->id }}">
                                                    <div class="fw-bold">{{ $prog->name }}</div>
                                                    <div class="small text-muted">{{ $prog->type }} &bull; {{ $prog->total_semesters }} Semesters</div>
                                                </label>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12">
                                            <div class="alert alert-warning">No {{ $program }} programs are currently available. Please contact the office.</div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                            @else
                            {{-- Inter: Dynamic Compulsory Preview --}}
                            <div class="alert alert-primary mb-4 border-0 shadow-sm" id="compulsory_preview_box">
                                <h6 class="fw-bold mb-2"><i class="fas fa-info-circle me-2"></i>Assigned Compulsory Subjects</h6>
                                <p class="small mb-2 text-muted">Based on your religion and nationality, the following subjects will be auto-assigned:</p>
                                <div id="compulsory_subjects_list" class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-white text-primary border shadow-sm px-3 py-2">Loading defaults...</span>
                                </div>
                            </div>
                            @endif

                            {{-- Father / Guardian Information --}}
                            <h4 class="text-primary mb-3 border-bottom pb-2"><i class="fas fa-male me-2"></i>Father / Guardian Information</h4>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Father's Name <span class="text-danger">*</span></label>
                                    <input type="text" name="father_name" class="form-control @error('father_name') is-invalid @enderror" value="{{ old('father_name', $application->father_name ?? '') }}" required pattern="[a-zA-Z\s]+" title="Only alphabets and spaces are allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Father's CNIC</label>
                                    <input type="text" name="father_cnic" class="form-control cnic-mask @error('father_cnic') is-invalid @enderror" value="{{ old('father_cnic', $application->father_cnic ?? '') }}" placeholder="00000-0000000-0" pattern="\d{5}-\d{7}-\d" title="Format: 00000-0000000-0" maxlength="15">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Father's Contact No.</label>
                                    <input type="text" name="father_contact" class="form-control phone-mask @error('father_contact') is-invalid @enderror" value="{{ old('father_contact', $application->father_contact ?? '') }}" placeholder="0300-0000000" pattern="03\d{2}-\d{7}" title="Format: 03XX-XXXXXXX" maxlength="12">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Occupation</label>
                                    <input type="text" name="father_occupation" class="form-control @error('father_occupation') is-invalid @enderror" value="{{ old('father_occupation', $application->father_occupation ?? '') }}" pattern="[a-zA-Z\s]*" title="Only alphabets and spaces are allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Annual Income (PKR)</label>
                                    <input type="number" name="father_annual_income" class="form-control @error('father_annual_income') is-invalid @enderror" value="{{ old('father_annual_income', $application->father_annual_income ?? '') }}" min="0">
                                </div>
                            </div>

                            {{-- Contact Details --}}
                            <h4 class="text-primary mb-3 border-bottom pb-2"><i class="fas fa-address-book me-2"></i>Contact Details</h4>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Your Contact No. <span class="text-danger">*</span></label>
                                    <input type="text" name="student_contact" class="form-control phone-mask @error('student_contact') is-invalid @enderror" value="{{ old('student_contact', $application->student_contact ?? '') }}" required placeholder="0300-0000000" pattern="03\d{2}-\d{7}" title="Format: 03XX-XXXXXXX" maxlength="12">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">WhatsApp No.</label>
                                    <input type="text" name="whatsapp_no" class="form-control phone-mask @error('whatsapp_no') is-invalid @enderror" value="{{ old('whatsapp_no', $application->whatsapp_no ?? '') }}" placeholder="0300-0000000" pattern="03\d{2}-\d{7}" title="Format: 03XX-XXXXXXX" maxlength="12">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email', $application->email ?? '') }}" placeholder="student@example.com">
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Permanent Address <span class="text-danger">*</span></label>
                                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" required>{{ old('address', $application->address ?? '') }}</textarea>
                                </div>
                            </div>

                            {{-- SSC Academic Record --}}
                            <h4 class="text-primary mb-3 border-bottom pb-2"><i class="fas fa-school me-2"></i>SSC (Matric) Academic Record</h4>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Board / BISE <span class="text-danger">*</span></label>
                                    <select name="bise_id" class="form-select @error('bise_id') is-invalid @enderror" required>
                                        <option value="">Select Board</option>
                                        @php $biseId = old('bise_id', $application->bise_id ?? '') @endphp
                                        @foreach($bises as $bise)
                                            <option value="{{ $bise->id }}" {{ $biseId == $bise->id ? 'selected' : '' }}>{{ $bise->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">School Name <span class="text-danger">*</span></label>
                                    <input type="text" name="ssc_school_name" class="form-control @error('ssc_school_name') is-invalid @enderror" value="{{ old('ssc_school_name', $application->ssc_school_name ?? '') }}" required placeholder="Govt. High School...">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Roll Number <span class="text-danger">*</span></label>
                                    <input type="number" name="ssc_roll_no" class="form-control @error('ssc_roll_no') is-invalid @enderror" value="{{ old('ssc_roll_no', $application->ssc_roll_no ?? '') }}" required min="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Registration Number</label>
                                    <input type="text" name="ssc_reg_no" class="form-control" value="{{ old('ssc_reg_no', $application->ssc_reg_no ?? '') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Total Marks <span class="text-danger">*</span></label>
                                    <input type="number" id="ssc_total_marks" name="ssc_total_marks" class="form-control @error('ssc_total_marks') is-invalid @enderror" value="{{ old('ssc_total_marks', $application->ssc_total_marks ?? 1100) }}" required min="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Obtained Marks <span class="text-danger">*</span></label>
                                    <input type="number" id="ssc_obtained_marks" name="ssc_obtained_marks" class="form-control @error('ssc_obtained_marks') is-invalid @enderror" value="{{ old('ssc_obtained_marks', $application->ssc_obtained_marks ?? '') }}" required min="0">
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Passing Year <span class="text-danger">*</span></label>
                                    <input type="number" name="ssc_passing_year" class="form-control @error('ssc_passing_year') is-invalid @enderror" value="{{ old('ssc_passing_year', $application->ssc_passing_year ?? date('Y')) }}" required min="{{ date('Y') - 10 }}" max="{{ date('Y') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Session <span class="text-danger">*</span></label>
                                    <select name="ssc_passing_session" class="form-select @error('ssc_passing_session') is-invalid @enderror" required>
                                        @php $session = old('ssc_passing_session', $application->ssc_passing_session ?? '') @endphp
                                        <option value="1st Annual" {{ $session == '1st Annual' ? 'selected' : '' }}>1st Annual / Spring</option>
                                        <option value="2nd Annual" {{ $session == '2nd Annual' ? 'selected' : '' }}>2nd Annual / Autumn</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Matric Group <span class="text-danger">*</span></label>
                                    <select name="ssc_group" class="form-select @error('ssc_group') is-invalid @enderror" required>
                                        @php $group = old('ssc_group', $application->ssc_group ?? '') @endphp
                                        <option value="Science" {{ $group == 'Science' ? 'selected' : '' }}>Science</option>
                                        <option value="Arts" {{ $group == 'Arts' ? 'selected' : '' }}>Arts / Humanities</option>
                                        <option value="Computer Science" {{ $group == 'Computer Science' ? 'selected' : '' }}>Computer Science</option>
                                    </select>
                                </div>
                            </div>

                            {{-- HSSC Record (ADP/BS only) --}}
                            @if(in_array($program, ['ADP', 'BS']))
                            <h4 class="text-primary mb-3 border-bottom pb-2"><i class="fas fa-university me-2"></i>HSSC (Intermediate) Academic Record</h4>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Board / BISE <span class="text-danger">*</span></label>
                                    <select name="hssc_bise_id" class="form-select @error('hssc_bise_id') is-invalid @enderror" required>
                                        <option value="">Select Board</option>
                                        @php $hBiseId = old('hssc_bise_id', $application->hssc_bise_id ?? '') @endphp
                                        @foreach($bises as $bise)
                                            <option value="{{ $bise->id }}" {{ $hBiseId == $bise->id ? 'selected' : '' }}>{{ $bise->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">College / Institution Name <span class="text-danger">*</span></label>
                                    <input type="text" name="hssc_college_name" class="form-control @error('hssc_college_name') is-invalid @enderror" value="{{ old('hssc_college_name', $application->hssc_college_name ?? '') }}" required placeholder="Govt. Degree College...">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Roll Number <span class="text-danger">*</span></label>
                                    <input type="number" name="hssc_roll_no" class="form-control @error('hssc_roll_no') is-invalid @enderror" value="{{ old('hssc_roll_no', $application->hssc_roll_no ?? '') }}" required min="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Registration Number <span class="text-danger">*</span></label>
                                    <input type="text" name="hssc_reg_no" class="form-control @error('hssc_reg_no') is-invalid @enderror" value="{{ old('hssc_reg_no', $application->hssc_reg_no ?? '') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Total Marks <span class="text-danger">*</span></label>
                                    <input type="number" name="hssc_total_marks" class="form-control @error('hssc_total_marks') is-invalid @enderror" value="{{ old('hssc_total_marks', $application->hssc_total_marks ?? 1100) }}" required min="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Obtained Marks <span class="text-danger">*</span></label>
                                    <input type="number" name="hssc_obtained_marks" class="form-control @error('hssc_obtained_marks') is-invalid @enderror" value="{{ old('hssc_obtained_marks', $application->hssc_obtained_marks ?? '') }}" required min="0">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Passing Year <span class="text-danger">*</span></label>
                                    <input type="number" name="hssc_passing_year" class="form-control @error('hssc_passing_year') is-invalid @enderror" value="{{ old('hssc_passing_year', $application->hssc_passing_year ?? date('Y')) }}" required min="{{ date('Y') - 10 }}" max="{{ date('Y') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Session <span class="text-danger">*</span></label>
                                    <select name="hssc_passing_session" class="form-select @error('hssc_passing_session') is-invalid @enderror" required>
                                        @php $hSession = old('hssc_passing_session', $application->hssc_passing_session ?? '') @endphp
                                        <option value="1st Annual" {{ $hSession == '1st Annual' ? 'selected' : '' }}>1st Annual / Spring</option>
                                        <option value="2nd Annual" {{ $hSession == '2nd Annual' ? 'selected' : '' }}>2nd Annual / Autumn</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Intermediate Group <span class="text-danger">*</span></label>
                                    <select name="hssc_group" class="form-select @error('hssc_group') is-invalid @enderror" required>
                                        <option value="">Select Group</option>
                                        @php $hGroup = old('hssc_group', $application->hssc_group ?? '') @endphp
                                        @foreach($hsscGroups as $grpName)
                                            <option value="{{ $grpName }}" {{ $hGroup == $grpName ? 'selected' : '' }}>{{ $grpName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @else
                            {{-- Inter: Elective Subject Selection --}}
                            <div class="bg-light p-4 rounded-3 border mb-4">
                                <h4 class="text-primary mb-3"><i class="fas fa-book me-2"></i>Admission Choice ({{ $program }})</h4>
                                <p class="text-muted small mb-3">Please select your preferred subject group and choose three elective subjects.</p>
                                
                                <div class="row g-3">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label d-block fw-bold mb-3">Choose Subject Group <span class="text-danger">*</span></label>
                                        <div class="row g-3">
                                            @foreach($subjectGroups as $group)
                                                <div class="col-md-4">
                                                    <div class="form-check card h-100 p-3 border shadow-sm bg-white">
                                                        <input class="form-check-input group-radio" type="radio" name="subject_group_id" id="group_{{ $group->id }}" value="{{ $group->id }}" data-subjects='@json($group->subjects)' required {{ old('subject_group_id', $application->subject_group_id ?? '') == $group->id ? 'checked' : '' }}>
                                                        <label class="form-check-label ps-2" for="group_{{ $group->id }}">
                                                            <div class="fw-bold">{{ $group->name }}</div>
                                                            <div class="small text-muted">{{ $group->description }}</div>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Elective Subject 1 <span class="text-danger">*</span></label>
                                        <select name="elective_subject_1" id="elective_subject_1" class="form-select elective-select @error('elective_subject_1') is-invalid @enderror" required>
                                            <option value="">Choose Subject...</option>
                                            @php $s1 = old('elective_subject_1', $application->elective_subject_1 ?? '') @endphp
                                            @foreach($subjects as $s)
                                                <option value="{{ $s->id }}" {{ $s1 == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Elective Subject 2 <span class="text-danger">*</span></label>
                                        <select name="elective_subject_2" id="elective_subject_2" class="form-select elective-select @error('elective_subject_2') is-invalid @enderror" required>
                                            <option value="">Choose Subject...</option>
                                            @php $s2 = old('elective_subject_2', $application->elective_subject_2 ?? '') @endphp
                                            @foreach($subjects as $s)
                                                <option value="{{ $s->id }}" {{ $s2 == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Elective Subject 3 <span class="text-danger">*</span></label>
                                        <select name="elective_subject_3" id="elective_subject_3" class="form-select elective-select @error('elective_subject_3') is-invalid @enderror" required>
                                            <option value="">Choose Subject...</option>
                                            @php $s3 = old('elective_subject_3', $application->elective_subject_3 ?? '') @endphp
                                            @foreach($subjects as $s)
                                                <option value="{{ $s->id }}" {{ $s3 == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="text-end">
                                <button type="button" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold" id="btnNext1">
                                    Next Step <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>

                        {{-- STEP 2 --}}
                        <div id="step2" style="display: none;">
                            <h4 class="text-primary mb-3 border-bottom pb-2"><i class="fas fa-file-upload me-2"></i>Document Uploads</h4>
                            <p class="text-muted mb-4">Please upload clear scanned copies or photos of the following documents. Formats: JPG, PNG. Max: 5MB per file.</p>
                            
                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <div class="card h-100 border-dashed p-3">
                                        <label class="form-label fw-bold">SSC (Matric) Result Card <span class="text-danger">*</span></label>
                                        <input type="file" name="ssc_result_card" class="form-control @error('ssc_result_card') is-invalid @enderror" accept="image/*" {{ isset($application) ? '' : 'required' }}>
                                        <small class="text-muted mt-1">Upload the front side clearly showing marks and roll number. {{ isset($application) ? 'Leave blank to keep current.' : '' }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100 border-dashed p-3">
                                        <label class="form-label fw-bold">Applicant's B-Form or CNIC <span class="text-danger">*</span></label>
                                        <input type="file" name="b_form_pic" class="form-control @error('b_form_pic') is-invalid @enderror" accept="image/*" {{ isset($application) ? '' : 'required' }}>
                                        <small class="text-muted mt-1">Upload full front view of B-Form or CNIC. {{ isset($application) ? 'Leave blank to keep current.' : '' }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100 border-dashed p-3">
                                        <label class="form-label fw-bold">Father's CNIC <span class="text-danger">*</span></label>
                                        <input type="file" name="father_cnic_pic" class="form-control @error('father_cnic_pic') is-invalid @enderror" accept="image/*" {{ isset($application) ? '' : 'required' }}>
                                        <small class="text-muted mt-1">Upload front side of father's / guardian's CNIC. {{ isset($application) ? 'Leave blank to keep current.' : '' }}</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Declaration --}}
                            <div class="form-check mb-4 p-3 bg-light rounded shadow-sm border">
                                <input class="form-check-input ms-0 me-3" type="checkbox" name="declaration" id="declaration" required>
                                <label class="form-check-label text-muted small" for="declaration">
                                    I solemnly declare that all information and documents provided above are correct and authentic to the best of my knowledge. I agree to abide by the rules and regulations of Govt. Graduate College CTN. I understand that misrepresentation of facts or providing fake documents will lead to immediate cancellation of my admission.
                                </label>
                            </div>

                            <div class="d-flex justify-content-between gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-lg rounded-pill px-4 fw-bold" id="btnBack1">
                                    <i class="fas fa-arrow-left me-2"></i> Back
                                </button>
                                <button type="submit" class="btn btn-success btn-lg rounded-pill px-5 fw-bold" onclick="return confirm('{{ isset($application) ? 'Are you sure you want to resubmit this application?' : 'Are you sure you want to submit this application? You will not be able to edit it later.' }}');">
                                    <i class="fas fa-check-circle me-2"></i> {{ isset($application) ? 'Update & Resubmit' : 'Submit Application' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>

<style>
.border-dashed { border-style: dashed !important; border-width: 2px !important; }
#step1, #step2 { transition: all 0.3s ease-in-out; }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isHe = {{ in_array($program, ['ADP', 'BS']) ? 'true' : 'false' }};

    // Multi-step Navigation
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const btnNext1 = document.getElementById('btnNext1');
    const btnBack1 = document.getElementById('btnBack1');
    const progress = document.getElementById('formProgress');
    const badgeStep1 = document.getElementById('badgeStep1');
    const badgeStep2 = document.getElementById('badgeStep2');

    btnNext1.addEventListener('click', function() {
        const inputs = step1.querySelectorAll('input[required], select[required], textarea[required], input[pattern]');
        let valid = true;
        const validatedNames = new Set();
        let firstInvalid = null;

        inputs.forEach(input => {
            let isItemValid = true;
            if (input.type === 'radio' || input.type === 'checkbox') {
                if (validatedNames.has(input.name)) return;
                
                const group = step1.querySelectorAll(`input[name="${input.name}"]`);
                const isChecked = Array.from(group).some(i => i.checked);
                
                if (input.hasAttribute('required') && !isChecked) {
                    isItemValid = false;
                    group.forEach(i => i.classList.add('is-invalid'));
                } else {
                    group.forEach(i => i.classList.remove('is-invalid'));
                }
                validatedNames.add(input.name);
            } else {
                // Check for required AND other HTML5 constraints (pattern, min, max, etc.)
                if (!input.checkValidity()) {
                    input.classList.add('is-invalid');
                    isItemValid = false;
                    
                    // Show validation message in tooltip or similar if needed, 
                    // but for now is-invalid and the alert are enough.
                } else {
                    input.classList.remove('is-invalid');
                }
            }

            if (!isItemValid) {
                valid = false;
                if (!firstInvalid) firstInvalid = input;
            }
        });

        if (valid) {
            step1.style.display = 'none';
            step2.style.display = 'block';
            progress.style.width = '100%';
            badgeStep2.classList.replace('bg-secondary', 'bg-primary');
            window.scrollTo(0, 0);
        } else {
            if (firstInvalid) {
                firstInvalid.focus();
                if (firstInvalid.reportValidity) firstInvalid.reportValidity();
            }
            alert('Please correct the highlighted errors in Step 1 before proceeding.');
        }
    });

    btnBack1.addEventListener('click', function() {
        step2.style.display = 'none';
        step1.style.display = 'block';
        progress.style.width = '50%';
        badgeStep2.classList.replace('bg-primary', 'bg-secondary');
        window.scrollTo(0, 0);
    });

    // Dynamic Compulsory Subjects Preview (Inter only)
    if (!isHe) {
        const religion_select = document.getElementById('religion_select');
        const nationality_select = document.getElementById('nationality_select');
        const compulsory_list = document.getElementById('compulsory_subjects_list');

        function fetchCompulsorySubjects() {
            const religion = religion_select.value;
            const nationality = nationality_select.value;
            
            compulsory_list.innerHTML = '<span class="text-muted small italic">Updating...</span>';

            fetch(`{{ route('apply.compulsory-subjects') }}?religion=${religion}&nationality=${nationality}&program={{ $program }}`)
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

        if (religion_select && nationality_select) {
            religion_select.addEventListener('change', fetchCompulsorySubjects);
            nationality_select.addEventListener('change', fetchCompulsorySubjects);
            fetchCompulsorySubjects();
        }
    }

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
            if(val.length > 0 && val[0] !== '0') { val = '0' + val; }
            if(val.length > 1 && val[1] !== '3') { val = '03' + val.substring(2); }
            let x = val.match(/(\d{0,4})(\d{0,7})/);
            e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2];
        });
    });

    // Elective subject cascade (Inter only)
    if (!isHe) {
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

                select.innerHTML = select.dataset.originalOptions;

                // Check if there are any subjects specifically fixed for this slot
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

                Array.from(select.options).forEach(option => {
                    if (option.value && !allowedIdsForThisSlot.includes(option.value)) {
                        option.remove();
                    }
                });

                if (mandatorySubjects[index]) {
                    const mandatoryId = mandatorySubjects[index].id.toString();
                    select.value = mandatoryId;
                    select.setAttribute('readonly', true);
                    select.style.pointerEvents = 'none';
                    select.style.backgroundColor = '#f8f9fa'; 
                    select.style.border = '1px solid #ced4da';
                } else {
                    select.removeAttribute('readonly');
                    select.style.pointerEvents = 'auto';
                    select.style.backgroundColor = '#fff';

                    const otherSelections = Array.from(electiveSelects)
                        .filter((s, i) => i !== index && s.value)
                        .map(s => s.value);

                    Array.from(select.options).forEach(option => {
                        const optVal = option.value;
                        if (optVal && otherSelections.includes(optVal)) {
                            option.remove();
                        }
                    });

                    const stillValid = Array.from(select.options).some(o => o.value === currentValue);
                    select.value = stillValid ? currentValue : '';
                }
            });
        }

        groupRadios.forEach(radio => radio.addEventListener('change', applySubjectRules));
        electiveSelects.forEach(select => select.addEventListener('change', applySubjectRules));
        
        if (document.querySelector('.group-radio:checked')) {
            applySubjectRules();
        }
    }

    // Auto-switch to Step 2 if there are errors related to Step 2 fields
    @if($errors->has('ssc_result_card') || $errors->has('b_form_pic') || $errors->has('father_cnic_pic') || $errors->has('declaration'))
        step1.style.display = 'none';
        step2.style.display = 'block';
        progress.style.width = '100%';
        badgeStep2.classList.replace('bg-secondary', 'bg-primary');
    @endif
});
</script>

<script src="{{ asset('js/face-api.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Photo Validation Logic
    const imageInput = document.querySelector('input[name="picture"]');
    let modelsLoaded = false;

    async function loadModels() {
        try {
            await faceapi.loadTinyFaceDetectorModel('{{ asset("models") }}');
            modelsLoaded = true;
            console.log("FaceAPI models loaded");
        } catch (err) {
            console.error("Failed to load models", err);
        }
    }
    
    if (typeof faceapi !== 'undefined') {
        loadModels();
    }

    if (imageInput) {
        imageInput.addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const submitBtn = $(imageInput).closest('form').find('button[type="submit"]');
            const originalBtnHtml = submitBtn.html();
            
            const img = document.createElement('img');
            const objectUrl = URL.createObjectURL(file);
            img.src = objectUrl;

            img.onload = async () => {
                try {
                    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Validating Photo...');
                    
                    if (!isBlueBackground(img)) {
                        alert("Error: Image must have a BLUE background.");
                        imageInput.value = '';
                        submitBtn.prop('disabled', false).html(originalBtnHtml);
                        return;
                    }

                    if (modelsLoaded) {
                        const detections = await faceapi.detectAllFaces(img, new faceapi.TinyFaceDetectorOptions());
                        
                        if (detections.length === 0) {
                            alert("Error: No face detected. Please upload a clear photo of a human face.");
                            imageInput.value = '';
                        } else if (detections.length > 1) {
                            alert("Error: Multiple faces detected. Please upload a photo of a single person.");
                            imageInput.value = '';
                        } else if (detections[0].score < 0.5) {
                            alert("Error: Face is not clearly visible. Please upload a better quality photo.");
                            imageInput.value = '';
                        }
                    } else {
                        console.warn("Face detection models not loaded. Skipping advanced face validation.");
                    }
                } catch (err) {
                    console.error("Validation error:", err);
                } finally {
                    submitBtn.prop('disabled', false).html(originalBtnHtml);
                    URL.revokeObjectURL(objectUrl);
                }
            };
        });
    }

    function isBlueBackground(img) {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);
        const sampleHeight = Math.floor(img.height * 0.3);
        if (sampleHeight <= 0) return true; 
        const imageData = ctx.getImageData(0, 0, img.width, sampleHeight);
        const data = imageData.data;
        let bluePixelCount = 0;
        let totalPixels = 0;
        for (let i = 0; i < data.length; i += 40) {
            const hsv = rgbToHsv(data[i], data[i+1], data[i+2]);
            if (hsv.h >= 180 && hsv.h <= 265 && hsv.s > 0.2 && hsv.v > 0.2) bluePixelCount++;
            totalPixels++;
        }
        return (bluePixelCount / totalPixels) > 0.4; 
    }

    function rgbToHsv(r, g, b) {
        r /= 255, g /= 255, b /= 255;
        const max = Math.max(r, g, b), min = Math.min(r, g, b);
        let h, s, v = max;
        const d = max - min;
        s = max === 0 ? 0 : d / max;
        if (max !== min) {
            switch (max) {
                case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                case g: h = (b - r) / d + 2; break;
                case b: h = (r - g) / d + 4; break;
            }
            h /= 6;
        } else h = 0;
        return { h: h * 360, s: s, v: v };
    }
});
</script>
@endsection

