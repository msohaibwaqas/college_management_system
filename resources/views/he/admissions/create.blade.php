@extends('layouts.app')
@section('title', 'New HE Registration')

@section('content')
<div class="container py-4" style="margin-top: 100px;">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0"><i class="fas fa-user-graduate me-2"></i>New HE Student Registration</h2>
            <a href="{{ route('admissions.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Admissions
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mx-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row mb-4 px-3">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 border-start border-warning border-5">
                <div class="card-body">
                    <h5 class="card-title text-warning mb-3"><i class="fas fa-search me-2"></i>Search Existing Student</h5>
                    <div class="input-group">
                        <input type="text" id="bise_search_roll" class="form-control" placeholder="Enter BISE Roll No (Inter 11th/12th)">
                        <button class="btn btn-warning" type="button" id="search_student_btn">
                            <i class="fas fa-search me-1"></i> Search & Auto-fill
                        </button>
                    </div>
                    <small class="text-muted mt-2 d-block">This will auto-fill details if the student already exists in our records.</small>
                    <div id="search_status" class="mt-2" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admissions.store') }}" method="POST" enctype="multipart/form-data" class="px-3" id="registration_form">
        @csrf

        {{-- Personal Info --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Blood Group</label>
                        <select name="blood_group_id" class="form-select">
                            <option value="">Select Blood Group</option>
                            @foreach($bloodGroups as $bg)
                                <option value="{{ $bg->id }}" {{ old('blood_group_id') == $bg->id ? 'selected' : '' }}>{{ $bg->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Quota</label>
                        <select name="quota_id" class="form-select">
                            <option value="">Select Quota</option>
                            @foreach($quotas as $quota)
                                <option value="{{ $quota->id }}" {{ old('quota_id') == $quota->id ? 'selected' : '' }}>{{ $quota->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Student CNIC</label>
                        <input type="text" name="student_cnic" class="form-control" value="{{ old('student_cnic') }}" placeholder="00000-0000000-0">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Religion <span class="text-danger">*</span></label>
                        <input type="text" name="religion" class="form-control @error('religion') is-invalid @enderror" value="{{ old('religion', 'Islam') }}" required>
                        @error('religion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Nationality <span class="text-danger">*</span></label>
                        <input type="text" name="nationality" class="form-control @error('nationality') is-invalid @enderror" value="{{ old('nationality', 'Pakistani') }}" required>
                        @error('nationality') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Student Picture</label>
                        <input type="file" name="picture" class="form-control @error('picture') is-invalid @enderror" accept="image/*">
                        <small class="text-muted">Optional - Max 2MB</small>
                        @error('picture') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Father Info --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Father Information</h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Father Name <span class="text-danger">*</span></label>
                        <input type="text" name="father_name" class="form-control" value="{{ old('father_name') }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Father CNIC</label>
                        <input type="text" name="father_cnic" class="form-control" value="{{ old('father_cnic') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Father Contact</label>
                        <input type="text" name="father_contact" class="form-control" value="{{ old('father_contact') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Occupation</label>
                        <input type="text" name="father_occupation" class="form-control" value="{{ old('father_occupation') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Annual Income</label>
                        <input type="number" name="father_annual_income" class="form-control" value="{{ old('father_annual_income') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact Info --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0"><i class="fas fa-address-book me-2"></i>Contact Information</h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Student Contact</label>
                        <input type="text" name="student_contact" class="form-control" value="{{ old('student_contact') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">WhatsApp No</label>
                        <input type="text" name="whatsapp_no" class="form-control" value="{{ old('whatsapp_no') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Program & Admission Info --}}
        <div class="card shadow-sm border-0 mb-4 border-start border-info border-5">
            <div class="card-header bg-info text-dark py-3">
                <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Program & Admission Information</h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Select Program <span class="text-danger">*</span></label>
                        <select name="program_id" id="program_id" class="form-select @error('program_id') is-invalid @enderror" required>
                            <option value="">Select Program...</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}" {{ old('program_id') == $program->id ? 'selected' : '' }}>
                                    {{ $program->name }} ({{ $program->type }})
                                </option>
                            @endforeach
                        </select>
                        @error('program_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted mt-1 d-block">Semester 1 will be assigned by default.</small>
                    </div>
                </div>
                <div class="alert alert-info mt-2 mb-0">
                    <i class="fas fa-info-circle me-1"></i> Subjects for the selected program and semester will be assigned automatically.
                </div>
            </div>
        </div>

        {{-- SSC Records --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-secondary text-white py-3">
                <h5 class="mb-0"><i class="fas fa-certificate me-2"></i>SSC (Matric) Academic Record</h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">BISE Board <span class="text-danger">*</span></label>
                        <select name="ssc_bise_id" class="form-select" required>
                            <option value="">Select BISE</option>
                            @foreach($bises as $bise)
                                <option value="{{ $bise->id }}" {{ old('ssc_bise_id') == $bise->id ? 'selected' : '' }}>{{ $bise->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Roll No <span class="text-danger">*</span></label>
                        <input type="text" name="ssc_roll_no" class="form-control" value="{{ old('ssc_roll_no') }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Registration No <span class="text-danger">*</span></label>
                        <input type="text" name="ssc_reg_no" class="form-control" value="{{ old('ssc_reg_no') }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Passing Year <span class="text-danger">*</span></label>
                        <input type="number" name="ssc_passing_year" class="form-control" value="{{ old('ssc_passing_year', date('Y')-2) }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">School Name <span class="text-danger">*</span></label>
                        <input type="text" name="ssc_school_name" class="form-control" value="{{ old('ssc_school_name') }}" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Total Marks <span class="text-danger">*</span></label>
                        <input type="number" name="ssc_total_mark" class="form-control" value="{{ old('ssc_total_mark', 1100) }}" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Obtained <span class="text-danger">*</span></label>
                        <input type="number" name="ssc_obtained_mark" class="form-control" value="{{ old('ssc_obtained_mark') }}" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Group <span class="text-danger">*</span></label>
                        <select name="ssc_group" class="form-select" required>
                            <option value="Science" {{ old('ssc_group') == 'Science' ? 'selected' : '' }}>Science</option>
                            <option value="Arts" {{ old('ssc_group') == 'Arts' ? 'selected' : '' }}>Arts</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Session <span class="text-danger">*</span></label>
                        <select name="ssc_passing_session" class="form-select" required>
                            <option value="1st Annual" {{ old('ssc_passing_session') == '1st Annual' ? 'selected' : '' }}>1st Annual</option>
                            <option value="2nd Annual" {{ old('ssc_passing_session') == '2nd Annual' ? 'selected' : '' }}>2nd Annual</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- HSSC Records --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-secondary text-white py-3">
                <h5 class="mb-0"><i class="fas fa-scroll me-2"></i>HSSC (Inter) Academic Record</h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">BISE Board <span class="text-danger">*</span></label>
                        <select name="hssc_bise_id" class="form-select" required>
                            <option value="">Select BISE</option>
                            @foreach($bises as $bise)
                                <option value="{{ $bise->id }}" {{ old('hssc_bise_id') == $bise->id ? 'selected' : '' }}>{{ $bise->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Roll No <span class="text-danger">*</span></label>
                        <input type="text" name="hssc_roll_no" class="form-control" value="{{ old('hssc_roll_no') }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Registration No <span class="text-danger">*</span></label>
                        <input type="text" name="hssc_reg_no_academic" class="form-control" value="{{ old('hssc_reg_no_academic') }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Passing Year <span class="text-danger">*</span></label>
                        <input type="number" name="hssc_passing_year" class="form-control" value="{{ old('hssc_passing_year', date('Y')) }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">College Name <span class="text-danger">*</span></label>
                        <input type="text" name="hssc_college_name" class="form-control" value="{{ old('hssc_college_name') }}" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Total Marks <span class="text-danger">*</span></label>
                        <input type="number" name="hssc_total_mark" class="form-control" value="{{ old('hssc_total_mark', 1100) }}" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Obtained <span class="text-danger">*</span></label>
                        <input type="number" name="hssc_obtained_mark" class="form-control" value="{{ old('hssc_obtained_mark') }}" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Group <span class="text-danger">*</span></label>
                        <select name="hssc_group" class="form-select" required>
                            <option value="FSc (Pre-Medical)" {{ old('hssc_group') == 'FSc (Pre-Medical)' ? 'selected' : '' }}>FSc (Pre-Medical)</option>
                            <option value="FSc (Pre-Engineering)" {{ old('hssc_group') == 'FSc (Pre-Engineering)' ? 'selected' : '' }}>FSc (Pre-Engineering)</option>
                            <option value="ICS" {{ old('hssc_group') == 'ICS' ? 'selected' : '' }}>ICS</option>
                            <option value="ICOM" {{ old('hssc_group') == 'ICOM' ? 'selected' : '' }}>ICOM</option>
                            <option value="FA" {{ old('hssc_group') == 'FA' ? 'selected' : '' }}>FA</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Session <span class="text-danger">*</span></label>
                        <select name="hssc_passing_session" class="form-select" required>
                            <option value="1st Annual" {{ old('hssc_passing_session') == '1st Annual' ? 'selected' : '' }}>1st Annual</option>
                            <option value="2nd Annual" {{ old('hssc_passing_session') == '2nd Annual' ? 'selected' : '' }}>2nd Annual</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 mb-5">
            <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold text-uppercase shadow-sm">
                <i class="fas fa-save me-2"></i>Complete Registration & Enroll
            </button>
        </div>
    </form>
</div>
@section('scripts')
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
    $(document).ready(function() {
        $('#search_student_btn').click(function() {
            const rollNo = $('#bise_search_roll').val().trim();
            if (!rollNo) {
                alert('Please enter a roll number');
                return;
            }

            const statusEl = $('#search_status');
            statusEl.show().html('<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i> Searching...</span>');
            $(this).prop('disabled', true);

            $.ajax({
                url: "{{ route('admissions.search-student') }}",
                data: { roll_no: rollNo },
                success: function(response) {
                    $('#search_student_btn').prop('disabled', false);
                    
                    if (response.found) {
                        statusEl.html('<span class="text-success"><i class="fas fa-check-circle me-1"></i> Student found in ' + response.source + '! Fields auto-filled.</span>');
                        
                        const data = response.data;
                        
                        // Populate Fields
                        $('[name="first_name"]').val(data.first_name);
                        $('[name="last_name"]').val(data.last_name);
                        $('[name="gender"]').val(data.gender);
                        $('[name="date_of_birth"]').val(data.date_of_birth);
                        $('[name="student_cnic"]').val(data.student_cnic);
                        $('[name="blood_group_id"]').val(data.blood_group_id);
                        $('[name="quota_id"]').val(data.quota_id);
                        $('[name="religion"]').val(data.religion || 'Islam');
                        $('[name="nationality"]').val(data.nationality || 'Pakistani');
                        
                        $('[name="father_name"]').val(data.father_name);
                        $('[name="father_cnic"]').val(data.father_cnic);
                        $('[name="father_contact"]').val(data.father_contact);
                        $('[name="father_occupation"]').val(data.father_occupation);
                        $('[name="father_annual_income"]').val(data.father_annual_income);
                        
                        $('[name="student_contact"]').val(data.student_contact);
                        $('[name="whatsapp_no"]').val(data.whatsapp_no);
                        $('[name="email"]').val(data.email);
                        $('[name="address"]').val(data.address);
                        
                        $('[name="ssc_bise_id"]').val(data.ssc_bise_id);
                        $('[name="ssc_roll_no"]').val(data.ssc_roll_no);
                        $('[name="ssc_reg_no"]').val(data.ssc_reg_no);
                        $('[name="ssc_school_name"]').val(data.ssc_school_name);
                        $('[name="ssc_total_mark"]').val(data.ssc_total_mark);
                        $('[name="ssc_obtained_mark"]').val(data.ssc_obtained_mark);
                        $('[name="ssc_passing_year"]').val(data.ssc_passing_year);
                        $('[name="ssc_passing_session"]').val(data.ssc_passing_session);
                        $('[name="ssc_group"]').val(data.ssc_group);
                        
                        $('[name="hssc_bise_id"]').val(data.hssc_bise_id);
                        $('[name="hssc_roll_no"]').val(data.hssc_roll_no);
                        $('[name="hssc_reg_no_academic"]').val(data.hssc_reg_no_academic);
                        $('[name="hssc_college_name"]').val(data.hssc_college_name);
                        $('[name="hssc_total_mark"]').val(data.hssc_total_mark);
                        $('[name="hssc_obtained_mark"]').val(data.hssc_obtained_mark);
                        $('[name="hssc_passing_year"]').val(data.hssc_passing_year);
                        $('[name="hssc_passing_session"]').val(data.hssc_passing_session);
                        $('[name="hssc_group"]').val(data.hssc_group);

                        // Scroll to top of first card to show progress
                        window.scrollTo({ top: 300, behavior: 'smooth' });

                    } else {
                        statusEl.html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> No student found with this roll number. Please enter details manually.</span>');
                    }
                },
                error: function() {
                    $('#search_student_btn').prop('disabled', false);
                    statusEl.html('<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Error searching for student.</span>');
                }
            });
        });
    });
</script>
@endsection
