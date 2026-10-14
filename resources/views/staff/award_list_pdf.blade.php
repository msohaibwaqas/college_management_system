<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Award List</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            margin: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 3px 0;
            font-size: 16px;
        }
        .header h4 {
            margin: 3px 0;
            font-size: 12px;
            color: #333;
        }
        .info-section {
            margin-bottom: 15px;
            font-size: 10px;
        }
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 25%;
        }
        .info-value {
            display: table-cell;
            width: 25%;
        }
        table.roll-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }
        table.roll-grid th,
        table.roll-grid td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 10px;
        }
        table.roll-grid th {
            background-color: #f0f0f0;
        }
        .footer-section {
            margin-top: 30px;
            border-top: 1px solid #000;
            padding-top: 10px;
            page-break-inside: avoid;
        }
        .footer-row {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .footer-cell {
            display: table-cell;
            width: 25%;
            vertical-align: bottom;
        }
    </style>
</head>
<body class="award-list">
    <div class="header">
        <h2>GOVT. GRADUATE COLLEGE, CHISHTIAN</h2>
        <h4>AWARD LIST</h4>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Admission Year:</span>
            <span class="info-value">{{ $year }}</span>
            <span class="info-label">Subject Code:</span>
            <span class="info-value">{{ $subject->code ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Subject Name:</span>
            <span class="info-value">{{ $subject->name }}</span>
            <span class="info-label">Subject Type:</span>
            <span class="info-value">{{ $subject->type }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Total Students:</span>
            <span class="info-value"><strong>{{ $students->count() }}</strong></span>
        </div>
    </div>

    <table class="roll-grid">
        <thead>
            <tr>
                <th width="10%">Roll No</th>
                <th width="10%">Marks</th>
                <th width="10%">Roll No</th>
                <th width="10%">Marks</th>
                <th width="10%">Roll No</th>
                <th width="10%">Marks</th>
                <th width="10%">Roll No</th>
                <th width="10%">Marks</th>
                <th width="10%">Roll No</th>
                <th width="10%">Marks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students->chunk(5) as $chunk)
                <tr>
                    @foreach($chunk as $student)
                        <td>{{ $student->admissions->first()->college_roll_no ?? 'N/A' }}</td>
                        <td></td>
                    @endforeach
                    @if($chunk->count() < 5)
                        @for($i = $chunk->count(); $i < 5; $i++)
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        @endfor
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-section">
        <div class="footer-row">
            <div class="footer-cell">
                <strong>Pass:</strong> ___________
            </div>
            <div class="footer-cell">
                <strong>Fail:</strong> ___________
            </div>
            <div class="footer-cell">
                <strong>Absent:</strong> ___________
            </div>
        </div>
        <div class="footer-row" style="margin-top: 40px;">
            <div class="footer-cell" style="width: 50%;">
                <strong>Teacher Name:</strong> {{ $staff->first_name }} {{ $staff->last_name }}
            </div>
            <div class="footer-cell" style="width: 50%; text-align: right;">
                <strong>Signature:</strong> ____________________
            </div>
        </div>
    </div>
</body>
</html>
