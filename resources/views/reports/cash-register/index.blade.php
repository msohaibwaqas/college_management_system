@extends('layouts.app')

@section('title', 'Cash Register Report')

@section('content')
<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-cash-register me-2"></i>Cash Register Report</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Select an admission year to view the voucher collection details.</p>
                    
                    <form action="{{ route('reports.cash-register.show') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="admission_year" class="form-label">Admission Year</label>
                            <select name="admission_year" id="admission_year" class="form-select @error('admission_year') is-invalid @enderror" required>
                                <option value="">Select Admission Year</option>
                                @foreach($admissionYears as $year)
                                    <option value="{{ $year }}" {{ old('admission_year') == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                            @error('admission_year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> Generate Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
