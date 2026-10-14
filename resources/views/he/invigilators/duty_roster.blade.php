@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3 no-print">
        <div class="col-md-12">
            <h2>HE Invigilator Duty Roster</h2>
            <div class="d-flex justify-content-between">
                <div>
                    <a href="{{ route('he.invigilators.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Roster
                </button>
            </div>
        </div>
    </div>

    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('he.invigilators.duty_roster') }}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label>Select HE Exam Date</label>
                        <select name="date" class="form-control" onchange="this.form.submit()">
                            <option value="">Select Date</option>
                            @foreach($examDates as $d)
                                <option value="{{ $d->exam_date->format('Y-m-d') }}" {{ $date == $d->exam_date->format('Y-m-d') ? 'selected' : '' }}>
                                    {{ $d->exam_date->format('d M Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($date && $assignments->isNotEmpty())
        <div class="print-area">
            <div class="text-center mb-4">
                <h4>{{ $institute->name ?? 'Institute Name' }}</h4>
                <h5>HE Invigilator Duty Roster</h5>
                <h6>Date: {{ \Carbon\Carbon::parse($date)->format('d F Y (l)') }}</h6>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr class="bg-light">
                            <th style="width: 30%">Room / Subject</th>
                            <th>Staff Members</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $group)
                            <tr>
                                <td><strong>{{ $group['room_name'] }}</strong></td>
                                <td>
                                    <ul class="list-unstyled mb-0">
                                        @foreach($group['staff'] as $staff)
                                            <li>
                                                {{ $staff->first_name }} {{ $staff->last_name }} 
                                                <small class="text-muted">({{ $staff->designationModel->name ?? 'N/A' }})</small>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($date)
        <div class="alert alert-warning">No HE invigilator assignments found for this date.</div>
    @else
        <div class="alert alert-info">Please select an HE exam date to view the duty roster.</div>
    @endif
</div>

<style>
@media print {
    .no-print { display: none !important; }
    .card { border: none !important; }
    .table-bordered th, .table-bordered td { border: 1px solid #000 !important; }
}
</style>
@endsection
