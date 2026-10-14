@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2>Invigilator Duty Rules</h2>
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

    <div class="card">
        <div class="card-header">
            <h5>Set Maximum Duty Days per Designation</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('invigilators.store_duty_rule') }}" method="POST">
                @csrf
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Designation</th>
                            <th>Max Duty Days</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($designations as $designation)
                            @php
                                $rule = $dutyRules->firstWhere('designation_id', $designation->id);
                            @endphp
                            <tr>
                                <td>{{ $designation->name }}</td>
                                <td>
                                    <form action="{{ route('invigilators.store_duty_rule') }}" method="POST" class="form-inline">
                                        @csrf
                                        <input type="hidden" name="designation_id" value="{{ $designation->id }}">
                                        <input type="number" name="max_duty_days" class="form-control mr-2" min="1" value="{{ $rule->max_duty_days ?? '' }}" placeholder="e.g., 6" required>
                                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                    </form>
                                </td>
                                <td>
                                    @if($rule)
                                        <span class="badge badge-success">Set</span>
                                    @else
                                        <span class="badge badge-warning">Not Set</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </form>

            <div class="alert alert-info mt-3">
                <strong>Examples:</strong>
                <ul class="mb-0">
                    <li>Associate Professor: 6 days</li>
                    <li>Assistant Professor: 8 days</li>
                    <li>Lecturer: 10 days</li>
                    <li>College Teaching Intern: 12 days</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
