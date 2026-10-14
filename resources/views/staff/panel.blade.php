@extends('layouts.app')
@section('title', 'Staff Panel')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-chalkboard-teacher me-2"></i>Staff Panel</h2>
            <p class="text-muted">View students assigned to teaching staff</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Select Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('staff.panel') }}" class="row g-3">
                <div class="col-md-3">
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

                <div class="col-md-3">
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

                <div class="col-md-3">
                    <label for="staff_id" class="form-label fw-semibold">Staff Member</label>
                    <select name="staff_id" id="staff_id" class="form-select" required>
                        <option value="">Select Staff</option>
                        @foreach($staffMembers as $member)
                            <option value="{{ $member->id }}" {{ $selectedStaff == $member->id ? 'selected' : '' }}>
                                {{ $member->first_name }} {{ $member->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Show Students
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($students->isNotEmpty())
        <!-- Students List -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Students assigned to {{ $staffName }} ({{ $students->count() }} students)
                </h5>
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
                                <th>Class</th>
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
                                    <td>{{ $admission->current_class ?? 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif($selectedYear && $selectedSubject && $selectedStaff)
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No students found for the selected criteria.
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Auto-submit when filters change
    $('#admission_year, #subject_id').on('change', function() {
        if ($('#admission_year').val() && $('#subject_id').val()) {
            $(this).closest('form').submit();
        }
    });
});
</script>
@endsection
