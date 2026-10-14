<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Timetable Summary - {{ $year }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        .container-print {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
        }
        .header-box {
            border-bottom: 2px solid #000;
            margin-bottom: 20px;
            padding-bottom: 10px;
            text-align: center;
        }
        .timetable-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .timetable-table th, .timetable-table td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 11px;
            vertical-align: top;
            text-align: center;
        }
        .timetable-table th {
            background-color: #f2f2f2 !important;
            -webkit-print-color-adjust: exact;
        }
        .time-col {
            width: 120px;
            background-color: #f8f9fa !important;
            font-weight: bold;
        }
        .entry-box {
            margin-bottom: 5px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 3px;
        }
        .entry-box:last-child {
            border-bottom: none;
        }
        .subject-name {
            font-weight: bold;
            display: block;
            color: #000;
        }
        .teacher-name {
            font-style: italic;
            display: block;
            color: #444;
        }
        .room-label {
            font-size: 9px;
            color: #666;
            display: block;
        }
        .section-badge {
            font-size: 9px;
            background: #eee;
            padding: 1px 4px;
            border-radius: 3px;
            display: inline-block;
            margin-top: 2px;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 1cm;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .container-print {
                width: 100%;
                max-width: none;
                margin: 0;
                padding: 0;
            }
            .timetable-table th, .timetable-table td {
                border: 1px solid #000 !important;
            }
        }
    </style>
</head>
<body>

<div class="container-print">
    <div class="no-print mb-4 d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.timetables.index', ['admission_year' => $year]) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Editor
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print (A4)
        </button>
    </div>

    <div class="header-box">
        <h2 class="mb-1">Weekly College Timetable</h2>
        <h5 class="text-uppercase text-muted">Session / Admission Year: {{ $year }}</h5>
        <p class="small mb-0">Generated on: {{ now()->format('d M, Y H:i A') }}</p>
    </div>

    <table class="timetable-table">
        <thead>
            <tr>
                <th class="time-col">Lecture / Time</th>
                @foreach($daysOfWeek as $day)
                    <th>{{ $day }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($timeSlots as $slot)
                <tr>
                    <td class="time-col">
                        <strong>{{ $slot->name }}</strong><br>
                        <small>{{ date('h:i A', strtotime($slot->start_time)) }} - {{ date('h:i A', strtotime($slot->end_time)) }}</small>
                    </td>
                    @foreach($daysOfWeek as $day)
                        <td>
                            @php $cellEntries = $grid[$slot->id][$day] ?? []; @endphp
                            @forelse($cellEntries as $entry)
                                <div class="entry-box">
                                    <span class="subject-name">{{ $entry->subject->name ?? 'N/A' }}</span>
                                    <span class="teacher-name">{{ $entry->staff->full_name ?? 'N/A' }}</span>
                                    @if($entry->room)
                                        <span class="room-label">Room: {{ $entry->room->name }}</span>
                                    @endif
                                    <span class="section-badge">{{ $entry->section }}</span>
                                </div>
                            @empty
                                <span class="text-muted small">-</span>
                            @endforelse
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4 text-center d-none d-print-block">
        <p class="small text-muted">Approved by Principal _________________________</p>
    </div>

    <div class="mt-5 text-center">
        @cmsCredits
    </div>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
