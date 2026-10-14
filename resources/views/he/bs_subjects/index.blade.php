@extends('layouts.app')
@section('title', 'BS Subjects List')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0"><i class="fas fa-book-open me-2"></i>BS Subjects List</h2>
            <a href="{{ route('bs-subjects.create') }}" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>Add Subject
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Credit Hours</th>
                            <th>Type</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subjects as $subject)
                            <tr>
                                <td><strong>{{ $subject->code }}</strong></td>
                                <td>{{ $subject->name }}</td>
                                <td>
                                    <span class="badge bg-primary" title="Theory">{{ $subject->theory_credit_hours }}</span> + 
                                    <span class="badge bg-info" title="Practical">{{ $subject->practical_credit_hours }}</span>
                                </td>
                                <td><span class="badge bg-secondary">{{ $subject->type }}</span></td>
                                <td class="text-center">
                                    <a href="{{ route('bs-subjects.edit', $subject) }}" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
@empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No BS subjects found</p>
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
