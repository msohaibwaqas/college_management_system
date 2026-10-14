<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HE Tabulation Sheet - {{ $program->name }}</title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 11px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 5px; text-align: center; }
        .data-table th { background-color: #f2f2f2; }
        .rotate { height: 100px; white-space: nowrap; }
        .rotate div { transform: rotate(-90deg); width: 30px; margin: 0 auto; }
        .student-info { text-align: left !important; padding-left: 10px !important; }
        @media print {
            .no-print { display: none; }
            @page { size: landscape; margin: 10mm; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Tabulation Sheet</button>
        <button onclick="window.history.back()" style="padding: 10px 20px; cursor: pointer;">Back</button>
    </div>

    <div class="header">
        <h2>{{ $settings->full_name ?? 'INSTITUTE NAME' }}</h2>
        <h3>Tabulation Sheet ({{ $examType }} Term) - {{ $program->name }}</h3>
        <h4>Semester: {{ $semester }} | Session: {{ $year }}</h4>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" width="3%">S#</th>
                <th rowspan="2" width="7%">Roll No</th>
                <th rowspan="2" width="15%">Student Name</th>
                <th colspan="{{ $subjects->count() }}">Subjects (Marks Obtained)</th>
                <th rowspan="2" width="5%">Total</th>
                <th rowspan="2" width="5%">%</th>
                <th rowspan="2" width="5%">GPA</th>
            </tr>
            <tr>
                @foreach($subjects as $subject)
                    <th class="rotate"><div>{{ $subject->name }}</div></th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $admission)
                @php
                    $studentResults = $results->get($admission->id, collect());
                    $totalObtained = 0;
                    $totalPossible = 0;
                    $gpaSum = 0;
                    $subjectCount = 0;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $admission->roll_no }}</td>
                    <td class="student-info">{{ $admission->student->full_name }}</td>
                    @foreach($subjects as $subject)
                        @php
                            $result = $studentResults->where('bs_subject_id', $subject->id)->first();
                            $marks = '-';
                            if($result) {
                                $marks = $result->is_absent ? 'A' : $result->total_obtained_marks;
                                if(!$result->is_absent) {
                                    $totalObtained += $result->total_obtained_marks;
                                    $totalPossible += $result->total_marks;
                                    $gpaSum += $result->gpa;
                                    $subjectCount++;
                                }
                            }
                        @endphp
                        <td>{{ $marks }}</td>
                    @endforeach
                    <td>{{ $totalObtained }} / {{ $totalPossible }}</td>
                    <td>{{ $totalPossible > 0 ? round(($totalObtained / $totalPossible) * 100, 1) : 0 }}%</td>
                    <td>{{ $subjectCount > 0 ? round($gpaSum / $subjectCount, 2) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 40px;">
        <p><strong>Note:</strong> 'A' denotes Absent. GPA is calculated as average of individual subject GPAs for this exam type.</p>
    </div>

    <div style="margin-top: 60px; display: flex; justify-content: space-between;">
        <div style="text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px;">
            <strong>Prepared By</strong>
        </div>
        <div style="text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px;">
            <strong>Checked By</strong>
        </div>
        <div style="text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px;">
            <strong>Controller Examinations</strong>
        </div>
    </div>
</body>
</html>
