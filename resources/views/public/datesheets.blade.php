@extends('layouts.public')
@section('title', 'Datesheets & Exam Schedules')

@section('content')
{{-- Hero Header --}}
<section class="page-hero-v2 text-center">
    <div class="container position-relative" style="z-index: 3;" data-aos="fade-up">
        <div class="mb-3">
            <span class="hero-badge">
                <i class="fas fa-calendar-check text-warning"></i> Examination Branch
            </span>
        </div>
        <h1 class="mb-3">Datesheets & Schedules</h1>
        <p class="lead mb-0">
            Download the latest examination schedules, practical exam timetables, and academic calendars.
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
        <div class="row justify-content-center" data-aos="fade-up">
            <div class="col-lg-10">
                <div class="page-card-v2 border-0 shadow-sm overflow-hidden mb-4">
                    <div class="px-4 py-3 bg-white border-bottom d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold text-dark" style="font-family: 'Playfair Display', serif;">
                            <i class="fas fa-file-invoice text-primary me-2"></i>Published Exam Datesheets
                        </h5>
                        <span class="badge bg-light text-primary border rounded-pill px-3 py-1 small">PDF Format</span>
                    </div>

                    <div class="p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="py-3 ps-4">Title & Details</th>
                                        <th class="py-3 text-center">Date Posted</th>
                                        <th class="py-3 pe-4 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($datesheets as $datesheet)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-danger bg-opacity-10 p-2.5 rounded-3 text-danger me-3 flex-shrink-0">
                                                    <i class="fas fa-file-pdf fa-lg"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold text-dark">{{ $datesheet->title }}</h6>
                                                    @if($datesheet->description)
                                                        <small class="text-muted d-block">{{ Str::limit($datesheet->description, 70) }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center text-muted small">
                                            <i class="far fa-calendar-alt me-1 text-primary"></i>
                                            {{ $datesheet->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="pe-4 text-end">
                                            <a href="{{ asset('storage/' . $datesheet->file_path) }}" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shine-btn" download>
                                                <i class="fas fa-download me-1"></i> Download
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-5">
                                            <div class="p-4 bg-white rounded-3 d-inline-block">
                                                <i class="fas fa-calendar-times fa-3x text-muted opacity-50 mb-3 d-block"></i>
                                                <h5 class="fw-bold text-dark">No active datesheets</h5>
                                                <p class="text-muted small mb-0">Exam datesheets will be listed here when released.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @if($datesheets->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $datesheets->links('pagination::bootstrap-5') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
