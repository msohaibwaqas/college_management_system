@extends('layouts.app')
@section('title', 'HE Results')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-chart-bar me-2"></i>BS/ADP Results</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('he.results.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Enter Marks
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('he.results.index') }}" class="row g-3">
                <div class="col-md-3">
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
                <div class="col-md-2">
                    <label for="semester_no" class="form-label">Semester</label>
                    <select name="semester_no" id="semester_no" class="form-select">
                        <option value="">All</option>
                        @for($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}" {{ request('semester_no') == $i ? 'selected' : '' }}>
                                Semester {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="exam_type" class="form-label">Exam Type</label>
                    <select name="exam_type" id="exam_type" class="form-select">
                        <option value="">All</option>
                        <option value="Mid" {{ request('exam_type') == 'Mid' ? 'selected' : '' }}>Mid</option>
                        <option value="Sessional" {{ request('exam_type') == 'Sessional' ? 'selected' : '' }}>Sessional</option>
                        <option value="Final" {{ request('exam_type') == 'Final' ? 'selected' : '' }}>Final</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="academic_year" class="form-label">Year</label>
                    <select name="academic_year" id="academic_year" class="form-select">
                        <option value="">All</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" {{ request('academic_year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="locked" class="form-label">Status</label>
                    <select name="locked" id="locked" class="form-select">
                        <option value="">All</option>
                        <option value="locked" {{ request('locked') == 'locked' ? 'selected' : '' }}>Locked</option>
                        <option value="unlocked" {{ request('locked') == 'unlocked' ? 'selected' : '' }}>Unlocked</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Roll No</th>
                            <th>Student Name</th>
                            <th>Program</th>
                            <th>Subject</th>
                            <th>Semester</th>
                            <th>Exam Type</th>
                            <th>Theory</th>
                            <th>Practical</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results as $result)
                            <tr>
                                <td>{{ $result->heAdmission->roll_no ?? 'N/A' }}</td>
                                <td>{{ $result->student_name }}</td>
                                <td>{{ $result->heAdmission->program->name ?? 'N/A' }}</td>
                                <td>{{ $result->subject_name }}</td>
                                <td>{{ $result->semester_no }}</td>
                                <td>
                                    <span class="badge bg-{{ $result->exam_type == 'Final' ? 'danger' : ($result->exam_type == 'Mid' ? 'warning' : 'info') }}">
                                        {{ $result->exam_type }}
                                    </span>
                                </td>
                                <td>{{ $result->is_absent ? 'Absent' : ($result->theory_marks ?? '-') }}</td>
                                <td>{{ $result->practical_marks ?? '-' }}</td>
                                <td><strong>{{ $result->total_obtained_marks }}</strong></td>
                                <td>
                                    <span class="badge bg-{{ $result->is_passed ? 'success' : 'danger' }}">
                                        {{ $result->grade }}
                                    </span>
                                </td>
                                <td>
                                    @if($result->is_locked)
                                        <span class="badge bg-secondary" title="Locked by {{ $result->lockedBy->name ?? 'Unknown' }}">
                                            <i class="fas fa-lock me-1"></i>Locked
                                        </span>
                                    @else
                                        <span class="badge bg-success">
                                            <i class="fas fa-unlock me-1"></i>Editable
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($result->canBeEditedBy(auth()->user()))
                                        <a href="{{ route('he.results.edit', ['id' => $result->id]) }}" 
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary" disabled title="Locked">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>No results found. Click "Enter Marks" to add results.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($results->hasPages())
            <div class="card-footer">
                {{ $results->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
