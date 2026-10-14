<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HE Seating Plan - {{ $seatingPlan->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        .no-print {
            padding: 20px;
            background: #fff;
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .plan-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .room-page {
            background: #fff;
            padding: 40px;
            margin-bottom: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            page-break-after: always;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px double #333;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
        }

        .header p {
            margin: 5px 0 0;
            font-size: 16px;
            color: #555;
        }

        .room-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f1f3f5;
            padding: 10px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .room-name {
            font-size: 20px;
            color: #0d6efd;
        }

        .seat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }

        .seat-card {
            border: 1px solid #dee2e6;
            padding: 12px;
            border-radius: 6px;
            text-align: center;
            background: #fff;
        }

        .seat-number {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
            display: block;
        }

        .roll-no {
            font-size: 18px;
            font-weight: 700;
            color: #111;
            margin-bottom: 2px;
        }

        .student-name {
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #444;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none; }
            .plan-container { max-width: 100%; padding: 0; }
            .room-page { 
                box-shadow: none; 
                padding: 0; 
                margin: 0;
                border: none;
            }
            .seat-card { border: 1px solid #333; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary btn-lg px-4 me-2">
            <i class="fas fa-print me-2"></i>Print Seating Plan
        </button>
        <a href="{{ route('he.seating_plans.attendance_sheet', $seatingPlan) }}" class="btn btn-success btn-lg px-4 me-2">
            <i class="fas fa-file-signature me-2"></i>Attendance Sheet
        </a>
        <a href="{{ route('he.seating_plans.index') }}" class="btn btn-outline-secondary btn-lg px-4">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <div class="plan-container">
        @foreach($data as $roomData)
            <div class="room-page">
                <div class="header">
                    <h1>Seating Plan</h1>
                    <p>
                        <strong>{{ $seatingPlan->title }}</strong><br>
                        {{ $seatingPlan->datesheet->bsSubject->name ?? 'N/A' }} | 
                        {{ \Carbon\Carbon::parse($seatingPlan->datesheet->exam_date)->format('l, d F Y') }}
                    </p>
                </div>

                <div class="room-info">
                    <div class="room-name">Room: {{ $roomData['room_name'] }}</div>
                    <div>
                        <div class="mb-1 text-end">Total Students: {{ $roomData['students']->count() }}</div>
                        <div class="small text-muted text-end">
                            @foreach($roomData['subject_counts'] as $subject)
                                <span class="badge bg-light text-dark border me-1">
                                    {{ $subject['name'] }} = {{ $subject['count'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="seat-grid">
                    @foreach($roomData['students'] as $detail)
                        @php
                            $rollNo = $detail->heAdmission->college_roll_no ?? '-';
                            $studentName = $detail->heAdmission ? ($detail->heAdmission->student->full_name ?? 'Deleted Student') : 'Empty / Vacant';
                            $admissionYear = $detail->heAdmission->admission_year ?? null;
                        @endphp
                        <div class="seat-card {{ !$detail->heAdmission ? 'bg-light text-muted' : '' }}">
                            <span class="seat-number">Seat #{{ $detail->seat_number }}</span>
                            <div class="roll-no">{{ $rollNo }}</div>
                            <div class="student-name" title="{{ $studentName }}">{{ $studentName }}</div>
                            @if($admissionYear)
                                <small class="text-muted d-block" style="font-size: 10px;">{{ $admissionYear }} Batch</small>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="no-print text-center mt-5 mb-3">
        @cmsCredits
    </div>
</body>
</html>
