@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- No Print Section -->
    <div class="row mb-3 d-print-none">
        <div class="col-md-12">
            <h2>Duty Roster Report</h2>
            <a href="{{ route('invigilators.index') }}" class="btn btn-secondary mb-3">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('invigilators.duty_roster') }}" class="form-inline">
                        <label class="mr-2">Select Date:</label>
                        <select name="date" class="form-control mr-2" onchange="this.form.submit()">
                            <option value="">-- Select Date --</option>
                            @foreach($examDates as $d)
                                <option value="{{ $d->exam_date }}" {{ $date == $d->exam_date ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::parse($d->exam_date)->format('d M Y (l)') }}
                                </option>
                            @endforeach
                        </select>
                        @if($date)
                            <button type="button" onclick="window.print()" class="btn btn-primary">
                                <i class="fas fa-print"></i> Print / Download PDF
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($date && $assignments->isNotEmpty())
        @php
            $maxInvigilators = 0;
            foreach($assignments as $data) {
                $maxInvigilators = max($maxInvigilators, $data['staff']->count());
            }
            $maxInvigilators = max($maxInvigilators, 1); // At least 1 column
        @endphp

        <!-- Printable Area -->
        <div class="print-area">
            <div class="text-center mb-4">
                @if($institute && $institute->logo_url)
                    <img src="{{ $institute->logo_url }}" alt="Logo" style="height: 80px; margin-bottom: 10px;">
                @endif
                <h2>{{ $institute->institute_title ?? 'Institute Name' }}</h2>
                <h3>Duty Roster</h3>
                <h4>Date: {{ \Carbon\Carbon::parse($date)->format('d F Y (l)') }}</h4>
            </div>

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 150px;">Room #</th>
                        @for($i = 1; $i <= $maxInvigilators; $i++)
                            <th>Invigilator {{ $i }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignments as $data)
                        <tr>
                            <td class="font-weight-bold">{{ $data['room_name'] }}</td>
                            @php
                                $staffList = $data['staff']->values();
                            @endphp
                            @for($i = 0; $i < $maxInvigilators; $i++)
                                <td>
                                    @if(isset($staffList[$i]))
                                        {{ $staffList[$i]->first_name }} {{ $staffList[$i]->last_name }}
                                        <br>
                                        <small class="text-muted">({{ $staffList[$i]->designationModel->name ?? $staffList[$i]->designation ?? '' }})</small>
                                    @else
                                        -
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="mt-5 row">
                <div class="col-4 text-center">
                    <p>______________________</p>
                    <p><strong>Controller Examination</strong></p>
                </div>
                <div class="col-4 text-center">
                    <p>______________________</p>
                    <p><strong>Checker</strong></p>
                </div>
                <div class="col-4 text-center">
                    <p>______________________</p>
                    <p><strong>Principal</strong></p>
                </div>
            </div>
        </div>
    @elseif($date)
        <div class="alert alert-info">No assignments found for this date.</div>
    @endif
</div>

<style>
    @media print {
        .d-print-none {
            display: none !important;
        }
        .container-fluid {
            width: 100%;
            padding: 0;
            margin: 0;
        }
        .print-area {
            display: block;
        }
        table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        th, td {
            border: 1px solid #000 !important;
            padding: 8px !important;
            font-size: 12pt;
        }
        h2, h3, h4 {
            margin: 5px 0;
            color: #000 !important;
        }
        /* Ensure background colors print if user enables it, but mostly keep it clean */
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.05) !important;
            -webkit-print-color-adjust: exact; 
        }
    }
</style>
@endsection
