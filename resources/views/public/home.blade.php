@extends('layouts.public')
@section('title', 'Home')

@section('content')

{{-- ============================================================
     NEWS TICKER
     ============================================================ --}}
<style>
    .news-ticker-container {
        background: #f8f9fa;
        border-bottom: 2px solid var(--col-primary);
        height: 44px;
        display: flex;
        align-items: center;
        overflow: hidden;
        position: relative;
        z-index: 1000;
    }
    .ticker-label {
        background: var(--col-primary);
        color: white;
        padding: 0 22px;
        height: 100%;
        display: flex;
        align-items: center;
        font-weight: 700;
        font-size: 0.78rem;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        white-space: nowrap;
        position: relative;
        z-index: 2;
        gap: 8px;
        flex-shrink: 0;
    }
    .ticker-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
        background: #ff4d4d;
        animation: tickerPulse 1.2s ease-in-out infinite;
        flex-shrink: 0;
    }
    @keyframes tickerPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(1.5); }
    }
    .ticker-content {
        flex: 1;
        overflow: hidden;
        display: flex;
        align-items: center;
        -webkit-mask-image: linear-gradient(to right, transparent 0, black 40px, black calc(100% - 40px), transparent 100%);
        mask-image: linear-gradient(to right, transparent 0, black 40px, black calc(100% - 40px), transparent 100%);
    }
    .ticker-scroll {
        display: inline-flex;
        white-space: nowrap;
        animation: ticker 40s linear infinite;
        padding-left: 100%;
    }
    .ticker-scroll:hover { animation-play-state: paused; }
    .ticker-item {
        display: inline-flex;
        align-items: center;
        margin-right: 60px;
        color: #444;
        text-decoration: none;
        font-size: 0.88rem;
        transition: color 0.3s;
    }
    .ticker-item:hover { color: var(--col-primary); }
    .ticker-badge {
        font-size: 0.68rem;
        padding: 2px 9px;
        border-radius: 20px;
        margin-right: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    @keyframes ticker {
        0% { transform: translateX(0); }
        100% { transform: translateX(-100%); }
    }
</style>

<div class="news-ticker-container shadow-sm">
    <div class="ticker-label">
        <span class="ticker-dot"></span>
        <i class="fas fa-rss" style="font-size:0.75rem;"></i> LIVE UPDATES
    </div>
    <div class="ticker-content">
        <div class="ticker-scroll">
            @if($admissionTicker)
                <a href="{{ $admissionTicker['url'] }}" class="ticker-item fw-bold text-danger">
                    <span class="ticker-badge bg-danger text-white">Admissions</span>
                    {{ $admissionTicker['label'] }}
                </a>
            @endif
            @if($examSummary)
                <div class="ticker-item">
                    <span class="ticker-badge bg-warning text-dark">Exams</span>
                    <i class="fas fa-calendar-check me-1 text-warning"></i> {{ $examSummary }}
                </div>
            @endif
            @foreach($tickerDownloads as $download)
                <a href="{{ route('download.file', $download->id) }}" class="ticker-item">
                    <span class="ticker-badge bg-primary text-white">Download</span>
                    <i class="fas fa-file-download me-1"></i> {{ $download->title }}
                </a>
            @endforeach
            @foreach($recentUpdates as $update)
                <a href="{{ route('public.news') }}" class="ticker-item">
                    <span class="ticker-badge bg-info text-white">News</span>
                    <i class="fas fa-newspaper me-1"></i> {{ $update->title }}
                </a>
            @endforeach
        </div>
    </div>
</div>

{{-- ============================================================
     HERO — Multi-image Swiper carousel with Ken Burns + typed text
     ============================================================ --}}
@php $settings = \App\Models\InstituteSetting::getSettings(); @endphp

<style>
.hero-swiper { width:100%; height:90vh; min-height:580px; position:relative; }
.hero-swiper .swiper-slide { position:relative; overflow:hidden; }
.hero-slide-bg {
    position:absolute; inset:0;
    background-size:cover; background-position:center; background-repeat:no-repeat;
    transform:scale(1.08);
    transition:transform 9s cubic-bezier(0.25,0.46,0.45,0.94);
}
.swiper-slide-active .hero-slide-bg { transform:scale(1); }
.hero-slide-overlay {
    position:absolute; inset:0;
    background:linear-gradient(160deg, rgba(5,25,55,0.88) 0%, rgba(10,30,60,0.68) 60%, rgba(5,15,40,0.5) 100%);
}
.hero-slide-content {
    position:absolute; inset:0;
    display:flex; align-items:center; justify-content:center; z-index:5;
}
.hero-swiper .swiper-button-next,
.hero-swiper .swiper-button-prev {
    background:rgba(255,255,255,0.12);
    backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px);
    border:1px solid rgba(255,255,255,0.2);
    border-radius:50%; width:52px; height:52px;
    color:white !important; transition:all 0.3s ease;
}
.hero-swiper .swiper-button-next:hover,
.hero-swiper .swiper-button-prev:hover { background:rgba(255,255,255,0.25); }
.hero-swiper .swiper-button-next::after,
.hero-swiper .swiper-button-prev::after { font-size:0.95rem !important; font-weight:900; }
.hero-swiper .swiper-pagination-bullet {
    background:rgba(255,255,255,0.4); opacity:1; width:10px; height:10px; transition:all 0.35s;
}
.hero-swiper .swiper-pagination-bullet-active {
    background:white; width:30px; border-radius:5px;
}
.hero-title { font-family:'Playfair Display','Georgia',serif; }
</style>

<section class="position-relative" style="z-index:1;">
    <div class="swiper hero-swiper">
        <div class="swiper-wrapper">
            @foreach($bannerSlides as $slide)
            <div class="swiper-slide">
                <div class="hero-slide-bg" style="background-image:url('{{ $slide->image_url }}');"></div>
                <div class="hero-slide-overlay"></div>
                <div class="hero-shape hero-shape-1"></div>
                <div class="hero-shape hero-shape-2"></div>

                <div class="hero-slide-content text-white text-center">
                    <div class="container px-4" data-aos="fade-up">

                        {{-- Floating badges --}}
                        <div class="mb-4 d-flex justify-content-center flex-wrap gap-2">
                            <span class="hero-badge"><i class="fas fa-trophy text-warning"></i> Top Ranked Institution</span>
                            <span class="hero-badge"><i class="fas fa-medal text-info"></i> 60+ Years of Excellence</span>
                        </div>

                        {{-- Headline --}}
                        @if($slide->title)
                            <h1 class="hero-title display-2 fw-bold mb-3"
                                style="text-shadow:0 4px 24px rgba(0,0,0,0.35);">
                                {{ $slide->title }}
                            </h1>
                            @if($slide->subtitle)
                            <p class="fs-4 fw-semibold mb-6" style="color:var(--col-highlight); min-height:1.6em; margin-bottom:2rem;">
                                {{ $slide->subtitle }}
                            </p>
                            @else
                            <p class="fs-4 fw-semibold mb-5" style="color:var(--col-highlight); min-height:1.6em;">
                                <span class="typed-tagline-{{ $loop->index }}"></span>
                            </p>
                            @endif
                        @else
                            <h1 class="hero-title display-2 fw-bold mb-3"
                                style="text-shadow:0 4px 24px rgba(0,0,0,0.35);">
                                Shaping Tomorrow's <br>
                                <span style="color:var(--col-highlight);">Leaders Today</span>
                            </h1>
                            <p class="fs-4 fw-semibold mb-5" style="color:var(--col-highlight); min-height:1.6em;">
                                <span class="typed-tagline-{{ $loop->index }}"></span>
                            </p>
                        @endif

                        {{-- CTA Buttons --}}
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="{{ route('public.about') }}"
                               class="btn btn-warning btn-lg px-5 rounded-pill fw-bold shine-btn shadow-lg"
                               style="letter-spacing:0.5px;">
                                <i class="fas fa-compass me-2"></i>Discover More
                            </a>
                            <a href="{{ route('public.contact') }}"
                               class="btn btn-outline-light btn-lg px-5 rounded-pill fw-bold shine-btn"
                               style="backdrop-filter:blur(8px); border-color:rgba(255,255,255,0.4);">
                                <i class="fas fa-envelope me-2"></i>Contact Us
                            </a>
                            @if(\App\Models\AdmissionApplication::isAnyOpen())
                            <a href="{{ route('apply.form') }}"
                               class="btn btn-success btn-lg px-5 rounded-pill fw-bold shine-btn shadow-lg btn-pulse">
                                <i class="fas fa-graduation-cap me-2"></i>Apply Now
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($bannerSlides->count() > 1)
        <div class="swiper-pagination" style="bottom:90px;"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
        @endif
    </div>

    <a href="#stats-band" class="scroll-chevron"><i class="fas fa-chevron-down"></i></a>

    {{-- Wave separator --}}
    <div class="position-absolute bottom-0 start-0 w-100 overflow-hidden" style="line-height:0; z-index:10;">
        <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"
             style="display:block; width:100%; height:80px;">
            <path fill="#051937" d="M0,40 C360,80 720,0 1080,40 C1260,60 1380,30 1440,40 L1440,80 L0,80 Z"/>
        </svg>
    </div>
</section>


{{-- ============================================================
     STATS BAND — Dark gradient with glassmorphic cards
     ============================================================ --}}
<section class="stats-band" id="stats-band">
    {{-- Decorative orbs --}}
    <div class="position-absolute" style="width:300px;height:300px;border-radius:50%;background:rgba(59,130,246,0.06);top:-100px;right:5%;pointer-events:none;"></div>
    <div class="position-absolute" style="width:200px;height:200px;border-radius:50%;background:rgba(139,92,246,0.06);bottom:-50px;left:8%;pointer-events:none;"></div>

    <div class="container position-relative">
        <div class="row g-4 justify-content-center">

            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-band-card s1">
                    <div class="stat-band-icon mx-auto" style="background:linear-gradient(135deg, var(--col-primary), var(--col-hover));">
                        <i class="fas fa-user-graduate text-white"></i>
                    </div>
                    <div>
                        <span class="stat-band-number" id="stat-students" data-target="{{ $stats['students'] }}">0</span>
                        <span class="stat-band-suffix">+</span>
                    </div>
                    <p class="stat-band-label">Students Enrolled</p>
                </div>
            </div>

            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-band-card s2">
                    <div class="stat-band-icon mx-auto" style="background:linear-gradient(135deg, var(--col-secondary), var(--col-mint));">
                        <i class="fas fa-chalkboard-teacher text-white"></i>
                    </div>
                    <div>
                        <span class="stat-band-number" id="stat-teachers" data-target="{{ $stats['teachers'] }}">0</span>
                        <span class="stat-band-suffix">+</span>
                    </div>
                    <p class="stat-band-label">Expert Faculty</p>
                </div>
            </div>

            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-band-card s3">
                    <div class="stat-band-icon mx-auto" style="background:linear-gradient(135deg, color-mix(in srgb, var(--col-primary) 70%, var(--col-secondary)), var(--col-secondary));">
                        <i class="fas fa-award text-white"></i>
                    </div>
                    <div>
                        <span class="stat-band-number" id="stat-graduated" data-target="{{ $stats['graduated'] }}">0</span>
                        <span class="stat-band-suffix">+</span>
                    </div>
                    <p class="stat-band-label">Graduates</p>
                </div>
            </div>

            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-band-card s4">
                    <div class="stat-band-icon mx-auto" style="background:linear-gradient(135deg, var(--col-dark), var(--col-primary));">
                        <i class="fas fa-university text-white"></i>
                    </div>
                    <div>
                        <span class="stat-band-number" id="stat-years" data-target="{{ $stats['years_active'] }}">0</span>
                        <span class="stat-band-suffix">+</span>
                    </div>
                    <p class="stat-band-label">Years of Legacy</p>
                </div>
            </div>

        </div>
    </div>

    {{-- Bottom wave --}}
    <div class="position-absolute bottom-0 start-0 w-100 overflow-hidden" style="line-height:0; z-index:5;">
        <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"
             style="display:block; width:100%; height:60px;">
            <path fill="#ffffff" d="M0,60 C480,0 960,60 1440,0 L1440,60 L0,60 Z"/>
        </svg>
    </div>
</section>


{{-- ============================================================
     PRINCIPAL'S MESSAGE
     ============================================================ --}}
<section class="py-5 pt-6" style="background:white; padding-top:80px;">
    <div class="container">
        <div class="row align-items-center g-5">

            {{-- Image side --}}
            <div class="col-lg-5 mb-5 mb-lg-0" data-aos="fade-right">
                <div class="principal-image-wrap d-inline-block w-100">
                    @if($currentPrincipal)
                        <img src="{{ $currentPrincipal->getImageUrl() }}"
                             alt="{{ $currentPrincipal->name }}"
                             class="img-fluid rounded-4 shadow-lg w-100"
                             style="max-height:400px; object-fit:cover; border:5px solid white;">
                    @else
                        <img src="https://images.unsplash.com/photo-1556157382-97eda2d62296?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
                             alt="Principal"
                             class="img-fluid rounded-4 shadow-lg w-100"
                             style="max-height:400px; object-fit:cover; border:5px solid white;">
                    @endif
                    {{-- Frosted name plate --}}
                    <div class="position-absolute bottom-0 start-0 end-0 m-4 rounded-3 p-3 text-white text-center"
                         style="background:rgba(5,25,55,0.7); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); border:1px solid rgba(255,255,255,0.12);">
                        <h5 class="mb-0 fw-bold">{{ $currentPrincipal ? $currentPrincipal->name : 'The Principal' }}</h5>
                        <small class="opacity-75">{{ $currentPrincipal ? $currentPrincipal->designation : 'Principal' }}</small>
                    </div>
                    {{-- Experience chip --}}
                    <div class="principal-experience-chip">
                        <i class="fas fa-star text-warning"></i>
                        Excellence
                    </div>
                </div>
            </div>

            {{-- Text side --}}
            <div class="col-lg-7" data-aos="fade-left">
                <span class="section-tag">From the Desk</span>
                <h2 class="mt-2 mb-4" style="font-family:'Playfair Display',serif; font-size:clamp(1.8rem,3vw,2.6rem); line-height:1.2; color:#0f172a;">
                    Inspiring Excellence, <br>
                    <span style="color:var(--col-primary);">Building Character</span>
                </h2>
                <div class="section-divider mb-4" style="justify-content:flex-start; margin-left:0;">
                    <div style="width:50px;height:2px;background:linear-gradient(90deg,var(--col-primary),transparent);"></div>
                    <div class="section-divider-dot"></div>
                    <div style="width:80px;height:2px;background:linear-gradient(90deg,var(--col-primary),transparent);"></div>
                </div>

                <div class="position-relative">
                    <div class="principal-quote-mark">"</div>
                    <div class="ps-3" style="border-left:4px solid var(--col-primary);">
                        @if($currentPrincipal && $currentPrincipal->details)
                            <p class="lead text-secondary mb-4" style="font-style:italic; line-height:1.9; font-size:1.05rem;">
                                {{ $currentPrincipal->getExcerpt(380) }}
                            </p>
                            <a href="{{ route('public.principal.show', $currentPrincipal->id) }}"
                               class="btn btn-primary rounded-pill px-5 shine-btn fw-semibold">
                                <i class="fas fa-book-open me-2"></i>Read Full Message
                            </a>
                        @else
                            <p class="lead text-secondary mb-4" style="font-style:italic; line-height:1.9; font-size:1.05rem;">
                                Education is not just about academic excellence, but also about character building
                                and moral values. We strive to provide a holistic environment where every student
                                can discover their potential and thrive.
                            </p>
                            <a href="{{ route('public.about') }}"
                               class="btn btn-primary rounded-pill px-5 shine-btn fw-semibold">
                                <i class="fas fa-info-circle me-2"></i>Learn More About Us
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


{{-- ============================================================
     WHY CHOOSE US — Premium gradient-fill feature cards
     ============================================================ --}}
<section class="py-5" style="background:linear-gradient(135deg,#f8fafc 0%,#eef2ff 100%); padding:80px 0;">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Our Strengths</span>
            <h2 class="mt-2 mb-2" style="font-family:'Playfair Display',serif; font-size:clamp(1.7rem,3vw,2.4rem); color:#0f172a;">
                Why Choose <span style="color:var(--col-primary);">GGC Chishtian?</span>
            </h2>
            <div class="section-divider mx-auto mb-3">
                <div class="section-divider-dot"></div>
            </div>
            <p class="text-muted mx-auto" style="max-width:540px; line-height:1.7;">
                A tradition of academic excellence combined with modern teaching methodologies to shape well-rounded leaders.
            </p>
        </div>

        <div class="row g-4">
            @php
            $features = [
                ['icon'=>'fas fa-users-cog','label'=>'Expert Faculty','desc'=>'Highly qualified and dedicated teachers committed to unlocking every student\'s potential.','bg'=>'linear-gradient(135deg, var(--col-primary), var(--col-hover))','cls'=>'fc-bg-1','num'=>'01'],
                ['icon'=>'fas fa-laptop-code','label'=>'Modern Curriculum','desc'=>'Up-to-date syllabi aligned with both national standards and global educational benchmarks.','bg'=>'linear-gradient(135deg, var(--col-secondary), var(--col-mint))','cls'=>'fc-bg-2','num'=>'02'],
                ['icon'=>'fas fa-seedling','label'=>'Holistic Development','desc'=>'Co-curricular activities that foster creativity, leadership, sports, and cultural values.','bg'=>'linear-gradient(135deg, color-mix(in srgb, var(--col-primary) 70%, var(--col-secondary)), var(--col-secondary))','cls'=>'fc-bg-3','num'=>'03'],
                ['icon'=>'fas fa-flask','label'=>'Research Focus','desc'=>'State-of-the-art labs encouraging scientific inquiry, innovation, and analytical thinking.','bg'=>'linear-gradient(135deg, var(--col-dark), var(--col-primary))','cls'=>'fc-bg-4','num'=>'04'],
                ['icon'=>'fas fa-hands-helping','label'=>'Community Values','desc'=>'Strong emphasis on ethics, service, civic responsibility, and national identity.','bg'=>'linear-gradient(135deg, var(--col-primary), var(--col-secondary))','cls'=>'fc-bg-5','num'=>'05'],
                ['icon'=>'fas fa-briefcase','label'=>'Career Ready','desc'=>'Comprehensive guidance and real-world exposure to prepare students for life beyond college.','bg'=>'linear-gradient(135deg, var(--col-secondary), var(--col-dark))','cls'=>'fc-bg-6','num'=>'06'],
            ];
            @endphp

            @foreach($features as $index => $feature)
            <div class="col-md-4 col-sm-6" data-aos="fade-up" data-aos-delay="{{ ($index % 3 + 1) * 100 }}">
                <div class="feature-card-v2 {{ $feature['cls'] }}">
                    <div class="fc-icon" style="background:{{ $feature['bg'] }};">
                        <i class="{{ $feature['icon'] }} text-white"></i>
                    </div>
                    <h5 class="text-dark">{{ $feature['label'] }}</h5>
                    <p class="text-muted small">{{ $feature['desc'] }}</p>
                    <div class="fc-number">{{ $feature['num'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>


{{-- ============================================================
     FACULTY SECTION — Premium arc-top cards + Swiper mobile
     ============================================================ --}}
<section class="py-5 bg-white" style="padding:80px 0;">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Our Team</span>
            <h2 class="mt-2" style="font-family:'Playfair Display',serif; font-size:clamp(1.7rem,3vw,2.4rem); color:#0f172a;">
                Meet Our Expert Faculty
            </h2>
            <div class="section-divider mx-auto mt-2">
                <div class="section-divider-dot"></div>
            </div>
        </div>

        {{-- Desktop: Premium arc cards --}}
        <div class="row g-4 d-none d-md-flex">
            @foreach($featuredStaff as $staff)
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="{{ $loop->iteration * 80 }}">
                <div class="faculty-card-v2">
                    <div class="faculty-card-top">
                        <img src="{{ $staff->getProfilePictureUrl() }}"
                             alt="{{ $staff->first_name }}"
                             class="faculty-avatar">
                    </div>
                    <div class="faculty-card-body">
                        <span class="faculty-dept-badge">{{ Str::limit($staff->designation ?? 'Faculty', 20) }}</span>
                        <h6 class="fw-bold mb-1 mt-1" style="font-size:1rem; color:#0f172a;">
                            {{ $staff->first_name }} {{ $staff->last_name }}
                        </h6>
                        <p class="text-muted small mb-3" style="font-size:0.8rem; line-height:1.5;">
                            {{ Str::limit($staff->highest_qualification, 45) }}
                        </p>
                        <a href="{{ route('public.staff') }}"
                           class="btn btn-sm btn-outline-primary rounded-pill px-3 shine-btn" style="font-size:0.8rem;">
                            <i class="fas fa-eye me-1"></i>View Profile
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Mobile: Swiper Carousel --}}
        <div class="d-md-none">
            <div class="swiper faculty-swiper" style="padding-bottom:40px;">
                <div class="swiper-wrapper">
                    @foreach($featuredStaff as $staff)
                    <div class="swiper-slide">
                        <div class="faculty-card-v2 mx-2">
                            <div class="faculty-card-top">
                                <img src="{{ $staff->getProfilePictureUrl() }}"
                                     alt="{{ $staff->first_name }}"
                                     class="faculty-avatar">
                            </div>
                            <div class="faculty-card-body">
                                <span class="faculty-dept-badge">{{ Str::limit($staff->designation ?? 'Faculty', 20) }}</span>
                                <h6 class="fw-bold mb-1 mt-1" style="font-size:1rem; color:#0f172a;">
                                    {{ $staff->first_name }} {{ $staff->last_name }}
                                </h6>
                                <p class="text-muted small mb-0" style="font-size:0.8rem;">
                                    {{ Str::limit($staff->highest_qualification, 40) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="swiper-pagination mt-3"></div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="{{ route('public.staff') }}" class="btn btn-primary rounded-pill px-5 shine-btn fw-semibold">
                <i class="fas fa-users me-2"></i>View All Faculty
            </a>
        </div>
    </div>
</section>


{{-- ============================================================
     ALUMNI SECTION
     ============================================================ --}}
@if(isset($alumni) && $alumni->count() > 0)
<section class="py-5" style="background:linear-gradient(135deg,#f8fafc 0%,#eef2ff 100%); padding:80px 0;">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Our Alumni</span>
            <h2 class="mt-2" style="font-family:'Playfair Display',serif; font-size:clamp(1.7rem,3vw,2.4rem); color:#0f172a;">
                Our Proud Graduates
            </h2>
            <div class="section-divider mx-auto mt-2">
                <div class="section-divider-dot"></div>
            </div>
        </div>

        <div id="alumniCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                @php $chunkedAlumni = $alumni->chunk(4); @endphp
                @foreach($chunkedAlumni as $index => $chunk)
                <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                    <div class="row g-4 justify-content-center">
                        @foreach($chunk as $alumnus)
                            @if($alumnus->student)
                            <div class="col-6 col-md-3">
                                <div class="alumni-card-v2">
                                    <img src="{{ $alumnus->student->getProfilePictureUrl() }}"
                                         alt="{{ $alumnus->student->first_name }}"
                                         class="alumni-avatar">
                                    <h6 class="fw-bold mb-1 text-dark">
                                        {{ $alumnus->student->first_name }} {{ $alumnus->student->last_name }}
                                    </h6>
                                    <p class="text-muted small mb-0">
                                        <i class="fas fa-graduation-cap me-1" style="color:var(--col-primary);"></i>
                                        Class of {{ $alumnus->admission_year }}
                                    </p>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @if($alumni->count() > 4)
            <div class="d-flex justify-content-center gap-2 mt-4">
                <button class="btn btn-outline-primary rounded-pill px-4 shine-btn btn-sm"
                        data-bs-target="#alumniCarousel" data-bs-slide="prev">
                    <i class="fas fa-arrow-left me-1"></i> Prev
                </button>
                <button class="btn btn-primary rounded-pill px-4 shine-btn btn-sm"
                        data-bs-target="#alumniCarousel" data-bs-slide="next">
                    Next <i class="fas fa-arrow-right ms-1"></i>
                </button>
            </div>
            @endif
        </div>
    </div>
</section>
@endif


{{-- ============================================================
     TESTIMONIALS — 3-column cards on dark gradient
     ============================================================ --}}
<section class="testimonial-v2-section">
    <div class="container position-relative">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag"
                  style="color:rgba(255,255,255,0.7); background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.1);">
                Voices of Excellence
            </span>
            <h2 class="mt-2 text-white" style="font-family:'Playfair Display',serif; font-size:clamp(1.7rem,3vw,2.4rem);">
                What Our Community Says
            </h2>
            <div class="section-divider mx-auto mt-2" style="--col-primary:rgba(255,255,255,0.5);">
                <div style="width:50px;height:1px;background:rgba(255,255,255,0.3);"></div>
                <div style="width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,0.5);"></div>
                <div style="width:50px;height:1px;background:rgba(255,255,255,0.3);"></div>
            </div>
        </div>

        @php
        $testimonials = [
            ['quote'=>'GGC Chishtian gave me not just a degree, but a purpose. The faculty here genuinely care about every student\'s growth and future.','author'=>'Alumni — BS Biology','icon'=>'fas fa-user-graduate','stars'=>5],
            ['quote'=>'The academic environment is truly unmatched. I developed critical thinking skills here that serve me every single day in my career.','author'=>'Alumni — Intermediate Pre-Medical','icon'=>'fas fa-stethoscope','stars'=>5],
            ['quote'=>'From state-of-the-art labs to an incredible library, everything is designed to inspire learning. Proud to be a graduate of this great institution.','author'=>'Alumni — ADP Computer Science','icon'=>'fas fa-laptop-code','stars'=>5],
        ];
        @endphp

        <div class="row g-4">
            @foreach($testimonials as $i => $t)
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="{{ ($i+1)*100 }}">
                <div class="testimonial-card-v2">
                    {{-- Stars --}}
                    <div class="star-rating mb-3">
                        @for($s=0;$s<$t['stars'];$s++)★@endfor
                    </div>
                    <p class="text-white mb-4" style="font-size:0.97rem; line-height:1.8; opacity:0.88; font-style:italic;">
                        "{{ $t['quote'] }}"
                    </p>
                    <div class="d-flex align-items-center gap-3 mt-auto">
                        <div class="testimonial-author-avatar">
                            <i class="{{ $t['icon'] }}"></i>
                        </div>
                        <div>
                            <p class="mb-0 fw-semibold text-white" style="font-size:0.9rem;">{{ $t['author'] }}</p>
                            <p class="mb-0 text-white-50" style="font-size:0.78rem;">Government Graduate College CTN</p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>


{{-- ============================================================
     DOWNLOADS — Premium icon-forward cards
     ============================================================ --}}
<section class="py-5 bg-white" style="padding:80px 0;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-5" data-aos="fade-up">
            <div>
                <span class="section-tag">Resources</span>
                <h2 class="mt-2 mb-0" style="font-family:'Playfair Display',serif; font-size:clamp(1.5rem,2.5vw,2.2rem); color:#0f172a;">
                    Latest Downloads
                </h2>
                <div class="section-divider mt-2" style="justify-content:flex-start; margin:8px 0 0;">
                    <div style="width:40px;height:2px;background:linear-gradient(90deg,var(--col-primary),transparent);"></div>
                    <div class="section-divider-dot"></div>
                    <div style="width:60px;height:2px;background:linear-gradient(90deg,var(--col-primary),transparent);"></div>
                </div>
            </div>
            <a href="{{ route('public.downloads') }}" class="btn btn-outline-primary rounded-pill btn-sm px-4 shine-btn">
                View All <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            @forelse($downloads as $download)
            @php
                $ext = strtoupper(pathinfo($download->file_name ?? 'file.pdf', PATHINFO_EXTENSION));
                $extMap = [
                    'PDF'  => ['bg'=>'#fee2e2','color'=>'#dc2626','icon'=>'fas fa-file-pdf'],
                    'DOC'  => ['bg'=>'#dbeafe','color'=>'#2563eb','icon'=>'fas fa-file-word'],
                    'DOCX' => ['bg'=>'#dbeafe','color'=>'#2563eb','icon'=>'fas fa-file-word'],
                    'XLS'  => ['bg'=>'#dcfce7','color'=>'#16a34a','icon'=>'fas fa-file-excel'],
                    'XLSX' => ['bg'=>'#dcfce7','color'=>'#16a34a','icon'=>'fas fa-file-excel'],
                    'JPG'  => ['bg'=>'#fef3c7','color'=>'#d97706','icon'=>'fas fa-file-image'],
                    'PNG'  => ['bg'=>'#fef3c7','color'=>'#d97706','icon'=>'fas fa-file-image'],
                    'ZIP'  => ['bg'=>'#f3e8ff','color'=>'#7c3aed','icon'=>'fas fa-file-archive'],
                ];
                $es = $extMap[$ext] ?? ['bg'=>'#f1f5f9','color'=>'#475569','icon'=>'fas fa-file'];
            @endphp
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="{{ ($loop->iteration - 1) % 3 * 100 + 100 }}">
                <div class="download-card-v2" style="--dc-color:{{ $es['color'] }};">
                    <div style="position:absolute;top:0;right:0;width:80px;height:80px;border-radius:0 20px 0 80px;background:{{ $es['bg'] }};opacity:0.6;"></div>

                    <div class="d-flex align-items-center gap-3 mb-3 position-relative">
                        <div class="download-icon-v2" style="background:{{ $es['bg'] }}; color:{{ $es['color'] }};">
                            <i class="{{ $es['icon'] }}" style="font-size:1.4rem;"></i>
                        </div>
                        <div>
                            <p class="mb-0 fw-bold text-dark">{{ Str::limit($download->title, 42) }}</p>
                            <small class="text-muted">{{ $download->formatted_date }}</small>
                        </div>
                    </div>

                    @if($download->description)
                    <p class="text-muted small mb-3 position-relative" style="line-height:1.6;">
                        {{ Str::limit($download->description, 80) }}
                    </p>
                    @endif

                    <div class="d-flex justify-content-between align-items-center mb-3 position-relative">
                        <span class="badge rounded-pill px-3 py-1 fw-semibold"
                              style="background:{{ $es['bg'] }}; color:{{ $es['color'] }}; font-size:0.7rem; letter-spacing:1px;">
                            {{ $ext ?: 'FILE' }}
                        </span>
                        <div class="d-flex align-items-center gap-3">
                            <small class="text-muted"><i class="fas fa-download me-1"></i>{{ $download->download_count }}</small>
                            <small class="text-muted">{{ $download->formatted_file_size }}</small>
                        </div>
                    </div>
                    <a href="{{ route('download.file', $download->id) }}"
                       class="btn btn-sm w-100 rounded-pill fw-semibold shine-btn position-relative"
                       style="background:{{ $es['bg'] }}; color:{{ $es['color'] }}; border:1px solid {{ $es['color'] }}20;">
                        <i class="fas fa-download me-1"></i> Download File
                    </a>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <i class="fas fa-folder-open fa-3x text-muted mb-3 d-block opacity-50"></i>
                <p class="text-muted">No downloads available at the moment.</p>
            </div>
            @endforelse
        </div>
    </div>
</section>


{{-- ============================================================
     NEWS & UPDATES — Premium cards with image zoom
     ============================================================ --}}
<section class="py-5" style="background:linear-gradient(135deg,#f8fafc 0%,#eef2ff 100%); padding:80px 0;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-5" data-aos="fade-up">
            <div>
                <span class="section-tag">News & Updates</span>
                <h2 class="mt-2 mb-0" style="font-family:'Playfair Display',serif; font-size:clamp(1.5rem,2.5vw,2.2rem); color:#0f172a;">
                    Latest Happenings
                </h2>
                <div class="section-divider mt-2" style="justify-content:flex-start; margin:8px 0 0;">
                    <div style="width:40px;height:2px;background:linear-gradient(90deg,var(--col-primary),transparent);"></div>
                    <div class="section-divider-dot"></div>
                    <div style="width:60px;height:2px;background:linear-gradient(90deg,var(--col-primary),transparent);"></div>
                </div>
            </div>
            <a href="{{ route('public.news') }}" class="btn btn-outline-primary rounded-pill btn-sm px-4 shine-btn">
                View All <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            @forelse($recentUpdates as $update)
            @php
                $typeCfg = [
                    1 => ['label'=>'News',         'pillBg'=>'#dbeafe','pillColor'=>'#2563eb','dot'=>'#3b82f6'],
                    2 => ['label'=>'Event',        'pillBg'=>'#dcfce7','pillColor'=>'#16a34a','dot'=>'#10b981'],
                    3 => ['label'=>'Announcement', 'pillBg'=>'#fef3c7','pillColor'=>'#d97706','dot'=>'#f59e0b'],
                ];
                $tc = $typeCfg[$update->type] ?? $typeCfg[1];
            @endphp
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="{{ $loop->iteration * 100 }}">
                <div class="news-card-v2">
                    <div class="news-img-wrap">
                        <img src="{{ $update->getImageUrl() }}" alt="{{ $update->title }}">
                        <div class="news-img-gradient"></div>
                        {{-- Floating type pill on image --}}
                        <div class="position-absolute top-0 start-0 m-3">
                            <span class="news-type-pill"
                                  style="background:{{ $tc['pillBg'] }}; color:{{ $tc['pillColor'] }}; border:1px solid {{ $tc['dot'] }}30;">
                                <span style="width:6px;height:6px;border-radius:50%;background:{{ $tc['dot'] }};display:inline-block;"></span>
                                {{ $tc['label'] }}
                            </span>
                        </div>
                        {{-- Date on image bottom --}}
                        <div class="position-absolute bottom-0 start-0 m-3">
                            <span style="background:rgba(0,0,0,0.5);backdrop-filter:blur(8px);color:rgba(255,255,255,0.9);border-radius:20px;padding:3px 12px;font-size:0.75rem;font-weight:600;">
                                <i class="far fa-calendar-alt me-1"></i>
                                {{ \Carbon\Carbon::parse($update->date)->format('M d, Y') }}
                            </span>
                        </div>
                    </div>
                    <div class="news-card-body-v2">
                        <h5>{{ Str::limit($update->title, 65) }}</h5>
                        <a href="{{ route('public.news') }}"
                           class="btn btn-sm rounded-pill px-4 shine-btn fw-semibold"
                           style="background:{{ $tc['pillBg'] }}; color:{{ $tc['pillColor'] }}; border:1px solid {{ $tc['dot'] }}30; font-size:0.82rem;">
                            Read More <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <i class="fas fa-newspaper fa-3x text-muted mb-3 d-block opacity-50"></i>
                <p class="text-muted">No recent updates available.</p>
            </div>
            @endforelse
        </div>
    </div>
</section>


{{-- ============================================================
     CALL TO ACTION — Dramatic with radial rings
     ============================================================ --}}
<section class="cta-v2 text-center">
    {{-- Decorative rings --}}
    <div class="cta-v2-ring" style="width:500px;height:500px;top:50%;left:50%;transform:translate(-50%,-50%);"></div>
    <div class="cta-v2-ring" style="width:700px;height:700px;top:50%;left:50%;transform:translate(-50%,-50%);"></div>
    <div class="cta-v2-ring" style="width:900px;height:900px;top:50%;left:50%;transform:translate(-50%,-50%);"></div>
    {{-- Icon ghosts --}}
    <div class="position-absolute" style="top:12%;left:4%;font-size:5rem;opacity:0.04;color:white;pointer-events:none;"><i class="fas fa-graduation-cap"></i></div>
    <div class="position-absolute" style="top:18%;right:6%;font-size:6rem;opacity:0.04;color:white;pointer-events:none;"><i class="fas fa-book-open"></i></div>
    <div class="position-absolute" style="bottom:14%;left:10%;font-size:4rem;opacity:0.04;color:white;pointer-events:none;"><i class="fas fa-flask"></i></div>
    <div class="position-absolute" style="bottom:18%;right:4%;font-size:4.5rem;opacity:0.04;color:white;pointer-events:none;"><i class="fas fa-award"></i></div>

    <div class="container position-relative" style="z-index:2;" data-aos="zoom-in">
        @if(\App\Models\AdmissionApplication::isAnyOpen())
            <div class="mb-4">
                <span class="badge fs-6 px-4 py-2 rounded-pill"
                      style="background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.2); backdrop-filter:blur(8px);">
                    <i class="fas fa-door-open me-2 text-warning"></i>Admissions Now Open
                </span>
            </div>
            <h2 class="cta-v2-heading">
                Admissions Open for<br>
                <span style="color:var(--col-highlight);">{{ $settings->current_admission_year ?? date('Y') }}</span>
            </h2>
            <p class="lead mb-5 text-white" style="opacity:0.75; max-width:520px; margin:0 auto 40px; line-height:1.7;">
                Join Govt. Graduate College Chishtian and secure your future.<br>
                Apply online today — seats are limited!
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
                @if(\App\Models\AdmissionApplication::areAdmissionsOpen('Inter'))
                <a href="{{ route('apply.form', ['program'=>'Inter']) }}"
                   class="btn btn-warning btn-lg rounded-pill px-5 fw-bold shine-btn btn-pulse shadow-lg">
                    <i class="fas fa-graduation-cap me-2"></i>Apply for HSSC (Intermediate)
                </a>
                @endif
                @if(\App\Models\AdmissionApplication::areAdmissionsOpen('ADP'))
                <a href="{{ route('apply.form', ['program'=>'ADP']) }}"
                   class="btn btn-success btn-lg rounded-pill px-5 fw-bold shine-btn shadow-lg">
                    <i class="fas fa-university me-2"></i>Apply for ADP
                </a>
                @endif
                @if(\App\Models\AdmissionApplication::areAdmissionsOpen('BS'))
                <a href="{{ route('apply.form', ['program'=>'BS']) }}"
                   class="btn btn-info btn-lg rounded-pill px-5 fw-bold shine-btn shadow-lg text-white">
                    <i class="fas fa-laptop-code me-2"></i>Apply for BS Programs
                </a>
                @endif
            </div>
            <a href="{{ route('apply.status') }}" class="btn btn-outline-light rounded-pill px-4 shine-btn">
                <i class="fas fa-search me-2"></i>Track Application Status
            </a>
        @else
            <h2 class="cta-v2-heading">Ready to Start <br>Your Journey?</h2>
            <p class="lead mb-5 text-white" style="opacity:0.75; max-width:480px; margin:0 auto 40px; line-height:1.7;">
                Discover academic excellence and a vibrant learning community.<br>
                Contact us for admission inquiries.
            </p>
            <a href="{{ route('public.contact') }}"
               class="btn btn-warning btn-lg rounded-pill px-5 fw-bold shine-btn btn-pulse shadow-lg">
                <i class="fas fa-envelope me-2"></i>Contact for Admissions
            </a>
        @endif
    </div>
</section>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Hero Swiper ──────────────────────────────────────────
    if (typeof Swiper !== 'undefined' && document.querySelector('.hero-swiper')) {
        new Swiper('.hero-swiper', {
            loop: true,
            speed: 1000,
            autoplay: { delay: 6000, disableOnInteraction: false },
            effect: 'fade',
            fadeEffect: { crossFade: true },
            pagination: { el: '.hero-swiper .swiper-pagination', clickable: true },
            navigation: {
                nextEl: '.hero-swiper .swiper-button-next',
                prevEl: '.hero-swiper .swiper-button-prev',
            },
        });
    }

    // ── Typed.js rotating taglines ──────────────────────────
    const taglineStrings = [
        'Academic Excellence.',
        'Moral Character.',
        'Lifelong Learning.',
        'Future Leaders.'
    ];
    document.querySelectorAll('[class^="typed-tagline-"]').forEach(function(el) {
        if (typeof Typed !== 'undefined') {
            new Typed(el, {
                strings: taglineStrings,
                typeSpeed: 60,
                backSpeed: 35,
                backDelay: 1800,
                loop: true,
                cursorChar: '|',
            });
        }
    });

    // ── CountUp on stats-band scroll ────────────────────────
    const statEls = [
        { id: 'stat-students',  target: parseInt(document.getElementById('stat-students')?.dataset.target  || 0) },
        { id: 'stat-teachers',  target: parseInt(document.getElementById('stat-teachers')?.dataset.target  || 0) },
        { id: 'stat-graduated', target: parseInt(document.getElementById('stat-graduated')?.dataset.target || 0) },
        { id: 'stat-years',     target: parseInt(document.getElementById('stat-years')?.dataset.target     || 0) },
    ];
    let statsAnimated = false;
    const statsBand = document.getElementById('stats-band');
    if (statsBand) {
        new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting && !statsAnimated) {
                statsAnimated = true;
                statEls.forEach(function(s) {
                    const el = document.getElementById(s.id);
                    if (!el) return;
                    if (typeof countUp !== 'undefined') {
                        const cu = new countUp.CountUp(s.id, s.target, { duration: 2.5, separator: ',' });
                        if (!cu.error) cu.start();
                    } else {
                        let cur = 0;
                        const step = s.target / (2000 / 16);
                        const timer = setInterval(function() {
                            cur = Math.min(cur + step, s.target);
                            el.textContent = Math.floor(cur).toLocaleString();
                            if (cur >= s.target) clearInterval(timer);
                        }, 16);
                    }
                });
            }
        }, { threshold: 0.3 }).observe(statsBand);
    }

    // ── Faculty Swiper (mobile) ──────────────────────────────
    if (typeof Swiper !== 'undefined' && document.querySelector('.faculty-swiper')) {
        new Swiper('.faculty-swiper', {
            slidesPerView: 1.25,
            spaceBetween: 16,
            centeredSlides: true,
            pagination: { el: '.faculty-swiper .swiper-pagination', clickable: true },
            breakpoints: { 480: { slidesPerView: 2, centeredSlides: false } }
        });
    }

    // ── Smooth scroll anchors ────────────────────────────────
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });

});
</script>
@endsection
