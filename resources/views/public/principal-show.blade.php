@extends('layouts.public')
@section('title', 'Principal\'s Message - ' . $principal->name)

@section('content')
{{-- Hero Header --}}
<section class="page-hero-v2 text-center">
    <div class="container position-relative" style="z-index: 3;" data-aos="fade-up">
        <div class="mb-3">
            <a href="{{ route('public.principals') }}" class="hero-badge text-decoration-none">
                <i class="fas fa-crown text-warning"></i> Roll of Honor
            </a>
        </div>
        <h1 class="mb-3">Principal's Message</h1>
        <p class="lead mb-0">
            A message to students, faculty, and parents from {{ $principal->title }} {{ $principal->name }}.
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
        <div class="row g-4 g-lg-5">
            <div class="col-lg-4" data-aos="fade-right">
                <div class="page-card-v2 border-0 shadow-sm overflow-hidden sticky-top" style="top: 100px;">
                    <div class="position-relative">
                        <img src="{{ $principal->getImageUrl() }}" alt="{{ $principal->name }}" class="w-100 object-fit-cover" style="height: 340px;">
                    </div>
                    <div class="card-body p-4 text-center">
                        <h4 class="fw-bold mb-1 text-dark" style="font-family: 'Playfair Display', serif;">{{ $principal->title }} {{ $principal->name }}</h4>
                        <p class="text-primary fw-semibold mb-3 small">{{ $principal->designation }}</p>
                        
                        <div class="mb-3">
                            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                                <i class="far fa-calendar-alt me-1 text-primary"></i>
                                Tenure: {{ \Carbon\Carbon::parse($principal->from_date)->format('Y') }} — {{ $principal->to_date ? \Carbon\Carbon::parse($principal->to_date)->format('Y') : 'Present' }}
                            </span>
                        </div>

                        @if($principal->is_current)
                            <span class="badge bg-success px-3 py-1.5 rounded-pill mb-3 text-white fw-bold" style="font-size: 0.75rem;">CURRENT PRINCIPAL</span>
                        @endif
                        
                        <div class="pt-3 border-top mt-3">
                            <a href="{{ route('public.principals') }}" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold">
                                <i class="fas fa-history me-1"></i> View Roll of Honor
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8" data-aos="fade-left">
                <div class="page-card-v2 p-4 p-md-5 border-0 shadow-sm mb-4 border-start border-4 border-primary">
                    <div class="d-flex align-items-center mb-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-primary me-3 flex-shrink-0"
                             style="width: 48px; height: 48px; background: color-mix(in srgb, var(--col-primary) 12%, transparent); font-size: 1.4rem;">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <h2 class="mb-0 fw-bold text-dark" style="font-family: 'Playfair Display', serif;">Message to Students & Parents</h2>
                    </div>
                    
                    <div class="message-content text-dark" style="font-family: 'Georgia', serif; font-size: 1.08rem; line-height: 1.9; white-space: pre-line;">
                        @if($principal->details)
                            {!! $principal->details !!}
                        @else
                            <p class="text-muted italic mb-0">No message text available.</p>
                        @endif
                    </div>
                </div>

                <div class="page-card-v2 p-4 border-0 shadow-sm">
                    <h5 class="fw-bold text-dark mb-3" style="font-family: 'Playfair Display', serif;">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Profile Details
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0 small">
                            <tr>
                                <th class="ps-0 text-muted" width="140">Full Name</th>
                                <td class="fw-bold text-dark">{{ $principal->title }} {{ $principal->name }}</td>
                            </tr>
                            <tr>
                                <th class="ps-0 text-muted">Father's Name</th>
                                <td class="fw-bold text-dark">{{ $principal->father_name }}</td>
                            </tr>
                            <tr>
                                <th class="ps-0 text-muted">Tenure Start</th>
                                <td class="fw-bold text-dark">{{ \Carbon\Carbon::parse($principal->from_date)->format('d M, Y') }}</td>
                            </tr>
                            <tr>
                                <th class="ps-0 text-muted">Tenure End</th>
                                <td class="fw-bold text-dark">{{ $principal->to_date ? \Carbon\Carbon::parse($principal->to_date)->format('d M, Y') : 'Serving' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
