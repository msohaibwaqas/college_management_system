@extends('layouts.app')
@section('title', 'Curriculum Management')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0"><i class="fas fa-list-ol me-2"></i>Curriculum Management</h2>
            <a href="{{ route('semesters.create') }}" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>Add Subject to Semester
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
                            <th>Program</th>
                            <th>Semester</th>
                            <th>Subject</th>
                            <th>Code</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($semesters as $semester)
                            <tr>
                                <td><strong>{{ $semester->program->name ?? 'Deleted Program' }}</strong></td>
                                <td><span class="badge bg-secondary">Semester {{ $semester->semester_no }}</span></td>
                                <td>{{ $semester->bsSubject->name }}</td>
                                <td><code>{{ $semester->bsSubject->code }}</code></td>
                                <td class="text-center">
                                    <form action="{{ route('semesters.destroy', $semester) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this subject from the program?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Remove">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-book-reader fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No curriculum defined yet.</p>
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
