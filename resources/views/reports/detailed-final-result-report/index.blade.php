@extends('layouts.app')

@section('title', 'Detailed Final Result Report')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Detailed Final Result Report</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('reports.detailed-final-result-report.index') }}" method="GET" class="row g-3">
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
                <div class="col-md-3">
                    <div class="form-check mt-4 pt-2">
                        @php
                            $isWithSubjects = request()->has('admission_year') ? request()->has('with_subjects') : true;
                        @endphp
                        <input class="form-check-input" type="checkbox" name="with_subjects" id="with_subjects" value="1" {{ $isWithSubjects ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="with_subjects">
                            With Subjects
                        </label>
                    </div>
                </div>
                <div class="col-md-2 align-self-end">
                    <div class="btn-group w-100">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-filter"></i> Load
                        </button>
                        <button type="submit" name="export" value="excel" class="btn btn-primary">
                            <i class="fas fa-file-excel"></i> Export
                        </button>
                    </div>
                </div>
            </form>

            @if(!empty($selectedYear))
                <hr class="my-4">
                @if(count($reportData) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover align-middle text-center" id="detailedReportTable" style="font-size: 0.85rem;">
                            <thead class="table-dark">
                                <tr>
                                    <th>Roll No</th>
                                    <th>BISE 11th Roll No</th>
                                    <th>BISE 12th Roll No</th>
                                    <th>Student Name</th>
                                    <th>Father Name</th>
                                    <th>English</th>
                                    <th>Urdu / Alt</th>
                                    <th>Islamic / Alt</th>
                                    <th>Tarjuma Quran / Alt</th>
                                    <th>Pak Studies</th>
                                    <th>Elective 1</th>
                                    <th>Elective 2</th>
                                    <th>Elective 3</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData as $row)
                                    <tr>
                                        <td class="fw-bold">{{ $row['roll_no'] }}</td>
                                        <td class="fw-bold text-primary">{{ $row['bise_roll_11'] }}</td>
                                        <td class="fw-bold text-primary">{{ $row['bise_roll_12'] }}</td>
                                        <td class="text-start">{{ $row['student_name'] }}</td>
                                        <td class="text-start">{{ $row['father_name'] }}</td>
                                        <td>
                                            @if($isWithSubjects)
                                                <span class="d-block text-muted small">{{ $row['english']['name'] }}</span>
                                            @endif
                                            <strong>{{ $row['english']['marks'] }}</strong>
                                            @if(!empty($row['english']['details']))
                                                <span class="d-block text-muted" style="font-size:0.7em;">{{ $row['english']['details'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($isWithSubjects)
                                                <span class="d-block text-muted small">{{ $row['urdu']['name'] }}</span>
                                            @endif
                                            <strong>{{ $row['urdu']['marks'] }}</strong>
                                            @if(!empty($row['urdu']['details']))
                                                <span class="d-block text-muted" style="font-size:0.7em;">{{ $row['urdu']['details'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($isWithSubjects)
                                                <span class="d-block text-muted small">{{ $row['islamic']['name'] }}</span>
                                            @endif
                                            <strong>{{ $row['islamic']['marks'] }}</strong>
                                            @if(!empty($row['islamic']['details']))
                                                <span class="d-block text-muted" style="font-size:0.7em;">{{ $row['islamic']['details'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($isWithSubjects)
                                                <span class="d-block text-muted small">{{ $row['tarjuma']['name'] }}</span>
                                            @endif
                                            <strong>{{ $row['tarjuma']['marks'] }}</strong>
                                            @if(!empty($row['tarjuma']['details']))
                                                <span class="d-block text-muted" style="font-size:0.7em;">{{ $row['tarjuma']['details'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($isWithSubjects)
                                                <span class="d-block text-muted small">{{ $row['pak_studies']['name'] }}</span>
                                            @endif
                                            <strong>{{ $row['pak_studies']['marks'] }}</strong>
                                            @if(!empty($row['pak_studies']['details']))
                                                <span class="d-block text-muted" style="font-size:0.7em;">{{ $row['pak_studies']['details'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($isWithSubjects)
                                                <span class="d-block text-muted small">{{ $row['elective_1']['name'] }}</span>
                                            @endif
                                            <strong>{{ $row['elective_1']['marks'] }}</strong>
                                            @if(!empty($row['elective_1']['details']))
                                                <span class="d-block text-muted" style="font-size:0.7em;">{{ $row['elective_1']['details'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($isWithSubjects)
                                                <span class="d-block text-muted small">{{ $row['elective_2']['name'] }}</span>
                                            @endif
                                            <strong>{{ $row['elective_2']['marks'] }}</strong>
                                            @if(!empty($row['elective_2']['details']))
                                                <span class="d-block text-muted" style="font-size:0.7em;">{{ $row['elective_2']['details'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($isWithSubjects)
                                                <span class="d-block text-muted small">{{ $row['elective_3']['name'] }}</span>
                                            @endif
                                            <strong>{{ $row['elective_3']['marks'] }}</strong>
                                            @if(!empty($row['elective_3']['details']))
                                                <span class="d-block text-muted" style="font-size:0.7em;">{{ $row['elective_3']['details'] }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        No final results found for students admitted in the selected year.
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
