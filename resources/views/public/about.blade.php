 @extends('layouts.public')
@section('title', 'About Us - History, Mission & Vision')

@section('content')
@php
    $settings = \App\Models\InstituteSetting::getSettings();
    $bannerUrl = $settings->banner_url;
@endphp

{{-- Hero Header --}}
<section class="page-hero-v2 text-center">
    <div class="container position-relative" style="z-index: 3;" data-aos="fade-up">
        <div class="mb-3">
            <span class="hero-badge">
                <i class="fas fa-university text-warning"></i> Instituional Legacy
            </span>
        </div>
        <h1 class="mb-3">About Our College</h1>
        <p class="lead mb-0">
            A legacy of academic excellence, character building, and holistic student empowerment since 1962.
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
        <div class="row align-items-center g-4 g-lg-5 mb-5">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="position-relative">
                    <img src="{{ $bannerUrl }}" alt="College Building" class="img-fluid rounded-4 shadow-lg w-100" style="max-height: 420px; object-fit: cover; border: 4px solid white;">
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <span class="badge rounded-pill px-3 py-1 mb-2 text-primary" style="background-color: color-mix(in srgb, var(--col-primary) 10%, transparent);">
                    OUR HISTORY
                </span>
                <h2 class="fw-bold mb-4 text-dark" style="font-family: 'Playfair Display', serif;">
                    Decades of Educational Leadership
                </h2>
                @if($settings->our_history)
                    <p class="text-secondary mb-4 small" style="line-height: 1.8;">{!! nl2br(e($settings->our_history)) !!}</p>
                @else
                    <p class="text-secondary mb-3 small" style="line-height: 1.8;">
                        Govt. Graduate College Chishtian was established in 1962 with a vision to provide quality higher education to the youth of the region. Over the decades, it has grown into a premier institution, known for academic rigor and vibrant campus life.
                    </p>
                    <p class="text-secondary mb-4 small" style="line-height: 1.8;">
                        Our alumni serve in prestigious positions across the globe, contributing to society. We take pride in our dedicated faculty, modern infrastructure, and student-centric approach.
                    </p>
                @endif
                
                <div class="row g-3">
                    <div class="col-6">
                        <div class="d-flex align-items-center p-3 rounded-3 bg-white border">
                            <i class="fas fa-check-circle text-success fa-2x me-3 flex-shrink-0"></i>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark">Quality Standard</h6>
                                <small class="text-muted">Higher Education</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center p-3 rounded-3 bg-white border">
                            <i class="fas fa-check-circle text-success fa-2x me-3 flex-shrink-0"></i>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark">Modern Campus</h6>
                                <small class="text-muted">Labs & Library</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mission & Vision --}}
        <div class="row g-4">
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="page-card-v2 p-4 p-md-5 h-100 border-0 shadow-sm border-start border-4 border-primary">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-primary mb-3"
                         style="width: 54px; height: 54px; background: color-mix(in srgb, var(--col-primary) 12%, transparent); font-size: 1.4rem;">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3 class="fw-bold mb-3 text-dark" style="font-family: 'Playfair Display', serif;">Our Mission</h3>
                    @if($settings->our_mission)
                        <p class="text-secondary small mb-0" style="line-height: 1.8;">{!! nl2br(e($settings->our_mission)) !!}</p>
                    @else
                        <p class="text-secondary small mb-0" style="line-height: 1.8;">
                            To empower students with knowledge, skills, and moral values that enable them to become responsible leaders in their fields, fostering critical thinking and lifelong learning.
                        </p>
                    @endif
                </div>
            </div>
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="page-card-v2 p-4 p-md-5 h-100 border-0 shadow-sm border-start border-4 border-warning">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-warning mb-3"
                         style="width: 54px; height: 54px; background: rgba(245, 158, 11, 0.12); font-size: 1.4rem;">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="fw-bold mb-3 text-dark" style="font-family: 'Playfair Display', serif;">Our Vision</h3>
                    @if($settings->our_vision)
                        <p class="text-secondary small mb-0" style="line-height: 1.8;">{!! nl2br(e($settings->our_vision)) !!}</p>
                    @else
                        <p class="text-secondary small mb-0" style="line-height: 1.8;">
                            To be a premier center of excellence in higher education recognized for academic achievements, research contributions, and community impact.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
