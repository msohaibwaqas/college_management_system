<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transcript - {{ $admission->student->full_name }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; margin: 40px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .header h1 { margin: 5px 0; color: #000; text-transform: uppercase; }
        .header h2 { margin: 5px 0; font-weight: normal; }
        
        .student-info { width: 100%; margin-bottom: 30px; }
        .student-info td { padding: 5px; }
        
        .semester-block { margin-bottom: 30px; page-break-inside: avoid; }
        .semester-header { background: #f4f4f4; padding: 8px 15px; font-weight: bold; border: 1px solid #ddd; border-bottom: none; display: flex; justify-content: space-between; }
        
        .result-table { width: 100%; border-collapse: collapse; }
        .result-table th, .result-table td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        .result-table th { background-color: #f9f9f9; font-weight: bold; }
        .result-table td.subject-name { text-align: left; }
        
        .summary-box { margin-top: 40px; background: #f9f9f9; padding: 20px; border: 1px solid #ddd; width: 300px; float: right; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px; }
        .summary-total { border-top: 2px solid #333; margin-top: 10px; padding-top: 10px; font-weight: bold; font-size: 16px; }
        
        .footer { margin-top: 100px; clear: both; }
        .signature-table { width: 100%; text-align: center; }
        .signature-table td { width: 50%; }
        .signature-line { border-top: 1px solid #000; width: 200px; margin: 0 auto 10px auto; }
        
        .no-print { margin-bottom: 20px; text-align: center; }
        .btn { padding: 10px 20px; cursor: pointer; background: #007bff; color: #fff; border: none; border-radius: 4px; text-decoration: none; }
        
        @media print {
            .no-print { display: none; }
            body { margin: 15mm; }
            .semester-block { border: 1px solid #000; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn">Print Transcript</button>
        <a href="{{ route('he.reports.transcript.search') }}" class="btn" style="background: #6c757d;">Back to Search</a>
    </div>

    <div class="header">
        @if($settings && $settings->logo_url)
            <img src="{{ $settings->logo_url }}" alt="Logo" style="height: 80px; margin-bottom: 10px;">
        @endif
        <h1>{{ $settings->full_name ?? 'INSTITUTE NAME' }}</h1>
        <h2>Official Academic Transcript</h2>
    </div>

    <table class="student-info">
        <tr>
            <td width="20%"><strong>Student Name:</strong></td>
            <td width="30%">{{ $admission->student->full_name }}</td>
            <td width="20%"><strong>Program:</strong></td>
            <td width="30%">{{ $admission->program->name }}</td>
        </tr>
        <tr>
            <td><strong>Father's Name:</strong></td>
            <td>{{ $admission->student->father->name ?? '-' }}</td>
            <td><strong>Batch / Session:</strong></td>
            <td>{{ $admission->admission_year }}</td>
        </tr>
        <tr>
            <td><strong>College Roll No:</strong></td>
            <td>{{ $admission->roll_no }}</td>
            <td><strong>Uni Roll No:</strong></td>
            <td>{{ $admission->uni_roll_no ?? 'Under Process' }}</td>
        </tr>
    </table>

    @foreach($resultsBySemester as $semester => $results)
        @php
            $semQP = 0;
            $semCH = 0;
        @endphp
        <div class="semester-block">
            <div class="semester-header">
                <span>Semester {{ $semester }}</span>
            </div>
            <table class="result-table">
                <thead>
                    <tr>
                        <th width="15%">Code</th>
                        <th width="40%">Subject Title</th>
                        <th width="10%">Cr. Hrs</th>
                        <th width="10%">Marks</th>
                        <th width="10%">Grade</th>
                        <th width="15%">GPA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $result)
                        @php
                            $ch = ($result->bsSubject->theory_credit_hours ?? 0) + ($result->bsSubject->practical_credit_hours ?? 0);
                            $semQP += $result->gpa * $ch;
                            $semCH += $ch;
                        @endphp
                        <tr>
                            <td>{{ $result->bsSubject->subject_code }}</td>
                            <td class="subject-name">{{ $result->bsSubject->name }}</td>
                            <td>{{ $ch }}</td>
                            <td>{{ $result->is_absent ? 'A' : $result->total_obtained_marks . '/' . $result->total_marks }}</td>
                            <td>{{ $result->grade }}</td>
                            <td>{{ number_format($result->gpa, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="text-align: right; padding: 8px; background: #f9f9f9; border: 1px solid #ddd; border-top: none;">
                <strong>Semester GPA (SGPA): {{ $semCH > 0 ? number_format($semQP / $semCH, 2) : '0.00' }}</strong>
            </div>
        </div>
    @endforeach

    <div class="summary-box">
        <div class="summary-row">
            <span>Total Credit Hours:</span>
            <span>{{ $admission->heResults->where('exam_type', 'Final')->sum(function($r) { return ($r->bsSubject->theory_credit_hours ?? 0) + ($r->bsSubject->practical_credit_hours ?? 0); }) }}</span>
        </div>
        <div class="summary-total">
            <span>Cumulative GPA (CGPA):</span>
            <span>{{ number_format($cgpa, 2) }}</span>
        </div>
    </div>

    <div class="footer">
        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    <strong>Controller of Examinations</strong>
                </td>
                <td>
                    <div class="signature-line"></div>
                    <strong>Principal</strong>
                </td>
            </tr>
        </table>
        <div style="margin-top: 30px; font-size: 11px; text-align: center; color: #666;">
            Generated on {{ date('d M Y, h:i A') }} | This is a computer generated document and may not require physical signature unless specified.
        </div>
    </div>
</body>
</html>
