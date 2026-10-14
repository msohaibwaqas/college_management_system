@extends('layouts.app')
@section('title', 'HE Admissions List')

@section('content')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0"><i class="fas fa-user-graduate me-2"></i>HE Admissions List</h2>
            <a href="{{ route('admissions.create') }}" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>Add Admission
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    @endif

    {{-- Filters Card --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admissions.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label fw-semibold">Search</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Name or Roll No" value="{{ request('search') }}">
                </div>

                <div class="col-md-3">
                    <label for="program_id" class="form-label fw-semibold">Program</label>
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
                    <label for="admission_year" class="form-label fw-semibold">Year</label>
                    <select name="admission_year" id="admission_year" class="form-select">
                        <option value="">All Years</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}" {{ request('admission_year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="status" class="form-label fw-semibold">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Applied" {{ request('status') == 'Applied' ? 'selected' : '' }}>Applied</option>
                        <option value="Admitted" {{ request('status') == 'Admitted' ? 'selected' : '' }}>Admitted</option>
                        <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Graduated" {{ request('status') == 'Graduated' ? 'selected' : '' }}>Graduated</option>
                        <option value="Dropped" {{ request('status') == 'Dropped' ? 'selected' : '' }}>Dropped</option>
                        <option value="Suspended" {{ request('status') == 'Suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 me-2">
                        <i class="fas fa-search me-1"></i> Apply
                    </button>
                    <a href="{{ route('admissions.index') }}" class="btn btn-secondary w-100">
                        <i class="fas fa-redo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light border-bottom">
                        <tr>
                            <th class="ps-4">
                                <a href="{{ route('admissions.index', array_merge(request()->all(), ['sort_by' => 'roll_no', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                    Roll No {!! request('sort_by') == 'roll_no' ? (request('sort_order') == 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '<i class="fas fa-sort text-muted"></i>' !!}
                                </a>
                            </th>
                            <th>
                                <a href="{{ route('admissions.index', array_merge(request()->all(), ['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                    Student {!! request('sort_by') == 'name' ? (request('sort_order') == 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '<i class="fas fa-sort text-muted"></i>' !!}
                                </a>
                            </th>
                            <th>Father Name</th>
                            <th>Program</th>
                            <th>
                                <a href="{{ route('admissions.index', array_merge(request()->all(), ['sort_by' => 'current_semester', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                    Sem/Class {!! request('sort_by') == 'current_semester' ? (request('sort_order') == 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '<i class="fas fa-sort text-muted"></i>' !!}
                                </a>
                            </th>
                            <th>Contact</th>
                            <th>
                                <a href="{{ route('admissions.index', array_merge(request()->all(), ['sort_by' => 'status', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                    Status {!! request('sort_by') == 'status' ? (request('sort_order') == 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '<i class="fas fa-sort text-muted"></i>' !!}
                                </a>
                            </th>
                            <th class="text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- New HE Admissions --}}
                        @foreach($heAdmissions as $admission)
                            <tr>
                                <td class="ps-4"><strong>{{ $admission->roll_no }}</strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            {{ strtoupper(substr($admission->student->first_name ?? '?', 0, 1)) }}
                                        </div>
                                        <span>{{ $admission->student->full_name ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td>{{ $admission->student->father->name ?? 'N/A' }}</td>
                                <td><span class="badge bg-primary text-white">{{ $admission->program->name ?? 'N/A' }}</span></td>
                                <td>Sem {{ $admission->current_semester }}</td>
                                <td>{{ $admission->student->contact->student_contact ?? 'N/A' }}</td>
                                <td>
                                    @php
                                        $statusClass = match($admission->status) {
                                            'Active' => 'bg-success-subtle text-success',
                                            'Graduated' => 'bg-info-subtle text-info',
                                            'Dropped' => 'bg-danger-subtle text-danger',
                                            'Suspended' => 'bg-warning-subtle text-warning',
                                            'Admitted' => 'bg-primary-subtle text-primary',
                                            'Applied' => 'bg-secondary-subtle text-secondary',
                                            default => 'bg-light text-dark border',
                                        };
                                    @endphp
                                    <span class="badge rounded-pill {{ $statusClass }}">{{ $admission->status }}</span>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admissions.show', $admission) }}" class="btn btn-outline-info" title="Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admissions.edit', $admission) }}" class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admissions.destroy', $admission) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this admission?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        {{-- Legacy BS/ADP Admissions (Only show if on first page and no specific HE filters that would exclude them) --}}
                         @if($heAdmissions->isEmpty() && $legacyAdmissions->isEmpty())
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-user-graduate fa-3x text-light mb-3"></i>
                                    <p class="text-muted fs-5">No admissions found</p>
                                    <a href="{{ route('admissions.create') }}" class="btn btn-primary mt-2">
                                        <i class="fas fa-plus me-1"></i> Register New Student
                                    </a>
                                </td>
                            </tr>
                        @else
                            {{-- Legacy Section - Optional: Can separate or merge visually. --}}
                            @if($legacyAdmissions->isNotEmpty())
                                <tr class="table-secondary"><td colspan="8" class="fw-bold ps-4">Legacy Records (BS/ADP)</td></tr>
                                @foreach($legacyAdmissions as $admission)
                                    <tr class="table-light">
                                        <td class="ps-4"><strong>{{ $admission->college_roll_no }}</strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2 bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                    {{ strtoupper(substr($admission->student->first_name ?? '?', 0, 1)) }}
                                                </div>
                                                <span>{{ $admission->student->full_name ?? 'N/A' }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $admission->student->father->name ?? 'N/A' }}</td>
                                        <td><span class="badge bg-secondary text-white">{{ $admission->program_name }} (Legacy)</span></td>
                                        <td>{{ $admission->current_class }}</td>
                                        <td>{{ $admission->student->contact->student_contact ?? 'N/A' }}</td>
                                        <td><span class="badge rounded-pill bg-light text-dark border">{{ $admission->status }}</span></td>
                                        <td class="text-center pe-4">
                                             <a href="{{ route('students.show', $admission->student_id) }}" class="btn btn-sm btn-outline-secondary" title="View Legacy Student">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Pagination --}}
        <div class="card-footer bg-white py-3">
             <div class="d-flex justify-content-center">
                {{ $heAdmissions->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection
