@extends('layouts.app')
@section('title', 'View Final Results')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-list-alt me-2"></i>View Final Results</h2>
        <div>
            <a href="{{ route('results.final.create') }}" class="btn btn-primary me-2">
                <i class="fas fa-plus me-2"></i>Add New Result
            </a>
            <a href="{{ route('results.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('results.final.view') }}" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="type" value="{{ request('type', 'entered') }}">
                <input type="hidden" name="sort" value="{{ request('sort', 'college_roll_no') }}">
                <input type="hidden" name="direction" value="{{ request('direction', 'asc') }}">
                
                <div class="col-md-3">
                    <label class="form-label">Admission Year</label>
                    <select name="admission_year" class="form-select" required>
                        <option value="">Select Year</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}" {{ request('admission_year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Show</label>
                    <select name="per_page" class="form-select">
                        <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        <option value="500" {{ request('per_page') == 500 ? 'selected' : '' }}>500</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(request('admission_year'))
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <a href="{{ request()->fullUrlWithQuery(['type' => 'total']) }}" class="text-decoration-none">
                    <div class="card bg-primary text-white h-100 {{ request('type') == 'total' ? 'border border-white border-3' : '' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Total Students</h6>
                                    <h2 class="mb-0">{{ $stats['total'] }}</h2>
                                </div>
                                <i class="fas fa-users fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ request()->fullUrlWithQuery(['type' => 'entered']) }}" class="text-decoration-none">
                    <div class="card bg-success text-white h-100 {{ (request('type', 'entered') == 'entered') ? 'border border-white border-3' : '' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Results Entered</h6>
                                    <h2 class="mb-0">{{ $stats['entered'] }}</h2>
                                </div>
                                <i class="fas fa-check-circle fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ request()->fullUrlWithQuery(['type' => 'remaining']) }}" class="text-decoration-none">
                    <div class="card bg-warning text-dark h-100 {{ request('type') == 'remaining' ? 'border border-dark border-3' : '' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Remaining</h6>
                                    <h2 class="mb-0">{{ $stats['remaining'] }}</h2>
                                </div>
                                <i class="fas fa-clock fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    {{ ucfirst(request('type', 'entered')) }} Students - Year: {{ request('admission_year') }}
                </h5>
                <div>
                    @if($students->total() > 0)
                        Showing {{ $students->firstItem() }} to {{ $students->lastItem() }} of {{ $students->total() }} results
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'college_roll_no', 'direction' => request('sort') == 'college_roll_no' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-dark text-decoration-none">
                                        Roll No
                                        @if(request('sort', 'college_roll_no') == 'college_roll_no')
                                            <i class="fas fa-sort-{{ request('direction', 'asc') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @else
                                            <i class="fas fa-sort ms-1 opacity-50"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>Name</th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'bise_roll_11', 'direction' => request('sort') == 'bise_roll_11' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-dark text-decoration-none">
                                        11th BISE
                                        @if(request('sort') == 'bise_roll_11')
                                            <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @else
                                            <i class="fas fa-sort ms-1 opacity-50"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'bise_roll_12', 'direction' => request('sort') == 'bise_roll_12' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-dark text-decoration-none">
                                        12th BISE
                                        @if(request('sort') == 'bise_roll_12')
                                            <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @else
                                            <i class="fas fa-sort ms-1 opacity-50"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>11th Total</th>
                                <th>12th Total</th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'grand_total', 'direction' => request('sort') == 'grand_total' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-dark text-decoration-none">
                                        Grand Total
                                        @if(request('sort') == 'grand_total')
                                            <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @else
                                            <i class="fas fa-sort ms-1 opacity-50"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                @php
                                    $allResults = $student->results;
                                    $grouped = $allResults->groupBy(function($r) { return $r->subject->name; });
                                    
                                    $passThreshold = 33; // Default
                                    $studentTotalObtained11 = 0;
                                    $studentTotalObtained12 = 0;
                                    $studentTotalMaxMarks = 0;
                                    $studentTotalDeficit = 0;
                                    
                                    $reappear11 = [];
                                    $reappear12 = [];
                                    $isAllAbsent = true;
                                    $hasAtLeastOneResult = ($allResults->count() > 0);

                                    foreach ($grouped as $subjectName => $sGroup) {
                                        $p1 = $sGroup->where('class_level', '11')->first();
                                        $p2 = $sGroup->where('class_level', '12')->first();
                                        $sub = $p1 ? $p1->subject : ($p2 ? $p2->subject : null);

                                        $p1Theory = $p1 ? ($p1->obtained_theory ?? 0) : 0;
                                        $p2Theory = $p2 ? ($p2->obtained_theory ?? 0) : 0;
                                        $totalPrac = ($p1 ? ($p1->obtained_practical ?? 0) : 0) + ($p2 ? ($p2->obtained_practical ?? 0) : 0);
                                        
                                        if (($p1 && !$p1->is_absent) || ($p2 && !$p2->is_absent)) {
                                            $isAllAbsent = false;
                                        }

                                        $p1Max = ($sub && $sub->total_11 > 0) ? $sub->total_11 : ($p1 && $p1->total_marks > 0 ? $p1->total_marks : 0);
                                        $p2TheoryMax = ($sub && $sub->theory_12 > 0) ? $sub->theory_12 : ($p2 ? $p2->total_marks - ($p2->obtained_practical ? $p2->total_marks * 0.15 : 0) : 0);
                                        $pracMax = ($sub && $sub->practical_12 > 0) ? $sub->practical_12 : 0;
                                        if (!$sub && $p2) $p2TheoryMax = $p2->total_marks;
                                        
                                        $subMax = $p1Max + $p2TheoryMax + $pracMax;
                                        $studentTotalMaxMarks += $subMax;
                                        
                                        $obtained = $p1Theory + $p2Theory + $totalPrac;
                                        $studentTotalObtained11 += $p1Theory;
                                        $studentTotalObtained12 += ($p2Theory + $totalPrac);
                                        
                                        $theoryObtained = $p1Theory + $p2Theory;
                                        $aggPassT = round($subMax * $passThreshold / 100);
                                        $theoryPassT = round(($p1Max + $p2TheoryMax) * $passThreshold / 100);
                                        $p1PassT = round($p1Max * $passThreshold / 100);
                                        $p2PassT = round($p2TheoryMax * $passThreshold / 100);
                                        $pracPassT = round($pracMax * $passThreshold / 100);

                                        $isPassed = ($theoryObtained >= $theoryPassT && $obtained >= $aggPassT);
                                        
                                        if (!$isPassed) {
                                            $deficit = max(0, $theoryPassT - $theoryObtained, $aggPassT - $obtained);
                                            $studentTotalDeficit += $deficit;
                                            
                                            // Identify reappear specific parts
                                            if ($p1 && $p1Theory < $p1PassT) $reappear11[] = $subjectName;
                                            if ($p2 && $p2Theory < $p2PassT) $reappear12[] = $subjectName;
                                            if ($pracMax > 0 && $totalPrac < $pracPassT) {
                                                 if (!in_array($subjectName, $reappear12)) $reappear12[] = $subjectName . " (Prac)";
                                            }
                                        }
                                    }

                                    $gracePool = ceil($studentTotalMaxMarks * 0.01);
                                    $passedByGrace = ($studentTotalDeficit > 0 && $studentTotalDeficit <= $gracePool);
                                    
                                    if ($passedByGrace) {
                                        $reappear11 = [];
                                        $reappear12 = [];
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $student->admissions->first()->college_roll_no ?? 'N/A' }}</td>
                                    <td>
                                        <div>{{ $student->full_name }}</div>
                                        <small class="text-muted">F: {{ $student->father->name ?? 'N/A' }}</small>
                                    </td>
                                    <td>{{ $student->admissions->first()->bise_roll_11 ?? 'N/A' }}</td>
                                    <td>{{ $student->admissions->first()->bise_roll_12 ?? 'N/A' }}</td>
                                     <td>
                                        @if(count($reappear11) > 0)
                                            <span class="text-danger small">Reappear: {{ implode(', ', $reappear11) }}</span>
                                        @else
                                            {{ $studentTotalObtained11 }}
                                        @endif
                                    </td>
                                    <td>
                                        @if(count($reappear12) > 0)
                                            <span class="text-danger small">Reappear: {{ implode(', ', $reappear12) }}</span>
                                        @else
                                            {{ $studentTotalObtained12 }}
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$hasAtLeastOneResult)
                                            <span class="text-muted">-</span>
                                        @elseif($isAllAbsent)
                                            <span class="badge bg-secondary">Absent</span>
                                        @elseif($passedByGrace)
                                            <span class="badge bg-info">Pass (Grace)</span>
                                            <div class="small fw-bold">{{ $studentTotalObtained11 + $studentTotalObtained12 }}</div>
                                        @elseif(count($reappear11) > 0 || count($reappear12) > 0)
                                            <span class="badge bg-danger">Reappear</span>
                                        @else
                                            <span class="badge bg-success">Pass</span>
                                            <div class="small fw-bold">{{ $studentTotalObtained11 + $studentTotalObtained12 }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('results.student', $student->id) }}" class="btn btn-sm btn-info text-white" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @php
                                                $admission = $student->admissions->first();
                                            @endphp
                                            @if($admission && $admission->bise_roll_12)
                                                <form action="{{ route('results.final.search') }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="admission_year" value="{{ $admission->admission_year }}">
                                                    <input type="hidden" name="bise_roll_12" value="{{ $admission->bise_roll_12 }}">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Enter/Edit Marks">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        No students found matching this criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($students->hasPages())
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-center">
                        {{ $students->links() }}
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
