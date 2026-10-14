@extends('layouts.app')

@section('title', 'Test Result Report')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3>Test Result Report</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('reports.test-result-report.index') }}" method="GET" class="row g-3">
                <div class="col-md-2">
                    <label for="admission_year" class="form-label">Admission Year</label>
                    <select name="admission_year" id="admission_year" class="form-select" required>
                        <option value="">Select Year</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}" {{ isset($selectedYear) && $selectedYear == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="class_level" class="form-label">Class Level</label>
                    <select name="class_level" id="class_level" class="form-select" required>
                        <option value="">Select Class</option>
                        <option value="11" {{ isset($selectedClassLevel) && $selectedClassLevel == '11' ? 'selected' : '' }}>11th</option>
                        <option value="12" {{ isset($selectedClassLevel) && $selectedClassLevel == '12' ? 'selected' : '' }}>12th</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="exam_type" class="form-label">Exam Type</label>
                    <select name="exam_type" id="exam_type" class="form-select" required>
                        <option value="">Select Exam Type</option>
                        @foreach($examTypes as $type)
                            <option value="{{ $type }}" {{ isset($selectedExamType) && $selectedExamType == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="pass_percentage" class="form-label">Pass %</label>
                    <div class="input-group">
                        <input type="number" name="pass_percentage" id="pass_percentage" class="form-control" 
                               value="{{ $customPassPercentage ?? 50 }}" min="0" max="100" step="0.01" required>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary w-100">Load Report</button>
                </div>
            </form>

            @if(isset($reportData))
                <hr>
                <div class="table-responsive mt-4">
                    <table class="table table-bordered table-striped" id="reportTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Year</th>
                                <th>Total Enrolled</th>
                                <th>Total Assigned</th>
                                <th>Pass</th>
                                <th>Fail</th>
                                <th>Absent</th>
                                <th>Pass % (Excl. Absent)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $subjectName => $rows)
                                @foreach($rows as $index => $row)
                                    <tr>
                                        @if($index === 0)
                                            <td rowspan="{{ count($rows) }}" class="align-middle fw-bold">{{ $subjectName }}</td>
                                        @endif
                                        <td>{{ $row['teacher_name'] }}</td>
                                        <td>{{ $row['year'] }}</td>
                                        @if($index === 0)
                                            <td rowspan="{{ count($rows) }}" class="align-middle text-center">{{ $row['total_enrolled'] }}</td>
                                        @endif
                                        <td class="text-center">{{ $row['total_assigned'] }}</td>
                                        <td class="text-center text-success">{{ $row['pass'] }}</td>
                                        <td class="text-center text-danger">{{ $row['fail'] }}</td>
                                        <td class="text-center text-warning">{{ $row['absent'] }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $row['pass_percentage'] >= 50 ? 'bg-success' : 'bg-danger' }}">
                                                {{ $row['pass_percentage'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No data found for the selected criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
