@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2>Invigilator Assignments</h2>
            <a href="{{ route('invigilators.assign') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Assign by Date
            </a>
            <a href="{{ route('invigilators.duty_rules') }}" class="btn btn-secondary">
                <i class="fas fa-cog"></i> Duty Rules
            </a>
            <a href="{{ route('invigilators.exemptions') }}" class="btn btn-warning">
                <i class="fas fa-user-slash"></i> Exemptions
            </a>
            <button type="button" id="bulkDeleteBtn" class="btn btn-danger ms-2" style="display: none;" onclick="confirmBulkDelete()">
                <i class="fas fa-trash me-2"></i> Delete Selected
            </button>
        </div>
    </div>

    <form id="bulkDeleteForm" action="{{ route('invigilators.bulk_destroy') }}" method="POST" style="display: none;">
        @csrf
    </form>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('report'))
        @php $report = session('report'); @endphp
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5><i class="fas fa-chart-bar"></i> Assignment Report</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3>{{ $report['total_assignments'] }}</h3>
                                <p class="text-muted">Total Assignments</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3>{{ $report['mandatory_assignments'] }}</h3>
                                <p class="text-muted">Mandatory (Teacher's Subject)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3>{{ $report['optional_assignments'] }}</h3>
                                <p class="text-muted">Optional Assignments</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if(!empty($report['daily_stats']))
                    <div class="mt-3 mb-3">
                        <button class="btn btn-sm btn-outline-info" type="button" data-toggle="collapse" data-target="#dailyStatsCollapse">
                            <i class="fas fa-calendar-alt"></i> View Daily Resource Statistics
                        </button>
                        <div class="collapse mt-2" id="dailyStatsCollapse">
                            <div class="card card-body bg-light">
                                <h6>Daily Resource Usage</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Rooms</th>
                                                <th>Invigilators Needed</th>
                                                <th>Available Teachers</th>
                                                <th>Excluded (Max Limit)</th>
                                                <th>Assigned</th>
                                                <th>Shortage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($report['daily_stats'] as $stat)
                                                <tr class="{{ $stat['shortage'] > 0 ? 'table-danger' : '' }}">
                                                    <td>{{ $stat['date'] }}</td>
                                                    <td>{{ $stat['total_rooms'] }}</td>
                                                    <td>{{ $stat['needed_invigilators'] }}</td>
                                                    <td>
                                                        {{ $stat['available_teachers'] }}
                                                        @if($stat['available_teachers'] < $stat['needed_invigilators'])
                                                            <i class="fas fa-exclamation-circle text-danger" title="Not enough unique teachers available today"></i>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $stat['excluded_by_limit'] ?? 0 }}
                                                        @if(($stat['excluded_by_limit'] ?? 0) > 0 && $stat['shortage'] > 0)
                                                            <i class="fas fa-info-circle text-info" title="Increasing max duty limit could help fill {{ min($stat['excluded_by_limit'], $stat['shortage']) }} slots"></i>
                                                        @endif
                                                    </td>
                                                    <td>{{ $stat['assigned_invigilators'] }}</td>
                                                    <td>
                                                        @if($stat['shortage'] > 0)
                                                            <span class="text-danger font-weight-bold">{{ $stat['shortage'] }}</span>
                                                        @else
                                                            <span class="text-success">0</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted mt-2">
                                    * <strong>Available Teachers:</strong> Number of active teaching staff who are not exempted, have not reached their max duty limit, and are not already assigned to another room on this day.
                                </small>
                            </div>
                        </div>
                    </div>
                @endif

                @if(!empty($report['warnings']))
                    <div class="alert alert-warning mt-3">
                        <strong><i class="fas fa-exclamation-triangle"></i> Warnings:</strong>
                        <ul class="mb-0">
                            @foreach($report['warnings'] as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($report['rooms_understaffed']))
                    <div class="alert alert-danger mt-3">
                        <strong><i class="fas fa-exclamation-circle"></i> Under-Staffed Rooms:</strong>
                        <table class="table table-sm mt-2">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Room</th>
                                    <th>Needed</th>
                                    <th>Assigned</th>
                                    <th>Shortage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['rooms_understaffed'] as $room)
                                    <tr>
                                        <td>{{ $room['date'] }}</td>
                                        <td>{{ $room['room'] }}</td>
                                        <td>{{ $room['needed'] }}</td>
                                        <td>{{ $room['assigned'] }}</td>
                                        <td class="text-danger"><strong>-{{ $room['shortage'] }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if(!empty($report['teachers_over_limit']))
                    <div class="alert alert-warning mt-3">
                        <strong><i class="fas fa-user-clock"></i> Teachers Over Duty Limit:</strong>
                        <table class="table table-sm mt-2">
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>Assigned Days</th>
                                    <th>Limit</th>
                                    <th>Excess</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['teachers_over_limit'] as $teacher)
                                    <tr>
                                        <td>{{ $teacher['name'] }}</td>
                                        <td>{{ $teacher['assigned'] }}</td>
                                        <td>{{ $teacher['limit'] }}</td>
                                        <td class="text-danger"><strong>+{{ $teacher['excess'] }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if(!empty($report['duty_distribution']))
                    <div class="mt-3">
                        <h6><strong>Duty Distribution by Designation:</strong></h6>
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Designation</th>
                                    <th>Teachers</th>
                                    <th>Total Duties</th>
                                    <th>Avg Duties/Teacher</th>
                                    <th>Max Limit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['duty_distribution'] as $designation => $stats)
                                    <tr>
                                        <td>{{ $designation }}</td>
                                        <td>{{ $stats['total_teachers'] }}</td>
                                        <td>{{ $stats['total_duties'] }}</td>
                                        <td>{{ $stats['avg_duties'] }}</td>
                                        <td>{{ $stats['max_limit'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <!-- Manual Assignment Form -->
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5><i class="fas fa-user-plus"></i> Assign Invigilator Manually</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('invigilators.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <label>Exam Date <span class="text-danger">*</span></label>
                        <select name="exam_date" class="form-control" required>
                            <option value="">Select Date</option>
                            @foreach($examDates as $date)
                                <option value="{{ $date->date->format('Y-m-d') }}">
                                    {{ $date->date->format('d M Y (l)') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Room <span class="text-danger">*</span></label>
                        <select name="room_id" class="form-control" required>
                            <option value="">Select Room</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Staff Member <span class="text-danger">*</span></label>
                        <select name="staff_id" class="form-control" required id="staffSelect">
                            <option value="">Select Staff</option>
                            @foreach($staff as $member)
                                @php
                                    $dutyCount = $dutyCounts[$member->id] ?? 0;
                                    $maxLimit = ($member->designation_id && isset($dutyRules[$member->designation_id])) 
                                        ? $dutyRules[$member->designation_id]->max_duty_days 
                                        : 999;
                                    $isExempted = $exemptions->where('staff_id', $member->id)->isNotEmpty();
                                    $hasNoDesignationId = $member->designation_id === null;
                                    // Use designationModel name if available, otherwise fall back to designation string
                                    $designationName = $member->designationModel->name ?? $member->designation ?? 'NO DESIGNATION';
                                @endphp
                                <option value="{{ $member->id }}" 
                                    data-duty-count="{{ $dutyCount }}" 
                                    data-max-limit="{{ $maxLimit }}"
                                    {{ $isExempted ? 'disabled' : '' }}
                                    style="{{ $hasNoDesignationId ? 'background-color: #fff3cd;' : '' }}">
                                    {{ $member->first_name }} {{ $member->last_name }} 
                                    ({{ $designationName }}) 
                                    - Duties: {{ $dutyCount }}/{{ $maxLimit }}
                                    @if($isExempted) - EXEMPTED @endif
                                    @if($hasNoDesignationId) ⚠️ No duty rule @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-check"></i> Assign
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('invigilators.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                    </div>
                    <div class="col-md-3">
                        <label>Room</label>
                        <select name="room_id" class="form-control">
                            <option value="">All Rooms</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                    {{ $room->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Staff</label>
                        <select name="staff_id" class="form-control">
                            <option value="">All Staff</option>
                            @foreach($staff as $member)
                                <option value="{{ $member->id }}" {{ request('staff_id') == $member->id ? 'selected' : '' }}>
                                    {{ $member->first_name }} {{ $member->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary form-control">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Assignments Table -->
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th>Date</th>
                        <th>Room</th>
                        <th>Invigilator</th>
                        <th>Designation</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $assignment)
                        <tr>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $assignment->id }}" class="form-check-input select-item">
                            </td>
                            <td>{{ $assignment->exam_date->format('d M Y') }}</td>
                            <td>
                                {{ $assignment->room->name }}
                                @if($assignment->heSeatingPlan)
                                    <br><small class="text-muted">({{ $assignment->heSeatingPlan->datesheet->bsSubject->name ?? 'HE' }})</small>
                                @endif
                            </td>
                            <td>{{ $assignment->staff->first_name }} {{ $assignment->staff->last_name }}</td>
                            <td>{{ $assignment->staff->designationModel->name ?? 'N/A' }}</td>
                            <td>
                                @if($assignment->seating_plan_id)
                                    <span class="badge badge-info">HSSC</span>
                                @else
                                    <span class="badge badge-success">HE</span>
                                @endif
                                <span class="badge badge-{{ $assignment->is_auto_assigned ? 'primary' : 'warning' }}">
                                    {{ $assignment->is_auto_assigned ? 'Auto' : 'Manual' }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#editModal{{ $assignment->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('invigilators.destroy', $assignment) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal{{ $assignment->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('invigilators.update', $assignment) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Assignment</h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label>Invigilator</label>
                                                        <select name="staff_id" class="form-control" required>
                                                            @foreach($staff as $member)
                                                                <option value="{{ $member->id }}" {{ $assignment->staff_id == $member->id ? 'selected' : '' }}>
                                                                    {{ $member->first_name }} {{ $member->last_name }} ({{ $member->designationModel->name ?? 'N/A' }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No assignments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@section('scripts')
<script>
    document.getElementById('selectAll')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.select-item');
        checkboxes.forEach(cb => cb.checked = this.checked);
        toggleBulkDeleteBtn();
    });

    document.querySelectorAll('.select-item').forEach(cb => {
        cb.addEventListener('change', toggleBulkDeleteBtn);
    });

    function toggleBulkDeleteBtn() {
        const checkedCount = document.querySelectorAll('.select-item:checked').length;
        document.getElementById('bulkDeleteBtn').style.display = checkedCount > 0 ? 'inline-block' : 'none';
    }

    function confirmBulkDelete() {
        if (confirm('Are you sure you want to delete the selected assignments?')) {
            const form = document.getElementById('bulkDeleteForm');
            const checkedItems = document.querySelectorAll('.select-item:checked');
            
            // Clear existing inputs
            form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            
            checkedItems.forEach(item => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = item.value;
                form.appendChild(input);
            });
            
            form.submit();
        }
    }
</script>
@endsection
@endsection
