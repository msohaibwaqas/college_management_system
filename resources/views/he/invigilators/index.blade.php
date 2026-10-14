@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2>HE Invigilator Assignments</h2>
            <a href="{{ route('he.invigilators.assign') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Assign by Date
            </a>
            <a href="{{ route('he.invigilators.duty_roster') }}" class="btn btn-info">
                <i class="fas fa-file-alt"></i> Duty Roster
            </a>
        </div>
    </div>

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

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('he.invigilators.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label>Date</label>
                        <select name="date" class="form-control">
                            <option value="">All Dates</option>
                            @foreach($examDates as $date)
                                <option value="{{ $date->date->format('Y-m-d') }}" {{ request('date') == $date->date->format('Y-m-d') ? 'selected' : '' }}>
                                    {{ $date->date->format('d M Y') }}
                                </option>
                            @endforeach
                        </select>
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
                        <th>Date</th>
                        <th>Room / Subject</th>
                        <th>Invigilator</th>
                        <th>Designation</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $assignment)
                        <tr>
                            <td>{{ $assignment->exam_date->format('d M Y') }}</td>
                            <td>
                                {{ $assignment->room->name }}
                                <br><small class="text-muted">({{ $assignment->heSeatingPlan->datesheet->bsSubject->name ?? 'HE' }})</small>
                            </td>
                            <td>{{ $assignment->staff->first_name }} {{ $assignment->staff->last_name }}</td>
                            <td>{{ $assignment->staff->designationModel->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-success">HE</span>
                                <span class="badge badge-{{ $assignment->is_auto_assigned ? 'primary' : 'warning' }}">
                                    {{ $assignment->is_auto_assigned ? 'Auto' : 'Manual' }}
                                </span>
                            </td>
                            <td>
                                <form action="{{ route('invigilators.destroy', $assignment) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
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
@endsection
