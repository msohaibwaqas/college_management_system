@extends('layouts.app')
@section('title', 'Auto Generate Timetable')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-magic me-2"></i>Auto Generate Timetable</h2>
        <a href="{{ route('admin.timetables.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Timetables
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Error:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Automatic Timetable Generation</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>How Auto-Generation Works:</h6>
                        <ul class="mb-0">
                            <li><strong>6 Periods per day:</strong> 09:00 - 13:30 (45 minutes each)</li>
                            <li><strong>6 Working days:</strong> Monday to Saturday (Sunday off)</li>
                            <li><strong>Subject Distribution:</strong> Subjects are evenly distributed across the week</li>
                            <li><strong>No Conflicts:</strong> Students won't have 2 subjects at the same time</li>
                            <li><strong>Room Assignment:</strong> Rooms are automatically assigned</li>
                            <li><strong>Spread Across Days:</strong> Same subject won't repeat on the same day</li>
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('timetables.generate') }}" onsubmit="return confirm('This will delete any existing timetable for this year and generate a new one. Continue?');">
                        @csrf
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Class 11 Admission Year</label>
                                <select name="admission_year_11" class="form-select">
                                    <option value="">Select Year (Optional)</option>
                                    @foreach($admissionYears as $year)
                                        <option value="{{ $year }}" {{ (isset($year11) && $year11 == $year) ? 'selected' : '' }}>
                                            {{ $year }} (Current 11th)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Class 12 Admission Year</label>
                                <select name="admission_year_12" class="form-select">
                                    <option value="">Select Year (Optional)</option>
                                    @foreach($admissionYears as $year)
                                        <option value="{{ $year }}" {{ (isset($year12) && $year12 == $year) ? 'selected' : '' }}>
                                            {{ $year }} (Current 12th)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 text-muted">
                            <small>
                                <i class="fas fa-info-circle"></i> Select years to generate timetables simultaneously. 
                                Subjects with >50 marks get 6 periods/week, others get 3 periods/week.
                            </small>
                        </div>

                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-clock me-2"></i>Lecture Schedule</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Period</th>
                                                    <th>Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(\App\Models\Timetable::getPeriodTimes() as $num => $times)
                                                    <tr>
                                                        <td>Period {{ $num }}</td>
                                                        <td>{{ $times['start'] }} - {{ $times['end'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-warning mb-0">
                                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Warning:</strong>
                                            <p class="mb-0">Any existing timetable for the selected year will be <strong>deleted</strong> and replaced with the auto-generated one.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-magic me-2"></i>Generate Timetable
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tips</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>After generation, you can manually adjust individual entries if needed</li>
                        <li>Staff can be assigned later through manual editing</li>
                        <li>The algorithm ensures no student has conflicting classes</li>
                        <li>Subjects are distributed to avoid repetition on the same day</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
