@extends('layouts.app')
@section('title', 'Merit List')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-list-ol me-2"></i>Merit List Generation</h4>
        <a href="{{ route('admission-applications.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>

    {{-- Selection & Configuration --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white"><i class="fas fa-cog me-2"></i>Configuration</div>
        <div class="card-body">
            <form method="GET" action="{{ route('admission-applications.merit-list') }}" class="row g-3 align-items-end">
                <input type="hidden" name="program" value="{{ $program }}">
                <div class="col-md-2">
                    <label class="form-label">Admission Year</label>
                    <select name="admission_year" class="form-select" required>
                        <option value="">Select Year</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pre-Medical Start</label>
                    <input type="number" name="roll_ranges[Pre-Medical]" class="form-control"
                        value="{{ $rollRanges['Pre-Medical'] ?? $defaultRanges['Pre-Medical'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pre-Engineering Start</label>
                    <input type="number" name="roll_ranges[Pre-Engineering]" class="form-control"
                        value="{{ $rollRanges['Pre-Engineering'] ?? $defaultRanges['Pre-Engineering'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">General Science Start</label>
                    <input type="number" name="roll_ranges[General Science]" class="form-control"
                        value="{{ $rollRanges['General Science'] ?? $defaultRanges['General Science'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Humanities Start</label>
                    <input type="number" name="roll_ranges[Humanities]" class="form-control"
                        value="{{ $rollRanges['Humanities'] ?? $defaultRanges['Humanities'] }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-eye me-1"></i> Preview
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Preview --}}
    @if($preview)
        @if($preview['total'] === 0)
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No fee-paid applications found for {{ $selectedYear }}.
            </div>
        @else
            <div class="alert alert-info">
                <strong>{{ $preview['total'] }} students</strong> ready for merit list in {{ count($preview['groups']) }} group(s).
            </div>

            @foreach($preview['groups'] as $groupName => $groupData)
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-users me-2"></i><strong>{{ $groupName }}</strong> — {{ $groupData['count'] }} students</span>
                    <span class="badge bg-primary">Roll: {{ $groupData['roll_start'] }} — {{ $groupData['roll_end'] }}</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Merit #</th>
                                <th>Proposed Roll #</th>
                                <th>Application #</th>
                                <th>Name</th>
                                <th>Father Name</th>
                                <th>SSC Marks</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groupData['students'] as $student)
                            <tr>
                                <td><strong>{{ $student['position'] }}</strong></td>
                                <td><span class="badge bg-dark">{{ $student['roll_no'] }}</span></td>
                                <td><code>{{ $student['application_number'] }}</code></td>
                                <td>{{ $student['name'] }}</td>
                                <td>{{ $student['father_name'] }}</td>
                                <td>{{ $student['ssc_obtained'] }}/{{ $student['ssc_total'] }}</td>
                                <td><strong>{{ $student['ssc_percentage'] }}%</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach

            {{-- Generate Button --}}
            <div class="card border-danger">
                <div class="card-body">
                    <form method="POST" action="{{ route('admission-applications.process-merit-list') }}"
                          onsubmit="return confirm('⚠️ WARNING: This will create student records and admission entries for {{ $preview['total'] }} students.\n\nThis action CANNOT be undone.\n\nAre you sure you want to proceed?');">
                        @csrf
                        <input type="hidden" name="admission_year" value="{{ $selectedYear }}">
                        <input type="hidden" name="program_type" value="{{ $program }}">
                        @foreach($rollRanges as $group => $range)
                            <input type="hidden" name="roll_ranges[{{ $group }}]" value="{{ $range }}">
                        @endforeach

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="confirm" name="confirm" value="1" required>
                            <label class="form-check-label" for="confirm">
                                I confirm that I want to generate the merit list and create <strong>{{ $preview['total'] }}</strong> student records. This action cannot be undone.
                            </label>
                        </div>

                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="fas fa-bolt me-1"></i> Generate Merit List & Create Students
                        </button>
                    </form>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
