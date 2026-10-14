@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Fee Heads</h1>
        <a href="{{ route('fee-heads.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Create New Fee Head
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Fee Heads</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Amount</th>
                            <th>Program</th>
                            <th>Gender</th>
                            <th>Frequency</th>
                            <th>Refundable?</th>
                            <th>Rules</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($feeHeads as $head)
                            <tr>
                                <td>{{ $head->name }}</td>
                                <td>{{ number_format($head->amount, 2) }}</td>
                                <td>{{ $head->program_type }}</td>
                                <td>{{ $head->applicable_gender }}</td>
                                <td>
                                    <span class="badge bg-info text-white">{{ ucfirst(str_replace('_', ' ', $head->payment_frequency)) }}</span>
                                </td>
                                <td>
                                    @if($head->is_refundable)
                                        <span class="badge bg-success text-white">Yes ({{ $head->refund_percentage }}%)</span>
                                    @else
                                        <span class="badge bg-secondary text-white">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if($head->belongs_to_practical)
                                        <span class="badge bg-warning text-dark"><i class="fas fa-flask"></i> Practical</span>
                                    @endif
                                    @if($head->is_computer_fund)
                                        <span class="badge bg-primary text-white"><i class="fas fa-desktop"></i> Computer</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('fee-heads.edit', $head) }}" class="btn btn-sm btn-info text-white"><i class="fas fa-edit"></i> Edit</a>
                                    <form action="{{ route('fee-heads.destroy', $head) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this fee head?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
