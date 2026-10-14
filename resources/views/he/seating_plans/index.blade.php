@extends('layouts.app')
@section('title', 'HE Seating Plans')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chair me-2"></i>BS/ADP Seating Plans</h2>
        <a href="{{ route('he.seating_plans.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Generate New Plan
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Exam/Subject</th>
                            <th>Program & Semester</th>
                            <th>Exam Date</th>
                            <th>Total Students</th>
                            <th>Rooms Used</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $plan->title }}</div>
                                    <small class="text-muted">{{ $plan->datesheet->bsSubject->name ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    {{ $plan->datesheet->program->name ?? 'N/A' }}
                                    @if($plan->datesheet)
                                        <span class="badge bg-info ms-1">{{ $plan->datesheet->semester_no }}{{ ordinal($plan->datesheet->semester_no) }} Sem</span>
                                    @endif
                                </td>
                                <td class="fw-bold">{{ $plan->datesheet ? \Carbon\Carbon::parse($plan->datesheet->exam_date)->format('d M Y') : 'N/A' }}</td>
                                <td>{{ $plan->student_count }}</td>
                                <td>{{ $plan->room_count }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('he.seating_plans.show', $plan) }}" class="btn btn-sm btn-info text-white" title="View Plan" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('he.seating_plans.attendance_sheet', $plan) }}" class="btn btn-sm btn-success" title="Attendance Sheet" target="_blank">
                                            <i class="fas fa-file-alt"></i>
                                        </a>
                                        <form action="{{ route('he.seating_plans.destroy', $plan) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this seating plan?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No seating plans found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($plans->hasPages())
            <div class="card-footer bg-white">
                {{ $plans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
