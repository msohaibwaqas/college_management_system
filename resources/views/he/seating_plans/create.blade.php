@extends('layouts.app')
@section('title', 'Generate HE Seating Plan')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-magic me-2"></i>Generate BS/ADP Seating Plan</h2>
        <a href="{{ route('he.seating_plans.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <div class="card shadow" style="max-width: 700px; margin: 0 auto;">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Seating Plan Configuration</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('he.seating_plans.store') }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label class="form-label">Select Exam from Datesheet <span class="text-danger">*</span></label>
                    <select name="he_datesheet_id" id="he_datesheet_id" class="form-select select2 @error('he_datesheet_id') is-invalid @enderror" required>
                        <option value="">-- Select Exam --</option>
                        @foreach($availableDatesheets as $ds)
                            <option value="{{ $ds->id }}" 
                                data-title="{{ $ds->exam_type }} Term: {{ $ds->bsSubject->name ?? 'N/A' }} ({{ $ds->program->name ?? 'N/A' }} {{ $ds->semester_no }}{{ ordinal($ds->semester_no) }} Sem)"
                                {{ old('he_datesheet_id') == $ds->id ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($ds->exam_date)->format('d M Y') }} - 
                                {{ $ds->bsSubject->name ?? 'N/A' }} 
                                ({{ $ds->program->name ?? 'N/A' }}, Sem {{ $ds->semester_no }}, {{ $ds->exam_type }})
                            </option>
                        @endforeach
                    </select>
                    @error('he_datesheet_id') 
                        <div class="invalid-feedback">{{ $message }}</div> 
                    @enderror
                    <small class="text-muted">Only exams with scheduled datesheets and no existing seating plan are shown.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Plan Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="e.g. Mid Term Seating - CS 1st Sem" required>
                    @error('title') 
                        <div class="invalid-feedback">{{ $message }}</div> 
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Description (Optional)</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Any special instructions or notes...">{{ old('description') }}</textarea>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>How it works:</strong>
                    <ul class="mb-0 mt-2 small">
                        <li>System fetches all students enrolled in the selected subject and semester.</li>
                        <li>Students are sorted by their College Roll Number.</li>
                        <li>Distribution is done across all active rooms based on their seating capacity.</li>
                        <li>Each student is assigned a specific room and seat number.</li>
                    </ul>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-cogs me-2"></i>Generate Seating Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5'
    });

    $('#he_datesheet_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const titleData = selectedOption.data('title');
        if (titleData && !$('#title').val()) {
            $('#title').val(titleData);
        }
    });
});
</script>
@endsection
