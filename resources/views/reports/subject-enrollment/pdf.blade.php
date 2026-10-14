<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Subject Enrollment Report</title>
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

        .roll-numbers {
            margin-top: 15px;
        }

        .roll-numbers h5 {
            margin: 10px 0 8px 0;
            font-size: 11px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        table.roll-grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            table-layout: fixed;
        }

        table.roll-grid-table td {
            width: 10%;
            padding: 4px 2px;
            text-align: center;
            border: 1px solid #ddd;
            font-size: 10px;
            vertical-align: top;
            height: 30px;
            word-wrap: break-word;
        }

        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #ccc;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h2>{{ $collegeName }}</h2>
        <h4>Subject Enrollment Report</h4>
    </div>

    <!-- Report Information -->
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Admission Year:</span>
            <span class="info-value">{{ $admissionYear }}</span>
            <span class="info-label">Subject Code:</span>
            <span class="info-value">{{ $subject->code }}</span>
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

    <!-- Roll Numbers Grid or Elective Subjects List -->
    <div class="roll-numbers">
        @if(isset($subject->id) && $subject->id === 'all_electives')
            <h5>Student Elective Subjects List</h5>
            @if($students->count() > 0)
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr style="background-color: #f2f2f2;">
                            <th style="border: 1px solid #666; padding: 5px; text-align: center; font-size: 10px; width: 5%;">#</th>
                            <th style="border: 1px solid #666; padding: 5px; text-align: center; font-size: 10px; width: 15%;">Roll No.</th>
                            <th style="border: 1px solid #666; padding: 5px; text-align: left; font-size: 10px; width: 25%;">Student Name</th>
                            <th style="border: 1px solid #666; padding: 5px; text-align: left; font-size: 10px; width: 20%;">Father Name</th>
                            <th style="border: 1px solid #666; padding: 5px; text-align: left; font-size: 10px; width: 35%;">Elective Subjects</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                            @php
                                $admission = $student->admissions->first();
                                $electivesList = $student->electives->pluck('subject.name')->filter()->implode(', ');
                            @endphp
                            <tr style="{{ $index % 2 == 1 ? 'background-color: #fcfcfc;' : '' }}">
                                <td style="border: 1px solid #ccc; padding: 4px; text-align: center; font-size: 9px;">{{ $index + 1 }}</td>
                                <td style="border: 1px solid #ccc; padding: 4px; text-align: center; font-size: 9px; font-weight: bold;">{{ $admission ? $admission->college_roll_no : 'N/A' }}</td>
                                <td style="border: 1px solid #ccc; padding: 4px; font-size: 9px;">{{ $student->first_name }} {{ $student->last_name }}</td>
                                <td style="border: 1px solid #ccc; padding: 4px; font-size: 9px;">{{ optional($student->father)->name ?? 'N/A' }}</td>
                                <td style="border: 1px solid #ccc; padding: 4px; font-size: 9px;">{{ $electivesList ?: 'None' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="padding: 10px; background-color: #f0f0f0; border: 1px solid #ccc; text-align: center;">
                    No students found for the selected admission year.
                </p>
            @endif
        @else
            <h5>Student Roll Numbers</h5>
            
            @if($students->count() > 0)
                <table class="roll-grid-table">
                    @foreach($students->chunk(10) as $chunk)
                        <tr>
                            @foreach($chunk as $student)
                                <td>
                                    <div style="font-weight: bold; font-size: 10px; color: #000;">{{ $student->college_roll_no }}</div>
                                    @if(!empty($student->elective_subjects))
                                        <div style="font-size: 7px; color: #444; margin-top: 2px; line-height: 1.1;">{{ $student->elective_subjects }}</div>
                                    @endif
                                </td>
                            @endforeach
                            @if($chunk->count() < 10)
                                @for($i = $chunk->count(); $i < 10; $i++)
                                    <td>&nbsp;</td>
                                @endfor
                            @endif
                        </tr>
                    @endforeach
                </table>
            @else
                <p style="padding: 10px; background-color: #f0f0f0; border: 1px solid #ccc; text-align: center;">
                    No students enrolled in this subject for the selected admission year.
                </p>
            @endif
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        Generated on: {{ now()->format('F d, Y h:i A') }}
    </div>
</body>
</html>
