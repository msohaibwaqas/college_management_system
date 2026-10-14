@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Fee Head</h1>
        <a href="{{ route('fee-heads.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('fee-heads.update', $feeHead) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fee Head Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $feeHead->name) }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $feeHead->amount) }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Payment Frequency</label>
                        <select name="payment_frequency" class="form-select" required>
                            <option value="yearly" {{ $feeHead->payment_frequency == 'yearly' ? 'selected' : '' }}>Yearly</option>
                            <option value="one_time" {{ $feeHead->payment_frequency == 'one_time' ? 'selected' : '' }}>One Time</option>
                            <option value="semester" {{ $feeHead->payment_frequency == 'semester' ? 'selected' : '' }}>Semester</option>
                            <option value="monthly" {{ $feeHead->payment_frequency == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Program Type</label>
                        <select name="program_type" class="form-select" required>
                            <option value="All" {{ $feeHead->program_type == 'All' ? 'selected' : '' }}>All Programs</option>
                            <option value="Inter" {{ $feeHead->program_type == 'Inter' ? 'selected' : '' }}>Intermediate (HSSC)</option>
                            <option value="ADP" {{ $feeHead->program_type == 'ADP' ? 'selected' : '' }}>Associate Degree (ADP)</option>
                            <option value="BS" {{ $feeHead->program_type == 'BS' ? 'selected' : '' }}>BS Program</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Gender Restriction</label>
                        <select name="applicable_gender" class="form-select" required>
                            <option value="All" {{ $feeHead->applicable_gender == 'All' ? 'selected' : '' }}>Both / All</option>
                            <option value="Male" {{ $feeHead->applicable_gender == 'Male' ? 'selected' : '' }}>Male Only</option>
                            <option value="Female" {{ $feeHead->applicable_gender == 'Female' ? 'selected' : '' }}>Female Only</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Subject / Background Group</label>
                        <select name="applicable_group" class="form-select">
                            <option value="All">All Groups</option>
                            @foreach($subjectGroups as $group)
                                <option value="{{ $group->name }}" {{ (old('applicable_group', $feeHead->applicable_group) == $group->name) ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">For Inter group or Degree background</small>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" name="is_refundable" id="isRefundable" value="1" {{ $feeHead->is_refundable ? 'checked' : '' }}>
                            <label class="form-check-label" for="isRefundable">Is Refundable?</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Refund Percentage</label>
                        <div class="input-group">
                            <input type="number" step="0.01" max="100" min="0" name="refund_percentage" class="form-control" value="{{ old('refund_percentage', $feeHead->refund_percentage) }}">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
                
                <hr>
                <h6 class="font-weight-bold">Advanced Rules (Conditional Fee Triggers)</h6>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="belongs_to_practical" id="belongsPractical" value="1" {{ $feeHead->belongs_to_practical ? 'checked' : '' }}>
                            <label class="form-check-label" for="belongsPractical">
                                Multiply by Practical Subjects Count (Science Breakage)
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_computer_fund" id="isComputer" value="1" {{ $feeHead->is_computer_fund ? 'checked' : '' }}>
                            <label class="form-check-label" for="isComputer">
                                Trigger if student selects Computer Science (Computer Fund)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Update Fee Head</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
