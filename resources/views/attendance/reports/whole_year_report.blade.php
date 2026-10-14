<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Whole Year Attendance Report - Class {{ $class }} (Year {{ $year }})</title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 11px; margin: 20px; color: #212529; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 3px 0; font-size: 20px; }
        .header h3 { margin: 3px 0; font-size: 16px; color: #495057; }
        .header h4 { margin: 3px 0; font-size: 13px; color: #6c757d; }
        .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .info-table td { padding: 4px 8px; font-size: 12px; }
        
        .matrix-table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: auto; }
        .matrix-table th, .matrix-table td { border: 1px solid #333; padding: 6px 4px; text-align: center; font-size: 11px; }
        .matrix-table th { background-color: #e9ecef; font-weight: bold; }
        .subject-col { background-color: #f8f9fa; }
        .text-left { text-align: left !important; }
        .badge-shortage { color: #dc3545; font-weight: bold; }
        .badge-good { color: #198754; font-weight: bold; }
        
        @media print {
            .no-print { display: none; }
            body { margin: 5mm; }
            .matrix-table th { background-color: #ddd !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px; display: flex; gap: 10px;">
        <button onclick="window.print()" style="padding: 8px 16px; cursor: pointer; background: #0d6efd; color: white; border: none; border-radius: 4px; font-weight: bold;">
            🖨️ Print Report
        </button>
        <button onclick="window.history.back()" style="padding: 8px 16px; cursor: pointer; background: #6c757d; color: white; border: none; border-radius: 4px; font-weight: bold;">
            ⬅️ Back
        </button>
    </div>

    <div class="header">
        @if($settings->logo_path)
            <img src="{{ $settings->logo_url }}" alt="Logo" style="max-height: 70px; margin-bottom: 5px;">
        @endif
        <h2>{{ $settings->full_name ?? 'COLLEGE MANAGEMENT SYSTEM' }}</h2>
        <h3>Admin Consolidated Whole-Year Attendance Report</h3>
        <h4>From: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} To: {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</h4>
    </div>

    <table class="info-table">
        <tr>
            <td width="20%"><strong>Class:</strong> Class {{ $class }} (Year {{ $year }})</td>
            <td width="30%"><strong>Total Enrolled Students:</strong> {{ $students->count() }}</td>
            <td width="30%"><strong>Total Subjects:</strong> {{ $subjects->count() }}</td>
            <td width="20%" style="text-align: right;"><strong>Generated On:</strong> {{ now()->format('d M Y') }}</td>
        </tr>
    </table>

    <table class="matrix-table">
        <thead>
            <tr>
                <th width="3%">S#</th>
                <th width="7%">Roll No</th>
                <th width="18%" class="text-left">Student Name</th>
                @foreach($subjects as $subject)
                    <th class="subject-col">
                        {{ $subject->name }}<br>
                        <small style="font-weight: normal; font-size: 9px; color: #555;">(Delivered: {{ $deliveredPerSubject->get($subject->id, 0) }})</small>
                    </th>
                @endforeach
                <th width="10%">Overall (Att/Del)</th>
                <th width="7%">Overall %</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $admission)
                @php
                    $overallAttended = 0;
                    $overallDelivered = 0;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $admission->college_roll_no }}</strong></td>
                    <td class="text-left">{{ $admission->student->full_name }}</td>

                    @foreach($subjects as $subject)
                        @php
                            $subData = $matrix[$admission->student_id][$subject->id] ?? ['attended' => 0, 'delivered' => 0];
                            $attended = $subData['attended'];
                            $delivered = $subData['delivered'];
                            
                            $overallAttended += $attended;
                            $overallDelivered += $delivered;
                            
                            $subPct = $delivered > 0 ? round(($attended / $delivered) * 100, 1) : 0;
                        @endphp
                        <td>
                            @if($delivered > 0)
                                <span style="{{ $subPct < 75 ? 'color: #dc3545;' : '' }}">
                                    {{ $attended }} / {{ $delivered }}
                                </span>
                                <br>
                                <small style="font-size: 9px; color: #666;">({{ $subPct }}%)</small>
                            @else
                                <span style="color: #999;">-</span>
                            @endif
                        </td>
                    @endforeach

                    @php
                        $overallPct = $overallDelivered > 0 ? round(($overallAttended / $overallDelivered) * 100, 1) : 0;
                    @endphp
                    <td><strong>{{ $overallAttended }} / {{ $overallDelivered }}</strong></td>
                    <td class="{{ $overallPct < 75 ? 'badge-shortage' : 'badge-good' }}">
                        {{ $overallPct }}%
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 5 + $subjects->count() }}" style="padding: 20px; color: #dc3545; font-size: 14px;">
                        No active/admitted students found for Admission Year {{ $year }} and Class {{ $class }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 10px; color: #555;">
        <p><strong>Note:</strong> Attendance percentage below 75% is highlighted in red. Attended lectures include both 'Present' and 'Late' records.</p>
    </div>

    <div style="margin-top: 50px; display: flex; justify-content: space-around;">
        <div style="text-align: center; width: 180px; border-top: 1px solid #000; padding-top: 4px;">
            <strong>Prepared By (Clerk)</strong>
        </div>
        <div style="text-align: center; width: 180px; border-top: 1px solid #000; padding-top: 4px;">
            <strong>Vice Principal</strong>
        </div>
        <div style="text-align: center; width: 180px; border-top: 1px solid #000; padding-top: 4px;">
            <strong>Principal</strong>
        </div>
    </div>
</body>
</html>
