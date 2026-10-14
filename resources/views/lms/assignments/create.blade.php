@extends('layouts.app')

@section('title', 'Create Assignment')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">Create New Assignment</h6>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger shadow-sm border-left-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('lms.assignments.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label font-weight-bold d-block">Program Type</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="subject_type" id="type_inter" value="inter" checked autocomplete="off">
                                    <label class="btn btn-outline-primary" for="type_inter">Intermediate (HSSC)</label>
                                    
                                    <input type="radio" class="btn-check" name="subject_type" id="type_he" value="he" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="type_he">Higher Education (BS/ADP)</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3" id="inter_subject_wrapper">
                                <label class="form-label font-weight-bold">Select Inter Subject</label>
                                <select name="subject_id" class="form-select" id="inter_subject_select">
                                    <option value="">Choose Subject...</option>
                                    @foreach($subjects->where('is_he', false) as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 d-none" id="he_subject_wrapper">
                                <label class="form-label font-weight-bold">Select HE Subject</label>
                                <select name="bs_subject_id" class="form-select" id="he_subject_select">
                                    <option value="">Choose Subject...</option>
                                    @foreach($subjects->where('is_he', true) as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Target Class / Semester</label>
                                <select name="target_group" class="form-select" required>
                                    <option value="">Select Target...</option>
                                    @foreach($targetGroups as $group)
                                        <option value="{{ $group }}">{{ $group }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g., Assignment #1 - Introduction" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Description / Instructions</label>
                            <textarea name="description" class="form-control" rows="5" placeholder="Define the assignment details and requirements..." required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-weight-bold">Due Date</label>
                                <input type="date" name="due_date" class="form-control" required min="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-weight-bold">Total Marks</label>
                                <input type="number" name="total_marks" class="form-control" value="10" min="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-weight-bold">Attachment (Optional)</label>
                                <input type="file" name="attachment" class="form-control">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('lms.assignments.index') }}" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Assignment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const interRadio = document.getElementById('type_inter');
        const heRadio = document.getElementById('type_he');
        const interWrapper = document.getElementById('inter_subject_wrapper');
        const heWrapper = document.getElementById('he_subject_wrapper');
        const interSelect = document.getElementById('inter_subject_select');
        const heSelect = document.getElementById('he_subject_select');

        function toggleType() {
            if (interRadio.checked) {
                interWrapper.classList.remove('d-none');
                heWrapper.classList.add('d-none');
                interSelect.setAttribute('required', 'required');
                heSelect.removeAttribute('required');
            } else {
                interWrapper.classList.add('d-none');
                heWrapper.classList.remove('d-none');
                interSelect.removeAttribute('required');
                heSelect.setAttribute('required', 'required');
            }
        }

        interRadio.addEventListener('change', toggleType);
        heRadio.addEventListener('change', toggleType);
        
        toggleType(); // Initial state
    });
</script>
@endpush
@endsection
