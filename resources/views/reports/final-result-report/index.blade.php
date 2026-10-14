@extends('layouts.app')

@section('title', 'Final Result Report')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3>Final Result Report</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('reports.final-result-report.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
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
                <div class="col-md-4">
                    <label for="pass_percentage" class="form-label">Pass Percentage (Default: 33%)</label>
                    <div class="input-group">
                        <input type="number" name="pass_percentage" id="pass_percentage" class="form-control" 
                               value="{{ $customPassPercentage ?? 33 }}" min="0" max="100" step="0.01">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-4 align-self-end">
                    <button type="submit" class="btn btn-primary w-100">Show Report</button>
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
                                <th>Total Enrolled</th> <!-- Subject Total -->
                                <th>Total Assigned</th> <!-- Teacher Total -->
                                <th>Pass</th>
                                <th>Fail</th>
                                <th>Absent</th>
                                <th>Missing</th>
                                <th>Pass % (Excl. Absent/Missing)</th>
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
                                        <td class="text-center text-success fw-bold">{{ $row['pass'] }}</td>
                                        <td class="text-center text-danger fw-bold">{{ $row['fail'] }}</td>
                                        <td class="text-center text-warning fw-bold">{{ $row['absent'] }}</td>
                                        <td class="text-center">
                                            @if($row['missing'] > 0)
                                                <span class="badge bg-secondary" style="cursor: help;" 
                                                      title="Missing: {{ implode(', ', $row['missing_details']) }}">
                                                    {{ $row['missing'] }}
                                                </span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $row['pass_percentage'] >= 50 ? 'bg-success' : 'bg-danger' }}">
                                                {{ $row['pass_percentage'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">No data found for the selected criteria.</td>
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
