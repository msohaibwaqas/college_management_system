@extends('layouts.public')
@section('title', 'Principals History - Roll of Honor')

@section('content')
{{-- Hero Header --}}
<section class="page-hero-v2 text-center">
    <div class="container position-relative" style="z-index: 3;" data-aos="fade-up">
        <div class="mb-3">
            <span class="hero-badge">
                <i class="fas fa-crown text-warning"></i> Roll of Honor
            </span>
        </div>
        <h1 class="mb-3">Our Visionary Principals</h1>
        <p class="lead mb-0">
            Celebrating the distinguished leaders who have shaped the legacy, academic excellence, and character of Govt. Graduate College Chishtian.
        </p>
    </div>
    <div class="page-hero-wave">
        <svg viewBox="0 0 1440 50" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,25 C360,50 720,0 1080,25 C1260,37 1380,20 1440,25 L1440,50 L0,50 Z" fill="#ffffff"></path>
        </svg>
    </div>
</section>

{{-- Principals Grid --}}
<section class="py-5 bg-light position-relative" style="min-height: 50vh;">
    <div class="container py-3">
        <div class="row g-4 justify-content-center">
            @forelse($principals as $principal)
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="{{ $loop->iteration * 80 }}">
                <div class="page-card-v2 h-100 position-relative p-4 text-center d-flex flex-column justify-content-between border-0 shadow-sm"
                     style="{{ $principal->is_current ? 'border-top: 4px solid var(--col-primary) !important;' : '' }}">

                    @if($principal->is_current)
                        <span class="badge rounded-pill px-3 py-2 position-absolute top-0 end-0 m-3 shadow-sm text-white"
                              style="background: linear-gradient(135deg, var(--col-primary), var(--col-dark)); font-size: 0.72rem; letter-spacing: 1px;">
                            <i class="fas fa-star text-warning me-1"></i> CURRENT PRINCIPAL
                        </span>
                    @endif

                    <div>
                        {{-- Avatar container with halo ring --}}
                        <div class="position-relative d-inline-block my-3">
                            <div class="p-1 rounded-circle" style="background: linear-gradient(135deg, var(--col-primary), var(--col-accent)); width: 132px; height: 132px; margin: 0 auto;">
                                <img src="{{ $principal->getImageUrl() }}"
                                     alt="{{ $principal->name }}"
                                     class="img-fluid rounded-circle w-100 h-100"
                                     style="object-fit: cover; border: 3px solid white;">
                            </div>
                        </div>

                        <h4 class="fw-bold mb-1 text-dark" style="font-family: 'Playfair Display', serif;">
                            {{ $principal->title }} {{ $principal->name }}
                        </h4>
                        <span class="badge rounded-pill px-3 py-1 mb-3 fw-semibold"
                              style="background: color-mix(in srgb, var(--col-primary) 12%, transparent); color: var(--col-primary); font-size: 0.78rem;">
                            {{ $principal->designation }}
                        </span>

                        <div class="text-muted small mb-3">
                            <span class="badge bg-white text-secondary border px-3 py-2 rounded-pill shadow-xs">
                                <i class="far fa-calendar-alt me-1" style="color: var(--col-primary);"></i>
                                {{ \Carbon\Carbon::parse($principal->from_date)->format('M Y') }} — 
                                {{ $principal->to_date ? \Carbon\Carbon::parse($principal->to_date)->format('M Y') : 'Present' }}
                            </span>
                        </div>

                        @if($principal->details)
                            <p class="text-secondary small mb-4" style="line-height: 1.7; font-style: italic;">
                                "{{ $principal->getExcerpt(120) }}"
                            </p>
                        @endif
                    </div>

                    @if($principal->details)
                        <div class="pt-3 border-top mt-auto">
                            <a href="{{ route('public.principal.show', $principal->id) }}"
                               class="btn btn-outline-primary rounded-pill btn-sm px-4 fw-semibold shine-btn">
                                Read Full Message <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-white rounded-4 shadow-sm d-inline-block">
                    <i class="fas fa-crown fa-3x text-muted opacity-50 mb-3"></i>
                    <h4 class="text-dark fw-bold">No principal records available</h4>
                    <p class="text-muted mb-0">Check back later for updated records.</p>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
