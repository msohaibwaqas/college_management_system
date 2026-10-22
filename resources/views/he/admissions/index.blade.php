@extends('layouts.app')
@section('title', 'HE Admissions List')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0"><i class="fas fa-user-graduate me-2"></i>HE Admissions List</h2>
        </div>
    </div>

    {{-- Top Action Buttons (matching students page) --}}
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('admissions.create') }}" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>Add Admission
            </a>
            <a href="{{ route('students.import') }}" class="btn btn-info ms-2 text-white">
                <i class="fas fa-file-import me-1"></i>Import Students
            </a>
            <button id="exportBtn" class="btn btn-primary ms-2">
                <i class="fas fa-file-export me-1"></i>Export to CSV
            </button>
            <button id="bulkDeleteBtn" class="btn btn-danger ms-2" style="display: none;">
                <i class="fas fa-trash me-1"></i>Delete Selected (<span id="selectedCount">0</span>)
            </button>
            <button id="generateIdCardsBtn" class="btn btn-warning ms-2" style="display: none;">
                <i class="fas fa-id-card me-1"></i>Generate ID Cards
            </button>
            <button id="bulkStatusBtn" class="btn btn-info ms-2 text-white" style="display: none;" data-bs-toggle="modal" data-bs-target="#statusUpdateModal">
                <i class="fas fa-user-edit me-1"></i>Update Status
            </button>
            <button id="bulkPromoteBtn" class="btn btn-warning ms-2" style="display: none;" data-bs-toggle="modal" data-bs-target="#promoteModal">
                <i class="fas fa-level-up-alt me-1"></i>Promote Semester
            </button>
            <button id="bulkCreateAccountsBtn" class="btn btn-success ms-2" style="display: none;">
                <i class="fas fa-user-plus me-1"></i>Generate Accounts
            </button>
            <a href="{{ route('students.trash') }}" class="btn btn-secondary ms-2">
                <i class="fas fa-trash-alt me-1"></i>Recycle Bin
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Loading Indicator --}}
    <div id="loadingIndicator" class="text-center mb-3" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form id="filterForm" method="GET" action="{{ route('admissions.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label fw-semibold">Search</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Name or Roll No" value="{{ request('search') }}">
                </div>

                <div class="col-md-2">
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
                    <label for="semester" class="form-label fw-semibold">Semester</label>
                    <select name="semester" id="semester" class="form-select">
                        <option value="">All Semesters</option>
                        @for($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}" {{ request('semester') == $i ? 'selected' : '' }}>
                                Semester {{ $i }}
                            </option>
                        @endfor
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
                        <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Struck Off" {{ request('status') == 'Struck Off' ? 'selected' : '' }}>Struck Off</option>
                        <option value="Graduated" {{ request('status') == 'Graduated' ? 'selected' : '' }}>Graduated</option>
                        <option value="Dropped" {{ request('status') == 'Dropped' ? 'selected' : '' }}>Dropped</option>
                        <option value="Suspended" {{ request('status') == 'Suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>

                <div class="col-md-1">
                    <label for="per_page" class="form-label fw-semibold">Per Page</label>
                    <select name="per_page" id="per_page" class="form-select">
                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        <option value="200" {{ request('per_page') == 200 ? 'selected' : '' }}>200</option>
                        <option value="500" {{ request('per_page') == 500 ? 'selected' : '' }}>500</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <div class="form-check mt-md-4 pt-md-2">
                        <input class="form-check-input" type="checkbox" name="show_graduated" id="show_graduated" value="1" {{ request('show_graduated') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="show_graduated">
                            Show Graduated
                        </label>
                    </div>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 me-2">
                        <i class="fas fa-search me-1"></i> Apply
                    </button>
                    <a href="{{ route('admissions.index') }}" class="btn btn-secondary w-100" id="resetBtn">
                        <i class="fas fa-redo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Admissions Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light border-bottom">
                        <tr>
                            <th class="ps-3" style="width: 40px;">
                                <input type="checkbox" id="selectAll" class="form-check-input" title="Select All">
                            </th>
                            <th>
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
                                    Semester {!! request('sort_by') == 'current_semester' ? (request('sort_order') == 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '<i class="fas fa-sort text-muted"></i>' !!}
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
                    <tbody id="admissionsTableBody">
                        @forelse($heAdmissions as $admission)
                            <tr>
                                <td class="ps-3">
                                    <input type="checkbox" class="student-checkbox form-check-input" 
                                           value="{{ $admission->student_id }}" 
                                           data-admission-id="{{ $admission->id }}" 
                                           data-name="{{ $admission->student->full_name ?? 'Student' }}">
                                </td>
                                <td><strong>{{ $admission->roll_no ?? 'N/A' }}</strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            {{ strtoupper(substr($admission->student->first_name ?? '?', 0, 1)) }}
                                        </div>
                                        <span class="fw-semibold">{{ $admission->student->full_name ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td>{{ $admission->student->father->name ?? 'N/A' }}</td>
                                <td><span class="badge bg-primary text-white">{{ $admission->program->name ?? 'N/A' }}</span></td>
                                <td><span class="badge bg-secondary">Semester {{ $admission->current_semester }}</span></td>
                                <td>{{ $admission->student->contact->student_contact ?? 'N/A' }}</td>
                                <td>
                                    @php
                                        $statusClass = match($admission->status) {
                                            'Active', 'admitted', 'Admitted', 'active' => 'bg-success',
                                            'Struck Off', 'struck_off' => 'bg-danger',
                                            'Graduated', 'graduated' => 'bg-primary',
                                            'Dropped', 'dropped' => 'bg-secondary',
                                            'Suspended', 'suspended' => 'bg-warning text-dark',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ $admission->status }}</span>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admissions.show', $admission) }}" class="btn btn-outline-info" title="View Details">
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
                        @empty
                            @if($legacyAdmissions->isEmpty())
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                                    <p class="text-muted fs-5">No admissions found</p>
                                    <a href="{{ route('admissions.create') }}" class="btn btn-primary mt-2">
                                        <i class="fas fa-plus me-1"></i> Register New Student
                                    </a>
                                </td>
                            </tr>
                            @endif
                        @endforelse

                        {{-- Legacy Section --}}
                        @if($legacyAdmissions->isNotEmpty())
                            <tr class="table-secondary"><td colspan="9" class="fw-bold ps-4">Legacy Records (BS/ADP)</td></tr>
                            @foreach($legacyAdmissions as $admission)
                                <tr class="table-light">
                                    <td class="ps-3">
                                        <input type="checkbox" class="student-checkbox form-check-input" 
                                               value="{{ $admission->student_id }}" 
                                               data-admission-id="{{ $admission->id }}" 
                                               data-name="{{ $admission->student->full_name ?? 'Student' }}">
                                    </td>
                                    <td><strong>{{ $admission->college_roll_no }}</strong></td>
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
                                    <td>Class {{ $admission->current_class }}</td>
                                    <td>{{ $admission->student->contact->student_contact ?? 'N/A' }}</td>
                                    <td><span class="badge bg-secondary">{{ $admission->status }}</span></td>
                                    <td class="text-center pe-4">
                                        <a href="{{ route('students.show', $admission->student_id) }}" class="btn btn-sm btn-outline-secondary" title="View Student">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Pagination --}}
        <div class="card-footer bg-white py-3" id="paginationContainer">
            <div class="d-flex justify-content-center">
                {{ $heAdmissions->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<!-- Bulk Status Update Modal -->
<div class="modal fade" id="statusUpdateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Update Status for Selected Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>You are about to update the status for <span id="statusModalCount" class="fw-bold text-primary">0</span> student(s).</p>
                <div class="mb-3">
                    <label for="bulkStatusSelect" class="form-label fw-semibold">Select New Status</label>
                    <select class="form-select" id="bulkStatusSelect">
                        <option value="Active">Active</option>
                        <option value="Struck Off">Struck Off</option>
                        <option value="Graduated">Graduated</option>
                        <option value="Dropped">Dropped</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmStatusUpdateBtn">Update Status</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Promote Semester Modal -->
<div class="modal fade" id="promoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-level-up-alt me-2"></i>Promote Selected HE Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>You are about to promote <span id="promoteModalCount" class="fw-bold text-primary">0</span> student(s).</p>
                <div class="mb-3">
                    <label for="promoteActionSelect" class="form-label fw-semibold">Promotion Action</label>
                    <select class="form-select" id="promoteActionSelect">
                        <option value="next_semester">Advance to Next Semester (+1)</option>
                        <option value="specific_semester">Set to Specific Semester</option>
                        <option value="graduate">Mark as Graduated</option>
                    </select>
                </div>
                <div class="mb-3" id="specificSemesterDiv" style="display: none;">
                    <label for="targetSemesterSelect" class="form-label fw-semibold">Target Semester</label>
                    <select class="form-select" id="targetSemesterSelect">
                        @for($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}">Semester {{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="alert alert-info py-2 small mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Students currently in Semester 8 will automatically be marked as <strong>Graduated</strong> when advancing to next semester.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmPromoteBtn">Confirm Promotion</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Select All checkbox
    $('#selectAll').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.student-checkbox').prop('checked', isChecked);
        updateBulkActions();
    });

    // Individual checkbox change
    $(document).on('change', '.student-checkbox', function() {
        updateSelectAllCheckbox();
        updateBulkActions();
    });

    // Update Select All checkbox state
    function updateSelectAllCheckbox() {
        const totalCheckboxes = $('.student-checkbox').length;
        const checkedCheckboxes = $('.student-checkbox:checked').length;
        $('#selectAll').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
    }

    // Update bulk actions buttons visibility
    function updateBulkActions() {
        const checkedCount = $('.student-checkbox:checked').length;
        $('#selectedCount').text(checkedCount);
        
        if (checkedCount > 0) {
            $('#bulkDeleteBtn').show();
            $('#generateIdCardsBtn').show();
            $('#bulkStatusBtn').show();
            $('#bulkPromoteBtn').show();
            $('#bulkCreateAccountsBtn').show();
            $('#statusModalCount').text(checkedCount);
            $('#promoteModalCount').text(checkedCount);
        } else {
            $('#bulkDeleteBtn').hide();
            $('#generateIdCardsBtn').hide();
            $('#bulkStatusBtn').hide();
            $('#bulkPromoteBtn').hide();
            $('#bulkCreateAccountsBtn').hide();
        }
    }

    // Toggle specific semester dropdown in promote modal
    $('#promoteActionSelect').on('change', function() {
        if ($(this).val() === 'specific_semester') {
            $('#specificSemesterDiv').slideDown();
        } else {
            $('#specificSemesterDiv').slideUp();
        }
    });

    // Bulk Create Accounts button click
    $('#bulkCreateAccountsBtn').on('click', function() {
        const selectedIds = [];
        $('.student-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Please select at least one student.');
            return;
        }

        if (confirm(`Are you sure you want to generate user accounts for ${selectedIds.length} student(s)?`)) {
            $('#loadingIndicator').show();
            $('#bulkCreateAccountsBtn').prop('disabled', true).text('Generating...');

            $.ajax({
                url: '{{ route("users.students.bulk-create") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    student_ids: selectedIds
                },
                success: function(response) {
                    $('#loadingIndicator').hide();
                    $('#bulkCreateAccountsBtn').prop('disabled', false).text('Generate Accounts');
                    
                    if (response.success) {
                        alert(response.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Failed to create accounts'));
                    }
                },
                error: function(xhr) {
                    $('#loadingIndicator').hide();
                    $('#bulkCreateAccountsBtn').prop('disabled', false).text('Generate Accounts');
                    alert('An error occurred. Please try again.');
                }
            });
        }
    });

    // Generate ID Cards button click
    $('#generateIdCardsBtn').on('click', function() {
        const selectedIds = [];
        $('.student-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Please select at least one student.');
            return;
        }

        const url = '{{ route("students.id_cards.generate") }}?student_ids=' + selectedIds.join(',');
        window.open(url, '_blank');
    });

    // Bulk Delete button click
    $('#bulkDeleteBtn').on('click', function() {
        const selectedAdmissionIds = [];
        const selectedStudentIds = [];
        const selectedNames = [];
        
        $('.student-checkbox:checked').each(function() {
            selectedAdmissionIds.push($(this).data('admission-id'));
            selectedStudentIds.push($(this).val());
            selectedNames.push($(this).data('name'));
        });

        if (selectedAdmissionIds.length === 0) {
            alert('Please select at least one admission to delete.');
            return;
        }

        let confirmMessage = `Are you sure you want to delete ${selectedAdmissionIds.length} admission(s)?\n\n`;
        if (selectedAdmissionIds.length <= 10) {
            confirmMessage += 'Students to be deleted:\n' + selectedNames.join('\n');
        } else {
            confirmMessage += 'First 10 students:\n' + selectedNames.slice(0, 10).join('\n') + '\n... and ' + (selectedAdmissionIds.length - 10) + ' more';
        }

        if (confirm(confirmMessage)) {
            $('#loadingIndicator').show();
            $('#bulkDeleteBtn').prop('disabled', true);

            $.ajax({
                url: '{{ route("admissions.bulk-delete") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    admission_ids: selectedAdmissionIds,
                    student_ids: selectedStudentIds
                },
                success: function(response) {
                    $('#loadingIndicator').hide();
                    $('#bulkDeleteBtn').prop('disabled', false);
                    
                    if (response.success) {
                        alert(response.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Failed to delete admissions'));
                    }
                },
                error: function(xhr) {
                    $('#loadingIndicator').hide();
                    $('#bulkDeleteBtn').prop('disabled', false);
                    let errorMessage = 'An error occurred while deleting admissions.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    alert(errorMessage);
                }
            });
        }
    });

    // Bulk Status Update Confirm
    $('#confirmStatusUpdateBtn').on('click', function() {
        const selectedAdmissionIds = [];
        const selectedStudentIds = [];
        $('.student-checkbox:checked').each(function() {
            selectedAdmissionIds.push($(this).data('admission-id'));
            selectedStudentIds.push($(this).val());
        });
        
        const newStatus = $('#bulkStatusSelect').val();

        if (selectedAdmissionIds.length === 0) {
            alert('No students selected.');
            return;
        }

        const originalBtnText = $(this).text();
        $(this).prop('disabled', true).text('Updating...');

        $.ajax({
            url: '{{ route("admissions.bulk-status-update") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                admission_ids: selectedAdmissionIds,
                student_ids: selectedStudentIds,
                status: newStatus
            },
            success: function(response) {
                $('#statusUpdateModal').modal('hide');
                $('#confirmStatusUpdateBtn').prop('disabled', false).text(originalBtnText);
                
                if (response.success) {
                    alert(response.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Failed to update status'));
                }
            },
            error: function(xhr) {
                $('#confirmStatusUpdateBtn').prop('disabled', false).text(originalBtnText);
                let errorMessage = 'An error occurred.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert(errorMessage);
            }
        });
    });

    // Bulk Promote Confirm
    $('#confirmPromoteBtn').on('click', function() {
        const selectedAdmissionIds = [];
        const selectedStudentIds = [];
        $('.student-checkbox:checked').each(function() {
            selectedAdmissionIds.push($(this).data('admission-id'));
            selectedStudentIds.push($(this).val());
        });

        if (selectedAdmissionIds.length === 0) {
            alert('No students selected.');
            return;
        }

        const action = $('#promoteActionSelect').val();
        const targetSemester = $('#targetSemesterSelect').val();

        const originalBtnText = $(this).text();
        $(this).prop('disabled', true).text('Promoting...');

        $.ajax({
            url: '{{ route("admissions.bulk-promote") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                admission_ids: selectedAdmissionIds,
                student_ids: selectedStudentIds,
                action: action,
                target_semester: targetSemester
            },
            success: function(response) {
                $('#promoteModal').modal('hide');
                $('#confirmPromoteBtn').prop('disabled', false).text(originalBtnText);
                
                if (response.success) {
                    alert(response.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Failed to promote students'));
                }
            },
            error: function(xhr) {
                $('#confirmPromoteBtn').prop('disabled', false).text(originalBtnText);
                let errorMessage = 'An error occurred.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert(errorMessage);
            }
        });
    });

    // Export to CSV button
    $('#exportBtn').on('click', function() {
        const params = new URLSearchParams($('#filterForm').serialize());
        window.location.href = '{{ route("admissions.export") }}?' + params.toString();
    });
});
</script>
@endsection
