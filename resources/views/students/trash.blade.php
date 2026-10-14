@extends('layouts.app')
@section('title', 'Deleted Students (Trash)')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0 text-danger"><i class="fas fa-trash-alt me-2"></i>Deleted Students (Trash)</h2>
            <div>
                @if(Auth::user()->hasRole('principal') || Auth::user()->is_super_admin)
                    <form action="{{ route('students.trash.empty') }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete ALL records in the trash? This action is IRREVERSIBLE.')">
                        @csrf
                        <button type="submit" class="btn btn-danger me-2">
                            <i class="fas fa-fire me-1"></i>Empty Recycle Bin
                        </button>
                    </form>
                @endif
                <a href="{{ route('students.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to List
                </a>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-muted small text-uppercase fw-bold">Recently Deleted</h5>
            <div class="search-box">
                <form action="{{ route('students.trash') }}" method="GET" class="d-flex">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Search by name or roll no..." value="{{ request('search') }}">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    @if(request('search'))
                        <a href="{{ route('students.trash') }}" class="btn btn-sm btn-outline-secondary ms-2">Clear</a>
                    @endif
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Father Name</th>
                            <th>Roll No</th>
                            <th>Deleted At</th>
                            <th class="text-center" style="width: 200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            @php
                                $admission = $student->admissions->first() ?? (object)['college_roll_no' => 'N/A'];
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $student->first_name }} {{ $student->last_name }}</div>
                                    @if($student->cnic)
                                        <small class="text-muted">{{ $student->cnic }}</small>
                                    @endif
                                </td>
                                <td>{{ $student->father->name ?? 'N/A' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $admission->college_roll_no }}</span></td>
                                <td>
                                    <div class="text-muted small">{{ $student->deleted_at->format('M d, Y') }}</div>
                                    <div class="text-muted extra-small">{{ $student->deleted_at->diffForHumans() }}</div>
                                </td>
                                <td class="text-center pe-4">
                                    <form action="{{ route('students.restore', $student->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Restore">
                                            <i class="fas fa-undo me-1"></i>Restore
                                        </button>
                                    </form>
                                    
                                    <form action="{{ route('students.force-delete', $student->id) }}" method="POST" class="d-inline ms-1" onsubmit="return confirm('WARNING: This will permanently delete this student record and all associated data. This action cannot be undone. Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Permanently Delete">
                                            <i class="fas fa-fire me-1"></i>Force Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted mb-3">
                                        <i class="fas fa-trash fa-3x opacity-25"></i>
                                    </div>
                                    <p class="mb-0">Trash is empty!</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($students->hasPages())
            <div class="card-footer bg-white py-3">
                <div class="d-flex justify-content-center">
                    {{ $students->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>

<style>
    .extra-small {
        font-size: 0.75rem;
    }
    .table > :not(caption) > * > * {
        padding: 1rem 0.5rem;
    }
</style>
@endsection
