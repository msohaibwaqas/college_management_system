<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fine Challan #FIN-{{ str_pad($fine->id, 6, '0', STR_PAD_LEFT) }} — {{ $fine->student?->full_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-size: 11px;
            color: #212529;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .challan-container {
            max-width: 1200px;
            margin: 20px auto;
            background: #fff;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .challan-copy {
            border: 1px dashed #6c757d;
            padding: 12px;
            height: 100%;
            background: #fff;
            position: relative;
        }
        .copy-tag {
            background: #212529;
            color: #fff;
            font-size: 9px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            display: inline-block;
            text-transform: uppercase;
        }
        .inst-title {
            font-size: 12px;
            font-weight: bold;
            line-height: 1.2;
        }
        .meta-table td, .meta-table th {
            padding: 2px 4px;
            font-size: 10.5px;
        }
        .breakdown-table th, .breakdown-table td {
            padding: 4px;
            font-size: 10.5px;
        }
        .stamp-box {
            border: 1px solid #ced4da;
            height: 45px;
            border-radius: 3px;
            text-align: center;
            line-height: 45px;
            font-size: 9px;
            color: #6c757d;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .challan-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .challan-copy {
                border: 1px dashed #000;
            }
            @page {
                size: A4 landscape;
                margin: 8mm;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid no-print py-3 text-center bg-white border-bottom shadow-sm">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 me-2">
        <i class="fas fa-print me-1"></i> Print Challan Slip
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">
        <i class="fas fa-times me-1"></i> Close
    </button>
</div>

<div class="challan-container">
    <div class="row g-2">
        @php
            $copies = [
                ['title' => 'Bank Copy', 'badge' => 'bg-danger text-white'],
                ['title' => 'College / Accounts Copy', 'badge' => 'bg-primary text-white'],
                ['title' => 'Student Copy', 'badge' => 'bg-success text-white'],
            ];
        @endphp

        @foreach($copies as $copy)
        <div class="col-4">
            <div class="challan-copy d-flex flex-column justify-content-between">
                <div>
                    {{-- Copy Label & Date --}}
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="copy-tag {{ $copy['badge'] }}">{{ $copy['title'] }}</span>
                        <span class="text-muted" style="font-size: 9.5px;">Challan #: <strong>FIN-{{ str_pad($fine->id, 5, '0', STR_PAD_LEFT) }}</strong></span>
                    </div>

                    {{-- College Header --}}
                    <div class="text-center border-bottom pb-1 mb-2">
                        <div class="inst-title text-uppercase">{{ $settings->institute_title ?? 'GOVT. GRADUATE COLLEGE' }}</div>
                        <div class="text-muted" style="font-size: 9px;">Fine &amp; Disciplinary Dues Challan</div>
                        <div class="text-muted" style="font-size: 8.5px;">Bank: <strong>National Bank of Pakistan (NBP)</strong> / College Counter</div>
                    </div>

                    {{-- Student Information --}}
                    <table class="table table-sm table-bordered meta-table mb-2">
                        <tbody>
                            <tr>
                                <th style="width: 32%;" class="bg-light">Roll No:</th>
                                <td class="fw-bold"><code>{{ $fine->admission?->college_roll_no ?? '—' }}</code></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Student Name:</th>
                                <td class="fw-bold">{{ $fine->student?->full_name }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light">Father Name:</th>
                                <td>{{ $fine->student?->father_name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light">Class / Year:</th>
                                <td>{{ $fine->admission?->current_class }}th (Session {{ $fine->academic_year }})</td>
                            </tr>
                            <tr>
                                <th class="bg-light">Date of Issue:</th>
                                <td>{{ $fine->created_at?->format('d-M-Y') ?? date('d-M-Y') }}</td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- Fine Itemization --}}
                    <table class="table table-sm table-bordered breakdown-table mb-2">
                        <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th class="text-center">Units</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>{{ $fine->formatted_type }}</strong>
                                    @if($fine->exam_name)
                                        <div class="text-muted" style="font-size: 9px;">Exam: {{ $fine->exam_name }}</div>
                                    @elseif($fine->period)
                                        <div class="text-muted" style="font-size: 9px;">Period: {{ $fine->period }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size: 8.5px; max-width: 180px;">{{ $fine->reason }}</div>
                                </td>
                                <td class="text-center align-middle">
                                    {{ $fine->units_count }} &times; {{ number_format($fine->amount_per_unit, 0) }}
                                </td>
                                <td class="text-end align-middle fw-bold">
                                    Rs. {{ number_format($fine->total_amount, 2) }}
                                </td>
                            </tr>
                            @if($fine->waived_amount > 0)
                            <tr class="table-warning">
                                <td colspan="2" class="text-end small">Authorized Waiver:</td>
                                <td class="text-end text-danger">- Rs. {{ number_format($fine->waived_amount, 2) }}</td>
                            </tr>
                            @endif
                            @if($fine->paid_amount > 0)
                            <tr class="table-success">
                                <td colspan="2" class="text-end small">Paid to Date:</td>
                                <td class="text-end text-success">- Rs. {{ number_format($fine->paid_amount, 2) }}</td>
                            </tr>
                            @endif
                            <tr class="table-light">
                                <th colspan="2" class="text-end">Net Due Amount:</th>
                                <th class="text-end text-danger">Rs. {{ number_format($fine->balance_amount, 2) }}</th>
                            </tr>
                        </tbody>
                    </table>

                    <div class="small mb-2" style="font-size: 9px;">
                        Status:
                        @if($fine->status === 'paid')
                            <span class="badge bg-success">PAID IN FULL</span>
                        @elseif($fine->status === 'waived')
                            <span class="badge bg-warning text-dark">WAIVED</span>
                        @else
                            <span class="badge bg-danger">PAYABLE DUES</span>
                        @endif
                    </div>
                </div>

                {{-- Signatures & Stamp --}}
                <div class="mt-2 pt-2 border-top">
                    <div class="row g-1 text-center" style="font-size: 8.5px;">
                        <div class="col-6">
                            <div class="stamp-box">Bank / Cash Stamp</div>
                        </div>
                        <div class="col-6 d-flex flex-column justify-content-end">
                            <div class="border-top border-dark pt-1">Cashier / Incharge</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

</body>
</html>
