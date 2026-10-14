<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attendance Sheet - {{ $seatingPlan->title }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            position: relative;
        }
        
        .logo {
            max-height: 50px;
            margin-bottom: 5px;
        }
        
        .college-name {
            font-size: 16px;
            font-weight: bold;
            margin: 2px 0;
            text-transform: uppercase;
        }
        
        .sheet-title {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
            text-decoration: underline;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
            text-align: left;
            font-size: 12px;
        }

        .info-item {
            margin-bottom: 4px;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .attendance-table th,
        .attendance-table td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
        }
        
        .attendance-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 10px;
        }
        
        .col-roll { width: 15%; }
        .col-name { width: 35%; text-align: left !important; padding-left: 8px !important; }
        .col-sign { width: 25%; }
        .col-remarks { width: 25%; }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #666;
        }
        
        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        
        .sig-box {
            text-align: center;
            width: 200px;
            border-top: 1px solid #000;
            padding-top: 5px;
            font-weight: bold;
        }
        
        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #fff;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        button {
            padding: 8px 15px;
            cursor: pointer;
            background: #0d6efd;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-weight: 600;
        }
        
        @media print {
            .no-print { display: none; }
            body { background: #fff; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">
            <i class="fas fa-print"></i> Print Attendance Sheets
        </button>
        <button onclick="window.close()" style="background: #6c757d; margin-left: 5px;">
            Close
        </button>
    </div>

    @foreach($data as $roomData)
        <div class="page-break">
            <div class="header">
                @if($instituteSettings && $instituteSettings->hasLogo())
                    <img src="{{ $instituteSettings->logo_url }}" alt="Logo" class="logo">
                @endif
                <div class="college-name">
                    {{ $instituteSettings->institute_title ?? 'Government Graduate College' }}
                </div>
                <div class="sheet-title">EXAMINATION ATTENDANCE SHEET</div>
            </div>

            <div class="info-grid">
                <div>
                    <div class="info-item"><strong>Exam:</strong> {{ $seatingPlan->title }}</div>
                    <div class="info-item"><strong>Subject:</strong> {{ $seatingPlan->datesheet->bsSubject->name ?? 'N/A' }}</div>
                    <div class="info-item"><strong>Program:</strong> {{ $seatingPlan->datesheet->program->name ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="info-item"><strong>Date:</strong> {{ \Carbon\Carbon::parse($seatingPlan->datesheet->exam_date)->format('d F Y') }}</div>
                    <div class="info-item"><strong>Room:</strong> {{ $roomData['room_name'] }}</div>
                    <div class="info-item"><strong>Semester:</strong> {{ $seatingPlan->datesheet->semester_no }}{{ ordinal($seatingPlan->datesheet->semester_no) }}</div>
                </div>
            </div>

            <table class="attendance-table">
                <thead>
                    <tr>
                        <th class="col-roll">Roll No</th>
                        <th class="col-name">Student Name</th>
                        <th class="col-sign">Signature</th>
                        <th class="col-remarks">Remarks/Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roomData['students'] as $detail)
                        <tr>
                            <td class="col-roll">{{ $detail->heAdmission->college_roll_no ?? '-' }}</td>
                            <td class="col-name">{{ $detail->student_name_safe }}</td>
                            <td class="col-sign">&nbsp;</td>
                            <td class="col-remarks">&nbsp;</td>
                        </tr>
                    @endforeach
                    @php $emptyRows = max(0, 10 - $roomData['students']->count()); @endphp
                    @for($i = 0; $i < $emptyRows; $i++)
                        <tr>
                            <td class="col-roll">&nbsp;</td>
                            <td class="col-name">&nbsp;</td>
                            <td class="col-sign">&nbsp;</td>
                            <td class="col-remarks">&nbsp;</td>
                        </tr>
                    @endfor
                </tbody>
            </table>

            <div class="footer">
                <div style="font-size: 12px; margin-bottom: 10px;">
                    <strong>Total Present:</strong> _________ &nbsp;&nbsp;&nbsp;
                    <strong>Total Absent:</strong> _________ &nbsp;&nbsp;&nbsp;
                    <strong>Total Students:</strong> {{ $roomData['students']->count() }}
                </div>
                
                <div class="signature-row">
                    <div class="sig-box">Invigilator Signature</div>
                    <div class="sig-box">Superintendent Signature</div>
                </div>
            </div>
        </div>
    @endforeach
    <footer class="no-print text-center mt-4">
        @cmsCredits
    </footer>
</body>
</html>
