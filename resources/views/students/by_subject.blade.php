@extends('layouts.app')
@section('title', 'Students by Subject')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-filter me-2"></i>Students by Subject</h2>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('students.by_subject') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="admission_year" class="form-label fw-semibold">Admission Year</label>
                    <select name="admission_year" id="admission_year" class="form-select" required>
                        <option value="">Select Year</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="subject_id" class="form-label fw-semibold">Subject</label>
                    <select name="subject_id" id="subject_id" class="form-select" required>
                        <option value="">Select Subject</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ $selectedSubject == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('students.by_subject') }}" class="btn btn-secondary">
                        <i class="fas fa-redo me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if($students->isNotEmpty())
        <!-- Results -->
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Students taking {{ $subjectName }} ({{ $students->count() }} students)
                </h5>
                <a href="{{ route('students.export', ['admission_year' => $selectedYear, 'subject_id' => $selectedSubject]) }}" 
                   class="btn btn-light btn-sm">
                    <i class="fas fa-download me-1"></i>Export CSV
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Roll No</th>
                                <th>Student Name</th>
                                <th>Father Name</th>
                                <th>CNIC</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $student)
                                @php $admission = $student->admissions->first(); @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $admission->college_roll_no ?? 'N/A' }}</strong></td>
                                    <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                                    <td>{{ $student->father->name ?? 'N/A' }}</td>
                                    <td>{{ $student->attribute->student_cnic ?? 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif($selectedYear && $selectedSubject)
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No students found for the selected criteria.
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Auto-submit when year changes to load subjects
    $('#admission_year').on('change', function() {
        if ($(this).val()) {
            $(this).closest('form').submit();
        }
    });
});
</script>
@endsection
