@extends('layouts.public')
@section('title', isset($departmentName) ? $departmentName . ' Department - Faculty' : 'Our Faculty')

@section('content')
{{-- Hero Header --}}
<section class="page-hero-v2 text-center">
    <div class="container position-relative" style="z-index: 3;" data-aos="fade-up">
        <div class="mb-3">
            <span class="hero-badge">
                <i class="fas fa-users-cog text-info"></i> {{ isset($departmentName) ? $departmentName . ' Department' : 'Academic Faculty' }}
            </span>
        </div>
        <h1 class="mb-3">{{ isset($departmentName) ? $departmentName . ' Department' : 'Our Distinguished Faculty' }}</h1>
        <p class="lead mb-0">
            {{ isset($departmentName) ? 'Meet our highly qualified educators and subject specialists in the ' . $departmentName . ' Department.' : 'Dedicated educators, scholars, and mentors shaping academic excellence at Govt. Graduate College Chishtian.' }}
        </p>
    </div>
    <div class="page-hero-wave">
        <svg viewBox="0 0 1440 50" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,25 C360,50 720,0 1080,25 C1260,37 1380,20 1440,25 L1440,50 L0,50 Z" fill="#ffffff"></path>
        </svg>
    </div>
</section>

<section class="py-5 bg-light position-relative" style="min-height: 50vh;">
    <div class="container py-2">
        @if(isset($stats))
        {{-- Department Statistics Band --}}
        <div class="row g-4 mb-5" data-aos="fade-up">
            <div class="col-12">
                <div class="page-card-v2 p-4 border-0 shadow-sm">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                             style="width: 44px; height: 44px; background: linear-gradient(135deg, var(--col-primary), var(--col-dark)); font-size: 1.2rem;">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold text-dark" style="font-family: 'Playfair Display', serif;">Department Enrollment Overview</h5>
                            <small class="text-muted">Current student enrollment counts for {{ $departmentName }}</small>
                        </div>
                    </div>

                    <div class="row g-3 text-center">
                        <div class="col-md-4">
                            <div class="p-3 rounded-4 bg-light border">
                                <h3 class="fw-bold text-primary mb-0" style="font-family: 'Outfit', sans-serif;">{{ $stats['class_11'] }}</h3>
                                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 1px;">Class 11 Enrolled</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-4 bg-light border">
                                <h3 class="fw-bold text-success mb-0" style="font-family: 'Outfit', sans-serif;">{{ $stats['class_12'] }}</h3>
                                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 1px;">Class 12 Enrolled</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-4 bg-light border">
                                <h3 class="fw-bold text-info mb-0" style="font-family: 'Outfit', sans-serif;">{{ $stats['graduated'] }}</h3>
                                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 1px;">Graduated Alumni</span>
                            </div>
                        </div>
                    </div>

                    @if(!empty($stats['enrolled_by_year']))
                    <hr class="my-4 opacity-10">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <span class="fw-bold text-dark me-2 small">Enrollment breakdown:</span>
                        @foreach($stats['enrolled_by_year'] as $year => $count)
                            <span class="badge rounded-pill px-3 py-2 text-primary" style="background-color: color-mix(in srgb, var(--col-primary) 10%, transparent); font-size: 0.78rem;">
                                Batch {{ $year }}: <strong>{{ $count }}</strong>
                            </span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Faculty Grid --}}
        <div class="row g-4">
            @forelse($staffMembers as $staff)
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 4 + 1) * 80 }}">
                <div class="faculty-card-v2 h-100 border-0 shadow-sm d-flex flex-column justify-content-between position-relative">
                    
                    {{-- Top Status Pills --}}
                    <div class="position-absolute top-0 end-0 m-3" style="z-index: 5;">
                        <span class="badge rounded-pill text-white shadow-xs"
                              style="background: rgba(0,0,0,0.5); backdrop-filter: blur(6px); font-size: 0.68rem; letter-spacing: 0.5px;">
                            {{ strtoupper(str_replace('_', ' ', $staff->category)) }}
                        </span>
                    </div>

                    <div>
                        <div class="faculty-card-top">
                            <img src="{{ $staff->getProfilePictureUrl() }}"
                                 alt="{{ $staff->first_name }}"
                                 class="faculty-avatar">

                            @if($staff->bps)
                            <div class="position-absolute bottom-0 start-50 translate-middle-x bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center shadow-sm fw-bold"
                                 style="width: 32px; height: 32px; font-size: 0.75rem; border: 2px solid white; z-index: 5; margin-bottom: 20px;"
                                 title="BPS Grade {{ $staff->bps }}">
                                B{{ $staff->bps }}
                            </div>
                            @endif
                        </div>

                        <div class="faculty-card-body pt-2">
                            <span class="faculty-dept-badge">{{ Str::limit($staff->designation ?? 'Faculty', 25) }}</span>
                            <h5 class="fw-bold mb-1 text-dark" style="font-size: 1.05rem;">
                                {{ $staff->first_name }} {{ $staff->last_name }}
                            </h5>

                            @if($staff->highest_qualification)
                            <p class="text-muted small mb-2" style="font-size: 0.82rem;">
                                <i class="fas fa-graduation-cap text-warning me-1"></i> {{ Str::limit($staff->highest_qualification, 45) }}
                            </p>
                            @endif

                            @if($staff->category == 'teaching' && $staff->subjectSpecializations->isNotEmpty())
                            <div class="mb-2">
                                <span class="badge bg-light text-secondary border rounded-pill px-3 py-1 small" style="font-size: 0.72rem;">
                                    <i class="fas fa-book me-1 text-primary"></i> {{ $staff->subjectSpecializations->pluck('subject.name')->take(2)->join(', ') }}
                                </span>
                            </div>
                            @endif

                            @php
                                $displayEmail = $staff->email ?? ($staff->user?->email);
                            @endphp
                            @if($displayEmail)
                            <div class="small mt-2 px-1 text-break">
                                <a href="mailto:{{ $displayEmail }}" class="text-muted text-decoration-none" style="font-size: 0.78rem; word-break: break-all;" title="{{ $displayEmail }}">
                                    <i class="fas fa-envelope text-primary me-1"></i>{{ $displayEmail }}
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>

                    @if(isset($stats['teacher_stats'][$staff->id]))
                    <div class="p-3 border-top bg-light bg-opacity-50 rounded-bottom-4 mt-3">
                        <small class="fw-bold text-dark d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">STUDENT ALLOCATIONS</small>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($stats['teacher_stats'][$staff->id] as $year => $count)
                                <span class="badge rounded-pill text-primary bg-white border px-2 py-1" style="font-size: 0.7rem;">
                                    {{ $year }}: <strong>{{ $count }}</strong>
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-white rounded-4 shadow-sm d-inline-block">
                    <i class="fas fa-chalkboard-teacher fa-4x text-muted opacity-50 mb-3"></i>
                    <h4 class="text-dark fw-bold">No faculty members found</h4>
                    <p class="text-muted mb-0">Staff details will be displayed here once updated.</p>
                </div>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($staffMembers->hasPages())
        <div class="d-flex justify-content-center mt-5">
            {{ $staffMembers->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</section>
@endsection
