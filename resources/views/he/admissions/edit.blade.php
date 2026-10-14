@extends('layouts.app')
@section('title', 'Edit HE Admission')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit HE Enrollment</h2>
            <a href="{{ route('admissions.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to List
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-info text-dark py-3">
                    <h5 class="mb-0">Enrollment Details: {{ $admission->student->full_name }}</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admissions.update', $admission) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="student_id" class="form-label fw-bold">Student</label>
                                <select name="student_id" id="student_id" class="form-select select2 @error('student_id') is-invalid @enderror" required>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" {{ old('student_id', $admission->student_id) == $student->id ? 'selected' : '' }}>
                                            {{ $student->full_name }} ({{ $student->id }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('student_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="program_id" class="form-label fw-bold">Program</label>
                                <select name="program_id" id="program_id" class="form-select @error('program_id') is-invalid @enderror" required>
                                    @foreach($programs as $program)
                                        <option value="{{ $program->id }}" {{ old('program_id', $admission->program_id) == $program->id ? 'selected' : '' }}>
                                            {{ $program->name }} ({{ $program->type }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('program_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="roll_no" class="form-label fw-bold">Roll Number</label>
                                <input type="text" name="roll_no" id="roll_no" class="form-control @error('roll_no') is-invalid @enderror" 
                                       value="{{ old('roll_no', $admission->roll_no) }}" required>
                                @error('roll_no')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="admission_year" class="form-label fw-bold">Admission Year</label>
                                <input type="number" name="admission_year" id="admission_year" class="form-control @error('admission_year') is-invalid @enderror" 
                                       value="{{ old('admission_year', $admission->admission_year) }}" required>
                                @error('admission_year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="current_semester" class="form-label fw-bold">Current Semester</label>
                                <select name="current_semester" id="current_semester" class="form-select @error('current_semester') is-invalid @enderror" required>
                                    @for($i=1; $i<=10; $i++)
                                        <option value="{{ $i }}" {{ old('current_semester', $admission->current_semester) == $i ? 'selected' : '' }}>Semester {{ $i }}</option>
                                    @endfor
                                </select>
                                @error('current_semester')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="status" class="form-label fw-bold">Admission Status</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="Applied" {{ old('status', $admission->status) == 'Applied' ? 'selected' : '' }}>Applied</option>
                                <option value="Admitted" {{ old('status', $admission->status) == 'Admitted' ? 'selected' : '' }}>Admitted</option>
                                <option value="Active" {{ old('status', $admission->status) == 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Graduated" {{ old('status', $admission->status) == 'Graduated' ? 'selected' : '' }}>Graduated</option>
                                <option value="Dropped" {{ old('status', $admission->status) == 'Dropped' ? 'selected' : '' }}>Dropped</option>
                                <option value="Suspended" {{ old('status', $admission->status) == 'Suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-info py-2 fw-bold text-uppercase">
                                <i class="fas fa-save me-2"></i>Update Enrollment
                            </button>
                            <button type="button" class="btn btn-link text-danger" onclick="if(confirm('Are you sure you want to delete this admission record?')) document.getElementById('delete-form').submit();">
                                <i class="fas fa-trash me-1"></i>Delete Admission
                            </button>
                        </div>
                    </form>
                    
                    <form id="delete-form" action="{{ route('admissions.destroy', $admission) }}" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
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
    });
</script>
@endsection
