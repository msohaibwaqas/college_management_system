@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-award me-2"></i>Generate Award List</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('staff.award_list.generate') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="admission_year" class="form-label">Admission Year <span class="text-danger">*</span></label>
                            <select name="admission_year" id="admission_year" class="form-select" required>
                                <option value="">-- Select Admission Year --</option>
                                @foreach($admissionYears as $year)
                                    <option value="{{ $year }}" {{ old('admission_year') == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                            @error('admission_year')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                            <select name="subject_id" id="subject_id" class="form-select" required>
                                <option value="">-- Select Subject --</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_id')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-file-alt me-2"></i>Generate Award List
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
