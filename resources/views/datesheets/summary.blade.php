@extends('layouts.app')
@section('title', 'Datesheet Summary')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
        <h2><i class="fas fa-calendar-check me-2"></i>Datesheet Summary</h2>
        <div>
            <button onclick="window.print()" class="btn btn-info me-2 text-white">
                <i class="fas fa-print me-2"></i>Print
            </button>
            <a href="{{ route('datesheets.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>
    </div>

    <div class="card shadow mb-4 d-print-none">
        <div class="card-body">
            <form action="{{ route('datesheets.summary') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Filter Summary
                    </button>
                    @if(request('start_date') || request('end_date'))
                        <a href="{{ route('datesheets.summary') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow print-clean-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0 align-middle text-center print-table-bordered">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th style="width: 15%">Date</th>
                            <th style="width: 15%">Day</th>
                            <th style="width: 30%">Class 11</th>
                            <th style="width: 30%">Class 12</th>
                            <th style="width: 10%">Total Students</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schedule as $row)
                            <tr>
                                <td class="fw-bold">{{ $row['date']->format('d M Y') }}</td>
                                <td>{{ $row['date']->format('l') }}</td>
                                <td>
                                    @foreach($row['class_11'] as $subject)
                                        <div class="badge bg-primary mb-1 print-badge">{{ $subject }}</div><br>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach($row['class_12'] as $subject)
                                        <div class="badge bg-success mb-1 print-badge">{{ $subject }}</div><br>
                                    @endforeach
                                </td>
                                <td class="fw-bold">{{ $row['total_students'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No tests scheduled.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
