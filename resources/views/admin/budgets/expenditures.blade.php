@extends('layouts.app')

@section('title', 'Expenditures')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-file-invoice-dollar me-2"></i>{{ $budget->department_name }}</h1>
            <p class="page-subtitle">Detailed expenditure log for {{ $budget->fiscal_year }}.</p>
        </div>
        <div class="text-end">
            <div class="small text-muted">Remaining Balance</div>
            <h3 class="fw-bold text-{{ $budget->remaining_amount < 0 ? 'danger' : 'success' }}">
                PKR {{ number_format($budget->remaining_amount) }}
            </h3>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Description</th>
                                <th class="pe-4 text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenditures as $exp)
                            <tr>
                                <td class="ps-4">{{ date('M d, Y', strtotime($exp->date)) }}</td>
                                <td class="fw-bold">{{ $exp->description }}</td>
                                <td class="pe-4 text-end fw-bold">PKR {{ number_format($exp->amount) }}</td>
                            </tr>
                            @endforeach
                            @if($expenditures->isEmpty())
                                <tr>
                                    <td colspan="3" class="p-5 text-center text-muted">No expenditures recorded for this budget.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3">Log New Expenditure</div>
                <div class="card-body">
                    <form action="{{ route('budgets.store_expenditure', $budget->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small">Amount (PKR)</label>
                            <input type="number" name="amount" class="form-control" required step="0.01">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Date</label>
                            <input type="date" name="date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Description</label>
                            <textarea name="description" class="form-control" rows="3" required placeholder="Reason for expenditure..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Record Expenditure</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
