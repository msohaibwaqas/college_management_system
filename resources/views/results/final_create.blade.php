@extends('layouts.app')
@section('title', 'Final Result Entry')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-graduation-cap me-2"></i>Final Result Entry (Single Student)</h2>
        <div>
            
            <a href="{{ route('results.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Results
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Search Student</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('results.final.search') }}" method="POST" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Admission Year</label>
                    <select name="admission_year" class="form-select" required>
                        <option value="">Select Year</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}" {{ (old('admission_year') == $year || (!old('admission_year') && $year == date('Y') - 2)) ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">BISE Roll No (12th)</label>
                    <input type="text" name="bise_roll_12" class="form-control" value="{{ old('bise_roll_12') }}" required placeholder="Enter Board Roll No" {{ !isset($student) ? 'autofocus' : '' }}>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-search me-2"></i>Search Student
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(isset($student))
        <!-- Student Info -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="mb-1">{{ $student->full_name }}</h4>
                        <p class="text-muted mb-0">Father: {{ $student->father->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="badge bg-info fs-6">Roll No: {{ $student->admissions->first()->college_roll_no ?? 'N/A' }}</span>
                        <span class="badge bg-warning fs-6 ms-2">BISE Roll: {{ $student->admissions->first()->bise_roll_12 ?? 'N/A' }}</span>
                        
                        @if($student->admissions->first()->bise_roll_12)
                            <button type="button" id="fetch-bise-btn" class="btn btn-sm btn-dark ms-2" 
                                    data-roll="{{ $student->admissions->first()->bise_roll_12 }}" autofocus>
                                <i class="fas fa-sync-alt me-1"></i>Fetch from BISE
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Marks Entry Form -->
        <form action="{{ route('results.final.store') }}" method="POST">
            @csrf
            <input type="hidden" name="student_id" value="{{ $student->id }}">
            <input type="hidden" name="admission_year" value="{{ request('admission_year') }}">

            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Enter Final Marks</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 align-middle">
                            <thead class="table-light text-center">
                                <tr>
                                    <th rowspan="2" style="width: 20%">Subject</th>
                                    <th colspan="2">Class 11 (Part I)</th>
                                    <th colspan="4">Class 12 (Part II)</th>
                                </tr>
                                <tr>
                                    <!-- Class 11 -->
                                    <th style="width: 15%">Theory</th>
                                    <th style="width: 10%">Absent?</th>
                                    
                                    <!-- Class 12 -->
                                    <th style="width: 15%">Theory</th>
                                    <th style="width: 10%">Absent?</th>
                                    <th style="width: 15%">Practical</th>
                                    <th style="width: 10%">Absent?</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subjects as $subject)
                                    @php
                                        $res11 = isset($results['11'][$subject->id]) ? $results['11'][$subject->id]->first() : null;
                                        $res12 = isset($results['12'][$subject->id]) ? $results['12'][$subject->id]->first() : null;
                                        
                                        $val11 = $res11 ? $res11->obtained_theory : '';
                                        $val12T = $res12 ? $res12->obtained_theory : '';
                                        $val12P = $res12 ? $res12->obtained_practical : '';
                                        
                                        $isAbsent11 = $res11 ? $res11->is_absent : false;
                                        $isAbsent12T = $res12 ? $res12->is_absent : false;
                                        $isAbsent12P = $res12 ? $res12->is_absent : false;
                                    @endphp
                                    <tr data-subject-name="{{ strtoupper($subject->name) }}" data-subject-id="{{ $subject->id }}">
                                        <td>
                                            <strong>{{ $subject->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $subject->type }}</small>
                                        </td>
                                        
                                        <!-- Class 11 Theory -->
                                        @php
                                            $disable11 = $subject->class_level == '12' || $subject->total_11 == 0;
                                        @endphp
                                        <td>
                                            @if(!$disable11)
                                                <input type="number" class="form-control mark-input validate-max" 
                                                    name="marks[{{ $subject->id }}][11][theory]" 
                                                    value="{{ $val11 }}" min="0" max="{{ $subject->total_11 }}"
                                                    id="m_11_t_{{ $subject->id }}"
                                                    placeholder="Max: {{ $subject->total_11 }}">
                                            @else
                                                <input type="text" class="form-control bg-light" disabled value="-">
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(!$disable11)
                                                <div class="form-check d-inline-block">
                                                    <input class="form-check-input absent-check" type="checkbox" 
                                                        name="marks[{{ $subject->id }}][11][is_absent]" 
                                                        data-target="#m_11_t_{{ $subject->id }}"
                                                        value="1" {{ $isAbsent11 ? 'checked' : '' }}>
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Class 12 Theory -->
                                        @php
                                            $disable12T = $subject->class_level == '11' || $subject->theory_12 == 0;
                                        @endphp
                                        <td>
                                            @if(!$disable12T)
                                                <input type="number" class="form-control mark-input validate-max" 
                                                    name="marks[{{ $subject->id }}][12][theory]" 
                                                    value="{{ $val12T }}" min="0" max="{{ $subject->theory_12 }}"
                                                    id="m_12_t_{{ $subject->id }}"
                                                    placeholder="Max: {{ $subject->theory_12 }}">
                                            @else
                                                <input type="text" class="form-control bg-light" disabled value="-">
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(!$disable12T)
                                                <div class="form-check d-inline-block">
                                                    <input class="form-check-input absent-check" type="checkbox" 
                                                        name="marks[{{ $subject->id }}][12][theory_absent]" 
                                                        data-target="#m_12_t_{{ $subject->id }}"
                                                        value="1" {{ $isAbsent12T ? 'checked' : '' }}>
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Class 12 Practical -->
                                        @php
                                            $disable12P = $subject->class_level == '11' || $subject->practical_12 == 0;
                                        @endphp
                                        <td>
                                            @if(!$disable12P)
                                                <input type="number" class="form-control mark-input validate-max" 
                                                    name="marks[{{ $subject->id }}][12][practical]" 
                                                    value="{{ $val12P }}" min="0" max="{{ $subject->practical_12 }}"
                                                    id="m_12_p_{{ $subject->id }}"
                                                    placeholder="Max: {{ $subject->practical_12 }}">
                                            @else
                                                <input type="text" class="form-control bg-light" disabled value="-">
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(!$disable12P)
                                                <div class="form-check d-inline-block">
                                                    <input class="form-check-input absent-check" type="checkbox" 
                                                        name="marks[{{ $subject->id }}][12][practical_absent]" 
                                                        data-target="#m_12_p_{{ $subject->id }}"
                                                        value="1" {{ $isAbsent12P ? 'checked' : '' }}>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white text-end">
                    <button type="submit" id="save-final-results-btn" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Save Final Results
                    </button>
                </div>
            </div>
        </form>
    @endif
</div>

@section('scripts')
<script>
$(document).ready(function() {
    // Handle Absent Checkboxes
    $('.absent-check').change(function() {
        const targetId = $(this).data('target');
        const input = $(targetId);
        
        if ($(this).is(':checked')) {
            input.val('').prop('readonly', true).addClass('bg-light');
        } else {
            input.prop('readonly', false).removeClass('bg-light');
        }
    });

    // Trigger change for any pre-checked boxes
    $('.absent-check:checked').trigger('change');

    // Fetch from BISE
    $('#fetch-bise-btn').click(function() {
        const rollNo = $(this).data('roll');
        const btn = $(this);
        const originalHtml = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Fetching...');

        $.ajax({
            url: "{{ route('results.bise.fetch') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                roll_no: rollNo
            },
            success: function(response) {
                if (response.status === 'Absent') {
                    markAllAbsent();
                    alert('Student is marked as ABSENT on BISE website. All local subjects have been marked as Absent.');
                    $('#save-final-results-btn').focus();
                } else if (response.data && response.data.length > 0) {
                    processBiseData(response.data);
                    alert('Data fetched successfully! Marks have been populated in the table.');
                    $('#save-final-results-btn').focus();
                } else {
                    alert('No data found for this roll number.');
                }
            },
            error: function(xhr) {
                let msg = 'Failed to fetch data from BISE.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg += '\n' + xhr.responseJSON.error;
                } else {
                    msg += `\nStatus: ${xhr.status} (${xhr.statusText})`;
                }
                alert(msg);
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    function markAllAbsent() {
        $('.absent-check').prop('checked', true).trigger('change');
    }

    function processBiseData(boardData) {
        const aliasMap = {
            'ARB': 'ARABIC',
            'POB': 'BANKING',
            'BIO': 'BIOLOGY',
            'BM': 'BUSINESS MATHEMATICS',
            'BSTAT': 'BUSINESS STAT',
            'CHE': 'CHEMISTRY',
            'CIV': 'CIVICS',
            'CIVN': 'CIVICS FOR NON MUSLIMS',
            'CGEO': 'COMMERCIAL GEOGRAPHY',
            'CS': 'COMPUTER SCIENCE',
            'CST': 'COMPUTER STUDIES(COMMERCE)',
            'ECO': 'ECONOMICS',
            'EDU': 'EDUCATION',
            'ENG': 'ENGLISH',
            'ENGL': 'ENGLISH LITERATURE',
            'ETH': 'ETHICS FOR NON MUSLIMS',
            'FART': 'FINE ARTS',
            'GEO': 'GEOGRAPHY',
            'HPED': 'HEALTH AND PHYSICAL EDUCATION',
            'HISI': 'HISTORY',
            'HISIIIB': 'HISTORY',
            'HISH': 'HISTORY',
            'HISW': 'HISTORY',
            'HISP': 'HISTORY',
            'IST': 'ISLAMIC STUDIES',
            'ISL': 'ISLAMIC EDUCATION',
            'LSC': 'LIBRARY SCIENCE',
            'MATH': 'MATHEMATICS',
            'HECO': 'OUT LINE OF HOME ECONOMICS',
            'PS': 'PAKISTAN STUDIES',
            'PC': 'PAKISTANI CULTURE',
            'PER': 'PERSIAN LANGUAGE',
            'PHIL': 'PHILOSOPHY',
            'PHY': 'PHYSICS',
            'POA': 'PRINCIPLES OF ACCOUNTING',
            'POC': 'PRINCIPLES OF COMMERCE',
            'POE': 'PRINCIPLES OF ECONOMICS',
            'PSY': 'PSYCHOLOGY',
            'PUNJ': 'PUNJABI LANGUAGE',
            'RE': 'RELIGIOUS EDUCATION (COMPULSORY)',
            'SRK': 'SARAIKI',
            'SOC': 'SOCIOLOGY',
            'STAT': 'STATISTICS',
            'TRQ': 'TRANSLATION OF HOLY QURAN',
            'UR': 'URDU',
            // Legacy/Common variations
            'MAT': 'MATHEMATICS',
            'COMP': 'COMPUTER SCIENCE',
            'IS': 'ISLAMIC STUDIES',
            'ECON': 'ECONOMICS',
            'GEOG': 'GEOGRAPHY',
            'HPE': 'HEALTH AND PHYSICAL EDUCATION',
            'ARA': 'ARABIC',
            'PUN': 'PUNJABI LANGUAGE',
            'SAR': 'SARAIKI',
            'BAL': 'BALOCHI',
            'PAS': 'PASHTO',
            'SIN': 'SINDHI',
            'UE': 'URDU ELECTIVE'
        };

        $('tr[data-subject-name]').each(function() {
            const row = $(this);
            const localName = row.data('subject-name').toUpperCase().trim();
            const subId = row.data('subject-id');

            // Helper function to prepare board code
            const getCleanBoardCode = (rawCode) => {
                let bc = rawCode.toUpperCase().trim();
                return bc.replace(/^(.{2,})(I{1,3}|IV|V|VI|VII|VIII)[A-Z]?$/, '$1').trim();
            };

            // 1. Priority 1: Exact matches (mapped name or code)
            let match = boardData.find(b => {
                const bCode = getCleanBoardCode(b.board_subject);
                const mappedFullName = aliasMap[bCode] ? aliasMap[bCode].toUpperCase() : null;
                return (mappedFullName && mappedFullName === localName) || (bCode === localName);
            });

            // 2. Priority 2: Substring matches (fallback for long names)
            if (!match) {
                match = boardData.find(b => {
                    const bCode = getCleanBoardCode(b.board_subject);
                    const mappedFullName = aliasMap[bCode] ? aliasMap[bCode].toUpperCase() : null;
                    if (!mappedFullName) return false;

                    // Special Case: "EDUCATION" should not match "HEALTH AND PHYSICAL EDUCATION"
                    // unless they are the same subject. "EDUCATION" is too generic.
                    if (mappedFullName === 'EDUCATION' && localName !== 'EDUCATION') return false;

                    // Standard Substring Logic
                    if (mappedFullName.length > 4 && localName.includes(mappedFullName)) return true;
                    if (localName.length > 4 && mappedFullName.includes(localName)) return true;
                    
                    return false;
                });
            }

            if (match) {
                // Fill Part-I Theory
                if (match.p1_theory !== null) {
                    const input11 = $(`#m_11_t_${subId}`);
                    const check11 = $(`input[name="marks[${subId}][11][is_absent]"]`);
                    if (match.p1_theory === 'A') {
                        check11.prop('checked', true).trigger('change');
                    } else {
                        check11.prop('checked', false).trigger('change');
                        input11.val(match.p1_theory).trigger('input');
                    }
                }

                // Fill Part-II Theory
                if (match.p2_theory !== null) {
                    const input12t = $(`#m_12_t_${subId}`);
                    const check12t = $(`input[name="marks[${subId}][12][theory_absent]"]`);
                    if (match.p2_theory === 'A') {
                        check12t.prop('checked', true).trigger('change');
                    } else {
                        check12t.prop('checked', false).trigger('change');
                        input12t.val(match.p2_theory).trigger('input');
                    }
                }

                // Fill Practical
                if (match.practical !== null) {
                    const input12p = $(`#m_12_p_${subId}`);
                    const check12p = $(`input[name="marks[${subId}][12][practical_absent]"]`);
                    if (match.practical === 'A') {
                        check12p.prop('checked', true).trigger('change');
                    } else {
                        check12p.prop('checked', false).trigger('change');
                        input12p.val(match.practical).trigger('input');
                    }
                }
            }
        });
    }

    // Validate Max Marks
    $('.validate-max').on('input', function() {
        const max = parseInt($(this).attr('max'));
        const val = parseInt($(this).val());
        
        if (val < 0) {
            $(this).val(0);
        } else if (val > max) {
            alert(`Marks cannot be greater than total marks (${max})`);
            $(this).val(max);
        }
    });
});
</script>
@endsection
@endsection
