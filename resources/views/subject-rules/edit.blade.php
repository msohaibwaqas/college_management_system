@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Subject Assignment Rule</h1>
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
            <form action="{{ route('subject-rules.update', $subjectRule) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Class Level</label>
                        <select name="class_level" class="form-select">
                            <option value="11" {{ $subjectRule->class_level == '11' ? 'selected' : '' }}>Class 11</option>
                            <option value="12" {{ $subjectRule->class_level == '12' ? 'selected' : '' }}>Class 12</option>
                            <option value="any" {{ in_array($subjectRule->class_level, ['any', null]) ? 'selected' : '' }}>Any Level</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Nationality</label>
                        <select name="nationality_type" class="form-select" required>
                            <option value="pakistani" {{ $subjectRule->nationality_type == 'pakistani' ? 'selected' : '' }}>Pakistani</option>
                            <option value="non_pakistani" {{ $subjectRule->nationality_type == 'non_pakistani' ? 'selected' : '' }}>Non-Pakistani</option>
                            <option value="any" {{ $subjectRule->nationality_type == 'any' ? 'selected' : '' }}>Any</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Religion</label>
                        <select name="religion_type" class="form-select" required>
                            <option value="muslim" {{ $subjectRule->religion_type == 'muslim' ? 'selected' : '' }}>Muslim</option>
                            <option value="non_muslim" {{ $subjectRule->religion_type == 'non_muslim' ? 'selected' : '' }}>Non-Muslim</option>
                            <option value="any" {{ $subjectRule->religion_type == 'any' ? 'selected' : '' }}>Any</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Assignment Type</label>
                        <select name="assignment_type" class="form-select" required>
                            <option value="compulsory" {{ $subjectRule->assignment_type == 'compulsory' ? 'selected' : '' }}>Compulsory Subject List</option>
                            <option value="elective" {{ $subjectRule->assignment_type == 'elective' ? 'selected' : '' }}>Elective Specific Options</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Target Subjects to Assign</label>
                    @php $assignedIds = $subjectRule->subjects->pluck('id')->toArray(); @endphp
                    <select name="subjects[]" class="form-select" multiple required style="height: 150px;">
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ in_array($subject->id, $assignedIds) ? 'selected' : '' }}>
                                {{ $subject->name }} ({{ $subject->code }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Hold CTRL (or CMD on Mac) to select multiple</small>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Update Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
