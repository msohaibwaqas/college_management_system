@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-info text-white py-3">
                    <h4 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>HE Student Transcripts</h4>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('he.reports.transcript.search') }}" method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Search by Name</label>
                            <input type="text" name="search" class="form-control" placeholder="Student name..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Search by Roll No</label>
                            <input type="text" name="roll_no" class="form-control" placeholder="College or Uni Roll No..." value="{{ request('roll_no') }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-info text-white w-100">
                                <i class="fas fa-search me-2"></i>Find Students
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>College Roll No</th>
                                    <th>Uni Roll No</th>
                                    <th>Student Name</th>
                                    <th>Program</th>
                                    <th>Batch</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $admission)
                                    <tr>
                                        <td class="fw-bold text-primary">{{ $admission->roll_no }}</td>
                                        <td>{{ $admission->uni_roll_no ?? 'N/A' }}</td>
                                        <td>{{ $admission->student->full_name }}</td>
                                        <td>{{ $admission->program->name }}</td>
                                        <td>{{ $admission->admission_year }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('he.reports.transcript.generate', $admission->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-file-alt me-1"></i>View Transcript
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No students found matching your search.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $students->appends(request()->input())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
