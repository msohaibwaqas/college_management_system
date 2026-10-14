@extends('layouts.public')
@section('title', 'Contact Us - Get in Touch')

@section('content')
@php
    $settings = \App\Models\InstituteSetting::getSettings();
@endphp

{{-- Hero Header --}}
<section class="page-hero-v2 text-center">
    <div class="container position-relative" style="z-index: 3;" data-aos="fade-up">
        <div class="mb-3">
            <span class="hero-badge">
                <i class="fas fa-envelope-open-text text-warning"></i> Connect With Us
            </span>
        </div>
        <h1 class="mb-3">Get in Touch</h1>
        <p class="lead mb-0">
            Have questions regarding admissions, faculty, or academic programs? We're here to assist you.
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
        <div class="row g-5 align-items-stretch">
            
            {{-- Left: Contact Details --}}
            <div class="col-lg-5" data-aos="fade-right">
                <div class="page-card-v2 p-4 p-md-5 h-100 border-0 shadow-sm d-flex flex-column justify-content-between">
                    <div>
                        <span class="badge rounded-pill px-3 py-1 mb-2 text-primary" style="background-color: color-mix(in srgb, var(--col-primary) 10%, transparent);">
                            CAMPUS DIRECTORY
                        </span>
                        <h3 class="fw-bold text-dark mb-4" style="font-family: 'Playfair Display', serif;">Contact Information</h3>
                        <p class="text-muted small mb-4" style="line-height: 1.8;">
                            Our administrative staff and admission desk are ready to assist students, parents, and alumni. Visit our campus or reach out through telephone or email.
                        </p>

                        <div class="d-flex mb-4">
                            <div class="rounded-circle me-3 d-flex align-items-center justify-content-center text-warning shadow-xs flex-shrink-0"
                                 style="width: 48px; height: 48px; background: rgba(245, 158, 11, 0.12); font-size: 1.2rem;">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Campus Location</h6>
                                <p class="text-secondary small mb-0">{{ $settings->address ?? 'College Road, Chishtian, District Bahawalnagar, Punjab, Pakistan' }}</p>
                            </div>
                        </div>

                        <div class="d-flex mb-4">
                            <div class="rounded-circle me-3 d-flex align-items-center justify-content-center text-primary shadow-xs flex-shrink-0"
                                 style="width: 48px; height: 48px; background: color-mix(in srgb, var(--col-primary) 12%, transparent); font-size: 1.2rem;">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Phone Inquiry</h6>
                                @if($settings->contact_number || $settings->landline)
                                    <a href="tel:{{ $settings->contact_number ?? $settings->landline }}" class="text-secondary small d-block text-decoration-none fw-semibold">
                                        {{ $settings->contact_number ?? $settings->landline }}
                                    </a>
                                @else
                                    <p class="text-secondary small mb-0">+92 (063) 250-XXXX</p>
                                @endif
                                <small class="text-muted opacity-75">Office Hours: Mon-Sat, 8:00 AM - 2:00 PM</small>
                            </div>
                        </div>

                        <div class="d-flex mb-4">
                            <div class="rounded-circle me-3 d-flex align-items-center justify-content-center text-success shadow-xs flex-shrink-0"
                                 style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.12); font-size: 1.2rem;">
                                <i class="fas fa-envelope-open-text"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Email Desk</h6>
                                @if($settings->email)
                                    <a href="mailto:{{ $settings->email }}" class="text-secondary small d-block text-decoration-none">
                                        {{ $settings->email }}
                                    </a>
                                @endif
                                @if($settings->inquiry_email && $settings->inquiry_email != $settings->email)
                                    <a href="mailto:{{ $settings->inquiry_email }}" class="text-secondary small d-block text-decoration-none">
                                        {{ $settings->inquiry_email }}
                                    </a>
                                @endif
                                @if(!$settings->email && !$settings->inquiry_email)
                                    <p class="text-secondary small mb-0">info@ggcctn.edu.pk</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($settings->google_map_url)
                    <div class="pt-3 border-top mt-4">
                        <a href="{{ $settings->google_map_url }}" target="_blank" class="btn btn-outline-primary rounded-pill btn-sm w-100 fw-semibold">
                            <i class="fas fa-map-marked-alt me-2"></i>Open in Google Maps
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Right: Contact Form --}}
            <div class="col-lg-7" data-aos="fade-left">
                <div class="page-card-v2 p-4 p-md-5 h-100 border-0 shadow-sm">
                    <h3 class="fw-bold text-dark mb-2" style="font-family: 'Playfair Display', serif;">Send us an Inquiry</h3>
                    <p class="text-muted small mb-4">Fill out the quick form below and our administrative office will get back to you shortly.</p>

                    <form onsubmit="alert('Thank you for contacting us! We will reply to your message soon.'); return false;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small">Your Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3 py-2.5 fs-6" placeholder="e.g. Sohaib Waqas" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control rounded-3 py-2.5 fs-6" placeholder="name@example.com" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold text-dark small">Subject / Purpose <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3 py-2.5 fs-6" placeholder="e.g. Admission Inquiry, Document Verification" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold text-dark small">Your Message <span class="text-danger">*</span></label>
                                <textarea class="form-control rounded-3 p-3 fs-6" rows="5" placeholder="Write your inquiry or question here..." required></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill w-100 fw-bold shine-btn shadow-sm py-3 fs-6">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- Map Embed Section --}}
<div class="container-fluid p-0">
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3466.674747474747!2d72.0!3d29.0!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjnCsDU1JzAwLjAiTiA3MsKwMDAnMDAuMCJF!5e0!3m2!1sen!2s!4v1620000000000!5m2!1sen!2s"
            width="100%" height="400" style="border:0; filter: contrast(1.05);" allowfullscreen="" loading="lazy"></iframe>
</div>
@endsection
