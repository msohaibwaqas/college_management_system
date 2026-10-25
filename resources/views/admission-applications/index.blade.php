@extends('layouts.app')
@section('title', 'Admission Applications')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Admission Applications</h4>
        <div>
            <a href="{{ route('admission-applications.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Walk-in
            </a>
            <a href="{{ route('admission-applications.merit-list') }}" class="btn btn-success ms-2">
                <i class="fas fa-list-ol me-1"></i> Merit List
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admission-applications.index') }}" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        @foreach(['draft','submitted','under_review','approved','fee_pending','fee_paid','admitted','rejected'] as $s)
                            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Year</label>
                    <select name="admission_year" class="form-select form-select-sm">
                        <option value="">All Years</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}" {{ request('admission_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Source</label>
                    <select name="source" class="form-select form-select-sm">
                        <option value="">All Sources</option>
                        <option value="online" {{ request('source') == 'online' ? 'selected' : '' }}>Online</option>
                        <option value="walk_in" {{ request('source') == 'walk_in' ? 'selected' : '' }}>Walk-in</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Subject Group</label>
                    <select name="subject_group" class="form-select form-select-sm">
                        <option value="">All Groups</option>
                        @foreach(['Pre-Medical','Pre-Engineering','General Science','Humanities'] as $g)
                            <option value="{{ $g }}" {{ request('subject_group') == $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Name, App #, Phone...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Results --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>App #</th>
                            <th>Name</th>
                            <th>Father</th>
                            <th>SSC Marks</th>
                            <th>Group</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Voucher</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $idx => $app)
                        <tr>
                            <td>{{ $applications->firstItem() + $idx }}</td>
                            <td><code>{{ $app->application_number }}</code></td>
                            <td>{{ $app->full_name }}</td>
                            <td>{{ $app->father_name }}</td>
                            <td>
                                <span class="fw-bold">{{ $app->ssc_obtained_marks }}</span>/{{ $app->ssc_total_marks }}
                                <small class="text-muted">({{ $app->ssc_percentage }}%)</small>
                            </td>
                            <td><span class="badge bg-secondary">{{ $app->subject_group ?? '—' }}</span></td>
                            <td>
                                <span class="badge bg-{{ $app->source === 'online' ? 'info' : 'dark' }}">
                                    {{ $app->source === 'online' ? 'Online' : 'Walk-in' }}
                                </span>
                            </td>
                            <td><span class="badge bg-{{ $app->status_badge }}">{{ $app->status_label }}</span></td>
                            <td>
                                @if($app->voucher)
                                    <span class="badge bg-{{ $app->voucher->status_badge }}">{{ $app->voucher->voucher_number }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('admission-applications.show', $app) }}" class="btn btn-sm btn-outline-primary" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($app->status === 'admitted' && $app->linked_student_id)
                                    <a href="{{ route('students.edit', $app->linked_student_id) }}" class="btn btn-sm btn-outline-info" title="Edit Student">
                                        <i class="fas fa-user-edit"></i>
                                    </a>
                                @else
                                    <a href="{{ route('admission-applications.edit', $app) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                                <form action="{{ route('admission-applications.destroy', $app) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this application? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No applications found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($applications->hasPages())
        <div class="card-footer">
            {{ $applications->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
