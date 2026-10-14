@extends('layouts.app')
@section('title', 'HE Datesheets')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-calendar-alt me-2"></i>BS/ADP Datesheets</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('he.datesheets.create') }}" class="btn btn-primary me-2">
                <i class="fas fa-plus me-1"></i>Add Entry
            </a>
            <a href="{{ route('he.datesheets.generate') }}" class="btn btn-success">
                <i class="fas fa-magic me-1"></i>Auto Generate
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('he.datesheets.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="program_id" class="form-label">Program</label>
                    <select name="program_id" id="program_id" class="form-select">
                        <option value="">All Programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>
                                {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="semester_no" class="form-label">Semester</label>
                    <select name="semester_no" id="semester_no" class="form-select">
                        <option value="">All</option>
                        @for($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}" {{ request('semester_no') == $i ? 'selected' : '' }}>
                                Semester {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="exam_type" class="form-label">Exam Type</label>
                    <select name="exam_type" id="exam_type" class="form-select">
                        <option value="">All</option>
                        <option value="Mid" {{ request('exam_type') == 'Mid' ? 'selected' : '' }}>Mid Term</option>
                        <option value="Final" {{ request('exam_type') == 'Final' ? 'selected' : '' }}>Final</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="academic_year" class="form-label">Year</label>
                    <select name="academic_year" id="academic_year" class="form-select">
                        <option value="">All</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" {{ request('academic_year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('he.datesheets.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Datesheets Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Program</th>
                            <th>Semester</th>
                            <th>Subject</th>
                            <th>Exam Type</th>
                            <th>Year</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($datesheets as $datesheet)
                            <tr>
                                <td>
                                    <strong>{{ $datesheet->formatted_date }}</strong>
                                    @if($datesheet->exam_date->isFuture())
                                        <span class="badge bg-success ms-1">Upcoming</span>
                                    @elseif($datesheet->exam_date->isToday())
                                        <span class="badge bg-warning ms-1">Today</span>
                                    @endif
                                </td>
                                <td>{{ $datesheet->formatted_time }}</td>
                                <td>{{ $datesheet->program_name }}</td>
                                <td>Sem {{ $datesheet->semester_no }}</td>
                                <td>{{ $datesheet->subject_name }}</td>
                                <td>
                                    <span class="badge bg-{{ $datesheet->exam_type == 'Final' ? 'danger' : 'warning' }}">
                                        {{ $datesheet->exam_type }}
                                    </span>
                                </td>
                                <td>{{ $datesheet->academic_year }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('he.datesheets.edit', $datesheet->id) }}" 
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('he.datesheets.destroy', $datesheet->id) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this entry?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                    <p>No datesheets found. Click "Add Entry" or "Auto Generate" to create.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($datesheets->hasPages())
            <div class="card-footer">
                {{ $datesheets->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
