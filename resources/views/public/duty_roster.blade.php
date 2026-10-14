@extends('layouts.public')
@section('title', 'My Duty Roster - Staff Portal')

@section('content')
{{-- Hero Header --}}
<section class="page-hero-v2 text-center">
    <div class="container position-relative" style="z-index: 3;" data-aos="fade-up">
        <div class="mb-3">
            <span class="hero-badge">
                <i class="fas fa-user-clock text-warning"></i> Examination Branch
            </span>
        </div>
        <h1 class="mb-3">Staff Duty Roster</h1>
        <p class="lead mb-0">
            View your upcoming invigilation duties, assigned examination halls, and exam dates.
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
            <div class="col-lg-9">
                <div class="page-card-v2 border-0 shadow-sm overflow-hidden mb-4">
                    <div class="px-4 py-3 bg-white border-bottom d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold text-dark" style="font-family: 'Playfair Display', serif;">
                            <i class="fas fa-calendar-alt text-primary me-2"></i>My Exam Invigilation Roster
                        </h5>
                    </div>

                    <div class="p-4">
                        @if(isset($selectedStaff))
                            <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3 border">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white me-3 flex-shrink-0"
                                     style="width: 44px; height: 44px; background: linear-gradient(135deg, var(--col-primary), var(--col-dark)); font-size: 1.2rem;">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">{{ $selectedStaff->first_name }} {{ $selectedStaff->last_name }}</h6>
                                    <small class="text-muted">{{ $selectedStaff->designation }}</small>
                                </div>
                            </div>
                            
                            @if(count($assignments) > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Date</th>
                                                <th>Day</th>
                                                <th class="pe-4 text-end">Assigned Room</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($assignments as $assignment)
                                                <tr>
                                                    <td class="ps-4 fw-bold text-primary">
                                                        <i class="far fa-calendar-alt me-2"></i>
                                                        {{ \Carbon\Carbon::parse($assignment->exam_date)->format('d M Y') }}
                                                    </td>
                                                    <td class="fw-semibold text-dark">{{ \Carbon\Carbon::parse($assignment->exam_date)->format('l') }}</td>
                                                    <td class="pe-4 text-end">
                                                        <span class="badge bg-primary rounded-pill px-3 py-1.5 fw-bold">
                                                            <i class="fas fa-door-open me-1"></i> {{ $assignment->room->name ?? 'N/A' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info border-0 shadow-xs rounded-3 p-4 mb-0">
                                    <i class="fas fa-info-circle me-2"></i> No upcoming invigilation duties found for your account.
                                </div>
                            @endif
                        @else
                            <div class="alert alert-warning border-0 shadow-xs rounded-3 p-4 mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i> No staff profile is currently linked to your account.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
