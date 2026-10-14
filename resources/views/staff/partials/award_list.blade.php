<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Award List - {{ $subject->name }} - {{ $year }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }

        .page {
            page-break-after: always;
            padding: 20px;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            max-width: 80px;
            max-height: 80px;
            margin-bottom: 10px;
        }

        .institute-name {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .teacher-info {
            margin: 15px 0;
            font-size: 11pt;
        }

        .teacher-info strong {
            display: inline-block;
            width: 150px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-size: 9pt;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .roll-col {
            width: 10%;
        }

        .marks-col {
            width: 10%;
        }

        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }

        @media print {
            .no-print {
                display: none;
            }

            .page {
                padding: 10px;
            }

            @page {
                size: A4;
                margin: 15mm;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
            <i class="fas fa-print"></i> Print
        </button>
        <button onclick="window.history.back()" class="btn btn-secondary" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
            <i class="fas fa-arrow-left"></i> Back
        </button>
    </div>

    @foreach($teachersData as $teacherData)
    <div class="page">
        <div class="header">
            @if($settings->hasLogo())
                <img src="{{ asset($settings->logo_url) }}" alt="Logo" class="logo">
            @endif
            <div class="institute-name">{{ $settings->full_name ?? 'Government Graduate College' }}</div>
        </div>

        <div class="teacher-info">
            <div><strong>Teacher Name:</strong> {{ $teacherData['teacher']->first_name }} {{ $teacherData['teacher']->last_name }}</div>
            <div><strong>Subject:</strong> {{ $subject->name }}</div>
            <div><strong>Admission Year:</strong> {{ $year }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="roll-col">Roll No</th>
                    <th class="marks-col">Marks</th>
                    <th class="roll-col">Roll No</th>
                    <th class="marks-col">Marks</th>
                    <th class="roll-col">Roll No</th>
                    <th class="marks-col">Marks</th>
                    <th class="roll-col">Roll No</th>
                    <th class="marks-col">Marks</th>
                    <th class="roll-col">Roll No</th>
                    <th class="marks-col">Marks</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $students = $teacherData['students']->toArray();
                    $totalStudents = count($students);
                    $rowCount = ceil($totalStudents / 5);
                @endphp
                
                @for($row = 0; $row < $rowCount; $row++)
                <tr>
                    @for($col = 0; $col < 5; $col++)
                        @php
                            $index = $row * 5 + $col;
                        @endphp
                        @if($index < $totalStudents)
                            <td class="roll-col">{{ $students[$index]['roll_no'] }}</td>
                            <td class="marks-col"></td>
                        @else
                            <td class="roll-col"></td>
                            <td class="marks-col"></td>
                        @endif
                    @endfor
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
    @endforeach

    @if(count($teachersData) == 0)
    <div class="page">
        <div class="header">
            @if($settings->hasLogo())
                <img src="{{ asset($settings->logo_url) }}" alt="Logo" class="logo">
            @endif
            <div class="institute-name">{{ $settings->full_name ?? 'Government Graduate College' }}</div>
        </div>
        <div style="text-align: center; margin-top: 50px; font-size: 14pt;">
            <p>No teachers found with assigned students for {{ $subject->name }} ({{ $year }})</p>
        </div>
    </div>
    @endif
</body>
</html>
