@extends('layouts.app')
@section('title', isset($subject) ? 'Edit Subject' : 'Add Subject')

@section('content')
<div class="container mt-4">
    <h1>
        @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        {{ isset($subject) ? 'Edit Subject' : 'Add Subject' }}
    </h1>

    <form method="POST" action="{{ isset($subject) ? route('subject.update', $subject) : route('subject.store') }}">
        @csrf
        @if(isset($subject))
            @method('PUT')
        @endif

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" value="{{ $subject->name ?? old('name') }}" class="form-control">
            @error('name') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Code</label>
            <input type="text" name="code" value="{{ $subject->code ?? old('code') }}" class="form-control">
            @error('code') <div class="text-danger">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Short Name / Board Code (e.g. TRQ, ISL, ENG, BIO)</label>
            <input type="text" name="short_name" value="{{ $subject->short_name ?? old('short_name') }}" class="form-control" placeholder="e.g. UR, ENG, TRQ, ISL, BIO">
            @error('short_name') <div class="text-danger">{{ $message }}</div> @enderror
        </div>


        <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
                <option value="Compulsory" {{ (isset($subject) && $subject->type=='Compulsory') ? 'selected' : '' }}>Compulsory</option>
                <option value="Elective" {{ (isset($subject) && $subject->type=='Elective') ? 'selected' : '' }}>Elective</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Class Level</label>
            <select name="class_level" id="classLevel" class="form-select">
                <option value="11" {{ (isset($subject) && $subject->class_level=='11') ? 'selected' : '' }}>11</option>
                <option value="12" {{ (isset($subject) && $subject->class_level=='12') ? 'selected' : '' }}>12</option>
                <option value="both" {{ (isset($subject) && $subject->class_level=='both') ? 'selected' : '' }}>Both</option>
            </select>
        </div>

        <div class="mb-3" id="total11Div">
            <label class="form-label">Total Marks (Class 11)</label>
            <input type="number" name="total_11" value="{{ $subject->total_11 ?? old('total_11') }}" class="form-control">
        </div>

        <div class="mb-3" id="theory12Div">
            <label class="form-label">Theory Marks (Class 12)</label>
            <input type="number" name="theory_12" value="{{ $subject->theory_12 ?? old('theory_12') }}" class="form-control">
        </div>

        <div class="mb-3" id="practical12Div">
            <label class="form-label">Practical Marks (Class 12)</label>
            <input type="number" name="practical_12" value="{{ $subject->practical_12 ?? old('practical_12') }}" class="form-control">
        </div>

        <button type="submit" class="btn btn-success">{{ isset($subject) ? 'Update' : 'Add' }}</button>
        <a href="{{ route('subject.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const classLevel = document.getElementById('classLevel');
    const total11Div = document.getElementById('total11Div');
    const theory12Div = document.getElementById('theory12Div');
    const practical12Div = document.getElementById('practical12Div');

    function updateFields() {
        const value = classLevel.value;

        if (value === '11') {
            total11Div.style.display = 'block';
            theory12Div.style.display = 'none';
            practical12Div.style.display = 'none';
        } else if (value === '12') {
            total11Div.style.display = 'none';
            theory12Div.style.display = 'block';
            practical12Div.style.display = 'none';
        } else if (value === 'both') {
            total11Div.style.display = 'block';
            theory12Div.style.display = 'block';
            practical12Div.style.display = 'block';
        }
    }

    // Initialize visibility on page load
    updateFields();

    // Update visibility when class level changes
    classLevel.addEventListener('change', updateFields);
});
</script>
@endsection
