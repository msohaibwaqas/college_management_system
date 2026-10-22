<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Voucher - {{ $voucher->voucher_number }}</title>
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
    @php
        $settings = $settings ?? \App\Models\InstituteSetting::getSettings();
        if (!($voucher instanceof \App\Contracts\PrintableVoucherInterface)) {
            $voucher = \App\Services\Voucher\VoucherFactory::create($voucher, $settings);
        }
        $copies = ['Bank Copy', 'College Copy', 'Student Copy'];
        
        $logoPath = $settings->logo_path ? public_path($settings->logo_path) : null;
        $logoBase64 = "";
        if ($logoPath && file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            if ($imageData) {
                $logoBase64 = base64_encode($imageData);
            }
        }
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
                        <td style="width: 33.3%; text-align: center;"><strong>Issue Date</strong><br>{{ $voucher->issue_date ? $voucher->issue_date->format('d-M-Y') : now()->format('d-M-Y') }}</td>
                        <td style="width: 33.3%; text-align: right;"><strong>Due Date</strong><br>{{ $voucher->due_date ? $voucher->due_date->format('d-M-Y') : now()->addDays(15)->format('d-M-Y') }}</td>
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

            @if($voucher->getStatusStamp())
            <div style="border: 1.5px solid #198754; color: #198754; font-weight: bold; font-size: 10px; text-align: center; padding: 2px; margin-bottom: 6px; letter-spacing: 1px;">
                {{ $voucher->getStatusStamp() }}
            </div>
            @endif

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
                        <td>
                            {{ $item->name }}
                            @if(!empty($item->detail))
                                <div style="font-size: 8.5px; color: #555; margin-top: 1px;">{{ $item->detail }}</div>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if(is_object($item) && method_exists($item, 'getFormattedAmount'))
                                {{ $item->getFormattedAmount() }}
                            @elseif(is_object($item) && isset($item->amount) && $item->amount < 0)
                                - {{ number_format(abs($item->amount)) }}
                            @else
                                {{ number_format(is_object($item) ? $item->amount : $item['amount']) }}
                            @endif
                        </td>
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
                {{ $voucher->getAmountInWords() }}
            </div>

            <div class="section-box" style="font-size: 8px;">
                <strong>Instructions:</strong>
                <ul style="margin: 0; padding-left: 12px;">
                    @foreach($voucher->getInstructions() as $instruction)
                        <li>{{ $instruction }}</li>
                    @endforeach
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
