@extends('layouts.app')

@section('title', 'Cash Register Report - ' . $admissionYear)

@section('styles')
<style>
    .report-table th, .report-table td {
        vertical-align: middle;
        white-space: nowrap;
    }
    .grand-total-row {
        background-color: #f8f9fa;
        font-weight: bold;
    }
    @media print {
        .btn-print, .btn-back { display: none !important; }
        .card { border: none !important; }
        .card-header { background: none !important; color: black !important; }
        .table-responsive { overflow: visible !important; }
    }
</style>
@endsection

@section('content')
<div class="container-fluid p-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">
                <i class="fas fa-cash-register me-2 text-primary"></i>
                Cash Register Report: Admission Year {{ $admissionYear }}
            </h5>
            <div>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary shadow-sm btn-print me-2">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                <a href="{{ route('reports.cash-register.index') }}" class="btn btn-sm btn-outline-secondary shadow-sm btn-back">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover report-table">
                    <thead class="table-dark">
                        <tr>
                            <th>S#</th>
                            <th>Roll No.</th>
                            <th>Name</th>
                            @foreach($feeHeadNames as $head)
                                <th class="text-end">{{ $head }}</th>
                            @endforeach
                            <th class="text-end">Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $row['roll_no'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                @foreach($feeHeadNames as $head)
                                    <td class="text-end">
                                        {{ $row['items'][$head] > 0 ? number_format($row['items'][$head], 0) : '-' }}
                                    </td>
                                @endforeach
                                <td class="text-end fw-bold">{{ number_format($row['total'], 0) }}</td>
                                <td>
                                    <span class="badge bg-{{ $row['status'] == 'paid' ? 'success' : ($row['status'] == 'pending' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($row['status']) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="grand-total-row">
                            <td colspan="3" class="text-end">Grand Total</td>
                            @foreach($feeHeadNames as $head)
                                <td class="text-end">{{ number_format($grandTotals[$head], 0) }}</td>
                            @endforeach
                            <td class="text-end">{{ number_format($finalGrandTotal, 0) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
