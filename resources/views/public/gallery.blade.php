@extends('layouts.public')
@section('title', 'Photo Gallery - Campus Life & Events')

@section('content')
{{-- Hero Header --}}
<section class="page-hero-v2 text-center">
    <div class="container position-relative" style="z-index: 3;" data-aos="fade-up">
        <div class="mb-3">
            <span class="hero-badge">
                <i class="fas fa-camera text-info"></i> Campus Life
            </span>
        </div>
        <h1 class="mb-3">Campus Photo Gallery</h1>
        <p class="lead mb-0">
            A visual journey capturing vibrant academic events, sports, cultural festivals, and memorable moments at GGC Chishtian.
        </p>
    </div>
    <div class="page-hero-wave">
        <svg viewBox="0 0 1440 50" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,25 C360,50 720,0 1080,25 C1260,37 1380,20 1440,25 L1440,50 L0,50 Z" fill="#ffffff"></path>
        </svg>
    </div>
</section>

{{-- Gallery Grid --}}
<section class="py-5 bg-light position-relative" style="min-height: 50vh;">
    <div class="container py-3">
        <div class="row g-4">
            @forelse($albums as $album)
            <div class="col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 4 + 1) * 80 }}">
                <a href="{{ route('public.album', $album->id) }}" class="text-decoration-none">
                    <div class="page-card-v2 h-100 d-flex flex-column border-0 shadow-sm overflow-hidden">
                        {{-- Cover Image Wrap with Zoom Effect --}}
                        <div class="position-relative overflow-hidden" style="height: 220px;">
                            @if($album->photos->count() > 0)
                                <img src="{{ $album->photos->first()->getImageUrl() }}"
                                     class="w-100 h-100"
                                     style="object-fit: cover; transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1);"
                                     alt="{{ $album->title }}"
                                     onmouseover="this.style.transform='scale(1.1)'"
                                     onmouseout="this.style.transform='scale(1)'">
                            @else
                                <div class="w-100 h-100 bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center text-muted">
                                    <i class="fas fa-images fa-3x opacity-50"></i>
                                </div>
                            @endif

                            {{-- Glass Photo Count Pill --}}
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge rounded-pill px-3 py-2 text-white shadow-sm"
                                      style="background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.2); font-size: 0.75rem;">
                                    <i class="fas fa-images me-1 text-warning"></i> {{ $album->photos_count }} {{ Str::plural('Photo', $album->photos_count) }}
                                </span>
                            </div>

                            {{-- Date Pill --}}
                            <div class="position-absolute bottom-0 start-0 m-3">
                                <span class="badge rounded-pill px-3 py-1 text-white-50"
                                      style="background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); font-size: 0.72rem;">
                                    <i class="far fa-calendar-alt me-1"></i> {{ \Carbon\Carbon::parse($album->date)->format('M d, Y') }}
                                </span>
                            </div>
                        </div>

                        <div class="card-body p-4 d-flex flex-column justify-content-between flex-grow-1">
                            <h5 class="fw-bold text-dark mb-2" style="font-size: 1.05rem; line-height: 1.4;">
                                {{ $album->title }}
                            </h5>

                            <div class="pt-3 border-top mt-auto d-flex justify-content-between align-items-center">
                                <span class="text-primary fw-semibold small">Explore Album</span>
                                <span class="btn btn-sm btn-primary rounded-circle" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-arrow-right" style="font-size: 0.75rem;"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-white rounded-4 shadow-sm d-inline-block">
                    <i class="fas fa-images fa-4x text-muted opacity-50 mb-3"></i>
                    <h4 class="text-dark fw-bold">No gallery albums available</h4>
                    <p class="text-muted mb-0">Photo albums will appear here once uploaded.</p>
                </div>
            </div>
            @endforelse
        </div>

        @if($albums->hasPages())
        <div class="d-flex justify-content-center mt-5">
            {{ $albums->links() }}
        </div>
        @endif
    </div>
</section>
@endsection
