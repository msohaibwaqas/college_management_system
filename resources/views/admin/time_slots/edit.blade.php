@extends('layouts.app')

@section('title', 'Edit Time Slot')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Time Slot</h1>
        <a href="{{ route('time-slots.index') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Edit Slot: {{ $timeSlot->name }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('time-slots.update', $timeSlot->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-3">
                            <label>Slot Number</label>
                            <input type="number" name="slot_number" class="form-control" value="{{ $timeSlot->slot_number }}" required min="1">
                        </div>
                        <div class="form-group mb-3">
                            <label>Slot Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $timeSlot->name }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Start Time</label>
                            <input type="time" name="start_time" class="form-control" value="{{ \Carbon\Carbon::parse($timeSlot->start_time)->format('H:i') }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>End Time</label>
                            <input type="time" name="end_time" class="form-control" value="{{ \Carbon\Carbon::parse($timeSlot->end_time)->format('H:i') }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Time Slot</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
