@extends('layouts.app')
@section('title', 'Group Students')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-layer-group me-2"></i>Group Students by Subject Combination</h2>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Select Admission Year</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('students.groups') }}" class="row g-3">
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

                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Show Groups
                    </button>
                    @if($selectedYear)
                        <a href="{{ route('students.groups.export', $selectedYear) }}" class="btn btn-success">
                            <i class="fas fa-download me-1"></i>Export CSV
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if(!empty($groups))
        <!-- Groups -->
        @foreach($groups as $groupName => $students)
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        {{ $groupName }} ({{ count($students) }} students)
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
                                    <th>Elective Subjects</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $index => $student)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><strong>{{ $student['roll_no'] }}</strong></td>
                                        <td>{{ $student['name'] }}</td>
                                        <td>{{ $student['father_name'] }}</td>
                                        <td>
                                            @foreach(explode(', ', $student['subjects']) as $subject)
                                                <span class="badge bg-info text-dark me-1">{{ $subject }}</span>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Summary -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($groups as $groupName => $students)
                        <div class="col-md-3 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-primary">{{ count($students) }}</h3>
                                    <p class="mb-0">{{ $groupName }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="col-md-3 mb-3">
                        <div class="card text-center bg-primary text-white">
                            <div class="card-body">
                                <h3>{{ array_sum(array_map('count', $groups)) }}</h3>
                                <p class="mb-0">Total Students</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif($selectedYear)
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No students found for the selected year.
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Auto-submit when year changes
    $('#admission_year').on('change', function() {
        if ($(this).val()) {
            $(this).closest('form').submit();
        }
    });
});
</script>
@endsection
