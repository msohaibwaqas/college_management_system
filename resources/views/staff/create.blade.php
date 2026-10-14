@extends('layouts.app')
@section('title', 'Add Staff Member')

@section('content')
<div class="container mt-4">
    <h1>Add Staff Member</h1>
    
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <form action="{{ route('staff.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Basic Information --}}
        <div class="card mb-3">
            <div class="card-header">Basic Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label>Staff ID <span class="text-danger">*</span></label>
                        <input type="text" name="staff_id" class="form-control @error('staff_id') is-invalid @enderror" value="{{ old('staff_id') }}" required>
                        @error('staff_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label>First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required pattern="[a-zA-Z\s]+" title="Only alphabets and spaces are allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" pattern="[a-zA-Z\s]*" title="Only alphabets and spaces are allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label>Father Name <span class="text-danger">*</span></label>
                        <input type="text" name="father_name" class="form-control @error('father_name') is-invalid @enderror" value="{{ old('father_name') }}" required pattern="[a-zA-Z\s]+" title="Only alphabets and spaces are allowed" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                        @error('father_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label>CNIC <span class="text-danger">*</span></label>
                        <input type="text" name="cnic" class="form-control cnic-mask @error('cnic') is-invalid @enderror" value="{{ old('cnic') }}" required placeholder="00000-0000000-0" pattern="\d{5}-\d{7}-\d" title="Format: 00000-0000000-0" maxlength="15">
                        @error('cnic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label>Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth') }}" required>
                        @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label>Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Blood Group</label>
                        <select name="blood_group_id" class="form-control">
                            <option value="">Select Blood Group</option>
                            @foreach($bloodGroups as $bg)
                                <option value="{{ $bg->id }}" {{ old('blood_group_id') == $bg->id ? 'selected' : '' }}>
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
                        <input type="text" name="contact_number" class="form-control phone-mask @error('contact_number') is-invalid @enderror" value="{{ old('contact_number') }}" placeholder="0300-0000000" pattern="03\d{2}-\d{7}" title="Format: 03XX-XXXXXXX" maxlength="12">
                        @error('contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label>WhatsApp Number</label>
                        <input type="text" name="whatsapp_number" class="form-control phone-mask @error('whatsapp_number') is-invalid @enderror" value="{{ old('whatsapp_number') }}" placeholder="0300-0000000" pattern="03\d{2}-\d{7}" title="Format: 03XX-XXXXXXX" maxlength="12">
                        @error('whatsapp_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
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
                            <option value="teaching" {{ old('category') == 'teaching' ? 'selected' : '' }}>Teaching</option>
                            <option value="non_teaching" {{ old('category') == 'non_teaching' ? 'selected' : '' }}>Non-Teaching</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Designation <span class="text-danger">*</span></label>
                        <select name="designation_id" id="designation_id" class="form-control" required>
                            <option value="">Select Designation</option>
                            @foreach($designations as $designation)
                                <option value="{{ $designation->id }}" {{ old('designation_id') == $designation->id ? 'selected' : '' }}>
                                    {{ $designation->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="designation" id="designation_name" value="{{ old('designation') }}">
                    </div>
                    <div class="col-md-3">
                        <label>BPS <span class="text-danger">*</span></label>
                        <input type="number" name="bps" id="bps" class="form-control" min="1" max="22" value="{{ old('bps') }}" readonly required>
                    </div>
                    <div class="col-md-3">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="left" {{ old('status') == 'left' ? 'selected' : '' }}>Left</option>
                            <option value="transferred" {{ old('status') == 'transferred' ? 'selected' : '' }}>Transferred</option>
                            <option value="retired" {{ old('status') == 'retired' ? 'selected' : '' }}>Retired</option>
                            <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Highest Qualification</label>
                        <input type="text" name="highest_qualification" class="form-control" value="{{ old('highest_qualification') }}">
                    </div>
                    <div class="col-md-4">
                        <label>Entry Date (Govt Service)</label>
                        <input type="date" name="entry_date_govt_service" class="form-control" value="{{ old('entry_date_govt_service') }}">
                    </div>
                    <div class="col-md-4">
                        <label>HED Joining Date</label>
                        <input type="date" name="hed_joining_date" class="form-control" value="{{ old('hed_joining_date') }}">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Current College Joining Date</label>
                        <input type="date" name="current_college_joining_date" class="form-control" value="{{ old('current_college_joining_date') }}">
                    </div>
                    <div class="col-md-4">
                        <label>Staff Picture</label>
                        <input type="file" name="picture" class="form-control @error('picture') is-invalid @enderror" accept="image/*">
                        <small class="text-muted">Required - Face detection & Blue Background check enabled.</small>
                        @error('picture') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Subject Specializations (for teaching staff) --}}
        <div class="card mb-3" id="subjectSpecializationsCard" style="display: none;">
            <div class="card-header">Subject Specializations (HSSC)</div>
            <div class="card-body">
                <div class="row">
                    @foreach($subjects as $subject)
                        <div class="col-md-3">
                            <input type="checkbox" name="subject_specializations[]" value="{{ $subject->id }}" 
                                   {{ in_array($subject->id, old('subject_specializations', [])) ? 'checked' : '' }}>
                            {{ $subject->name }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- BS/ADP Subject Specializations (for teaching staff) --}}
        <div class="card mb-3" id="bsSubjectSpecializationsCard" style="display: none;">
            <div class="card-header bg-info text-white">
                <i class="fas fa-university me-2"></i>BS/ADP Subject Specializations (Higher Education)
            </div>
            <div class="card-body">
                @if($bsSubjects->count() > 0)
                    <div class="row">
                        @foreach($bsSubjects as $bsSubject)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" 
                                           name="bs_subject_specializations[]" 
                                           value="{{ $bsSubject->id }}" 
                                           id="bs_subj_{{ $bsSubject->id }}"
                                           {{ in_array($bsSubject->id, old('bs_subject_specializations', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="bs_subj_{{ $bsSubject->id }}">
                                        {{ $bsSubject->name }} 
                                        <small class="text-muted">({{ $bsSubject->code }})</small>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">No BS subjects available. <a href="{{ route('bs-subjects.create') }}">Add BS Subjects</a></p>
                @endif
            </div>
        </div>

        <button class="btn btn-primary">Save Staff Member</button>
        <a href="{{ route('staff.index') }}" class="btn btn-secondary">Cancel</a>
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

    // Show/hide subject specializations based on category
    function toggleSubjectSpecializations() {
        if ($('#category').val() === 'teaching') {
            $('#subjectSpecializationsCard').show();
            $('#bsSubjectSpecializationsCard').show();
        } else {
            $('#subjectSpecializationsCard').hide();
            $('#bsSubjectSpecializationsCard').hide();
        }
    }

    $('#category').on('change', toggleSubjectSpecializations);
    toggleSubjectSpecializations(); // Initial check

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
