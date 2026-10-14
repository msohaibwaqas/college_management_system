@extends('layouts.app')
@section('title', 'Timetables')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-week me-2"></i>Class 1st / 2nd Year Timetables</h2>
        <div>
            <a href="{{ route('timetables.create') }}" class="btn btn-primary me-2">
                <i class="fas fa-plus me-2"></i>Add Manual Entry
            </a>
            <a href="{{ route('timetables.generate.form') }}" class="btn btn-success">
                <i class="fas fa-magic me-2"></i>Auto Generate
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.timetables.index') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Admission Year</label>
                        <select name="admission_year" class="form-select" required onchange="this.form.submit()">
                            <option value="">Select Year</option>
                            @foreach($admissionYears as $year)
                                <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Hidden field to preserve selected day across year changes -->
                    <input type="hidden" name="day_of_week" value="{{ $selectedDay }}">
                    
                    @if($selectedYear)
                        <div class="col-md-8 d-flex align-items-end justify-content-end">
                            <form method="POST" action="{{ route('timetables.destroy_all') }}" onsubmit="return confirm('Delete entire timetable for this year?');" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="admission_year" value="{{ $selectedYear }}">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash me-2"></i>Delete All
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($selectedYear)
        <!-- Day Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="dayTabs">
            @foreach($days as $day)
                <li class="nav-item">
                    <a class="nav-link {{ $selectedDay == $day ? 'active fw-bold' : '' }}" 
                       href="{{ route('admin.timetables.index', ['admission_year' => $selectedYear, 'day_of_week' => $day]) }}">
                        {{ $day }}
                    </a>
                </li>
            @endforeach
        </ul>

        <!-- Timetable Grid -->
        <div class="card shadow border-top-0 rounded-bottom">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 align-middle">
                        <thead class="table-dark text-center">
                            <tr>
                                <th style="width: 120px;">Lecture</th>
                                @foreach($groups as $group)
                                    <th>{{ $group->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($periods as $periodNum => $times)
                                <tr>
                                    <td class="text-center fw-bold bg-light">
                                        <div class="fs-5">{{ $periodNum }}</div>
                                        <span class="badge bg-secondary">{{ $times['start'] }} - {{ $times['end'] }}</span>
                                    </td>
                                    
                                    @foreach($groups as $group)
                                        <td class="p-2 text-center" style="min-width: 180px; vertical-align: top;">
                                            @php
                                                // Get all entries for this group and period
                                                $entriesForCell = $timetables->where('subject_group_id', $group->id)
                                                                             ->where('period_number', $periodNum);
                                            @endphp

                                            @if($entriesForCell->isNotEmpty())
                                                @foreach($entriesForCell as $entry)
                                                    <div class="mb-2 p-2 rounded position-relative {{ $entry->is_break ? 'bg-warning bg-opacity-25 border border-warning' : 'bg-primary bg-opacity-10 border border-primary-subtle' }}">
                                                        @if($entry->is_break)
                                                            <div class="fw-bold text-warning-emphasis"><i class="fas fa-mug-hot me-2"></i>Break</div>
                                                        @else
                                                            <div class="fw-bold text-primary">{{ $entry->subject->name }}</div>
                                                            <div class="small fw-semibold mt-1">
                                                                {{ $entry->staff ? $entry->staff->full_name : 'No Teacher' }}
                                                            </div>
                                                            @if($entry->room)
                                                                <div class="small text-muted mt-1 text-truncate">
                                                                    <i class="fas fa-door-open me-1"></i>{{ $entry->room->name }}
                                                                </div>
                                                            @endif
                                                        @endif
                                                        
                                                        <div class="position-absolute top-0 end-0 p-1">
                                                            <form method="POST" action="{{ route('timetables.destroy', $entry) }}" onsubmit="return confirm('Delete this entry?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-link btn-sm text-danger p-0 m-0 text-decoration-none" title="Delete">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="text-center text-muted py-3">
                                                    <small class="fst-italic">- Free -</small>
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-secondary text-center shadow-sm">
            <i class="fas fa-arrow-up me-2"></i>Select an admission year to view the timetable
        </div>
    @endif
</div>
@endsection
