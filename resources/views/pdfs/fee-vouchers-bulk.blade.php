<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Vouchers - Bulk Download</title>
    <style>
        /* --- Base Styles from Design 1 --- */
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
            background: #0d6efd;
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
            font-size: 20px;
            font-weight: bold;
            line-height: 1.2;
        }
        .title-cell small {
            font-size: 15px;
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
            background: #0d6efd;
            color: #fff;
            border: 1px solid #000;
            padding: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .fee-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            font-size: 12px;
        }
        .total-row {
            background: #dbeafe;
            font-weight: bold;
        }
        .stamp-box {
            width: 80px;
            height: 80px;
            border: 2px dashed #000;
            margin: 0 auto;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            padding-top: 30px;
            box-sizing: border-box;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            margin-top: 45px;
            margin-bottom: 3px;
        }
        .footer-note {
            font-size: 7.5px;
            color: #555;
            margin-top: 8px;
            font-style: italic;
            line-height: 1.2;
        }

        /* --- Bulk Print Specific Styles --- */
        .page-break { 
            page-break-after: always; 
        }
        .page-break:last-child { 
            page-break-after: auto; 
        }

        @media print {
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
            .page-break:last-child { page-break-after: auto; }
            body { padding: 0; margin: 0; }
            /* Ensure background colors print */
            .copy-label, .fee-table th, .total-row {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    {{-- Print Controls (Hidden when printing) --}}
    <div class="no-print" style="text-align: center; padding: 15px; background: #f0f0f0; border-bottom: 2px solid #333; margin-bottom: 20px;">
        <strong>{{ count($vouchers) }} Voucher(s) Ready to Print</strong> &nbsp;
        <button onclick="window.print()" style="padding: 8px 20px; font-size: 14px; cursor: pointer; background: #0d6efd; color: white; border: none; border-radius: 4px;">
            <i class="fas fa-print"></i> Print All Vouchers
        </button>
        <button onclick="window.close()" style="padding: 8px 20px; font-size: 14px; cursor: pointer; background: #dc3545; color: white; border: none; border-radius: 4px; margin-left: 10px;">
            Close
        </button>
    </div>

    @php
        $settings = \App\Models\InstituteSetting::getSettings();
        $copies = ['Bank Copy', 'College Copy', 'Student Copy'];
        
        $logoPath = public_path($settings->logo_path);
        $logoBase64 = "";
        if ($settings->logo_path && file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            if ($imageData) {
                $logoBase64 = base64_encode($imageData);
            }
        }
    @endphp

    {{-- Loop through each student's voucher --}}
    @foreach($vouchers as $voucher)
    <div class="page-break">
        <div class="wrapper">
            {{-- Loop through the 3 copies for each voucher --}}
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
                            <small>{{ $voucher->remarks ?? 'Admission Fee Voucher' }} ({{ $voucher->admission_year }})</small>
                        </div>
                    </div>
                </div>

                <div class="section-box">
                    <table style="width: 100%; border-collapse: collapse; border: 0;">
                        <tr>
                            <td style="width: 50%;"><strong>Bank:</strong> {{ $voucher->bank_name ?: $settings->bank_name }}</td>
                            <td style="width: 50%; text-align: right;"><strong>Branch:</strong> {{ $voucher->bank_branch ?: $settings->bank_branch }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding-top: 4px;"><strong>Account No:</strong> {{ $voucher->account_number ?: $settings->bank_account_number }}</td>
                        </tr>
                    </table>
                </div>

                <div class="section-box">
                    <table style="width: 100%; border-collapse: collapse; border: 0;">
                        <tr>
                            <td style="width: 33.3%;"><strong>Voucher #</strong><br>{{ $voucher->voucher_number }}</td>
                            <td style="width: 33.3%; text-align: center;"><strong>Issue Date</strong><br>{{ $voucher->issue_date->format('d-M-Y') }}</td>
                            <td style="width: 33.3%; text-align: right;"><strong>Due Date</strong><br>{{ $voucher->due_date->format('d-M-Y') }}</td>
                        </tr>
                    </table>
                </div>

                <div class="section-box">
                    <table style="width: 100%; border-collapse: collapse; border: 0;">
                        <tr>
                            <td><strong>Student:</strong> {{ $voucher->applicant_name }}</td>
                            @if($voucher->college_roll_no)
                                <td style="text-align: right;"><strong>Roll No:</strong> {{ $voucher->college_roll_no }}</td>
                            @endif
                        </tr>
                        <tr>
                            <td colspan="2" style="padding-top: 2px;"><strong>CNIC/B-Form:</strong> {{ $voucher->cnic }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding-top: 2px;"><strong>Class/Program:</strong> {{ $voucher->subject_group }}</td>
                        </tr>
                    </table>
                </div>

                <table class="fee-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="width: 80px; text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($voucher->items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td style="text-align: right;">{{ number_format($item->amount) }}</td>
                        </tr>
                        @endforeach
                        <tr class="total-row">
                            <td>Total Amount</td>
                            <td style="text-align: right;">Rs. {{ number_format($voucher->amount) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="section-box" style="font-size: 12px;">
                    <strong>Amount in Words:</strong><br>
                    {{ numberToWords($voucher->amount) }} Rupees Only
                </div>

                <div class="section-box" style="font-size: 8px;">
                    <strong>Instructions:</strong>
                    <ul style="margin: 0; padding-left: 12px;">
                        <li>Pay fee on or before due date.</li>
                        <li>Fee once paid is non-refundable.</li>
                        <li>Please keep this copy for your record.</li>
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
    </div>
    @endforeach
    
    @cmsCredits
</body>
</html>