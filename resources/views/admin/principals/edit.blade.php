@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">Edit Principal Record</h1>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('principals.update', $principal->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <select class="form-select @error('title') is-invalid @enderror" id="title" name="title" required>
                                        <option value="Mr." {{ old('title', $principal->title) == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                                        <option value="Ms." {{ old('title', $principal->title) == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                                        <option value="Mrs." {{ old('title', $principal->title) == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                                        <option value="Dr." {{ old('title', $principal->title) == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                                        <option value="Prof." {{ old('title', $principal->title) == 'Prof.' ? 'selected' : '' }}>Prof.</option>
                                    </select>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $principal->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="father_name" class="form-label">Father Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('father_name') is-invalid @enderror" id="father_name" name="father_name" value="{{ old('father_name', $principal->father_name) }}" required>
                                    @error('father_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                                        <option value="Male" {{ old('gender', $principal->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ old('gender', $principal->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                        <option value="Other" {{ old('gender', $principal->gender) == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('gender')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="designation" class="form-label">Designation <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('designation') is-invalid @enderror" id="designation" name="designation" value="{{ old('designation', $principal->designation) }}" required>
                            @error('designation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="from_date" class="form-label">From Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('from_date') is-invalid @enderror" id="from_date" name="from_date" value="{{ old('from_date', $principal->from_date) }}" required>
                                    @error('from_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="to_date" class="form-label">To Date</label>
                                    <input type="date" class="form-control @error('to_date') is-invalid @enderror" id="to_date" name="to_date" value="{{ old('to_date', $principal->to_date) }}">
                                    <small class="text-muted">Leave blank if currently serving</small>
                                    @error('to_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="details" class="form-label">Details / Bio</label>
                            <textarea class="form-control @error('details') is-invalid @enderror" id="details" name="details" rows="4">{{ old('details', $principal->details) }}</textarea>
                            @error('details')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="is_current" class="form-label">Current Principal? <span class="text-danger">*</span></label>
                            <select class="form-select @error('is_current') is-invalid @enderror" id="is_current" name="is_current" required>
                                <option value="0" {{ old('is_current', $principal->is_current) == '0' ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('is_current', $principal->is_current) == '1' ? 'selected' : '' }}>Yes</option>
                            </select>
                            <small class="text-muted">Setting to Yes will unset any other current principal.</small>
                            @error('is_current')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="1" {{ old('status', $principal->status) == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status', $principal->status) == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Photo</label>
                            <div class="mb-2">
                                <img src="{{ $principal->getImageUrl() }}" alt="Current Photo" class="img-fluid rounded" style="max-height: 150px;">
                            </div>
                            <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                            <small class="text-muted">Max size: 2MB. Formats: JPG, PNG. Leave blank to keep current.</small>
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="staff_id" class="form-label">Link to Staff (Optional)</label>
                            <select class="form-select @error('staff_id') is-invalid @enderror" id="staff_id" name="staff_id">
                                <option value="">-- Select Staff Member --</option>
                                @foreach($staff as $member)
                                    <option value="{{ $member->id }}" {{ old('staff_id', $principal->staff_id) == $member->id ? 'selected' : '' }}>
                                        {{ $member->full_name }} ({{ $member->designation }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Link to existing staff profile if available.</small>
                            @error('staff_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <a href="{{ route('principals.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
