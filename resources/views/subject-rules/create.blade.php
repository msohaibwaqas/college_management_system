@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Create Subject Assignment Rule</h1>
        <a href="{{ route('subject-rules.index') }}" class="btn btn-sm btn-secondary">
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
            <form action="{{ route('subject-rules.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Class Level</label>
                        <select name="class_level" class="form-select">
                            <option value="11">Class 11</option>
                            <option value="12">Class 12</option>
                            <option value="any">Any Level</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Nationality</label>
                        <select name="nationality_type" class="form-select" required>
                            <option value="pakistani">Pakistani</option>
                            <option value="non_pakistani">Non-Pakistani</option>
                            <option value="any" selected>Any</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Religion</label>
                        <select name="religion_type" class="form-select" required>
                            <option value="muslim">Muslim</option>
                            <option value="non_muslim">Non-Muslim</option>
                            <option value="any" selected>Any</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Assignment Type</label>
                        <select name="assignment_type" class="form-select" required>
                            <option value="compulsory">Compulsory Subject List</option>
                            <option value="elective">Elective Specific Options</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Target Subjects to Assign</label>
                    <select name="subjects[]" class="form-select" multiple required style="height: 150px;">
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->code }})</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Hold CTRL (or CMD on Mac) to select multiple</small>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
