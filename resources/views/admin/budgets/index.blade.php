@extends('layouts.app')

@section('title', 'Budget Management')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-wallet me-2"></i>Departmental Budgets</h1>
            <p class="page-subtitle">Allocation and expenditure tracking for the current fiscal year.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newBudgetModal">
            <i class="fas fa-plus me-1"></i> New Budget Allocation
        </button>
    </div>

    <div class="row mt-4">
        @foreach($budgets as $budget)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-1">{{ $budget->department_name }}</h5>
                        <div class="text-muted small mb-3">Fiscal Year: {{ $budget->fiscal_year }}</div>

                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Utilized</span>
                            <span class="fw-bold">{{ number_format(($budget->used_amount / $budget->allocated_amount) * 100, 1) }}%</span>
                        </div>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar {{ $budget->used_amount > $budget->allocated_amount ? 'bg-danger' : 'bg-primary' }}" 
                                 role="progressbar" 
                                 style="width: {{ min(($budget->used_amount / $budget->allocated_amount) * 100, 100) }}%"></div>
                        </div>

                        <div class="bg-light p-3 rounded mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="small text-muted">Budget</span>
                                <span class="fw-bold">PKR {{ number_format($budget->allocated_amount) }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="small text-muted">Remaining</span>
                                <span class="fw-bold text-{{ $budget->remaining_amount < 0 ? 'danger' : 'success' }}">
                                    PKR {{ number_format($budget->remaining_amount) }}
                                </span>
                            </div>
                        </div>

                        <a href="{{ route('budgets.expenditures', $budget->id) }}" class="btn btn-outline-primary w-100 btn-sm">
                            View Expenditures <i class="fas fa-receipt ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
        @if($budgets->isEmpty())
            <div class="col-12 text-center p-5 text-muted">
                <i class="fas fa-wallet fa-3x mb-3 opacity-25"></i>
                <p>No departmental budgets are currently allocated.</p>
            </div>
        @endif
    </div>
</div>

<!-- New Budget Modal -->
<div class="modal fade" id="newBudgetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('budgets.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">New Budget Allocation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Department / Head Name</label>
                        <input type="text" name="department_name" class="form-control" placeholder="e.g. Science Department, Events, Library" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Allocated Amount (PKR)</label>
                        <input type="number" name="allocated_amount" class="form-control" required step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fiscal Year</label>
                        <select name="fiscal_year" class="form-select" required>
                            @php $thisYear = date('Y'); @endphp
                            <option value="{{ $thisYear }}-{{ $thisYear+1 }}">{{ $thisYear }}-{{ $thisYear+1 }}</option>
                            <option value="{{ $thisYear-1 }}-{{ $thisYear }}">{{ $thisYear-1 }}-{{ $thisYear }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Allocate Budget</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

