@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Institute Settings</h1>
            <p class="text-muted">Manage your college information and settings</p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <!-- Logo Upload Section -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-image me-2"></i>College Logo</h5>
                </div>
                <div class="card-body text-center">
                    {{-- Debug Info --}}
                    @if($settings->logo_path)
                        <div class="alert alert-info text-start p-2 mb-2" style="font-size: 11px;">
                            <strong>Debug:</strong><br>
                            DB: {{ $settings->logo_path }}<br>
                            URL: {{ $settings->logo_url }}
                        </div>
                    @endif

                    @if($settings->hasLogo())
                        <img src="{{ $settings->logo_url }}" alt="College Logo" class="img-fluid mb-3" style="max-height: 200px;">
                    @else
                        <div class="bg-light p-5 mb-3 rounded">
                            <i class="fas fa-university fa-4x text-muted"></i>
                            <p class="text-muted mt-2">No logo uploaded</p>
                        </div>
                    @endif
                    
                    <form action="{{ route('settings.logo.upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <input type="file" class="form-control @error('logo') is-invalid @enderror" name="logo" accept="image/*" required>
                            @error('logo')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Max size: 2MB. Formats: JPG, PNG, SVG</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-2"></i>Upload Logo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Banner Slides Management -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-images me-2"></i>Homepage Banner Slides
                        <small class="fw-normal ms-2 opacity-75">(Hero image slideshow — admin can add multiple images)</small>
                    </h5>
                    <span class="badge bg-white text-success">{{ $bannerSlides->count() }} slide(s)</span>
                </div>
                <div class="card-body">

                    {{-- Upload New Slide --}}
                    <form action="{{ route('banner-slides.store') }}" method="POST" enctype="multipart/form-data" class="mb-4 p-3 border rounded bg-light">
                        @csrf
                        <h6 class="fw-bold text-success mb-3"><i class="fas fa-plus-circle me-2"></i>Add New Slide</h6>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Image <span class="text-danger">*</span></label>
                                <input type="file" class="form-control @error('banner_slide') is-invalid @enderror"
                                       name="banner_slide" accept="image/*" required>
                                @error('banner_slide')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Recommended: 1920×700px. Max 5MB. JPG/PNG/WebP</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Slide Title <small class="text-muted">(optional)</small></label>
                                <input type="text" class="form-control" name="slide_title"
                                       placeholder="e.g. Welcome to GGC Chishtian" maxlength="150">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Subtitle <small class="text-muted">(optional)</small></label>
                                <input type="text" class="form-control" name="slide_subtitle"
                                       placeholder="e.g. Excellence in Education" maxlength="200">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-upload me-1"></i> Upload Slide
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- Existing Slides Grid --}}
                    @if($bannerSlides->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-images fa-3x mb-3 d-block opacity-50"></i>
                            <p class="mb-1">No banner slides added yet.</p>
                            <small>Upload your first slide above. Until then, the default banner image (if set) will be used.</small>
                        </div>
                    @else
                        <div class="row g-3" id="slidesGrid">
                            @foreach($bannerSlides as $slide)
                            <div class="col-md-4 col-lg-3" data-slide-id="{{ $slide->id }}">
                                <div class="card h-100 border {{ $slide->is_active ? 'border-success' : 'border-secondary opacity-75' }}">
                                    <div class="position-relative">
                                        <img src="{{ $slide->image_url }}"
                                             alt="Slide {{ $slide->sort_order }}"
                                             class="card-img-top"
                                             style="height: 140px; object-fit: cover;">
                                        <span class="position-absolute top-0 start-0 m-2 badge {{ $slide->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $slide->is_active ? 'Active' : 'Hidden' }}
                                        </span>
                                        <span class="position-absolute top-0 end-0 m-2 badge bg-dark opacity-75">
                                            #{{ $slide->sort_order }}
                                        </span>
                                    </div>
                                    <div class="card-body p-2">
                                        @if($slide->title)
                                            <p class="mb-0 fw-bold small">{{ Str::limit($slide->title, 30) }}</p>
                                        @endif
                                        @if($slide->subtitle)
                                            <p class="mb-0 text-muted" style="font-size:0.75rem;">{{ Str::limit($slide->subtitle, 40) }}</p>
                                        @endif
                                    </div>
                                    <div class="card-footer p-2 d-flex gap-1">
                                        {{-- Toggle active --}}
                                        <form action="{{ route('banner-slides.toggle', $slide->id) }}" method="POST" class="flex-grow-1">
                                            @csrf
                                            <button type="submit" class="btn btn-sm w-100 {{ $slide->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }}"
                                                    title="{{ $slide->is_active ? 'Hide slide' : 'Show slide' }}">
                                                <i class="fas {{ $slide->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                                {{ $slide->is_active ? 'Hide' : 'Show' }}
                                            </button>
                                        </form>
                                        {{-- Delete --}}
                                        <form action="{{ route('banner-slides.destroy', $slide->id) }}" method="POST"
                                              onsubmit="return confirm('Delete this slide?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <p class="text-muted small mt-3 mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Slides display in order (#1 first). Toggle visibility without deleting. 
                            Add titles/subtitles to overlay text on each slide (optional).
                        </p>
                    @endif

                </div>
            </div>
        </div>

        <!-- Principal's Message Management Section -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-quote-left me-2"></i>Principal's Message</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">Manage the principal's message shown on the home page and in the history section.</p>
                    
                    @php
                        $currentPrincipal = \App\Models\Principal::where('is_current', 1)->first();
                    @endphp

                    @if($currentPrincipal)
                        <div class="d-flex align-items-center mb-4 p-2 bg-light rounded">
                            <img src="{{ $currentPrincipal->getImageUrl() }}" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $currentPrincipal->name }}</h6>
                                <small class="text-muted">Current Principal</small>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="{{ route('principals.edit', $currentPrincipal->id) }}" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i>Edit Current Message
                            </a>
                        </div>
                    @else
                        <div class="alert alert-info py-2 small mb-4">
                            <i class="fas fa-info-circle me-1"></i> No active principal set as "Current".
                        </div>
                    @endif

                    <div class="mt-4 pt-3 border-top">
                        <div class="d-grid gap-2">
                            <a href="{{ route('principals.create') }}" class="btn btn-outline-primary">
                                <i class="fas fa-plus me-2"></i>Add New Principal Record
                            </a>
                            <a href="{{ route('principals.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-2"></i>View History
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Form -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Institute Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Basic Information -->
                        <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Institute Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('institute_title') is-invalid @enderror" name="institute_title" value="{{ old('institute_title', $settings->institute_title) }}" required>
                                @error('institute_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Institute Sub-Title</label>
                                <input type="text" class="form-control" name="institute_subtitle" value="{{ old('institute_subtitle', $settings->institute_subtitle) }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Short Name</label>
                                <input type="text" class="form-control" name="short_name" value="{{ old('short_name', $settings->short_name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Full Name</label>
                                <input type="text" class="form-control" name="full_name" value="{{ old('full_name', $settings->full_name) }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Location</label>
                                <input type="text" class="form-control" name="institute_location" value="{{ old('institute_location', $settings->institute_location) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Type (Admission for)</label>
                                <input type="text" class="form-control" name="admission_type" value="{{ old('admission_type', $settings->admission_type) }}" placeholder="e.g., Boys, Girls, Co-Education">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Reg# / EMS Code</label>
                                <input type="text" class="form-control" name="reg_number" value="{{ old('reg_number', $settings->reg_number) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">EMS Code</label>
                                <input type="text" class="form-control" name="ems_code" value="{{ old('ems_code', $settings->ems_code) }}">
                            </div>
                        </div>

                        <hr>

                        <!-- Contact Information -->
                        <h6 class="text-primary mb-3 mt-4"><i class="fas fa-phone me-2"></i>Contact Information</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Landline#</label>
                                <input type="text" class="form-control" name="landline" value="{{ old('landline', $settings->landline) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Contact#</label>
                                <input type="text" class="form-control" name="contact_number" value="{{ old('contact_number', $settings->contact_number) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">WhatsApp#</label>
                                <input type="text" class="form-control" name="whatsapp_number" value="{{ old('whatsapp_number', $settings->whatsapp_number) }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <input type="text" class="form-control" name="email" value="{{ old('email', $settings->email) }}" autocomplete="off" placeholder="example@domain.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contact / Inquiry Email</label>
                                <input type="text" class="form-control" name="inquiry_email" value="{{ old('inquiry_email', $settings->inquiry_email) }}" autocomplete="off" placeholder="inquiry@domain.com">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Contact / Inquiry Password</label>
                            <input type="text" class="form-control" name="inquiry_password" value="{{ old('inquiry_password', $settings->inquiry_password) }}" autocomplete="new-password" placeholder="Leave blank to keep current">
                            <small class="text-muted">Used for inquiry email account access</small>
                        </div>

                        <hr>

                        <!-- Bank Details -->
                        <h6 class="text-primary mb-3 mt-4"><i class="fas fa-university me-2"></i>Bank Details for Vouchers</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Bank Name</label>
                                <input type="text" class="form-control" name="bank_name" value="{{ old('bank_name', $settings->bank_name) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Account Number</label>
                                <input type="text" class="form-control" name="bank_account_number" value="{{ old('bank_account_number', $settings->bank_account_number) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Branch Name / Code</label>
                                <input type="text" class="form-control" name="bank_branch" value="{{ old('bank_branch', $settings->bank_branch) }}">
                            </div>
                        </div>

                        <hr>

                        <!-- Social Media -->
                        <h6 class="text-primary mb-3 mt-4"><i class="fas fa-share-alt me-2"></i>Social Media</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Facebook URL</label>
                                <input type="url" class="form-control" name="facebook_url" value="{{ old('facebook_url', $settings->facebook_url) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Twitter URL</label>
                                <input type="url" class="form-control" name="twitter_url" value="{{ old('twitter_url', $settings->twitter_url) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Instagram URL</label>
                                <input type="url" class="form-control" name="instagram_url" value="{{ old('instagram_url', $settings->instagram_url) }}">
                            </div>
                        </div>

                        <hr>

                        <!-- Additional Information -->
                        <h6 class="text-primary mb-3 mt-4"><i class="fas fa-map-marker-alt me-2"></i>Additional Information</h6>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Institute Motto</label>
                            <textarea class="form-control" name="motto" rows="2">{{ old('motto', $settings->motto) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Institute Address</label>
                            <textarea class="form-control" name="address" rows="3">{{ old('address', $settings->address) }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Google Map Address (Embed URL)</label>
                            <textarea class="form-control" name="google_map_url" rows="2" placeholder="Paste Google Maps embed URL">{{ old('google_map_url', $settings->google_map_url) }}</textarea>
                            <small class="text-muted">Get embed code from Google Maps → Share → Embed a map</small>
                        </div>

                        {{-- About Us Content --}}
                        <h6 class="text-primary mb-3 mt-4"><i class="fas fa-info-circle me-2"></i>About Us Content</h6>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Our History</label>
                            <textarea class="form-control" name="our_history" rows="4" placeholder="Enter the college history...">{{ old('our_history', $settings->our_history) }}</textarea>
                            <small class="text-muted">This will appear on the About Us page</small>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Our Mission</label>
                                <textarea class="form-control" name="our_mission" rows="4" placeholder="Enter the mission statement...">{{ old('our_mission', $settings->our_mission) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Our Vision</label>
                                <textarea class="form-control" name="our_vision" rows="4" placeholder="Enter the vision statement...">{{ old('our_vision', $settings->our_vision) }}</textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
