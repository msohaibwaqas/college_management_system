@extends('layouts.app')
@section('title', 'HE Results Summary')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-file-invoice me-2"></i>Results Summary (BS/ADP)</h2>
            <p class="text-muted">Consolidated view of student results across subjects.</p>
        </div>
        <div class="col-md-4 text-end">
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="fas fa-print me-1"></i>Print Summary
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 d-print-none">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('he.results.summary') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="program_id" class="form-label">Program</label>
                    <select name="program_id" id="program_id" class="form-select">
                        <option value="">All Programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>
                                {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="semester_no" class="form-label">Semester</label>
                    <select name="semester_no" id="semester_no" class="form-select">
                        <option value="">All Semesters</option>
                        @for($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}" {{ request('semester_no') == $i ? 'selected' : '' }}>
                                Semester {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="academic_year" class="form-label">Year</label>
                    <select name="academic_year" id="academic_year" class="form-select">
                        <option value="">All Years</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" {{ request('academic_year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Content -->
    @forelse($studentResults as $admissionId => $results)
        @php 
            $firstResult = $results->first();
            $admission = $firstResult->heAdmission;
            $student = $admission->student ?? null;
        @endphp
        @if($admission && $student)
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold">{{ $student->first_name }} {{ $student->last_name }}</h5>
                    <small class="text-muted">Roll No: <strong>{{ $admission->roll_no }}</strong> | Program: {{ $admission->program->name }}</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-primary">Semester {{ $firstResult->semester_no }}</span>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="40%">Subject</th>
                            <th width="15%">Exam Type</th>
                            <th width="10%" class="text-center">Theory</th>
                            <th width="10%" class="text-center">Practical</th>
                            <th width="10%" class="text-center">Total</th>
                            <th width="15%" class="text-center">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            <tr>
                                <td>{{ $result->subject_name }}</td>
                                <td>
                                    <span class="badge rounded-pill bg-{{ $result->exam_type == 'Final' ? 'danger' : ($result->exam_type == 'Mid' ? 'warning' : 'info') }}">
                                        {{ $result->exam_type }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $result->is_absent ? 'A' : ($result->theory_marks ?? '-') }}</td>
                                <td class="text-center">{{ $result->practical_marks ?? '-' }}</td>
                                <td class="text-center fw-bold">{{ $result->total_obtained_marks }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $result->is_passed ? 'success' : 'danger' }}">
                                        {{ $result->grade }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @empty
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h5>No results found for the selected criteria.</h5>
                <p class="text-muted">Please adjust your filters and try again.</p>
            </div>
        </div>
    @endforelse
</div>

<style>
@media print {
    .d-print-none { display: none !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
    .table-dark { background-color: #fff !important; color: #000 !important; }
    .badge { border: 1px solid #000 !important; color: #000 !important; background: none !important; }
}
</style>
@endsection
