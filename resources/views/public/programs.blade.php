@extends('layouts.public')
@section('title', 'Academic Programs & Curricula')

@section('content')
{{-- Hero Header --}}
<section class="page-hero-v2 text-center">
    <div class="container position-relative" style="z-index: 3;" data-aos="fade-up">
        <div class="mb-3">
            <span class="hero-badge">
                <i class="fas fa-graduation-cap text-warning"></i> Degrees & Academics
            </span>
        </div>
        <h1 class="mb-3">Academic Programs</h1>
        <p class="lead mb-0">
            Explore our comprehensive educational pathways across Intermediate (HSSC), Associate Degrees (ADP), and Bachelor Studies (BS).
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
        {{-- Tabs Navigation --}}
        <div class="d-flex justify-content-center mb-5" data-aos="fade-up">
            <ul class="nav nav-pills shadow-sm rounded-pill p-1 bg-white border" id="programTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4 py-2.5 fw-bold" id="hssc-tab" data-bs-toggle="tab" data-bs-target="#hssc" type="button" role="tab">
                        <i class="fas fa-book-reader me-2"></i>Intermediate (HSSC)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 py-2.5 fw-bold" id="adp-tab" data-bs-toggle="tab" data-bs-target="#adp" type="button" role="tab">
                        <i class="fas fa-university me-2"></i>Associate Degree (ADP)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 py-2.5 fw-bold" id="bs-tab" data-bs-toggle="tab" data-bs-target="#bs" type="button" role="tab">
                        <i class="fas fa-user-graduate me-2"></i>Bachelor Studies (BS)
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content" id="programTabsContent">
            {{-- HSSC Section --}}
            <div class="tab-pane fade show active" id="hssc" role="tabpanel">
                <div class="row g-4">
                    {{-- Compulsory Subjects --}}
                    <div class="col-12" data-aos="fade-up">
                        <div class="page-card-v2 border-0 shadow-sm overflow-hidden mb-3">
                            <div class="px-4 py-3 text-white d-flex align-items-center justify-content-between"
                                 style="background: linear-gradient(135deg, var(--col-primary), var(--col-dark));">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-star me-2 text-warning"></i>Compulsory Subjects</h5>
                                <span class="badge bg-white text-dark rounded-pill px-3 py-1">Required for All Groups</span>
                            </div>
                            <div class="p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Code</th>
                                                <th>Subject Name</th>
                                                <th>Class Level</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($compulsorySubjects as $subject)
                                            <tr>
                                                <td class="fw-bold text-primary ps-4">{{ $subject->code }}</td>
                                                <td class="fw-semibold text-dark">{{ $subject->name }}</td>
                                                <td>
                                                    <span class="badge bg-light text-secondary border px-3 py-1 rounded-pill">
                                                        {{ $subject->class_level == 'both' ? 'Class 11 & 12' : 'Class ' . $subject->class_level }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Subject Groups (Electives) --}}
                    @foreach($subjectGroups as $group)
                    <div class="col-lg-6" data-aos="fade-up" data-aos-delay="{{ $loop->iteration * 80 }}">
                        <div class="page-card-v2 border-0 shadow-sm h-100 overflow-hidden">
                            <div class="px-4 py-3 bg-white border-bottom">
                                <h5 class="mb-0 fw-bold text-dark" style="font-family: 'Playfair Display', serif;">
                                    <i class="fas fa-layer-group me-2" style="color: var(--col-primary);"></i>
                                    {{ $group->name }}
                                </h5>
                            </div>
                            <div class="p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Code</th>
                                                <th>Elective Subject</th>
                                                <th>Level</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($group->subjects as $subject)
                                            <tr>
                                                <td class="fw-bold text-muted ps-4">{{ $subject->code }}</td>
                                                <td class="fw-semibold text-dark">{{ $subject->name }}</td>
                                                <td>
                                                    <span class="badge bg-light text-secondary border px-2.5 py-1 rounded-pill">
                                                        {{ $subject->class_level == 'both' ? 'Class 11 & 12' : 'Class ' . $subject->class_level }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-3">No elective subjects listed</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @if(\App\Models\AdmissionApplication::areAdmissionsOpen('Inter'))
                    <div class="text-center mt-5">
                        <a href="{{ route('apply.form', ['program' => 'Inter']) }}" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shine-btn shadow-lg">
                            <i class="fas fa-graduation-cap me-2"></i>Apply Online for Intermediate (HSSC)
                        </a>
                    </div>
                @endif
            </div>

            {{-- ADP Section --}}
            <div class="tab-pane fade" id="adp" role="tabpanel">
                <div class="row g-4">
                    @forelse($programs->get('ADP', []) as $program)
                    <div class="col-12" data-aos="fade-up">
                        <div class="page-card-v2 border-0 shadow-sm mb-4 overflow-hidden">
                            <div class="px-4 py-3 text-white d-flex justify-content-between align-items-center"
                                 style="background: linear-gradient(135deg, #10b981, #059669);">
                                <h5 class="mb-0 fw-bold">{{ $program->name }}</h5>
                                <span class="badge bg-white text-success rounded-pill px-3 py-1 fw-bold">{{ $program->total_semesters }} Semesters</span>
                            </div>
                            <div class="p-0">
                                <div class="accordion accordion-flush" id="accordionADP{{ $program->id }}">
                                    @php
                                        $semesters = $program->semesters->groupBy('semester_no')->sortKeys();
                                    @endphp
                                    @foreach($semesters as $semesterNo => $semesterSubjects)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseADP{{ $program->id }}S{{ $semesterNo }}">
                                                <i class="fas fa-book-open me-2 text-success"></i> Semester {{ $semesterNo }}
                                            </button>
                                        </h2>
                                        <div id="collapseADP{{ $program->id }}S{{ $semesterNo }}" class="accordion-collapse collapse" data-bs-parent="#accordionADP{{ $program->id }}">
                                            <div class="accordion-body p-0">
                                                <table class="table table-sm table-hover mb-0 align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="ps-4">Code</th>
                                                            <th>Course Title</th>
                                                            <th>Credit Hours</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($semesterSubjects as $semSub)
                                                        <tr>
                                                            <td class="ps-4 fw-bold text-muted small">{{ $semSub->bsSubject->code }}</td>
                                                            <td class="fw-semibold text-dark">{{ $semSub->bsSubject->name }}</td>
                                                            <td><span class="badge bg-light text-dark border">{{ $semSub->bsSubject->theory_credit_hours + $semSub->bsSubject->practical_credit_hours }} Cr.</span></td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-center py-5">
                        <div class="p-5 bg-white rounded-4 shadow-sm d-inline-block">
                            <i class="fas fa-university fa-3x text-muted opacity-50 mb-3"></i>
                            <h4 class="text-dark fw-bold">No ADP programs listed</h4>
                        </div>
                    </div>
                    @endforelse
                </div>
                @if(\App\Models\AdmissionApplication::areAdmissionsOpen('ADP'))
                    <div class="text-center mt-4">
                        <a href="{{ route('apply.form', ['program' => 'ADP']) }}" class="btn btn-success btn-lg rounded-pill px-5 fw-bold shine-btn shadow-lg">
                            <i class="fas fa-paper-plane me-2"></i>Apply Online for ADP
                        </a>
                    </div>
                @endif
            </div>

            {{-- BS Section --}}
            <div class="tab-pane fade" id="bs" role="tabpanel">
                <div class="row g-4">
                    @forelse($programs->get('BS', []) as $program)
                    <div class="col-12" data-aos="fade-up">
                        <div class="page-card-v2 border-0 shadow-sm mb-4 overflow-hidden">
                            <div class="px-4 py-3 text-white d-flex justify-content-between align-items-center"
                                 style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                                <h5 class="mb-0 fw-bold">{{ $program->name }}</h5>
                                <span class="badge bg-white text-primary rounded-pill px-3 py-1 fw-bold">{{ $program->total_semesters }} Semesters (4-Year)</span>
                            </div>
                            <div class="p-0">
                                <div class="accordion accordion-flush" id="accordionBS{{ $program->id }}">
                                    @php
                                        $semesters = $program->semesters->groupBy('semester_no')->sortKeys();
                                    @endphp
                                    @foreach($semesters as $semesterNo => $semesterSubjects)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBS{{ $program->id }}S{{ $semesterNo }}">
                                                <i class="fas fa-graduation-cap me-2 text-primary"></i> Semester {{ $semesterNo }}
                                            </button>
                                        </h2>
                                        <div id="collapseBS{{ $program->id }}S{{ $semesterNo }}" class="accordion-collapse collapse" data-bs-parent="#accordionBS{{ $program->id }}">
                                            <div class="accordion-body p-0">
                                                <table class="table table-sm table-hover mb-0 align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="ps-4">Code</th>
                                                            <th>Course Title</th>
                                                            <th>Credit Hours</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($semesterSubjects as $semSub)
                                                        <tr>
                                                            <td class="ps-4 fw-bold text-muted small">{{ $semSub->bsSubject->code }}</td>
                                                            <td class="fw-semibold text-dark">{{ $semSub->bsSubject->name }}</td>
                                                            <td><span class="badge bg-light text-dark border">{{ $semSub->bsSubject->theory_credit_hours + $semSub->bsSubject->practical_credit_hours }} Cr.</span></td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-center py-5">
                        <div class="p-5 bg-white rounded-4 shadow-sm d-inline-block">
                            <i class="fas fa-user-graduate fa-3x text-muted opacity-50 mb-3"></i>
                            <h4 class="text-dark fw-bold">No BS programs listed</h4>
                        </div>
                    </div>
                    @endforelse
                </div>
                @if(\App\Models\AdmissionApplication::areAdmissionsOpen('BS'))
                    <div class="text-center mt-4">
                        <a href="{{ route('apply.form', ['program' => 'BS']) }}" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shine-btn shadow-lg text-white">
                            <i class="fas fa-paper-plane me-2"></i>Apply Online for BS Degree
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<style>
    .nav-pills .nav-link {
        color: #475569 !important;
        transition: all 0.3s ease;
    }
    .nav-pills .nav-link.active {
        color: #ffffff !important;
        background-color: var(--col-primary) !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.15);
    }
</style>
@endsection
