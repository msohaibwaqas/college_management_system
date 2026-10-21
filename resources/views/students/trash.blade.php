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

    <!-- Filters Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Search &amp; Filters</h5>
        </div>
        <div class="card-body">
            <form id="filterForm" method="GET" action="{{ route('students.trash') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="search" class="form-label fw-semibold">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" id="search" class="form-control" 
                               placeholder="Search by Name, Father, or Roll No..." value="{{ request('search') }}">
                        @if(request('search'))
                            <a href="{{ route('students.trash', request()->except('search', 'page')) }}" class="btn btn-outline-secondary" title="Clear Search">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="per_page" class="form-label fw-semibold">Per Page</label>
                    <select name="per_page" id="per_page" class="form-select" onchange="this.form.submit()">
                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        <option value="200" {{ request('per_page') == 200 ? 'selected' : '' }}>200</option>
                    </select>
                </div>

                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-search me-1"></i>Search
                    </button>
                    @if(request('search') || request('per_page'))
                        <a href="{{ route('students.trash') }}" class="btn btn-secondary flex-fill">
                            <i class="fas fa-redo me-1"></i>Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Results Info -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="text-muted small">
            @if($students->total() > 0)
                Showing <strong>{{ $students->firstItem() }}</strong> to <strong>{{ $students->lastItem() }}</strong> of <strong>{{ $students->total() }}</strong> deleted students
                @if(request('search'))
                    for "<strong>{{ request('search') }}</strong>"
                @endif
            @else
                No deleted students found
                @if(request('search'))
                    matching "<strong>{{ request('search') }}</strong>"
                @endif
            @endif
        </div>
        @if(request('search'))
            <a href="{{ route('students.trash') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times me-1"></i>Clear Search Filter
            </a>
        @endif
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Name</th>
                            <th>Father Name</th>
                            <th>Roll No</th>
                            <th>Deleted At</th>
                            <th class="text-center" style="width: 200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $i => $student)
                            @php
                                $admission = $student->admissions->first() ?? (object)['college_roll_no' => 'N/A'];
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted small">{{ $students->firstItem() + $i }}</td>
                                <td>
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
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted mb-3">
                                        <i class="fas fa-trash fa-3x opacity-25"></i>
                                    </div>
                                    <p class="mb-0">
                                        @if(request('search'))
                                            No deleted students match your search query.
                                        @else
                                            Trash is empty!
                                        @endif
                                    </p>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    let searchTimeout;
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') return;
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                document.getElementById('filterForm').submit();
            }, 500);
        });
    }
});
</script>
@endsection
