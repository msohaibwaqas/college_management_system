@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-user-edit me-2"></i>My Profile</h5>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row g-4">
                            <!-- Profile Picture Section -->
                            <div class="col-md-3 text-center">
                                <div class="mb-3">
                                    <img src="{{ $user->profile_picture_url }}" 
                                         alt="Profile Picture" 
                                         class="img-thumbnail rounded-circle shadow-sm"
                                         style="width: 180px; height: 180px; object-fit: cover;">
                                </div>
                                <div class="mb-3">
                                    <label for="picture" class="form-label small fw-bold text-muted">Change Picture</label>
                                    <input type="file" class="form-control form-control-sm" id="picture" name="picture" accept="image/*">
                                </div>
                            </div>

                            <!-- Form Fields Section -->
                            <div class="col-md-9">
                                <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2">Personal Information</h6>
                                <div class="row g-3 mb-4">
                                    @if($staff)
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $staff->first_name ?? '') }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $staff->last_name ?? '') }}">
                                    </div>
                                    @else
                                    <div class="col-md-12">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" value="{{ $user->full_name }}" disabled>
                                        <div class="form-text small">Student names can only be updated by the administration.</div>
                                    </div>
                                    @endif
                                    @if($staff)
                                    <div class="col-md-6">
                                        <label for="father_name" class="form-label">Father Name</label>
                                        <input type="text" class="form-control" id="father_name" name="father_name" value="{{ old('father_name', $staff->father_name ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cnic" class="form-label">CNIC</label>
                                        <input type="text" class="form-control" id="cnic" name="cnic" value="{{ old('cnic', $staff->cnic ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', optional($staff)->date_of_birth ? $staff->date_of_birth->format('Y-m-d') : '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" id="gender" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="male" {{ (old('gender', $staff->gender ?? '') == 'male') ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ (old('gender', $staff->gender ?? '') == 'female') ? 'selected' : '' }}>Female</option>
                                            <option value="other" {{ (old('gender', $staff->gender ?? '') == 'other') ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="blood_group_id" class="form-label">Blood Group</label>
                                        <select class="form-select" id="blood_group_id" name="blood_group_id">
                                            <option value="">Select Blood Group</option>
                                            @foreach($bloodGroups as $bg)
                                                <option value="{{ $bg->id }}" {{ (old('blood_group_id', $staff->blood_group_id ?? '') == $bg->id) ? 'selected' : '' }}>{{ $bg->code }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @elseif($user->student)
                                    <div class="col-md-6">
                                        <label class="form-label">Admission No</label>
                                        <input type="text" class="form-control" value="{{ $user->student->admission_no }}" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Roll No</label>
                                        <input type="text" class="form-control" value="{{ $user->student->roll_no }}" disabled>
                                    </div>
                                    @endif
                                </div>

                                <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2">Contact Information</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="username" name="username" value="{{ old('username', $user->username) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contact_number" class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" id="contact_number" name="contact_number" value="{{ old('contact_number', $staff->contact_number ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="whatsapp_number" class="form-label">WhatsApp Number</label>
                                        <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $staff->whatsapp_number ?? '') }}">
                                    </div>
                                    <div class="col-md-12">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="2">{{ old('address', $staff->address ?? '') }}</textarea>
                                    </div>
                                </div>

                                @if($staff)
                                <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2">Professional Information</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="designation_id" class="form-label">Designation</label>
                                        <select class="form-select" id="designation_id" name="designation_id">
                                            <option value="">Select Designation</option>
                                            @foreach($designations as $designation)
                                                <option value="{{ $designation->id }}" {{ (old('designation_id', $staff->designation_id ?? '') == $designation->id) ? 'selected' : '' }}>{{ $designation->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="highest_qualification" class="form-label">Highest Qualification</label>
                                        <input type="text" class="form-control" id="highest_qualification" name="highest_qualification" value="{{ old('highest_qualification', $staff->highest_qualification ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="entry_date_govt_service" class="form-label">Entry Date (Govt Service)</label>
                                        <input type="date" class="form-control" id="entry_date_govt_service" name="entry_date_govt_service" value="{{ old('entry_date_govt_service', optional($staff)->entry_date_govt_service ? $staff->entry_date_govt_service->format('Y-m-d') : '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="hed_joining_date" class="form-label">HED Joining Date</label>
                                        <input type="date" class="form-control" id="hed_joining_date" name="hed_joining_date" value="{{ old('hed_joining_date', optional($staff)->hed_joining_date ? $staff->hed_joining_date->format('Y-m-d') : '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="current_college_joining_date" class="form-label">Current College Joining Date</label>
                                        <input type="date" class="form-control" id="current_college_joining_date" name="current_college_joining_date" value="{{ old('current_college_joining_date', optional($staff)->current_college_joining_date ? $staff->current_college_joining_date->format('Y-m-d') : '') }}">
                                    </div>
                                </div>
                                @endif

                                {{-- === DEMO MODE: Password change section hidden === --}}
                                {{-- <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2">Security</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">New Password <small class="text-muted">(Leave blank to keep current)</small></label>
                                        <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                                    </div>
                                </div> --}}

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
