@extends('layouts.public')
@section('title', $album->title . ' - Photo Gallery')

@section('content')
{{-- Hero Header --}}
<section class="page-hero-v2 text-center">
    <div class="container position-relative" style="z-index: 3;" data-aos="fade-up">
        <div class="mb-3">
            <a href="{{ route('public.gallery') }}" class="hero-badge text-decoration-none">
                <i class="fas fa-arrow-left text-warning"></i> Back to Gallery
            </a>
        </div>
        <h1 class="mb-3">{{ $album->title }}</h1>
        <p class="lead mb-0">
            <i class="far fa-calendar-alt me-1 text-warning"></i> {{ \Carbon\Carbon::parse($album->date)->format('F d, Y') }} &bull; {{ $album->photos->count() }} Photos
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
        <div class="row g-3 g-md-4" id="lightgallery">
            @forelse($album->photos as $photo)
            <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 4 + 1) * 60 }}">
                <a href="{{ $photo->getImageUrl() }}" class="gallery-item text-decoration-none">
                    <div class="page-card-v2 border-0 shadow-sm overflow-hidden position-relative">
                        <img src="{{ $photo->getImageUrl() }}"
                             class="w-100"
                             alt="{{ $photo->title ?? $album->title }}"
                             style="height: 220px; object-fit: cover; transition: transform 0.5s ease;"
                             onmouseover="this.style.transform='scale(1.08)'"
                             onmouseout="this.style.transform='scale(1)'">
                        <div class="position-absolute bottom-0 start-0 end-0 p-2 text-white text-center"
                             style="background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);">
                            <small class="fw-bold"><i class="fas fa-search-plus me-1"></i> View Photo</small>
                        </div>
                    </div>
                </a>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-white rounded-4 shadow-sm d-inline-block">
                    <i class="fas fa-images fa-4x text-muted opacity-50 mb-3"></i>
                    <h4 class="text-dark fw-bold">No photos in this album</h4>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</section>

{{-- Lightbox Modal --}}
<div id="lightbox" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <img src="" class="img-fluid rounded-4 shadow-lg" id="lightbox-img" style="max-height: 85vh;">
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const galleryItems = document.querySelectorAll('.gallery-item');
        const lightboxEl = document.getElementById('lightbox');
        if (lightboxEl) {
            const lightbox = new bootstrap.Modal(lightboxEl);
            const lightboxImg = document.getElementById('lightbox-img');

            galleryItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    lightboxImg.src = this.href;
                    lightbox.show();
                });
            });
        }
    });
</script>
@endsection
