@extends('layouts.app')
@section('title', 'Head of Department (HOD) Management')

@section('styles')
<style>
.hod-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s;
}
.kpi-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    background: #fff;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1.2rem;
}
.kpi-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}
.hod-table thead th {
    position: sticky;
    top: 0;
    background: #f8fafc;
    z-index: 2;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    border-bottom: 2px solid #e2e8f0;
    padding: 12px 16px;
}
.hod-table tbody td {
    padding: 14px 16px;
    vertical-align: middle;
}
.hod-table tbody tr:hover {
    background-color: #f1f5f9;
}
.teacher-pill {
    display: inline-block;
    padding: 2px 8px;
    margin: 2px;
    background: #e2e8f0;
    color: #334155;
    border-radius: 12px;
    font-size: 0.78rem;
    font-weight: 500;
}
.teacher-pill.current-hod {
    background: #dbeafe;
    color: #1e40af;
    font-weight: 700;
    border: 1px solid #93c5fd;
}
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
}
</style>
@endsection

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fas fa-user-tie text-primary me-2"></i>Subject HOD Management
                <span class="badge bg-primary fs-6 ms-2 align-middle">Intermediate / HSSC</span>
            </h3>
            <p class="text-muted mb-0 small">
                Assign one Head of Department for each Intermediate subject. HODs can create sections and assign active students to teachers within their department. (BS departments will be configured separately in future).
            </p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('admin.hods.auto_assign_single') }}" method="POST" onsubmit="return confirm('Auto-assign HOD for all departments that currently have only 1 teacher?');">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-magic me-1"></i> Auto-Assign 1-Teacher Departments
                </button>
            </form>
        </div>
    </div>

    {{-- KPIs --}}
    @php
        $totalSubjects = $subjects->count();
        $assignedCount = $subjects->whereNotNull('hod_staff_id')->count();
        $unassignedCount = $totalSubjects - $assignedCount;
        $singleTeacherCount = $subjects->filter(function($s) {
            return $s->staffSubjectSpecializations->count() === 1;
        })->count();
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Subjects</div>
                    <div class="h4 fw-bold mb-0 text-dark">{{ $totalSubjects }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="text-muted small">HODs Assigned</div>
                    <div class="h4 fw-bold mb-0 text-success">{{ $assignedCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div>
                    <div class="text-muted small">Pending HOD</div>
                    <div class="h4 fw-bold mb-0 text-warning">{{ $unassignedCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon bg-info bg-opacity-10 text-info">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <div class="text-muted small">Single-Teacher Depts</div>
                    <div class="h4 fw-bold mb-0 text-info">{{ $singleTeacherCount }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="card hod-card">
        <div class="card-header bg-white py-3 border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary active filter-btn" data-filter="all">All ({{ $totalSubjects }})</button>
                    <button type="button" class="btn btn-sm btn-outline-success filter-btn" data-filter="assigned">Assigned ({{ $assignedCount }})</button>
                    <button type="button" class="btn btn-sm btn-outline-warning filter-btn" data-filter="unassigned">Unassigned ({{ $unassignedCount }})</button>
                </div>
                <div style="min-width: 250px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="subjectSearch" class="form-control border-start-0" placeholder="Search subject or teacher...">
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table hod-table align-middle mb-0" id="hodTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Subject</th>
                        <th>Program / Type</th>
                        <th>Eligible Teachers</th>
                        <th>Current HOD</th>
                        <th style="min-width: 260px;" class="text-end">Assign / Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjects as $index => $subject)
                        @php
                            $eligibleTeachers = $subject->staffSubjectSpecializations->map(fn($s) => $s->staff)->filter();
                            $teacherCount = $eligibleTeachers->count();
                            $hasHod = (bool) $subject->hod_staff_id;
                            $hodStaff = $subject->hod;
                        @endphp
                        <tr data-status="{{ $hasHod ? 'assigned' : 'unassigned' }}" data-search="{{ strtolower($subject->name . ' ' . $subject->code . ' ' . ($hodStaff ? $hodStaff->first_name . ' ' . $hodStaff->last_name : '')) }}">
                            <td class="text-muted small">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $subject->name }}</div>
                                <div class="text-muted small">Code: <span class="badge bg-light text-secondary">{{ $subject->code }}</span></div>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $subject->program_type ?? 'Inter' }}</span>
                                <span class="badge bg-light text-dark border ms-1">{{ $subject->type }}</span>
                            </td>
                            <td>
                                <div class="small fw-semibold text-secondary mb-1">
                                    {{ $teacherCount }} teacher{{ $teacherCount == 1 ? '' : 's' }} specialized:
                                </div>
                                <div style="max-width: 320px;">
                                    @forelse($eligibleTeachers as $t)
                                        <span class="teacher-pill {{ $subject->hod_staff_id == $t->id ? 'current-hod' : '' }}" title="{{ $t->designationModel?->title }}">
                                            @if($subject->hod_staff_id == $t->id)
                                                <i class="fas fa-crown text-primary me-1"></i>
                                            @endif
                                            {{ $t->first_name }} {{ $t->last_name }}
                                        </span>
                                    @empty
                                        <span class="text-muted small fst-italic">No active teachers assigned yet</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                @if($hasHod && $hodStaff)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $hodStaff->first_name }} {{ $hodStaff->last_name }}</div>
                                            <div class="text-muted small">{{ $hodStaff->designationModel?->title ?? 'Teacher' }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="badge bg-warning bg-opacity-25 text-dark status-badge">
                                        <i class="fas fa-clock me-1"></i> Unassigned
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($teacherCount > 0)
                                    <form action="{{ route('admin.hods.assign') }}" method="POST" class="d-inline-flex gap-1 align-items-center justify-content-end">
                                        @csrf
                                        <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                                        
                                        <select name="staff_id" class="form-select form-select-sm" style="max-width: 170px;" required>
                                            <option value="">-- Choose HOD --</option>
                                            @foreach($eligibleTeachers as $t)
                                                <option value="{{ $t->id }}" {{ $subject->hod_staff_id == $t->id ? 'selected' : '' }}>
                                                    {{ $t->first_name }} {{ $t->last_name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <button type="submit" class="btn btn-sm {{ $hasHod ? 'btn-outline-primary' : 'btn-primary' }}" title="{{ $hasHod ? 'Change HOD' : 'Assign HOD' }}">
                                            <i class="fas fa-check"></i> {{ $hasHod ? 'Update' : 'Assign' }}
                                        </button>
                                    </form>

                                    @if($hasHod)
                                        <form action="{{ route('admin.hods.unassign') }}" method="POST" class="d-inline ms-1" onsubmit="return confirm('Remove HOD role for {{ $subject->name }}?');">
                                            @csrf
                                            <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove HOD">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-muted small fst-italic">No teachers available</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No subjects found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('subjectSearch');
    const table = document.getElementById('hodTable');
    const rows = table.querySelectorAll('tbody tr');
    const filterBtns = document.querySelectorAll('.filter-btn');

    let currentFilter = 'all';

    function applyFilterAndSearch() {
        const query = (searchInput.value || '').trim().toLowerCase();

        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            const searchData = row.getAttribute('data-search') || '';

            const matchesFilter = (currentFilter === 'all') || (status === currentFilter);
            const matchesSearch = !query || searchData.includes(query);

            if (matchesFilter && matchesSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilterAndSearch);
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.getAttribute('data-filter');
            applyFilterAndSearch();
        });
    });
});
</script>
@endsection
