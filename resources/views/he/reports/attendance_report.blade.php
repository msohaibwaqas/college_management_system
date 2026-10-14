<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HE Attendance Report - {{ $subject->name }}</title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 12px; margin: 30px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 8px; text-align: center; }
        .data-table th { background-color: #f2f2f2; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 5px; }
        @media print {
            .no-print { display: none; }
            body { margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Report</button>
        <button onclick="window.history.back()" style="padding: 10px 20px; cursor: pointer;">Back</button>
    </div>

    <div class="header">
        <h2>{{ $settings->full_name ?? 'INSTITUTE NAME' }}</h2>
        <h3>Attendance Summary Report</h3>
        <h4>From: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} To: {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</h4>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Program:</strong></td>
            <td width="35%">{{ $program->name }} (Semester {{ $semester }})</td>
            <td width="15%"><strong>Subject:</strong></td>
            <td width="35%">{{ $subject->name }} ({{ $subject->subject_code }})</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">S#</th>
                <th width="10%">Roll No</th>
                <th width="30%" style="text-align: left;">Student Name</th>
                <th width="10%">Present</th>
                <th width="10%">Absent</th>
                <th width="10%">Leave</th>
                <th width="10%">Total Lectures</th>
                <th width="15%">Attendance %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $admission)
                @php
                    $studentAttendance = $attendanceData->get($admission->id, collect());
                    $present = $studentAttendance->where('status', 'present')->count();
                    $absent = $studentAttendance->where('status', 'absent')->count();
                    $leave = $studentAttendance->where('status', 'leave')->count();
                    $late = $studentAttendance->where('status', 'late')->count();
                    
                    $presentTotal = $present + $late; // Late counts as present
                    $total = $studentAttendance->count();
                    $percentage = $total > 0 ? round(($presentTotal / $total) * 100, 2) : 0;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $admission->roll_no }}</td>
                    <td style="text-align: left;">{{ $admission->student->full_name }}</td>
                    <td>{{ $present }} @if($late > 0) <small class="text-muted">(+{{ $late }} Late)</small> @endif</td>
                    <td>{{ $absent }}</td>
                    <td>{{ $leave }}</td>
                    <td>{{ $total }}</td>
                    <td style="font-weight: bold; {{ $percentage < 75 ? 'color: red;' : '' }}">
                        {{ $percentage }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 40px; font-size: 11px;">
        <p><strong>Note:</strong> Minimum attendance required for examination is usually 75%. Students highlighted in red are short of attendance.</p>
    </div>

    <div style="margin-top: 60px; display: flex; justify-content: space-around;">
        <div style="text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px;">
            <strong>Subject Teacher</strong>
        </div>
        <div style="text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px;">
            <strong>In-Charge HE</strong>
        </div>
    </div>
</body>
</html>
