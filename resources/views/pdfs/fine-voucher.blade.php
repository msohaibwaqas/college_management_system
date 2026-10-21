<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fine Voucher - FIN-{{ str_pad($fine->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 5px;
            color: #000;
            background: #fff;
        }
        .wrapper {
            width: 100%;
            display: table;
            table-layout: fixed;
        }
        .voucher-copy {
            display: table-cell;
            width: 31.5%;
            padding: 0 12px;
            border-right: 2px dashed #000;
            vertical-align: top;
            position: relative;
        }
        .voucher-copy:last-child {
            border-right: none;
        }
        .copy-label {
            background: #dc3545;
            color: #fff;
            text-align: center;
            font-weight: bold;
            padding: 4px;
            margin-bottom: 8px;
            border: 1px solid #000;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .college-header {
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        .logo-container {
            display: table;
            width: 100%;
        }
        .logo-cell {
            display: table-cell;
            vertical-align: middle;
            width: 50px;
        }
        .logo {
            max-height: 45px;
            max-width: 45px;
            object-fit: contain;
        }
        .title-cell {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }
        .title-cell h5 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            line-height: 1.2;
        }
        .title-cell small {
            font-size: 13px;
            color: #333;
        }
        .section-box {
            border: 1px solid #000;
            padding: 6px;
            margin-bottom: 8px;
            background: #fff;
        }
        .section-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .section-box td {
            padding: 1px 0;
            font-size: 12px;
            line-height: 1.3;
        }
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .fee-table th {
            background: #dc3545;
            color: #fff;
            border: 1px solid #000;
            padding: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .fee-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            font-size: 11px;
        }
        .total-row {
            background: #fee2e2;
            font-weight: bold;
        }
        .stamp-box {
            width: 80px;
            height: 70px;
            border: 2px dashed #000;
            margin: 0 auto;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            padding-top: 25px;
            box-sizing: border-box;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            margin-top: 40px;
            margin-bottom: 3px;
        }
        .footer-note {
            font-size: 7.5px;
            color: #555;
            margin-top: 8px;
            font-style: italic;
            line-height: 1.2;
        }
        .status-stamp {
            border: 2px solid #dc3545;
            color: #dc3545;
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            padding: 3px;
            margin-bottom: 6px;
            letter-spacing: 1px;
        }
        .status-stamp.paid {
            border-color: #198754;
            color: #198754;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            @page {
                size: A4 landscape;
                margin: 6mm;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; padding: 10px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; margin-bottom: 10px;">
        <button onclick="window.print()" style="padding: 6px 18px; background: #0d6efd; color: #fff; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
            Print Voucher
        </button>
        <button onclick="window.close()" style="padding: 6px 14px; background: #6c757d; color: #fff; border: none; border-radius: 4px; margin-left: 8px; cursor: pointer;">
            Close
        </button>
    </div>

    @php
        $settings = \App\Models\InstituteSetting::getSettings();
        $copies = ['Bank Copy', 'College Copy', 'Student Copy'];
        
        $logoPath = $settings->logo_path ? public_path($settings->logo_path) : null;
        $logoBase64 = "";
        if ($logoPath && file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            if ($imageData) {
                $logoBase64 = base64_encode($imageData);
            }
        }

        $payableAmount = $fine->balance_amount > 0 ? $fine->balance_amount : $fine->total_amount;
        $issueDate = $fine->created_at ? $fine->created_at->format('d-M-Y') : now()->format('d-M-Y');
        $dueDate = $fine->created_at ? $fine->created_at->copy()->addDays(15)->format('d-M-Y') : now()->addDays(15)->format('d-M-Y');
    @endphp

    <div class="wrapper">
        @foreach($copies as $copyName)
        <div class="voucher-copy">
            <div class="copy-label">{{ $copyName }}</div>

            <div class="college-header">
                <div class="logo-container">
                    <div class="logo-cell">
                        @if($logoBase64)
                            <img src="data:image/png;base64,{{ $logoBase64 }}" class="logo" alt="Logo">
                        @else
                            <div style="font-weight: bold; font-size: 14px;">SCMS</div>
                        @endif
                    </div>
                    <div class="title-cell">
                        <h5>{{ $settings->institute_title ?? 'Government College' }}</h5>
                        <small>{{ $fine->formatted_type }} Voucher ({{ $fine->academic_year }})</small>
                    </div>
                </div>
            </div>

            <div class="section-box">
                <table style="width: 100%; border-collapse: collapse; border: 0;">
                    <tr>
                        <td style="width: 50%;"><strong>Bank:</strong> {{ $settings->bank_name ?: 'National Bank of Pakistan' }}</td>
                        <td style="width: 50%; text-align: right;"><strong>Branch:</strong> {{ $settings->bank_branch ?: 'College Branch' }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top: 4px;"><strong>Account No:</strong> {{ $settings->bank_account_number ?: '10492-8' }}</td>
                    </tr>
                </table>
            </div>

            <div class="section-box">
                <table style="width: 100%; border-collapse: collapse; border: 0;">
                    <tr>
                        <td style="width: 33.3%;"><strong>Voucher #</strong><br>FIN-{{ str_pad($fine->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td style="width: 33.3%; text-align: center;"><strong>Issue Date</strong><br>{{ $issueDate }}</td>
                        <td style="width: 33.3%; text-align: right;"><strong>Due Date</strong><br>{{ $dueDate }}</td>
                    </tr>
                </table>
            </div>

            @php
                $adm = $fine->admission ?? $fine->student?->admissions?->first();
                $heAdm = $fine->student?->heAdmissions?->first();
                $rollNo = $adm?->college_roll_no ?? $heAdm?->roll_no;
                $className = $adm ? ($adm->current_class . "th Class") : ($heAdm?->program?->name ?? "Student");
                $fatherName = $fine->student?->father?->name ?? "—";
                $studentCnic = $fine->student?->cnic ?? "—";
            @endphp
            <div class="section-box">
                <table style="width: 100%; border-collapse: collapse; border: 0;">
                    <tr>
                        <td><strong>Student:</strong> {{ $fine->student?->full_name }}</td>
                        @if($rollNo)
                            <td style="text-align: right;"><strong>Roll No:</strong> {{ $rollNo }}</td>
                        @endif
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top: 2px;"><strong>Father Name:</strong> {{ $fatherName }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top: 2px;"><strong>CNIC/B-Form:</strong> {{ $studentCnic }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top: 2px;"><strong>Class/Program:</strong> {{ $className }} (Session {{ $fine->academic_year }})</td>
                    </tr>
                </table>
            </div>

            {{-- Status Stamp --}}
            @if($fine->status === 'paid')
                <div class="status-stamp paid">PAID IN FULL {{ $fine->payment_reference ? '(' . $fine->payment_reference . ')' : '' }}</div>
            @elseif($fine->status === 'waived')
                <div class="status-stamp" style="border-color: #ffc107; color: #b45309;">WAIVED BY PRINCIPAL</div>
            @elseif($fine->status === 'partially_paid')
                <div class="status-stamp" style="border-color: #0d6efd; color: #0d6efd;">PARTIALLY PAID (BAL: Rs. {{ number_format($fine->balance_amount, 2) }})</div>
            @else
                <div class="status-stamp">UNPAID FINE DUES</div>
            @endif

            <table class="fee-table">
                <thead>
                    <tr>
                        <th>Description / Breakdown</th>
                        <th style="width: 80px; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>{{ $fine->formatted_type }}</strong>
                            <div style="font-size: 9.5px; color: #444; margin-top: 2px;">{{ $fine->reason }}</div>
                            <div style="font-size: 8.5px; color: #666; margin-top: 1px;">
                                Rate: {{ $fine->units_count }} &times; Rs. {{ number_format($fine->amount_per_unit, 2) }}
                            </div>
                        </td>
                        <td style="text-align: right; vertical-align: middle;">{{ number_format($fine->total_amount) }}</td>
                    </tr>
                    @if($fine->waived_amount > 0)
                    <tr style="background: #fffbeb;">
                        <td>Authorized Concession / Waiver</td>
                        <td style="text-align: right; color: #b45309;">- {{ number_format($fine->waived_amount) }}</td>
                    </tr>
                    @endif
                    @if($fine->paid_amount > 0)
                    <tr style="background: #f0fdf4;">
                        <td>Amount Paid to Date</td>
                        <td style="text-align: right; color: #166534;">- {{ number_format($fine->paid_amount) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td>Net Payable Amount</td>
                        <td style="text-align: right;">Rs. {{ number_format($payableAmount) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="section-box" style="font-size: 11px;">
                <strong>Amount in Words:</strong><br>
                {{ numberToWords($payableAmount) }} Rupees Only
            </div>

            <div class="section-box" style="font-size: 8px;">
                <strong>Instructions:</strong>
                <ul style="margin: 0; padding-left: 12px;">
                    <li>Pay fine dues on or before due date at college designated bank/counter.</li>
                    <li>Fine once deposited is non-refundable.</li>
                    <li>Submit college copy of deposited voucher to college accounts office.</li>
                    <li>Please keep student copy for your record.</li>
                </ul>
            </div>

            <table style="width: 100%; margin-top: 15px;">
                <tr>
                    <td style="width: 50%; text-align: center; vertical-align: top;">
                        <div class="stamp-box">
                            BANK STAMP
                        </div>
                    </td>
                    <td style="width: 50%; vertical-align: top; padding-left: 15px;">
                        <div class="signature-line"></div>
                        <div style="font-size: 8px; text-align: center;">Authorized Signature</div>
                    </td>
                </tr>
            </table>

            <div class="footer-note">
                Printed: {{ now()->format('d-M-Y H:i') }}<br>
                SCMS Powered Portal.
            </div>
        </div>
        @endforeach
    </div>
    @cmsCredits
</body>
</html>
