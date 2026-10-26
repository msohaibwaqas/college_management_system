@extends('layouts.app')

@section('title', 'Final Result Report')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3>Final Result Report</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('reports.final-result-report.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="admission_year" class="form-label fw-semibold">Admission Year</label>
                    <select name="admission_year" id="admission_year" class="form-select" required>
                        <option value="">Select Year</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}" {{ isset($selectedYear) && $selectedYear == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="result_part" class="form-label fw-semibold">Result Type / Part</label>
                    <select name="result_part" id="result_part" class="form-select">
                        <option value="combine" {{ ($selectedPart ?? 'combine') == 'combine' ? 'selected' : '' }}>
                            Combine (Part I & II)
                        </option>
                        <option value="part1" {{ ($selectedPart ?? '') == 'part1' ? 'selected' : '' }}>
                            First Year (Part I)
                        </option>
                        <option value="part2" {{ ($selectedPart ?? '') == 'part2' ? 'selected' : '' }}>
                            Second Year (Part II)
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="pass_percentage" class="form-label fw-semibold">Pass Percentage (Default: 33%)</label>
                    <div class="input-group">
                        <input type="number" name="pass_percentage" id="pass_percentage" class="form-control" 
                               value="{{ $customPassPercentage ?? 33 }}" min="0" max="100" step="0.01">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-3 align-self-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Show Report
                    </button>
                </div>

                <div class="col-12 mt-2">
                    <div class="p-2 px-3 bg-light rounded-3 border d-inline-flex align-items-center">
                        <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                            <input type="hidden" name="include_grace" value="0">
                            <input class="form-check-input mt-0" type="checkbox" name="include_grace" id="include_grace" value="1" 
                                   {{ ($includeGrace ?? true) ? 'checked' : '' }} style="cursor: pointer; width: 2.2em; height: 1.2em;">
                            <label class="form-check-label fw-semibold text-dark user-select-none" for="include_grace" style="cursor: pointer;">
                                <i class="fas fa-award text-warning me-1"></i> Include BISE Board 1% Grace Marks
                                <span class="text-muted fw-normal ms-1 small">(Promotes eligible students with deficit &le; 1% of total exam marks into Pass count)</span>
                            </label>
                        </div>
                    </div>
                </div>
            </form>

            @if(isset($reportData))
                <hr>
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0 fw-bold">Report Results (Admission Year: {{ $selectedYear }})</h5>
                        <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                            <span class="badge bg-primary">
                                {{ ($selectedPart ?? 'combine') == 'part1' ? 'First Year (Part I)' : (($selectedPart ?? '') == 'part2' ? 'Second Year (Part II)' : 'Combine (Part I & II)') }}
                            </span>
                            <span class="badge bg-light text-dark border">
                                Threshold: <strong>{{ $customPassPercentage ?? 33 }}%</strong>
                            </span>
                            @if($includeGrace ?? true)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                    <i class="fas fa-check-circle me-1"></i> Board 1% Grace: Included
                                </span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                    <i class="fas fa-times-circle me-1"></i> Strict Raw Marks (Grace Excluded)
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($includeGrace ?? true)
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" id="toggleGraceBadgeBtn" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-xs" onclick="toggleGraceBadges()" data-hidden="0">
                            <i class="fas fa-eye-slash" id="graceToggleIcon"></i>
                            <span id="graceToggleText">Hide Grace Tags</span>
                        </button>
                    </div>
                    @endif
                </div>

                <div class="table-responsive mt-2">
                    <table class="table table-bordered table-striped" id="reportTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Year</th>
                                <th>Part</th>
                                <th>Total Enrolled</th> <!-- Subject Total -->
                                <th>Total Assigned</th> <!-- Teacher Total -->
                                <th>Pass</th>
                                <th>Fail</th>
                                <th>Absent</th>
                                <th>Missing</th>
                                <th>Pass % (Excl. Absent/Missing)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $subjectName => $rows)
                                @foreach($rows as $index => $row)
                                    <tr>
                                        @if($index === 0)
                                            <td rowspan="{{ count($rows) }}" class="align-middle fw-bold">{{ $subjectName }}</td>
                                        @endif
                                        <td>{{ $row['teacher_name'] }}</td>
                                        <td>{{ $row['year'] }}</td>
                                        <td>
                                            <span class="badge {{ $row['part'] == 'part1' ? 'bg-info text-dark' : ($row['part'] == 'part2' ? 'bg-secondary' : 'bg-primary') }}">
                                                {{ $row['part'] == 'part1' ? 'Part I' : ($row['part'] == 'part2' ? 'Part II' : 'Combine') }}
                                            </span>
                                        </td>
                                        @if($index === 0)
                                            <td rowspan="{{ count($rows) }}" class="align-middle text-center">{{ $row['total_enrolled'] }}</td>
                                        @endif
                                        <td class="text-center">{{ $row['total_assigned'] }}</td>
                                        <td class="text-center text-success fw-bold">
                                            {{ $row['pass'] }}
                                            @if(($includeGrace ?? true) && isset($row['grace']) && $row['grace'] > 0)
                                                <small class="grace-badge badge bg-warning bg-opacity-10 text-dark border border-warning ms-1" style="font-size: 0.68rem;" title="{{ $row['grace'] }} student(s) passed via BISE Board 1% Grace Marks">
                                                    +{{ $row['grace'] }} Grace
                                                </small>
                                            @endif
                                        </td>
                                        <td class="text-center text-danger fw-bold">{{ $row['fail'] }}</td>
                                        <td class="text-center text-warning fw-bold">{{ $row['absent'] }}</td>
                                        <td class="text-center">
                                            @if($row['missing'] > 0)
                                                <span class="badge bg-secondary" style="cursor: help;" 
                                                      title="Missing: {{ implode(', ', $row['missing_details']) }}">
                                                    {{ $row['missing'] }}
                                                </span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $row['pass_percentage'] >= 50 ? 'bg-success' : 'bg-danger' }}">
                                                {{ $row['pass_percentage'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">No data found for the selected criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleGraceBadges() {
    const badges = document.querySelectorAll('.grace-badge');
    const textEl = document.getElementById('graceToggleText');
    const iconEl = document.getElementById('graceToggleIcon');
    const btnEl = document.getElementById('toggleGraceBadgeBtn');
    if (!textEl || !iconEl || !btnEl) return;

    const isCurrentlyHidden = btnEl.getAttribute('data-hidden') === '1';

    if (isCurrentlyHidden) {
        // Show tags
        badges.forEach(b => b.classList.remove('d-none'));
        textEl.textContent = 'Hide Grace Tags';
        iconEl.className = 'fas fa-eye-slash';
        btnEl.setAttribute('data-hidden', '0');
        btnEl.classList.remove('btn-secondary', 'text-white');
        btnEl.classList.add('btn-outline-secondary');
        try { localStorage.setItem('hide_grace_badges', '0'); } catch(e) {}
    } else {
        // Hide tags
        badges.forEach(b => b.classList.add('d-none'));
        textEl.textContent = 'Show Grace Tags';
        iconEl.className = 'fas fa-eye';
        btnEl.setAttribute('data-hidden', '1');
        btnEl.classList.remove('btn-outline-secondary');
        btnEl.classList.add('btn-secondary', 'text-white');
        try { localStorage.setItem('hide_grace_badges', '1'); } catch(e) {}
    }
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        if (localStorage.getItem('hide_grace_badges') === '1') {
            toggleGraceBadges();
        }
    } catch(e) {}
});
</script>
@endsection
