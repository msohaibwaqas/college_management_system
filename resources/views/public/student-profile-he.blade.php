@extends('layouts.public')
@section('title', 'Student Profile (' . $program->type . ')')

@section('content')
{{-- Modern Collegiate Top Banner --}}
<section class="page-hero-v2 py-4 mb-4">
    <div class="container position-relative" style="z-index: 3;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="hero-badge mb-2 d-inline-block">
                    <i class="fas fa-graduation-cap text-warning"></i> Higher Education Portal
                </span>
                <h2 class="text-white fw-bold mb-1" style="font-family: 'Playfair Display', serif;">
                    Student Profile <span class="fw-normal text-white-50">({{ $program->type }})</span>
                </h2>
                <p class="text-white-50 mb-0 small">Official academic and fee credentials record</p>
            </div>
            <a href="{{ route('public.students-corner') }}" class="btn btn-sm btn-outline-light rounded-pill px-4 shadow-sm fw-semibold" style="backdrop-filter: blur(8px); border-color: rgba(255,255,255,0.4);">
                <i class="fas fa-arrow-left me-1"></i> Back to Search
            </a>
        </div>
    </div>
    <div class="page-hero-wave">
        <svg viewBox="0 0 1440 30" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,15 C360,30 720,0 1080,15 C1260,22 1380,12 1440,15 L1440,30 L0,30 Z" fill="#f8fafc"></path>
        </svg>
    </div>
</section>

<div class="container mb-5">
    {{-- Student Basic Info --}}
    <div class="row mb-4">
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body p-4">
                    <img src="{{ $student->getProfilePictureUrl() }}" 
                             alt="{{ $student->full_name }}" 
                             class="img-fluid rounded-circle shadow mb-3"
                             style="width: 200px; height: 200px; object-fit: cover;">
                    <h4 class="fw-bold mb-1">{{ $student->full_name }}</h4>
                    <p class="text-muted mb-0">{{ $program->name }}</p>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-2">Semester {{ $admission->current_semester }}</span>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle"><i class="fas fa-user-graduate fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">Personal Information</h5></div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-user text-primary me-3 mt-1"></i>
                                <div>
                                    <small class="text-muted d-block">Student Name</small>
                                    <strong>{{ $student->full_name }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-user-tie text-primary me-3 mt-1"></i>
                                <div>
                                    <small class="text-muted d-block">Father Name</small>
                                    <strong>{{ $student->father->name ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-id-card text-primary me-3 mt-1"></i>
                                <div>
                                    <small class="text-muted d-block">University Roll No</small>
                                    <strong>{{ $admission->roll_no ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-calendar text-primary me-3 mt-1"></i>
                                <div>
                                    <small class="text-muted d-block">Admission Year</small>
                                    <strong>{{ $admission->admission_year }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-university text-primary me-3 mt-1"></i>
                                <div>
                                    <small class="text-muted d-block">Program</small>
                                    <strong>{{ $program->name }} ({{ $program->type }})</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-clock text-primary me-3 mt-1"></i>
                                <div>
                                    <small class="text-muted d-block">Status</small>
                                    <span class="badge bg-{{ $admission->getStatusColor() }}">{{ $admission->status }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Fee Vouchers --}}
    @if($student->vouchers->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle"><i class="fas fa-file-invoice-dollar fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">Fee Vouchers</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="padding-left: 15px;">Voucher No</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end" style="padding-right: 15px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($student->vouchers->sortByDesc('created_at') as $voucher)
                        <tr>
                            <td style="padding-left: 15px;"><strong>{{ $voucher->voucher_number }}</strong></td>
                            <td>{{ $voucher->issue_date ? $voucher->issue_date->format('d-M-Y') : '-' }}</td>
                            <td>{{ $voucher->due_date ? $voucher->due_date->format('d-M-Y') : '-' }}</td>
                            <td>Rs. {{ number_format($voucher->amount, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $voucher->status_badge }}">{{ ucfirst($voucher->status) }}</span>
                            </td>
                            <td class="text-end" style="padding-right: 15px;">
                                <a href="{{ route('public.students.voucher.download', $voucher) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                    <i class="fas fa-download me-1"></i> Download
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    
    {{-- Disciplinary & Academic Fines --}}
    @if($student->fines && $student->fines->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center"><div class="d-flex align-items-center"><span class="badge bg-danger-subtle text-danger me-2 p-2 rounded-circle"><i class="fas fa-exclamation-triangle fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">Fines &amp; Disciplinary Penalties</h5></div>
            @if($student->totalOutstandingFine() > 0)
                <span class="badge bg-light text-danger fw-bold fs-6">Outstanding: Rs. {{ number_format($student->totalOutstandingFine(), 2) }}</span>
            @else
                <span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i>All Fines Paid</span>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="padding-left: 15px;">Voucher #</th>
                            <th>Type & Description</th>
                            <th>Units / Rate</th>
                            <th>Total Fine</th>
                            <th>Waived / Paid</th>
                            <th>Payable Balance</th>
                            <th>Status</th>
                            <th class="text-end" style="padding-right: 15px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($student->fines->sortByDesc('id') as $fine)
                        <tr>
                            <td style="padding-left: 15px;">
                                <strong>FIN-{{ str_pad($fine->id, 5, "0", STR_PAD_LEFT) }}</strong>
                                <small class="text-muted d-block">{{ $fine->created_at ? $fine->created_at->format("d-M-Y") : "-" }}</small>
                            </td>
                            <td>
                                <strong class="text-dark">{{ $fine->formatted_type }}</strong>
                                <div class="small text-muted">{{ $fine->reason }}</div>
                                @if($fine->period)
                                    <span class="badge bg-secondary text-white" style="font-size: 0.75rem;">Period: {{ $fine->period }}</span>
                                @endif
                                @if($fine->exam_name)
                                    <span class="badge bg-secondary text-white" style="font-size: 0.75rem;">Exam: {{ $fine->exam_name }}</span>
                                @endif
                            </td>
                            <td>
                                {{ $fine->units_count }} &times; Rs. {{ number_format($fine->amount_per_unit, 2) }}
                            </td>
                            <td><strong>Rs. {{ number_format($fine->total_amount, 2) }}</strong></td>
                            <td>
                                @if($fine->waived_amount > 0)
                                    <span class="text-warning d-block" title="Waived amount">Waived: Rs. {{ number_format($fine->waived_amount, 2) }}</span>
                                @endif
                                @if($fine->paid_amount > 0)
                                    <span class="text-success d-block" title="Paid amount">Paid: Rs. {{ number_format($fine->paid_amount, 2) }}</span>
                                @endif
                                @if($fine->waived_amount == 0 && $fine->paid_amount == 0)
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($fine->balance_amount > 0)
                                    <strong class="text-danger">Rs. {{ number_format($fine->balance_amount, 2) }}</strong>
                                @else
                                    <span class="text-success fw-bold">Rs. 0.00</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $fine->status_badge }} text-uppercase px-2 py-1">
                                    {{ str_replace("_", " ", $fine->status) }}
                                </span>
                            </td>
                            <td class="text-end" style="padding-right: 15px;">
                                <a href="{{ route('public.students.fine.voucher', $fine->id) }}" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill">
                                    <i class="fas fa-file-invoice me-1"></i> Download Voucher
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-success"><i class="fas fa-shield-alt me-2"></i>Fines & Disciplinary Status</h5>
            <span class="badge bg-success"><i class="fas fa-check me-1"></i>No Dues</span>
        </div>
        <div class="card-body py-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle text-success fs-3 me-3"></i>
                <div>
                    <strong class="text-success d-block">No outstanding fines or disciplinary penalties recorded.</strong>
                    <small class="text-muted">Your disciplinary dues record is completely clear. No fine vouchers pending.</small>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Current Semester Subjects --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle"><i class="fas fa-book fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">Current Enrolled Subjects (Semester {{ $admission->current_semester }})</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 15%">Course Code</th>
                            <th style="width: 35%">Subject Name</th>
                            <th style="width: 25%" class="text-center">Attendance</th>
                            <th style="width: 10%">Credit</th>
                            <th style="width: 15%">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($currentSubjects as $item)
                        @php $subject = $item['bsSubject']; @endphp
                        <tr>
                            <td>{{ $subject->code }}</td>
                            <td><strong>{{ $subject->name }}</strong></td>
                            <td class="text-center">
                                @if($item['delivered'] > 0)
                                    <span class="fw-bold text-primary">{{ $item['attended'] }}</span> / {{ $item['delivered'] }}
                                    <small class="text-muted d-block">{{ number_format(($item['attended'] / $item['delivered']) * 100, 1) }}%</small>
                                @else
                                    <span class="text-muted">No records</span>
                                @endif
                            </td>
                            <td>{{ $subject->credit_hours }}</td>
                            <td>{{ $subject->is_compulsory ? 'Compulsory' : 'Elective' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No subjects enrolled in current semester</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Academic Results (By Semester) --}}
    @if($allResults->count() > 0)
    <h3 class="fw-bold mb-3"><i class="fas fa-chart-bar text-primary me-2"></i>Academic Record</h3>
    <div class="accordion mb-4" id="resultsAccordion">
        @foreach($allResults as $semester => $results)
        <div class="accordion-item shadow-sm border-0 mb-3">
            <h2 class="accordion-header" id="heading{{ $semester }}">
                <button class="accordion-button {{ $loop->last ? '' : 'collapsed' }} fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $semester }}">
                    Semester {{ $semester }} Results
                </button>
            </h2>
            <div id="collapse{{ $semester }}" class="accordion-collapse collapse {{ $loop->last ? 'show' : '' }}" data-bs-parent="#resultsAccordion">
                <div class="accordion-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Course Code</th>
                                    <th>Subject</th>
                                    <th class="text-center">Mid</th>
                                    <th class="text-center">Final</th>
                                    <th class="text-center">Practical</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Grade</th>
                                    <th class="text-center">GP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results->groupBy('bs_subject_id') as $subjectId => $subjectResults)
                                @php
                                    $subject = $subjectResults->first()->bsSubject;
                                    $mid = $subjectResults->where('exam_type', 'Mid')->first();
                                    $final = $subjectResults->where('exam_type', 'Final')->first();
                                    
                                    // Marks
                                    $midTheory = $mid ? ($mid->is_absent ? 'Absent' : $mid->theory_marks) : '-';
                                    $finalTheory = $final ? ($final->is_absent ? 'Absent' : $final->theory_marks) : '-';
                                    
                                    // Practical
                                    $midPrac = $mid ? $mid->practical_marks : 0;
                                    $finalPrac = $final ? $final->practical_marks : 0;
                                    $totalPractical = $midPrac + $finalPrac;
                                    
                                    // Totals
                                    $grandObtained = ($mid ? ($mid->is_absent ? 0 : $mid->total_obtained_marks) : 0) + 
                                                     ($final ? ($final->is_absent ? 0 : $final->total_obtained_marks) : 0);
                                                     
                                    $grandTotal = ($mid ? $mid->total_marks : 0) + ($final ? $final->total_marks : 0);
                                    
                                    // Grade Calculation (Simplified)
                                    $percentage = $grandTotal > 0 ? ($grandObtained / $grandTotal) * 100 : 0;
                                    $grade = 'F';
                                    if ($percentage >= 80) $grade = 'A+';
                                    elseif ($percentage >= 70) $grade = 'A';
                                    elseif ($percentage >= 60) $grade = 'B';
                                    elseif ($percentage >= 50) $grade = 'C';
                                    elseif ($percentage >= 40) $grade = 'D';

                                    // Use stored grade if only one exam exists and other is N/A?
                                    // If both exist, we need combined grade.
                                    // If only one exists (e.g. Mid only), use its grade?
                                    if (!$final && $mid) {
                                        $grade = $mid->grade;
                                    } elseif (!$mid && $final) {
                                        $grade = $final->grade;
                                    }

                                    // Calculate GP
                                    $gp = 0.0;
                                    if ($grade === 'A+' || $grade === 'A') $gp = 4.0;
                                    elseif ($grade === 'B') $gp = 3.0;
                                    elseif ($grade === 'C') $gp = 2.0;
                                    elseif ($grade === 'D') $gp = 1.0;
                                    else $gp = 0.0;
                                @endphp
                                <tr>
                                    <td>{{ $subject->code }}</td>
                                    <td>{{ $subject->name }}</td>
                                    <td class="text-center">{{ $midTheory }}</td>
                                    <td class="text-center">{{ $finalTheory }}</td>
                                    <td class="text-center">{{ $totalPractical > 0 ? $totalPractical : '-' }}</td>
                                    <td class="text-center fw-bold">{{ $grandObtained }} / {{ $grandTotal }}</td>
                                    <td class="text-center"><span class="badge bg-{{ $grade == 'F' ? 'danger' : 'success' }}">{{ $grade }}</span></td>
                                    <td class="text-center">{{ number_format($gp, 1) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Weekly Timetable --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle"><i class="fas fa-clock fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">Weekly Timetable</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 150px;">Time</th>
                            @foreach($heDays as $day)
                                <th>{{ $day }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hePeriods as $periodNum => $times)
                            <tr>
                                <td class="table-light">
                                    <strong>Period {{ $periodNum }}</strong><br>
                                    <small class="text-muted">{{ $times['start'] }} - {{ $times['end'] }}</small>
                                </td>
                                @foreach($heDays as $day)
                                    @php $entry = $heGrid[$periodNum][$day] ?? null; @endphp
                                    <td>
                                        @if($entry)
                                            <span class="d-block fw-bold text-primary">{{ $entry->subject->name }}</span>
                                            <small class="d-block text-muted">{{ $entry->staff->full_name }}</small>
                                            @if($entry->room)
                                                <span class="badge bg-light text-dark border"><i class="fas fa-door-open me-1"></i>{{ $entry->room->name }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Upcoming Exams --}}
    @if($upcomingExams->count() > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle"><i class="fas fa-calendar-alt fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">Upcoming Exams</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Subject</th>
                            <th>Exam Type</th>
                            <th>Date</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcomingExams as $exam)
                        <tr>
                            <td><strong>{{ $exam->bsSubject->name }}</strong></td>
                            <td><span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ $exam->exam_type }}</span></td>
                            <td>
                                <i class="fas fa-calendar text-primary me-2"></i>
                                {{ $exam->formatted_date }}
                            </td>
                            <td>
                                <i class="fas fa-clock text-info me-2"></i>
                                {{ $exam->formatted_time }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <div class="text-center">
        <a href="{{ route('public.students-corner') }}" class="btn btn-primary btn-lg rounded-pill px-5">
            <i class="fas fa-search me-2"></i>Search Another Student
        </a>
    </div>
</div>
@endsection
