@extends('layouts.app')
@section('title', 'Edit Staff Member')

@section('content')
<div class="container mt-4">
    <h1>Edit Staff Member</h1>
    
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <form action="{{ route('staff.update', $staff) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Basic Information --}}
        <div class="card mb-3">
            <div class="card-header">Basic Information</div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label>Staff Picture</label>
                        @if($staff->picture)
                            <div class="mb-2">
                                <img src="{{ $staff->getProfilePictureUrl() }}" alt="Current Picture" class="img-thumbnail" style="max-width: 150px;">
                                <p class="text-muted small mb-0">Current Picture</p>
                            </div>
                        @endif
                        <input type="file" name="picture" class="form-control" accept="image/*">
                        <small class="text-muted">Optional - JPG, PNG (Max 2MB). Leave empty to keep current picture.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <label>Staff ID <span class="text-danger">*</span></label>
                        <input type="text" name="staff_id" class="form-control @error('staff_id') is-invalid @enderror" value="{{ old('staff_id', $staff->staff_id) }}" required>
                        @error('staff_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label>First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $staff->first_name) }}" required pattern="[a-zA-Z\s]+" title="Only alphabets and spaces are allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $staff->last_name) }}" pattern="[a-zA-Z\s]*" title="Only alphabets and spaces are allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label>Father Name <span class="text-danger">*</span></label>
                        <input type="text" name="father_name" class="form-control @error('father_name') is-invalid @enderror" value="{{ old('father_name', $staff->father_name) }}" required pattern="[a-zA-Z\s]+" title="Only alphabets and spaces are allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                        @error('father_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label>CNIC <span class="text-danger">*</span></label>
                        <input type="text" name="cnic" class="form-control cnic-mask @error('cnic') is-invalid @enderror" value="{{ old('cnic', $staff->cnic) }}" required placeholder="00000-0000000-0" pattern="\d{5}-\d{7}-\d" title="Format: 00000-0000000-0" maxlength="15">
                        @error('cnic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label>Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $staff->date_of_birth ? $staff->date_of_birth->format('Y-m-d') : '') }}" required>
                        @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label>Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender', $staff->gender) == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $staff->gender) == 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Blood Group</label>
                        <select name="blood_group_id" class="form-control">
                            <option value="">Select Blood Group</option>
                            @foreach($bloodGroups as $bg)
                                <option value="{{ $bg->id }}" {{ old('blood_group_id', $staff->blood_group_id) == $bg->id ? 'selected' : '' }}>
                                    {{ $bg->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact Information --}}
        <div class="card mb-3">
            <div class="card-header">Contact Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" class="form-control phone-mask @error('contact_number') is-invalid @enderror" value="{{ old('contact_number', $staff->contact_number) }}" placeholder="0300-0000000" pattern="03\d{2}-\d{7}" title="Format: 03XX-XXXXXXX" maxlength="12">
                        @error('contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label>WhatsApp Number</label>
                        <input type="text" name="whatsapp_number" class="form-control phone-mask @error('whatsapp_number') is-invalid @enderror" value="{{ old('whatsapp_number', $staff->whatsapp_number) }}" placeholder="0300-0000000" pattern="03\d{2}-\d{7}" title="Format: 03XX-XXXXXXX" maxlength="12">
                        @error('whatsapp_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $staff->email) }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address', $staff->address) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Employment Information --}}
        <div class="card mb-3">
            <div class="card-header">Employment Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label>Category <span class="text-danger">*</span></label>
                        <select name="category" id="category" class="form-control" required>
                            <option value="">Select Category</option>
                            <option value="teaching" {{ old('category', $staff->category) == 'teaching' ? 'selected' : '' }}>Teaching</option>
                            <option value="non_teaching" {{ old('category', $staff->category) == 'non_teaching' ? 'selected' : '' }}>Non-Teaching</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Designation <span class="text-danger">*</span></label>
                        <select name="designation_id" id="designation_id" class="form-control" required>
                            <option value="">Select Designation</option>
                            @foreach($designations as $designation)
                                <option value="{{ $designation->id }}" {{ old('designation_id', $staff->designation_id) == $designation->id ? 'selected' : '' }}>
                                    {{ $designation->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="designation" id="designation_name" value="{{ old('designation', $staff->designation) }}">
                    </div>
                    <div class="col-md-3">
                        <label>BPS <span class="text-danger">*</span></label>
                        <input type="number" name="bps" id="bps" class="form-control" min="1" max="22" value="{{ old('bps', $staff->bps) }}" readonly required>
                    </div>
                    <div class="col-md-3">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="active" {{ old('status', $staff->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="left" {{ old('status', $staff->status) == 'left' ? 'selected' : '' }}>Left</option>
                            <option value="transferred" {{ old('status', $staff->status) == 'transferred' ? 'selected' : '' }}>Transferred</option>
                            <option value="retired" {{ old('status', $staff->status) == 'retired' ? 'selected' : '' }}>Retired</option>
                            <option value="suspended" {{ old('status', $staff->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Highest Qualification</label>
                        <input type="text" name="highest_qualification" class="form-control" value="{{ old('highest_qualification', $staff->highest_qualification) }}">
                    </div>
                    <div class="col-md-4">
                        <label>Entry Date (Govt Service)</label>
                        <input type="date" name="entry_date_govt_service" class="form-control" value="{{ old('entry_date_govt_service', $staff->entry_date_govt_service ? $staff->entry_date_govt_service->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-4">
                        <label>HED Joining Date</label>
                        <input type="date" name="hed_joining_date" class="form-control" value="{{ old('hed_joining_date', $staff->hed_joining_date ? $staff->hed_joining_date->format('Y-m-d') : '') }}">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Current College Joining Date</label>
                        <input type="date" name="current_college_joining_date" class="form-control" value="{{ old('current_college_joining_date', $staff->current_college_joining_date ? $staff->current_college_joining_date->format('Y-m-d') : '') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Assignment Management (Teaching Only) --}}
        <div class="card mb-3" id="assignmentLinksCard" style="{{ old('category', $staff->category) == 'teaching' ? '' : 'display: none;' }}">
            <div class="card-header bg-primary text-white">Student Assignment Management</div>
            <div class="card-body">
                <p class="text-muted small">Define which students this teacher is currently teaching. Note: Qualifications (Specializations) must be set below first.</p>
                <div class="d-flex gap-2">
                    <a href="{{ route('staff.student_assignment') }}" class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-users-cog me-1"></i> Manage HSSC Assignments
                    </a>
                    <a href="{{ route('he.teacher_assignment') }}" class="btn btn-outline-info" target="_blank">
                        <i class="fas fa-graduation-cap me-1"></i> Manage BS/ADP Assignments
                    </a>
                </div>
            </div>
        </div>

        {{-- HSSC Specializations (Teaching Only) --}}
        <div class="card mb-3" id="subjectSpecializationsCard" style="{{ old('category', $staff->category) == 'teaching' ? '' : 'display: none;' }}">
            <div class="card-header">HSSC Subject Specializations</div>
            <div class="card-body">
                <div class="row">
                    @foreach($subjects as $subject)
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="subject_specializations[]" value="{{ $subject->id }}" id="subject_{{ $subject->id }}" {{ in_array($subject->id, old('subject_specializations', $currentSubjects)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="subject_{{ $subject->id }}">
                                    {{ $subject->name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- BS Specializations (Teaching Only) --}}
        <div class="card mb-3" id="bsSubjectSpecializationsCard" style="{{ old('category', $staff->category) == 'teaching' ? '' : 'display: none;' }}">
            <div class="card-header">BS/ADP Subject Specializations</div>
            <div class="card-body">
                <div class="row">
                    @foreach($bsSubjects as $bsSubject)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="bs_subject_specializations[]" value="{{ $bsSubject->id }}" id="bs_subject_{{ $bsSubject->id }}" {{ in_array($bsSubject->id, old('bs_subject_specializations', $currentBsSubjects)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="bs_subject_{{ $bsSubject->id }}">
                                    {{ $bsSubject->name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Academic Qualifications --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white">
                <span>Academic Qualifications</span>
                <button type="button" class="btn btn-sm btn-light" onclick="addRow('qualifications-table')">
                    <i class="fas fa-plus me-1"></i>Add Qualification
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm" id="qualifications-table">
                        <thead>
                            <tr>
                                <th>Level</th>
                                <th>Subject</th>
                                <th>Board/University</th>
                                <th>Total</th>
                                <th>Obtained</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(old('qualifications', $staff->academicQualifications) as $index => $qual)
                                <tr>
                                    <td>
                                        <select name="qualifications[{{ $index }}][degree_level]" class="form-select form-select-sm">
                                            @foreach(['ssc' => 'SSC', 'hssc' => 'HSSC', 'bachelor' => 'Bachelor', 'master' => 'Master', 'bs' => 'BS', 'mphil' => 'MPhil', 'phd' => 'PhD'] as $val => $label)
                                                <option value="{{ $val }}" {{ (isset($qual['degree_level']) ? $qual['degree_level'] : '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="qualifications[{{ $index }}][subject]" class="form-control form-control-sm" value="{{ $qual['subject'] ?? '' }}"></td>
                                    <td><input type="text" name="qualifications[{{ $index }}][group_field]" class="form-control form-control-sm" value="{{ $qual['group_field'] ?? '' }}"></td>
                                    <td><input type="number" name="qualifications[{{ $index }}][total_marks]" class="form-control form-control-sm" value="{{ $qual['total_marks'] ?? '' }}"></td>
                                    <td><input type="number" name="qualifications[{{ $index }}][obtained_marks]" class="form-control form-control-sm" value="{{ $qual['obtained_marks'] ?? '' }}"></td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Promotion History --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
                <span>Promotion History</span>
                <button type="button" class="btn btn-sm btn-light" onclick="addRow('promotions-table')">
                    <i class="fas fa-plus me-1"></i>Add Promotion
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm" id="promotions-table">
                        <thead>
                            <tr>
                                <th>Prev. Designation</th>
                                <th>Prev. BPS</th>
                                <th>New Designation</th>
                                <th>New BPS</th>
                                <th>Date</th>
                                <th>Order#</th>
                                <th>Order Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(old('promotions', $staff->promotionHistory) as $index => $promo)
                                <tr>
                                    <td><input type="text" name="promotions[{{ $index }}][previous_designation]" class="form-control form-control-sm" value="{{ $promo['previous_designation'] ?? '' }}"></td>
                                    <td><input type="number" name="promotions[{{ $index }}][previous_bps]" class="form-control form-control-sm" value="{{ $promo['previous_bps'] ?? '' }}"></td>
                                    <td><input type="text" name="promotions[{{ $index }}][new_designation]" class="form-control form-control-sm" value="{{ $promo['new_designation'] ?? '' }}"></td>
                                    <td><input type="number" name="promotions[{{ $index }}][new_bps]" class="form-control form-control-sm" value="{{ $promo['new_bps'] ?? '' }}"></td>
                                    <td><input type="date" name="promotions[{{ $index }}][promotion_date]" class="form-control form-control-sm" value="{{ isset($promo['promotion_date']) ? (is_string($promo['promotion_date']) ? $promo['promotion_date'] : $promo['promotion_date']->format('Y-m-d')) : '' }}"></td>
                                    <td><input type="text" name="promotions[{{ $index }}][promotion_order_number]" class="form-control form-control-sm" value="{{ $promo['promotion_order_number'] ?? '' }}"></td>
                                    <td><input type="date" name="promotions[{{ $index }}][promotion_order_date]" class="form-control form-control-sm" value="{{ isset($promo['promotion_order_date']) ? (is_string($promo['promotion_order_date']) ? $promo['promotion_order_date'] : $promo['promotion_order_date']->format('Y-m-d')) : '' }}"></td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Service History --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center bg-success text-white">
                <span>Previous Service History</span>
                <button type="button" class="btn btn-sm btn-light" onclick="addRow('service-table')">
                    <i class="fas fa-plus me-1"></i>Add Record
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm" id="service-table">
                        <thead>
                            <tr>
                                <th>Institution</th>
                                <th>Designation</th>
                                <th>BPS</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(old('service_history', $staff->serviceHistory) as $index => $service)
                                <tr>
                                    <td><input type="text" name="service_history[{{ $index }}][institution_name]" class="form-control form-control-sm" value="{{ $service['institution_name'] ?? '' }}"></td>
                                    <td><input type="text" name="service_history[{{ $index }}][designation]" class="form-control form-control-sm" value="{{ $service['designation'] ?? '' }}"></td>
                                    <td><input type="number" name="service_history[{{ $index }}][bps]" class="form-control form-control-sm" value="{{ $service['bps'] ?? '' }}"></td>
                                    <td><input type="date" name="service_history[{{ $index }}][start_date]" class="form-control form-control-sm" value="{{ isset($service['start_date']) ? (is_string($service['start_date']) ? $service['start_date'] : $service['start_date']->format('Y-m-d')) : '' }}"></td>
                                    <td><input type="date" name="service_history[{{ $index }}][end_date]" class="form-control form-control-sm" value="{{ isset($service['end_date']) ? (is_string($service['end_date']) ? $service['end_date'] : $service['end_date']->format('Y-m-d')) : '' }}"></td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4 mb-5">
            <button class="btn btn-lg btn-primary">Update Staff Member</button>
            <a href="{{ route('staff.index') }}" class="btn btn-lg btn-secondary ms-2">Cancel</a>
        </div>
    </form>
</div>

@section('scripts')
<script src="{{ asset('js/face-api.min.js') }}"></script>
<script>
function addRow(tableId) {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const index = tbody.rows.length;
    let html = '';

    if (tableId === 'qualifications-table') {
        html = `
            <tr>
                <td>
                    <select name="qualifications[${index}][degree_level]" class="form-select form-select-sm">
                        <option value="ssc">SSC</option>
                        <option value="hssc">HSSC</option>
                        <option value="bachelor">Bachelor</option>
                        <option value="master">Master</option>
                        <option value="bs">BS</option>
                        <option value="mphil">MPhil</option>
                        <option value="phd">PhD</option>
                    </select>
                </td>
                <td><input type="text" name="qualifications[${index}][subject]" class="form-control form-control-sm"></td>
                <td><input type="text" name="qualifications[${index}][group_field]" class="form-control form-control-sm"></td>
                <td><input type="number" name="qualifications[${index}][total_marks]" class="form-control form-control-sm"></td>
                <td><input type="number" name="qualifications[${index}][obtained_marks]" class="form-control form-control-sm"></td>
                <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
    } else if (tableId === 'promotions-table') {
        html = `
            <tr>
                <td><input type="text" name="promotions[${index}][previous_designation]" class="form-control form-control-sm" value=""></td>
                <td><input type="number" name="promotions[${index}][previous_bps]" class="form-control form-control-sm" value=""></td>
                <td><input type="text" name="promotions[${index}][new_designation]" class="form-control form-control-sm"></td>
                <td><input type="number" name="promotions[${index}][new_bps]" class="form-control form-control-sm"></td>
                <td><input type="date" name="promotions[${index}][promotion_date]" class="form-control form-control-sm"></td>
                <td><input type="text" name="promotions[${index}][promotion_order_number]" class="form-control form-control-sm"></td>
                <td><input type="date" name="promotions[${index}][promotion_order_date]" class="form-control form-control-sm"></td>
                <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
    } else if (tableId === 'service-table') {
        html = `
            <tr>
                <td><input type="text" name="service_history[${index}][institution_name]" class="form-control form-control-sm"></td>
                <td><input type="text" name="service_history[${index}][designation]" class="form-control form-control-sm"></td>
                <td><input type="number" name="service_history[${index}][bps]" class="form-control form-control-sm"></td>
                <td><input type="date" name="service_history[${index}][start_date]" class="form-control form-control-sm"></td>
                <td><input type="date" name="service_history[${index}][end_date]" class="form-control form-control-sm"></td>
                <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
    }

    tbody.insertAdjacentHTML('beforeend', html);
}

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
            
            // Basic Blue Background Check (Always Run)
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

                    if (!modelsLoaded) {
                        console.warn("Face detection models not loaded. Skipping face validation.");
                        // If we want to allow upload even if models fail, we just proceed
                        // Otherwise, we keep the alert but make it more informative
                        if (typeof faceapi === 'undefined') {
                            console.error("face-api.js is missing.");
                        }
                    } else {
                        const detections = await faceapi.detectAllFaces(img, new faceapi.TinyFaceDetectorOptions());
                        
                        if (detections.length === 0) {
                            alert("Error: No face detected. Please upload a clear photo of a human face.");
                            imageInput.value = '';
                            submitBtn.prop('disabled', false).html(originalBtnHtml);
                            return;
                        } else if (detections.length > 1) {
                            alert("Error: Multiple faces detected. Please upload a photo of a single person.");
                            imageInput.value = '';
                            submitBtn.prop('disabled', false).html(originalBtnHtml);
                            return;
                        } else if (detections[0].score < 0.5) {
                            alert("Error: Face is not clearly visible. Please upload a better quality photo.");
                            imageInput.value = '';
                            submitBtn.prop('disabled', false).html(originalBtnHtml);
                            return;
                        }
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

    // Show/hide subject specializations based on category
    function toggleSubjectSpecializations() {
        if ($('#category').val() === 'teaching') {
            $('#subjectSpecializationsCard').show();
            $('#bsSubjectSpecializationsCard').show();
            $('#assignmentLinksCard').show();
        } else {
            $('#subjectSpecializationsCard').hide();
            $('#bsSubjectSpecializationsCard').hide();
            $('#assignmentLinksCard').hide();
        }
    }

    $('#category').on('change', toggleSubjectSpecializations);

    // Auto-fill BPS when designation is selected
    $('#designation_id').on('change', function() {
        const designationId = $(this).val();
        const designationName = $(this).find('option:selected').text();
        
        if (designationId) {
            // Fetch BPS from server
            $.ajax({
                url: '{{ route("staff.designation_bps") }}',
                type: 'GET',
                data: { designation_id: designationId },
                success: function(response) {
                    $('#bps').val(response.bps);
                    $('#designation_name').val(designationName);
                },
                error: function(xhr) {
                    console.error('Error fetching BPS:', xhr);
                    alert('Error fetching BPS for selected designation');
                }
            });
        } else {
            $('#bps').val('');
            $('#designation_name').val('');
        }
    });

    // CNIC Input Masking
    $('.cnic-mask').on('input', function() {
        let val = $(this).val().replace(/\D/g, '');
        let x = val.match(/(\d{0,5})(\d{0,7})(\d{0,1})/);
        $(this).val(!x[2] ? x[1] : x[1] + '-' + x[2] + (x[3] ? '-' + x[3] : ''));
    });

    // Phone Input Masking
    $('.phone-mask').on('input', function() {
        let val = $(this).val().replace(/\D/g, '');
        if(val.length > 0 && val[0] !== '0') {
            val = '0' + val; // Force leading zero
        }
        if(val.length > 1 && val[1] !== '3') {
            val = '03' + val.substring(2); // Force 03
        }
        let x = val.match(/(\d{0,4})(\d{0,7})/);
        $(this).val(!x[2] ? x[1] : x[1] + '-' + x[2]);
    });
});
</script>
@endsection
@endsection
