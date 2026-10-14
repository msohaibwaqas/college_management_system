@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2>Exam Conductor Exemptions</h2>
            <a href="{{ route('invigilators.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Assignments
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header">
            <h5>Add New Exemption</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('invigilators.store_exemption') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Staff Member <span class="text-danger">*</span></label>
                            <select name="staff_id" class="form-control" required>
                                <option value="">Select Staff</option>
                                @foreach($staff as $member)
                                    <option value="{{ $member->id }}">
                                        {{ $member->first_name }} {{ $member->last_name }} ({{ $member->designationModel->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="exam_start_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>End Date <span class="text-danger">*</span></label>
                            <input type="date" name="exam_end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Reason</label>
                            <input type="text" name="reason" class="form-control" placeholder="e.g., Conducting exam">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Exemption
                </button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Current Exemptions</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th>Designation</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Reason</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exemptions as $exemption)
                        <tr>
                            <td>{{ $exemption->staff->first_name }} {{ $exemption->staff->last_name }}</td>
                            <td>{{ $exemption->staff->designationModel->name ?? 'N/A' }}</td>
                            <td>{{ $exemption->exam_start_date->format('d M Y') }}</td>
                            <td>{{ $exemption->exam_end_date->format('d M Y') }}</td>
                            <td>{{ $exemption->reason ?? '-' }}</td>
                            <td>
                                <form action="{{ route('invigilators.destroy_exemption', $exemption) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No exemptions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
