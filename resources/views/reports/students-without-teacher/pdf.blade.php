<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Students Without Teacher Report</title>
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
        .table-pdf {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table-pdf th, .table-pdf td {
            border: 1px solid #666;
            padding: 5px 6px;
            font-size: 10px;
            text-align: left;
        }
        .table-pdf th {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .table-pdf tr:nth-child(even) {
            background-color: #fcfcfc;
        }
        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #ccc;
            font-size: 8px;
            color: #666;
        }
        .alert.success {
            padding: 10px;
            background-color: #d4edda;
            border: 1px solid #28a745;
            color: #155724;
            font-size: 11px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h2>{{ $collegeName }}</h2>
        <h4>Students Without Teacher Report</h4>
    </div>

    <!-- Report Information -->
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Admission Year:</span>
            <span class="info-value">{{ $admissionYear }}</span>
            <span class="info-label">Filter Subject:</span>
            <span class="info-value">{{ $subject ? $subject->name : 'All Subjects' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Total Unassigned Students:</span>
            <span class="info-value"><strong>{{ $reportData->count() }}</strong></span>
        </div>
    </div>

    <!-- Student Unassigned List -->
    <div class="roll-numbers">
        <h5>⚠ Students With Missing Teacher Assignments</h5>
        
        @if($reportData->count() > 0)
            <table class="table-pdf">
                <thead>
                    <tr>
                        <th style="text-align: center; width: 6%;">#</th>
                        <th style="text-align: center; width: 14%;">Roll No.</th>
                        <th style="width: 25%;">Student Name</th>
                        <th style="width: 20%;">Father Name</th>
                        <th style="width: 35%;">Unassigned Subjects (No Teacher)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData as $index => $row)
                        <tr>
                            <td style="text-align: center;">{{ $index + 1 }}</td>
                            <td style="text-align: center; font-weight: bold;">{{ $row['college_roll_no'] }}</td>
                            <td>{{ $row['student_name'] }}</td>
                            <td>{{ $row['father_name'] }}</td>
                            <td>{{ $row['unassigned_subjects_str'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="alert success">
                ✓ All students enrolled in subjects for {{ $admissionYear }} have been assigned to teachers.
            </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        Generated on: {{ now()->format('F d, Y h:i A') }}
    </div>
</body>
</html>
