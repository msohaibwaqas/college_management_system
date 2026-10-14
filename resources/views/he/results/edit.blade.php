@extends('layouts.app')
@section('title', 'Edit BS/ADP Result')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-edit me-2"></i>Edit Student Result</h2>
            <p class="text-muted">Modify marks for <strong>{{ $result->student_name }}</strong> in <strong>{{ $result->subject_name }}</strong>.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Result Details</h5>
                    <span class="badge bg-light text-primary">{{ $result->exam_type }} - {{ $result->academic_year }}</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('he.results.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{ $result->id }}">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Program</label>
                                <p class="fw-bold mb-0">{{ $result->heAdmission->program->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Semester</label>
                                <p class="fw-bold mb-0">Semester {{ $result->semester_no }}</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="text-muted small">Subject</label>
                            <p class="fw-bold mb-0 text-primary">{{ $result->subject_name }} ({{ $result->bsSubject->code ?? '' }})</p>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_absent" name="is_absent" value="1" {{ $result->is_absent ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="is_absent">Mark as Absent</label>
                            </div>
                        </div>

                        <div class="row marks-input-group">
                            <div class="col-md-4 mb-3">
                                <label for="theory_marks" class="form-label">Theory Marks</label>
                                <input type="number" name="theory_marks" id="theory_marks" class="form-control" 
                                       value="{{ $result->theory_marks }}" {{ $result->is_absent ? 'disabled' : '' }} min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="practical_marks" class="form-label">Practical Marks</label>
                                <input type="number" name="practical_marks" id="practical_marks" class="form-control" 
                                       value="{{ $result->practical_marks }}" {{ $result->is_absent ? 'disabled' : '' }} min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="total_marks" class="form-label">Total Obtained</label>
                                <input type="number" name="total_marks" id="total_marks" class="form-control bg-light fw-bold" 
                                       value="{{ $result->total_marks }}" readonly>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <a href="{{ route('he.results.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-1"></i>Update Result
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-header bg-info text-dark">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Editing Policy</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted small mb-0 ps-3">
                        <li class="mb-2"><strong>Teachers</strong> can only edit marks for subjects assigned to them.</li>
                        <li class="mb-2"><strong>Clerks</strong> can edit any result until it is <strong>Locked & Submitted</strong>.</li>
                        <li><strong>Admins</strong> and <strong>Principals</strong> can unlock and edit any result at any time.</li>
                    </ul>
                </div>
            </div>
            
            @if($result->is_locked)
                <div class="alert alert-warning mt-3 border-start border-4">
                    <i class="fas fa-lock me-2"></i> This result is <strong>Locked</strong>. You are seeing this form because you have administrative privileges.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    function calculateTotal() {
        if ($('#is_absent').is(':checked')) {
            $('#total_marks').val(0);
            return;
        }
        var theory = parseFloat($('#theory_marks').val()) || 0;
        var practical = parseFloat($('#practical_marks').val()) || 0;
        $('#total_marks').val(theory + practical);
    }

    $('#theory_marks, #practical_marks').on('input', calculateTotal);

    $('#is_absent').on('change', function() {
        var isAbsent = $(this).is(':checked');
        $('#theory_marks, #practical_marks').prop('disabled', isAbsent);
        if (isAbsent) {
            $('#theory_marks, #practical_marks').val('');
            $('#total_marks').val(0);
        } else {
            calculateTotal();
        }
    });
});
</script>
@endsection
