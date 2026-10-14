@extends('layouts.public')
@section('title', 'Downloads & Resource Center')

@section('content')
{{-- Hero Header --}}
<section class="page-hero-v2 text-center">
    <div class="container position-relative" style="z-index: 3;" data-aos="fade-up">
        <div class="mb-3">
            <span class="hero-badge">
                <i class="fas fa-file-download text-warning"></i> Resource Center
            </span>
        </div>
        <h1 class="mb-3">Downloads & Public Documents</h1>
        <p class="lead mb-0">
            Access official college prospectuses, academic datesheets, syllabus guides, notifications, and application forms.
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
        
        {{-- Search Bar --}}
        <div class="row mb-5 justify-content-center" data-aos="fade-up">
            <div class="col-md-8 col-lg-6">
                <form action="{{ route('public.downloads') }}" method="GET" class="shadow-sm rounded-pill overflow-hidden bg-white d-flex p-1 border">
                    <input type="text" name="search" class="form-control border-0 px-4 py-2 flex-grow-1 fs-6" 
                           placeholder="Search documents, datesheets, forms..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shine-btn">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </form>
                @if(request('search'))
                    <div class="text-center mt-3">
                        <a href="{{ route('public.downloads') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                            <i class="fas fa-times me-1"></i> Clear Filter: "{{ request('search') }}"
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Downloads Grid --}}
        <div class="row g-4" data-aos="fade-up">
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
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 3 + 1) * 80 }}">
                <div class="download-card-v2 h-100 d-flex flex-column justify-content-between p-4 bg-white rounded-4 border-0 shadow-sm transition-all"
                     style="--dc-color:{{ $es['color'] }};">
                    
                    <div>
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="download-icon-v2 flex-shrink-0" style="background:{{ $es['bg'] }}; color:{{ $es['color'] }}; width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                                <i class="{{ $es['icon'] }}"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1" style="font-size: 1.05rem; line-height: 1.4;">
                                    {{ Str::limit($download->title, 55) }}
                                </h6>
                                <small class="text-muted"><i class="far fa-calendar-alt me-1"></i>{{ $download->formatted_date }}</small>
                            </div>
                        </div>

                        @if($download->description)
                        <p class="text-secondary small mb-3" style="line-height: 1.6;">
                            {{ Str::limit($download->description, 95) }}
                        </p>
                        @endif
                    </div>

                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3 pt-2 border-top">
                            <span class="badge rounded-pill px-3 py-1 fw-bold"
                                  style="background:{{ $es['bg'] }}; color:{{ $es['color'] }}; font-size: 0.72rem; letter-spacing: 1px;">
                                {{ $ext ?: 'FILE' }}
                            </span>
                            <div class="d-flex align-items-center gap-3">
                                <small class="text-muted"><i class="fas fa-download me-1" style="color: var(--col-primary);"></i>{{ $download->download_count }} downloads</small>
                                <small class="text-muted fw-semibold">{{ $download->formatted_file_size }}</small>
                            </div>
                        </div>

                        <a href="{{ route('download.file', $download->id) }}"
                           class="btn btn-sm w-100 rounded-pill fw-bold shine-btn py-2.5"
                           style="background:{{ $es['bg'] }}; color:{{ $es['color'] }}; border: 1px solid {{ $es['color'] }}30;">
                            <i class="fas fa-download me-1"></i> Download File
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-white rounded-4 shadow-sm d-inline-block">
                    <i class="fas fa-folder-open fa-4x text-muted opacity-50 mb-3"></i>
                    <h4 class="text-dark fw-bold">No download resources found</h4>
                    <p class="text-muted mb-0">Try searching with different terms or check back later.</p>
                </div>
            </div>
            @endforelse
        </div>

        @if($downloads->hasPages())
        <div class="mt-5 d-flex justify-content-center">
            {{ $downloads->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</section>

{{-- Features Section --}}
<section class="py-5 bg-white border-top">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="p-4 rounded-4 bg-light h-100">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-primary mb-3"
                         style="width: 54px; height: 54px; background: color-mix(in srgb, var(--col-primary) 12%, transparent); font-size: 1.4rem;">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Official & Verified</h5>
                    <p class="text-muted small mb-0">All files are officially uploaded and checked by college authorities.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 rounded-4 bg-light h-100">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-success mb-3"
                         style="width: 54px; height: 54px; background: rgba(16, 185, 129, 0.12); font-size: 1.4rem;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Real-time Updates</h5>
                    <p class="text-muted small mb-0">Latest examination datesheets and admission prospectuses added instantly.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 rounded-4 bg-light h-100">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-warning mb-3"
                         style="width: 54px; height: 54px; background: rgba(245, 158, 11, 0.12); font-size: 1.4rem;">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Need Assistance?</h5>
                    <p class="text-muted small mb-0">
                        <a href="{{ route('public.contact') }}" class="text-primary text-decoration-none fw-semibold">Contact us</a> for missing documents or inquiries.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
