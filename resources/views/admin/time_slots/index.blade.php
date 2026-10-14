@extends('layouts.app')

@section('title', 'Manage Time Slots')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Time Slots Management</h1>
    </div>

    <div class="row">
        <!-- Add Time Slot Form -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Add New Time Slot</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('time-slots.store') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label>Slot Number (e.g. 1 for 1st Period)</label>
                            <input type="number" name="slot_number" class="form-control" required min="1">
                        </div>
                        <div class="form-group mb-3">
                            <label>Slot Name (e.g. 1st Period)</label>
                            <input type="text" name="name" class="form-control" required placeholder="1st Period">
                        </div>
                        <div class="form-group mb-3">
                            <label>Start Time</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>End Time</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add Time Slot</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Time Slots Table -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Available Time Slots</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Timing</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($timeSlots as $slot)
                                    <tr>
                                        <td>{{ $slot->slot_number }}</td>
                                        <td>{{ $slot->name }}</td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }} - 
                                            {{ \Carbon\Carbon::parse($slot->end_time)->format('g:i A') }}
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="{{ route('time-slots.edit', $slot->id) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('time-slots.destroy', $slot->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this slot?');" class="ms-1">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No time slots found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
