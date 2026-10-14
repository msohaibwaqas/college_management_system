@extends('layouts.public')
@section('title', 'Students Corner - Roll & Profile Lookup')

@section('content')
{{-- Hero Header --}}
<section class="page-hero-v2 text-center">
    <div class="container position-relative" style="z-index: 3;" data-aos="fade-up">
        <div class="mb-3">
            <span class="hero-badge">
                <i class="fas fa-search-location text-warning"></i> Student Portal Lookup
            </span>
        </div>
        <h1 class="mb-3">Students Corner</h1>
        <p class="lead mb-0">
            Quickly search student profiles, roll numbers, BISE information, and fee status records by program and admission year.
        </p>
    </div>
    <div class="page-hero-wave">
        <svg viewBox="0 0 1440 50" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,25 C360,50 720,0 1080,25 C1260,37 1380,20 1440,25 L1440,50 L0,50 Z" fill="#ffffff"></path>
        </svg>
    </div>
</section>

<section class="py-5 bg-light position-relative">
    <div class="container py-2">
        <div class="row justify-content-center">
            <div class="col-lg-8" data-aos="fade-up">

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <div class="page-card-v2 p-4 p-md-5 border-0 shadow-sm mb-4">
                    <div class="text-center mb-4 pb-2">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 text-white shadow-sm"
                             style="width: 72px; height: 72px; background: linear-gradient(135deg, var(--col-primary), var(--col-dark)); font-size: 1.8rem;">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3 class="fw-bold text-dark" style="font-family: 'Playfair Display', serif;">Search Student Records</h3>
                        <p class="text-muted small">
                            Select your academic program & admission year, then enter your Roll No or Name to query results.
                        </p>
                    </div>

                    <form action="{{ route('public.students.search') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">
                                <i class="fas fa-graduation-cap me-1" style="color: var(--col-primary);"></i> Program Category <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-lg rounded-3 fs-6 @error('program_type') is-invalid @enderror" name="program_type" required>
                                <option value="">Select Academic Program</option>
                                <option value="hssc" {{ old('program_type') == 'hssc' ? 'selected' : '' }}>Intermediate (FSc/FA/ICS/ICom)</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}" {{ old('program_type') == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>
                                @endforeach
                            </select>
                            @error('program_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">
                                <i class="far fa-calendar-alt me-1" style="color: var(--col-primary);"></i> Admission Year <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-lg rounded-3 fs-6 @error('admission_year') is-invalid @enderror" name="admission_year" required>
                                <option value="">Select Admission Year</option>
                                @foreach($admissionYears as $year)
                                    <option value="{{ $year }}" {{ old('admission_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                            @error('admission_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">
                                <i class="fas fa-search me-1" style="color: var(--col-primary);"></i> Search Criteria <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-lg rounded-3 fs-6 @error('search_value') is-invalid @enderror"
                                   name="search_value"
                                   value="{{ old('search_value') }}"
                                   placeholder="e.g. College Roll No, BISE Roll No, Student Name, or Father Name"
                                   required>
                            @error('search_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted mt-2 small">
                                <i class="fas fa-info-circle me-1" style="color: var(--col-primary);"></i>
                                Search flexibly using College Roll Number, BISE 11/12 Roll Number, Student Name, or Father's Name.
                            </div>
                        </div>

                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <label class="form-label fw-bold text-dark mb-2">
                                <i class="fas fa-shield-alt me-1 text-success"></i> Security Verification <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold bg-white text-primary fs-6">{{ $captcha_question }} = </span>
                                <input type="number"
                                       class="form-control form-control-lg fs-6 @error('captcha') is-invalid @enderror"
                                       name="captcha"
                                       required
                                       placeholder="Calculate & enter answer">
                                @error('captcha')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shine-btn shadow-sm py-3 fs-6">
                                <i class="fas fa-search me-2"></i>Find Student Record
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Guide Box --}}
                <div class="page-card-v2 p-4 bg-white border-0 shadow-sm">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="fas fa-lightbulb text-warning me-2"></i>Quick Tips for Searching
                    </h5>
                    <ul class="text-secondary small mb-0 ps-3" style="line-height: 1.8;">
                        <li class="mb-1">Make sure to select the correct <strong>Admission Year</strong> from the dropdown menu.</li>
                        <li class="mb-1">You can type a partial or full <strong>Student Name</strong> or <strong>Father's Name</strong>.</li>
                        <li class="mb-1">For exact results, use the assigned <strong>College Roll Number</strong> (e.g. <code>2024-CS-101</code>).</li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
