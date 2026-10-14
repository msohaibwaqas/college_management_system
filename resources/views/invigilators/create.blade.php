@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2>Auto-Assign Invigilators</h2>
            <a href="{{ route('invigilators.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('invigilators.auto_assign') }}" method="POST">
        @csrf

        <div class="card mb-3">
            <div class="card-header">
                <h5>Exam Period</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Start Date <span class="text-danger">*</span></label>
                            <select name="start_date" class="form-control" required>
                                <option value="">Select Start Date</option>
                                @foreach($examDates as $date)
                                    <option value="{{ $date->format('Y-m-d') }}" {{ old('start_date') == $date->format('Y-m-d') ? 'selected' : '' }}>
                                        {{ $date->format('d M Y (l)') }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Select from actual exam dates</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>End Date <span class="text-danger">*</span></label>
                            <select name="end_date" class="form-control" required>
                                <option value="">Select End Date</option>
                                @foreach($examDates as $date)
                                    <option value="{{ $date->format('Y-m-d') }}" {{ old('end_date') == $date->format('Y-m-d') ? 'selected' : '' }}>
                                        {{ $date->format('d M Y (l)') }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Select from actual exam dates</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Invigilators Per Room <span class="text-danger">*</span></label>
                            <input type="number" name="invigilators_per_room" class="form-control" min="1" max="4" required value="{{ old('invigilators_per_room', 2) }}">
                            <small class="form-text text-muted">Number of invigilators to assign per room (1-4)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5>Duty Rules (Days per Designation)</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Current duty rules. <a href="{{ route('invigilators.duty_rules') }}">Click here</a> to modify.</p>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Designation</th>
                            <th>Max Duty Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($designations as $designation)
                            <tr>
                                <td>{{ $designation->name }}</td>
                                <td>{{ $dutyRules[$designation->id]->max_duty_days ?? 'Not Set' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5>Exempted Staff (Exam Conductors)</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Select staff members who are conducting exams and should be exempted from invigilator duty.</p>
                <div class="row">
                    @foreach($staff as $member)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="exempted_staff[]" value="{{ $member->id }}" id="staff{{ $member->id }}">
                                <label class="form-check-label" for="staff{{ $member->id }}">
                                    {{ $member->first_name }} {{ $member->last_name }} ({{ $member->designationModel->name ?? 'N/A' }})
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                @if($examConductors->isNotEmpty())
                    <hr>
                    <div class="alert alert-info">
                        <strong><i class="fas fa-info-circle"></i> Permanent Exemptions (Automatically Applied):</strong>
                        <p class="mb-2">The following staff are already exempted via the Exemptions page and will be automatically excluded from assignment:</p>
                        <ul class="mb-0">
                            @foreach($examConductors as $conductor)
                                <li>
                                    <strong>{{ $conductor->staff->first_name }} {{ $conductor->staff->last_name }}</strong> 
                                    ({{ $conductor->staff->designationModel->name ?? 'N/A' }})
                                    - {{ $conductor->exam_start_date->format('d M Y') }} to {{ $conductor->exam_end_date->format('d M Y') }}
                                    @if($conductor->reason)
                                        <br><small class="text-muted">Reason: {{ $conductor->reason }}</small>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-magic"></i> Auto-Assign Invigilators
                </button>
                <p class="text-muted mt-2">
                    <strong>Note:</strong> This will delete all existing auto-assigned records in the selected date range and create new assignments based on the rules above.
                </p>
            </div>
        </div>
    </form>
</div>
@endsection
