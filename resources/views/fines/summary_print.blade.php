<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Fines Summary Report — {{ $settings->institute_title ?? 'College' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-size: 11px; color: #000; font-family: Arial, sans-serif; }
        .table th, .table td { padding: 4px 6px; }
        @media print {
            .no-print { display: none !important; }
            @page { margin: 10mm; }
        }
    </style>
</head>
<body class="bg-white p-4">

<div class="no-print mb-3 text-center">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 me-2">
        <i class="fas fa-print me-1"></i> Print Summary
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">
        <i class="fas fa-times me-1"></i> Close
    </button>
</div>

<div class="text-center mb-4 border-bottom pb-3">
    <h4 class="fw-bold mb-1 text-uppercase">{{ $settings->institute_title ?? 'GOVT. GRADUATE COLLEGE' }}</h4>
    <h6 class="text-muted mb-0">STUDENT FINES &amp; DISCIPLINARY DUES SUMMARY</h6>
    <small class="text-muted">Printed on {{ date('d M Y, h:i A') }} | Total Records: {{ $fines->count() }}</small>
</div>

<table class="table table-bordered table-striped align-middle">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Roll No</th>
            <th>Student Name</th>
            <th>Father Name</th>
            <th>Class</th>
            <th>Type</th>
            <th>Exam / Period</th>
            <th>Reason / Details</th>
            <th class="text-center">Units &times; Rate</th>
            <th class="text-end">Total Due</th>
            <th class="text-end">Paid</th>
            <th class="text-end">Balance</th>
            <th class="text-center">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($fines as $i => $fine)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td class="fw-bold"><code>{{ $fine->admission?->college_roll_no ?? '—' }}</code></td>
            <td class="fw-bold">{{ $fine->student?->full_name }}</td>
            <td>{{ $fine->student?->father_name ?? '—' }}</td>
            <td>{{ $fine->admission?->current_class }}th</td>
            <td>{{ $fine->formatted_type }}</td>
            <td>{{ $fine->exam_name ?? $fine->period ?? '—' }}</td>
            <td class="small">{{ $fine->reason }}</td>
            <td class="text-center">{{ $fine->units_count }} &times; {{ number_format($fine->amount_per_unit, 0) }}</td>
            <td class="text-end fw-bold">Rs. {{ number_format($fine->total_amount, 2) }}</td>
            <td class="text-end text-success">Rs. {{ number_format($fine->paid_amount, 2) }}</td>
            <td class="text-end fw-bold text-danger">Rs. {{ number_format($fine->balance_amount, 2) }}</td>
            <td class="text-center text-uppercase fw-semibold" style="font-size: 10px;">
                {{ $fine->status }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="13" class="text-center py-4 text-muted">No fine records found.</td>
        </tr>
        @endforelse
    </tbody>
    <tfoot class="table-light fw-bold">
        <tr>
            <td colspan="9" class="text-end">GRAND TOTALS:</td>
            <td class="text-end">Rs. {{ number_format($fines->sum('total_amount'), 2) }}</td>
            <td class="text-end text-success">Rs. {{ number_format($fines->sum('paid_amount'), 2) }}</td>
            <td class="text-end text-danger">Rs. {{ number_format($fines->sum('balance_amount'), 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

<div class="row mt-5 pt-4 text-center">
    <div class="col-4">
        <div class="border-top border-dark pt-1">Prepared By (Fee Clerk)</div>
    </div>
    <div class="col-4">
        <div class="border-top border-dark pt-1">Accounts Officer</div>
    </div>
    <div class="col-4">
        <div class="border-top border-dark pt-1">Principal / Incharge</div>
    </div>
</div>

</body>
</html>
