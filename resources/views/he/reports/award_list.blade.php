<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HE Award List - {{ $subject->name }}</title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 12px; margin: 30px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; text-transform: uppercase; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 5px; border: 0; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 8px; text-align: center; }
        .data-table th { background-color: #f2f2f2; }
        .footer { margin-top: 50px; }
        .footer-table { width: 100%; }
        .footer-table td { text-align: center; width: 33%; }
        .signature-line { border-top: 1px solid #000; width: 80%; margin: 10px auto; }
        @media print {
            .no-print { display: none; }
            body { margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Award List</button>
        <button onclick="window.history.back()" style="padding: 10px 20px; cursor: pointer;">Back</button>
    </div>

    <div class="header">
        @if($settings && $settings->logo_url)
            <img src="{{ $settings->logo_url }}" alt="Logo" style="height: 60px;">
        @endif
        <h2>{{ $settings->full_name ?? 'INSTITUTE NAME' }}</h2>
        <h3>Award List - {{ $program->name }}</h3>
        <h4>Semester: {{ $semester }} | Session: {{ $year }}</h4>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Subject:</strong></td>
            <td width="35%">{{ $subject->name }} ({{ $subject->subject_code }})</td>
            <td width="15%"><strong>Teacher:</strong></td>
            <td width="35%">{{ $teacher ? $teacher->full_name : '____________________' }}</td>
        </tr>
        <tr>
            <td><strong>Credit Hours:</strong></td>
            <td>T: {{ $subject->theory_credit_hours }} | P: {{ $subject->practical_credit_hours }}</td>
            <td><strong>Exam Type:</strong></td>
            <td>Final Term Examination</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">S#</th>
                <th width="10%">Roll No</th>
                <th width="12%">Uni Roll No</th>
                <th width="20%">Student Name</th>
                <th width="18%">Father Name</th>
                <th width="7%">Mid (20)</th>
                <th width="7%">Sess (20)</th>
                <th width="7%">Final (60)</th>
                <th width="7%">Total (100)</th>
                <th width="7%">Grade</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $admission)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $admission->roll_no }}</td>
                    <td>{{ $admission->uni_roll_no ?? '-' }}</td>
                    <td style="text-align: left;">{{ $admission->student->full_name }}</td>
                    <td style="text-align: left;">{{ $admission->student->father->name ?? '-' }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    <strong>Internal Examiner</strong>
                </td>
                <td>
                    <div class="signature-line"></div>
                    <strong>External Examiner</strong>
                </td>
                <td>
                    <div class="signature-line"></div>
                    <strong>Principal / HOD</strong>
                </td>
            </tr>
        </table>
    </div>

    <script>
        // Auto print on load if requested
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
