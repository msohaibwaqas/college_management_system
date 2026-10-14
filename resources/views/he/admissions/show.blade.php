@extends('layouts.app')
@section('title', 'HE Admission Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0"><i class="fas fa-id-card me-2"></i>Enrollment Details</h2>
            <div>
                <a href="{{ route('admissions.edit', $admission) }}" class="btn btn-primary me-2">
                    <i class="fas fa-edit me-1"></i>Edit
                </a>
                <a href="{{ route('admissions.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to List
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Student & Program Info -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="{{ $admission->student->profile_picture_url }}" class="rounded-circle img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">
                        <h4 class="mt-2 mb-0">{{ $admission->student->full_name }}</h4>
                        <p class="text-muted">Roll No: <strong>{{ $admission->roll_no }}</strong></p>
                    </div>
                    <hr>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th class="ps-0" style="width: 40%;">Program:</th>
                            <td class="pe-0"><strong>{{ $admission->program->name }}</strong></td>
                        </tr>
                        <tr>
                            <th class="ps-0">Type:</th>
                            <td class="pe-0"><span class="badge bg-info text-dark">{{ $admission->program->type }}</span></td>
                        </tr>
                        <tr>
                            <th class="ps-0">Semester:</th>
                            <td class="pe-0">Semester {{ $admission->current_semester }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0">Year:</th>
                            <td class="pe-0">{{ $admission->admission_year }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0">Status:</th>
                            <td class="pe-0"><span class="badge bg-success">{{ $admission->status }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Assigned Subjects (Academic History) -->
        <div class="col-md-8">
            @php
                $groupedSubjects = $admission->subjects->sortBy('semester_no')->groupBy('semester_no');
            @endphp

            @forelse($groupedSubjects as $semesterNo => $subjects)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header {{ $semesterNo == $admission->current_semester ? 'bg-primary' : 'bg-secondary' }} text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-book-open me-2"></i>
                            Semester {{ $semesterNo }}
                            @if($semesterNo == $admission->current_semester)
                                <span class="badge bg-light text-dark ms-2">Current</span>
                            @endif
                        </h5>
                        <span class="badge bg-light text-dark">{{ $subjects->count() }} Subjects</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Code</th>
                                        <th>Subject Name</th>
                                        <th>Credit Hours</th>
                                        <th>Type</th>
                                        <th class="pe-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subjects as $studentSubject)
                                        <tr>
                                            <td class="ps-4"><strong>{{ $studentSubject->bsSubject->code }}</strong></td>
                                            <td>{{ $studentSubject->bsSubject->name }}</td>
                                            <td>{{ ($studentSubject->bsSubject->theory_credit_hours ?? 0) + ($studentSubject->bsSubject->practical_credit_hours ?? 0) }}</td>
                                            <td><span class="badge bg-secondary text-white">{{ $studentSubject->bsSubject->type }}</span></td>
                                            <td class="pe-4">
                                                @php
                                                    $statusColor = match($studentSubject->status) {
                                                        'Enrolled' => 'primary',
                                                        'Passed' => 'success',
                                                        'Failed' => 'danger',
                                                        'Withdrawn' => 'warning',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $statusColor }}">{{ $studentSubject->status }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h4>No Subjects Found</h4>
                        <p class="text-muted">This student has not been enrolled in any subjects yet.</p>
                        <a href="{{ route('admissions.edit', $admission) }}" class="btn btn-primary mt-2">
                            <i class="fas fa-edit me-1"></i> Update Semester to Auto-Enroll
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
