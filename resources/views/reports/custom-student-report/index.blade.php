@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-11">
            <div class="card border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header bg-gradient-primary text-white p-4" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1 fw-bold text-white"><i class="fas fa-file-invoice me-2"></i>Dynamic Custom Student Report</h4>
                            <p class="mb-0 text-white-50 small">Select columns and filters to generate a flexible report.</p>
                        </div>
                        @if(!empty($reportData))
                        <button onclick="window.print()" class="btn btn-light btn-sm shadow-sm">
                            <i class="fas fa-print me-1"></i>Print Report
                        </button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-4 bg-light d-print-none">
                    <form action="{{ url('/reports/custom-student-report') }}" method="GET" id="reportForm">
                        <div class="row g-3">
                            <!-- Filters -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Admission Year</label>
                                <select name="admission_year" class="form-select border-0 shadow-sm" required onchange="this.form.submit()">
                                    <option value="">Select Year</option>
                                    @foreach($admissionYears as $year)
                                        <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Pass % Threshold</label>
                                <input type="number" name="pass_percentage" class="form-control border-0 shadow-sm" value="{{ request('pass_percentage', 33) }}" min="0" max="100">
                            </div>
                        </div>

                        <hr class="my-4 opacity-10">

                        <!-- Column Selection -->
                        <div class="row">
                            <div class="col-12 mb-3">
                                <h6 class="fw-bold text-primary mb-3"><i class="fas fa-columns me-2"></i>Select Columns to Include</h6>
                                <div class="btn-group mb-3" role="group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="checkAll(true)">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="checkAll(false)">Clear All</button>
                                </div>
                            </div>

                            <!-- Column Categories -->
                            <div class="col-md-3">
                                <div class="p-3 bg-white rounded shadow-sm h-100">
                                    <p class="fw-bold small text-muted border-bottom pb-2 mb-3">PERSONAL / CONTACT</p>
                                    @php $personal = ['name' => 'Name', 'college_roll_no' => 'College Roll No', 'father_name' => 'Father Name', 'religion' => 'Religion', 'contact' => 'Contact Number']; @endphp
                                    @foreach($personal as $id => $label)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="{{ $id }}" id="col_{{ $id }}" {{ in_array($id, $selectedColumns) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="col_{{ $id }}">{{ $label }}</label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="p-3 bg-white rounded shadow-sm h-100">
                                    <p class="fw-bold small text-muted border-bottom pb-2 mb-3">BOARD / SSC INFO</p>
                                    @php $board = ['bise_roll_11' => 'BISE 11 Roll No', 'bise_roll_12' => 'BISE 12 Roll No', 'school_name' => 'SSC School Name', 'ssc_marks' => 'SSC Marks']; @endphp
                                    @foreach($board as $id => $label)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="{{ $id }}" id="col_{{ $id }}" {{ in_array($id, $selectedColumns) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="col_{{ $id }}">{{ $label }}</label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="p-3 bg-white rounded shadow-sm h-100">
                                    <p class="fw-bold small text-muted border-bottom pb-2 mb-3">ACADEMIC / TEST</p>
                                    @php $academic = ['electives' => 'Elective Subjects', 'oct_test' => 'Oct Test Marks', 'dec_test' => 'Dec Test Marks']; @endphp
                                    @foreach($academic as $id => $label)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="{{ $id }}" id="col_{{ $id }}" {{ in_array($id, $selectedColumns) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="col_{{ $id }}">{{ $label }}</label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="p-3 bg-white rounded shadow-sm h-100">
                                    <p class="fw-bold small text-muted border-bottom pb-2 mb-3">FINAL RESULTS</p>
                                    @php $results = ['total_11' => '11th Total', 'total_12' => '12th Total', 'status' => 'Status (Pass/Fail)', 'reappear' => 'Reappear In']; @endphp
                                    @foreach($results as $id => $label)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="{{ $id }}" id="col_{{ $id }}" {{ in_array($id, $selectedColumns) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="col_{{ $id }}">{{ $label }}</label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-5 shadow-sm rounded-pill">
                                <i class="fas fa-sync-alt me-2"></i>Generate Report
                            </button>
                        </div>
                    </form>
                </div>

                @if(!empty($reportData))
                <div class="card-body p-0 border-top bg-white">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0 align-middle">
                            <thead class="bg-light text-center small text-uppercase fw-bold text-muted">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    @foreach(array_keys($reportData[0]) as $columnName)
                                    <th>{{ $columnName }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="small">
                                @foreach($reportData as $index => $row)
                                <tr>
                                    <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                    @foreach($row as $key => $value)
                                    <td class="{{ $key == 'Status' ? ($value == 'Pass' || $value == 'Pass (Grace)' ? 'text-success fw-bold' : ($value == 'Absent' ? 'text-secondary' : 'text-danger fw-bold')) : '' }}">
                                        @if($key == 'Status')
                                            <span class="badge {{ $value == 'Pass' || $value == 'Pass (Grace)' ? 'bg-success-light text-success' : ($value == 'Absent' ? 'bg-secondary-light text-secondary' : 'bg-danger-light text-danger') }} border-0">
                                                {{ $value }}
                                            </span>
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @elseif($selectedYear)
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-search fa-3x text-light"></i>
                    </div>
                    <h5 class="text-muted">No data found or no columns selected.</h5>
                    <p class="text-muted small">Please adjust your filters and check at least one column box.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-primary { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); }
    .bg-success-light { background-color: rgba(25, 135, 84, 0.1); }
    .bg-danger-light { background-color: rgba(220, 53, 69, 0.1); }
    .bg-secondary-light { background-color: rgba(108, 117, 125, 0.1); }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,.02); }
    @media print {
        body { background: white !important; }
        .container-fluid { padding: 0 !important; width: 100% !important; max-width: 100% !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-header { background: white !important; color: black !important; border-bottom: 2px solid #333 !important; }
        .card-header p, .card-header .btn { display: none !important; }
        .card-header h4 { color: black !important; margin: 0 !important; text-align: center; }
        .table { width: 100% !important; border-collapse: collapse !important; }
        .table th, .table td { border: 1px solid #ddd !important; padding: 4px !important; font-size: 10px !important; }
    }
</style>

<script>
    function checkAll(checked) {
        document.querySelectorAll('.form-check-input').forEach(cb => cb.checked = checked);
    }
</script>
@endsection
