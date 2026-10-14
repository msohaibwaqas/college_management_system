@extends('layouts.public')
@section('title', 'News & Updates - Campus Announcements')

@section('content')
{{-- Hero Header --}}
<section class="page-hero-v2 text-center">
    <div class="container position-relative" style="z-index: 3;" data-aos="fade-up">
        <div class="mb-3">
            <span class="hero-badge">
                <i class="fas fa-newspaper text-info"></i> Latest Bulletins
            </span>
        </div>
        <h1 class="mb-3">News & Campus Updates</h1>
        <p class="lead mb-0">
            Stay informed with the latest academic announcements, campus events, examination news, and college activities.
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
        <div class="row g-4">
            @forelse($updates as $update)
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 3 + 1) * 80 }}">
                <div class="news-card-v2 h-100 border-0 shadow-sm overflow-hidden d-flex flex-column justify-content-between">
                    <div>
                        <div class="news-img-wrap">
                            <img src="{{ $update->getImageUrl() }}" alt="{{ $update->title }}">
                            <div class="news-img-gradient"></div>
                            
                            {{-- Type pill --}}
                            <div class="position-absolute top-0 start-0 m-3">
                                @if($update->type == 1)
                                    <span class="badge rounded-pill bg-primary px-3 py-1 text-white shadow-xs">News</span>
                                @elseif($update->type == 2)
                                    <span class="badge rounded-pill bg-success px-3 py-1 text-white shadow-xs">Event</span>
                                @else
                                    <span class="badge rounded-pill bg-warning text-dark px-3 py-1 shadow-xs">Announcement</span>
                                @endif
                            </div>

                            {{-- Date --}}
                            <div class="position-absolute bottom-0 start-0 m-3">
                                <span class="badge rounded-pill text-white-50" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(6px); font-size: 0.75rem;">
                                    <i class="far fa-calendar-alt me-1"></i> {{ \Carbon\Carbon::parse($update->date)->format('M d, Y') }}
                                </span>
                            </div>
                        </div>

                        <div class="news-card-body-v2 p-4">
                            @if($update->category)
                                <span class="badge bg-light text-secondary border rounded-pill px-3 py-1 mb-2 small" style="font-size: 0.72rem;">
                                    {{ $update->category }}
                                </span>
                            @endif
                            <h5 class="fw-bold text-dark mb-0" style="font-size: 1.05rem; line-height: 1.4;">
                                {{ $update->title }}
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-white rounded-4 shadow-sm d-inline-block">
                    <i class="fas fa-newspaper fa-4x text-muted opacity-50 mb-3"></i>
                    <h4 class="text-dark fw-bold">No news or updates available</h4>
                    <p class="text-muted mb-0">Check back later for recent news and events.</p>
                </div>
            </div>
            @endforelse
        </div>

        @if($updates->hasPages())
        <div class="d-flex justify-content-center mt-5">
            {{ $updates->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</section>
@endsection
