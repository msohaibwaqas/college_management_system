@extends('layouts.app')
@section('title','Students List')

@section('content')
@php
    $activeRole = session('active_role');
    if (!$activeRole && Auth::check()) {
        if (Auth::user()->is_super_admin) {
            $activeRole = 'admin';
        } elseif (Auth::user()->roles->isNotEmpty()) {
            if (Auth::user()->hasRole('admin')) $activeRole = 'admin';
            elseif (Auth::user()->hasRole('principal')) $activeRole = 'principal';
            elseif (Auth::user()->hasRole('clerk')) $activeRole = 'clerk';
            elseif (Auth::user()->hasRole('teacher')) $activeRole = 'teacher';
            else $activeRole = Auth::user()->roles->first()->name;
        }
    }
@endphp

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0"><i class="fas fa-users me-2"></i>Students List</h2>
        </div>
    </div>
    
    <div class="row mb-3">
        <div class="col-12">
            @if($activeRole != 'teacher')
            <a href="{{ route('students.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add Student
            </a>
            <a href="{{ route('students.import') }}" class="btn btn-outline-primary ms-2">
                <i class="fas fa-file-import me-1"></i>Import Students
            </a>
            @endif
            <button id="exportBtn" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-file-export me-1"></i>Export to CSV
            </button>
            @if($activeRole != 'teacher')
            <button id="bulkDeleteBtn" class="btn btn-outline-danger ms-2" style="display: none;">
                <i class="fas fa-trash me-1"></i>Delete Selected (<span id="selectedCount">0</span>)
            </button>
            <button id="generateIdCardsBtn" class="btn btn-outline-primary ms-2" style="display: none;">
                <i class="fas fa-id-card me-1"></i>Generate ID Cards
            </button>
            <button id="bulkStatusBtn" class="btn btn-outline-secondary ms-2" style="display: none;" data-bs-toggle="modal" data-bs-target="#statusUpdateModal">
                <i class="fas fa-user-edit me-1"></i>Update Status
            </button>
            <button id="bulkPromoteBtn" class="btn btn-outline-primary ms-2" style="display: none;" data-bs-toggle="modal" data-bs-target="#promoteModal">
                <i class="fas fa-level-up-alt me-1"></i>Promote to Class 12
            </button>
            <button id="bulkCreateAccountsBtn" class="btn btn-outline-primary ms-2" style="display: none;">
                <i class="fas fa-user-plus me-1"></i>Generate Accounts
            </button>
            @endif
            <a href="{{ route('students.trash') }}" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-trash-alt me-1"></i>Recycle Bin
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label for="admission_year" class="form-label fw-semibold">Admission Year</label>
                    <select name="admission_year" id="admission_year" class="form-select">
                        <option value="">All Years</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}" {{ request('admission_year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="search" class="form-label fw-semibold">Search</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Name or Roll No" value="{{ request('search') }}">
                </div>

                <div class="col-md-2">
                    <label for="per_page" class="form-label fw-semibold">Per Page</label>
                    <select name="per_page" id="per_page" class="form-select">
                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        <option value="200" {{ request('per_page') == 200 ? 'selected' : '' }}>200</option>
                        <option value="500" {{ request('per_page') == 500 ? 'selected' : '' }}>500</option>
                        <option value="1000" {{ request('per_page') == 1000 ? 'selected' : '' }}>1000</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="sort_by" class="form-label fw-semibold">Sort By</label>
                    <select name="sort_by" id="sort_by" class="form-select">
                        <option value="college_roll_no" {{ request('sort_by', 'college_roll_no') == 'college_roll_no' ? 'selected' : '' }}>College Roll</option>
                        <option value="bise_roll_11" {{ request('sort_by') == 'bise_roll_11' ? 'selected' : '' }}>BISE Roll 11</option>
                        <option value="bise_roll_12" {{ request('sort_by') == 'bise_roll_12' ? 'selected' : '' }}>BISE Roll 12</option>
                        <option value="first_name" {{ request('sort_by') == 'first_name' ? 'selected' : '' }}>Name</option>
                        <option value="admission_year" {{ request('sort_by') == 'admission_year' ? 'selected' : '' }}>Admission Year</option>
                        <option value="status" {{ request('sort_by') == 'status' ? 'selected' : '' }}>Status</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold d-none d-md-block">&nbsp;</label>
                    <div class="form-check mt-md-2">
                        <input class="form-check-input" type="checkbox" name="show_graduated" id="show_graduated" value="1" {{ request('show_graduated') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="show_graduated">
                            Show Graduated
                        </label>
                    </div>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" id="resetBtn" class="btn btn-secondary w-100">
                        <i class="fas fa-redo me-1"></i>Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="text-center mb-3" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Results Info -->
    <div class="mb-3">
        <small class="text-muted" id="resultsInfo">
            Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} 
            of {{ $students->total() }} students
        </small>
    </div>

    <!-- Students Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" id="studentsTableContainer">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>College Roll No</th>
                            <th>Program</th>
                            <th>Shift</th>
                            <th>Sem/Class</th>
                            <th>Student Name</th>
                            <th>Father Name</th>
                            <th>Admission Year</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody">
                        @forelse($students as $student)
                            @php
                                $admission = $student->admissions->first();
                                $programInfo = $admission->program_name ?? 'Inter';
                                $shiftInfo = $admission->shift ?? 'Morning';
                                $semInfo = ($admission && $admission->program_type == 'Inter') ? ($admission->current_class ?? '-') : ('Sem ' . ($admission->semester ?? '-'));
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" class="student-checkbox form-check-input" 
                                           value="{{ $student->id }}" data-name="{{ $student->first_name }} {{ $student->last_name }}">
                                </td>
                                <td><strong>{{ $admission->college_roll_no ?? 'N/A' }}</strong></td>
                                <td><span class="badge bg-info text-dark">{{ $programInfo }}</span></td>
                                <td>{{ $shiftInfo }}</td>
                                <td>{{ $semInfo }}</td>
                                <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                                <td>{{ $student->father->name ?? 'N/A' }}</td>
                                <td><span class="badge bg-primary">{{ $admission->admission_year ?? 'N/A' }}</span></td>
                                <td>
                                    @php $status = $admission->status ?? 'N/A'; @endphp
                                    @if($status == 'Active' || $status == 'Admitted')
                                        <span class="badge bg-success">Active</span>
                                    @elseif($status == 'Struck Off' || $status == 'struck_off')
                                        <span class="badge bg-danger">Struck Off</span>
                                    @elseif($status == 'Graduated')
                                        <span class="badge bg-primary">Graduated</span>
                                    @elseif($status == 'Dropped')
                                        <span class="badge bg-secondary">Dropped</span>
                                    @elseif($status == 'Suspended')
                                        <span class="badge bg-warning text-dark">Suspended</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $status }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-info me-1 student-action-btn" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($activeRole != 'teacher')
                                    <a href="{{ route('students.edit', $student) }}" class="btn btn-sm btn-primary student-action-btn" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No students found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4" id="paginationContainer">
        {{ $students->links('pagination::bootstrap-5') }}
    </div>
</div>

<!-- Bulk Status Update Modal -->
<div class="modal fade" id="statusUpdateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Status for Selected Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>You are about to update the status for <span id="statusModalCount" class="fw-bold">0</span> student(s).</p>
                <div class="mb-3">
                    <label for="bulkStatusSelect" class="form-label">Select New Status</label>
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

<!-- Bulk Promote Modal -->
<div class="modal fade" id="promoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Promote Selected Students to Class 12</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-danger fw-bold">Warning: This action will:</p>
                <ul>
                    <li>Change current class to <strong>12</strong></li>
                    <li>Increment <strong>Academic Year</strong></li>
                    <li>Update subject levels to 12</li>
                </ul>
                <p>Only students currently in <strong>Class 11</strong> will be affected.</p>
                <p>Are you sure you want to promote <span id="promoteModalCount" class="fw-bold">0</span> student(s)?</p>
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

        // Redirect to generation route
        const url = '{{ route("students.id_cards.generate") }}?student_ids=' + selectedIds.join(',');
        window.open(url, '_blank');
    });

    // Bulk delete button click
    $('#bulkDeleteBtn').on('click', function() {
        const selectedIds = [];
        const selectedNames = [];
        
        $('.student-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
            selectedNames.push($(this).data('name'));
        });

        if (selectedIds.length === 0) {
            alert('Please select at least one student to delete.');
            return;
        }

        // Create confirmation message
        let confirmMessage = `Are you sure you want to delete ${selectedIds.length} student(s)?\n\n`;
        if (selectedIds.length <= 10) {
            confirmMessage += 'Students to be deleted:\n' + selectedNames.join('\n');
        } else {
            confirmMessage += 'First 10 students:\n' + selectedNames.slice(0, 10).join('\n') + '\n... and ' + (selectedIds.length - 10) + ' more';
        }

        if (confirm(confirmMessage)) {
            // Show loading indicator
            $('#loadingIndicator').show();
            $('#bulkDeleteBtn').prop('disabled', true);

            // Send AJAX request
            $.ajax({
                url: '{{ route("students.bulk-delete") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    student_ids: selectedIds
                },
                success: function(response) {
                    $('#loadingIndicator').hide();
                    $('#bulkDeleteBtn').prop('disabled', false);
                    
                    if (response.success) {
                        alert(response.message);
                        // Reload the page to show updated list
                        window.location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Failed to delete students'));
                    }
                },
                error: function(xhr) {
                    $('#loadingIndicator').hide();
                    $('#bulkDeleteBtn').prop('disabled', false);
                    
                    let errorMessage = 'An error occurred while deleting students.';
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
        const selectedIds = [];
        $('.student-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        const newStatus = $('#bulkStatusSelect').val();

        if (selectedIds.length === 0) {
            alert('No students selected.');
            return;
        }

        // Show loading state
        const originalBtnText = $(this).text();
        $(this).prop('disabled', true).text('Updating...');

        $.ajax({
            url: '{{ route("students.bulk-status-update") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                student_ids: selectedIds,
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
        const selectedIds = [];
        $('.student-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('No students selected.');
            return;
        }

        // Show loading state
        const originalBtnText = $(this).text();
        $(this).prop('disabled', true).text('Promoting...');

        $.ajax({
            url: '{{ route("students.bulk-promote") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                student_ids: selectedIds
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

    // Auto-submit on filter change
    $('#admission_year, #per_page, #sort_by, #show_graduated').on('change', function() {
        loadStudents();
    });

    // Search with debounce
    let searchTimeout;
    $('#search').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            loadStudents();
        }, 500);
    });

    // Reset button
    $('#resetBtn').on('click', function() {
        $('#filterForm')[0].reset();
        loadStudents();
    });

    // Export button
    $('#exportBtn').on('click', function() {
        const params = new URLSearchParams($('#filterForm').serialize());
        window.location.href = '{{ route("students.export") }}?' + params.toString();
    });

    // Pagination click handler
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        if (url) {
            const page = new URL(url).searchParams.get('page');
            loadStudents(page);
        }
    });

    function loadStudents(page = 1) {
        console.log('Loading students, page:', page);
        $('#loadingIndicator').show();
        
        const formData = $('#filterForm').serialize();
        const url = '{{ route("students.index") }}?page=' + page + '&' + formData;
        console.log('AJAX URL:', url);

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            timeout: 30000, // 30 second timeout
            success: function(response) {
                console.log('AJAX success, response length:', response.length);
                try {
                    // Parse the response to extract table and pagination
                    const $response = $(response);
                    const $newTableBody = $response.find('#studentsTableBody');
                    const $newPagination = $response.find('#paginationContainer');
                    const $newResultsInfo = $response.find('#resultsInfo');

                    console.log('Found elements:', {
                        tableBody: $newTableBody.length,
                        pagination: $newPagination.length,
                        resultsInfo: $newResultsInfo.length
                    });

                    // Update table body
                    if ($newTableBody.length > 0) {
                        $('#studentsTableBody').html($newTableBody.html());
                    } else {
                        console.warn('Table body not found in response');
                    }
                    
                    // Update pagination
                    if ($newPagination.length > 0) {
                        $('#paginationContainer').html($newPagination.html());
                    }
                    
                    // Update results info
                    if ($newResultsInfo.length > 0) {
                        $('#resultsInfo').html($newResultsInfo.html());
                    }

                    // Update URL without reload
                    window.history.pushState({}, '', url);
                    
                    // Reset checkboxes
                    $('#selectAll').prop('checked', false);
                    updateBulkActions();
                    
                    console.log('Update complete');
                } catch (e) {
                    console.error('Error processing response:', e);
                    alert('Error processing response: ' + e.message);
                } finally {
                    $('#loadingIndicator').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {xhr, status, error});
                console.error('Response text:', xhr.responseText);
                $('#loadingIndicator').hide();
            }
        });
    }

    // Auto-append redirect_to parameter to student action links
    $(document).on('click', '.student-action-btn, a[title="View Details"], a[title="Edit"]', function(e) {
        let href = $(this).attr('href');
        if (href && href.indexOf('redirect_to=') === -1) {
            let currentUrl = window.location.href;
            let separator = href.indexOf('?') !== -1 ? '&' : '?';
            $(this).attr('href', href + separator + 'redirect_to=' + encodeURIComponent(currentUrl));
        }
    });
});
</script>
@endsection
