<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Sheet - {{ $seatingPlan->test_date->format('d M Y') }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        
        .logo {
            max-height: 60px;
            margin-bottom: 5px;
        }
        
        .college-name {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .date-line {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .room-line {
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .attendance-table th,
        .attendance-table td {
            border: 1px solid #000;
            padding: 8px 5px;
            text-align: center;
        }
        
        .attendance-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .roll-cell,
        .name-cell,
        .subject-cell,
        .signature-cell {
            width: 8%;
            font-size: 11px; /* Slightly larger than 5px for readability, but still small */
            word-wrap: break-word;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #000;
        }
        
        .footer-line {
            margin: 8px 0;
            font-size: 13px;
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
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">
            Print Attendance Sheets
        </button>
        <button onclick="window.location.href='{{ route('seating-plans.show', $seatingPlan) }}'" style="padding: 10px 20px; font-size: 16px; cursor: pointer; margin-left: 10px;">
            Back to Seating Plan
        </button>
    </div>

    @foreach($data as $index => $roomData)
        <div class="{{ $index < count($data) - 1 ? 'page-break' : '' }}">
            <div class="header">
                @if($instituteSettings && $instituteSettings->hasLogo())
                    <img src="{{ $instituteSettings->logo_url }}" alt="Logo" class="logo">
                @endif
                <div class="college-name">
                    {{ $instituteSettings->institute_title ?? 'Government Graduate College' }}
                </div>
                <div class="date-line">
                    Attendance Sheet - {{ $seatingPlan->test_date->format('d F Y') }}
                </div>
                <div class="room-line">
                    Room: {{ $roomData['room_name'] }} <br>
                    @php
                        // Determine Class/Semester for this room
                        $classes = $roomData['students']->map(function($detail) {
                            if (!$detail->student) return null;
                            
                            $admission = $detail->student->admissions()
                                ->where('admission_year', $detail->admission_year)
                                ->first();
                            if ($admission) {
                                if ($admission->program_type && $admission->program_type != 'Inter') {
                                    return $admission->program_type . ' Sem-' . $admission->semester;
                                }
                                return 'Class ' . $admission->current_class;
                            }
                            return null;
                        })->filter();
                        
                        $uniqueClasses = $classes->unique();
                        $classDisplay = $uniqueClasses->count() > 1 ? $uniqueClasses->implode(' / ') : $uniqueClasses->first();
                    @endphp
                    <span style="font-weight: bold;">{{ $classDisplay }}</span><br>
                    <span style="font-weight: normal; font-size: 12px;">
                        <strong>Invigilators:</strong>
                        @forelse($roomData['invigilators'] as $invigilator)
                            {{ $invigilator->staff->first_name }} {{ $invigilator->staff->last_name }} 
                            ({{ $invigilator->staff->designationModel->name ?? 'N/A' }}){{ !$loop->last ? ',' : '' }}
                        @empty
                            Not Assigned
                        @endforelse
                    </span>
                </div>
            </div>

            <table class="attendance-table">
                <thead>
                        <tr>
                            <th class="roll-cell">Roll #</th>
                            <th class="name-cell">Name</th>
                            <th class="subject-cell">Teacher</th>
                            <th class="signature-cell">Signature</th>
                            <th class="roll-cell">Roll #</th>
                            <th class="name-cell">Name</th>
                            <th class="subject-cell">Teacher</th>
                            <th class="signature-cell">Signature</th>
                            <th class="roll-cell">Roll #</th>
                            <th class="name-cell">Name</th>
                            <th class="subject-cell">Teacher</th>
                            <th class="signature-cell">Signature</th>
                        </tr>
                </thead>
                <tbody>
                @php
                    $students = $roomData['students']->values(); // Collection of students
                    $totalStudents = $students->count();
                    $rowCount = ceil($totalStudents / 3);
                @endphp
                
                @for($row = 0; $row < $rowCount; $row++)
                    <tr>
                        @for($col = 0; $col < 3; $col++)
                            @php
                                $index = $row + ($col * $rowCount);
                            @endphp
                            @if($index < $totalStudents)
                                @php
                                    $detail = $students->get($index);
                                    $collegeRoll = '-';
                                    $studentName = 'Empty / Vacant';
                                    
                                    if ($detail->student) {
                                        $admission = $detail->student->admissions()
                                            ->where('admission_year', $detail->admission_year)
                                            ->first();
                                        $collegeRoll = $admission->college_roll_no ?? $detail->student->id;
                                        $studentName = $detail->student->first_name . ' ' . $detail->student->last_name;
                                    }
                                @endphp
                                <td class="roll-cell">{{ $collegeRoll }}</td>
                                <td class="name-cell">{{ $studentName }}</td>
                                <td class="subject-cell">{{ $detail->teacher_name }}</td>
                                <td class="signature-cell">&nbsp;</td>
                            @else
                                <td class="roll-cell">&nbsp;</td>
                                <td class="name-cell">&nbsp;</td>
                                <td class="subject-cell">&nbsp;</td>
                                <td class="signature-cell">&nbsp;</td>
                            @endif
                        @endfor
                    </tr>
                @endfor
                </tbody>
            </table>

            <div class="footer">
                <div class="footer-line">
                    <strong>Total Students:</strong> {{ $roomData['total_students'] }}
                </div>
                <div class="footer-line">
                    <strong>Present:</strong> __________ &nbsp;&nbsp;&nbsp; 
                    <strong>Absent:</strong> __________ &nbsp;&nbsp;&nbsp; 
                    <strong>Invigilator Signature:</strong> ___________________
                </div>
            </div>
        </div>
    @endforeach
    <div class="d-print-none" style="text-align: center; padding: 20px 0; border-top: 1px solid #eee;">
        @cmsCredits
    </div>
</body>
</html>
