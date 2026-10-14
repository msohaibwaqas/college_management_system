@extends('layouts.app')
@section('title', 'Automated Datesheet Generation')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-magic me-2"></i>Automated Datesheet Generation</h2>
        <a href="{{ route('datesheets.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Generation Parameters</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('datesheets.store_generated') }}" method="POST" id="generateForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date') }}" required min="{{ date('Y-m-d') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date') }}" required min="{{ date('Y-m-d') }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Admission Years (Classes)</label>
                        <select name="admission_years[]" class="form-select" multiple required size="5">
                            @foreach($admissionYears as $year)
                                <option value="{{ $year }}" {{ (collect(old('admission_years'))->contains($year)) ? 'selected' : '' }}>
                                    Batch {{ $year }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Hold Ctrl (Windows) or Cmd (Mac) to select multiple batches.</div>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            The system will automatically schedule tests for all subjects taken by students in the selected batches.
                            <ul class="mb-0 mt-2">
                                <li>No student will have two tests on the same day.</li>
                                <li>Tests will not be scheduled on Sundays.</li>
                                <li>The system will attempt to leave a gap day between tests for students where possible.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-cogs me-2"></i>Generate Datesheet
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
$(document).ready(function() {
    $('#start_date, #end_date').on('change', function() {
        const startVal = $('#start_date').val();
        const endVal = $('#end_date').val();
        
        if (startVal && endVal) {
            if (new Date(startVal) > new Date(endVal)) {
                alert('End Date must be after Start Date');
                $('#end_date').val('');
            }
        }
    });
});
</script>
@endsection
@endsection
