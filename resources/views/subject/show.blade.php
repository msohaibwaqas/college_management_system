@extends('layouts.app')
@section('title','Subject Details')

@section('content')
<div class="container mt-4">
    <h1>Subject Details</h1>

    <table class="table table-bordered">
        <tr><th>ID</th><td>{{ $subject->id }}</td></tr>
        <tr><th>Name</th><td>{{ $subject->formatted_name }}</td></tr>
        <tr><th>Code</th><td>{{ $subject->code }}</td></tr>
        <tr><th>Type</th><td>{{ $subject->type }}</td></tr>
        <tr><th>Class Level</th><td>{{ $subject->class_level }}</td></tr>
        <tr><th>Total Marks Class 11</th><td>{{ $subject->total_11 }}</td></tr>
        <tr><th>Theory Marks Class 12</th><td>{{ $subject->theory_12 }}</td></tr>
        <tr><th>Practical Marks Class 12</th><td>{{ $subject->practical_12 }}</td></tr>
        <tr><th>Total Marks Class 12</th><td>{{ $subject->total_12 }}</td></tr>
        <tr><th>Practical %</th><td>{{ $subject->practical_percentage }}%</td></tr>
        <tr><th>Students Count</th><td>{{ $subject->getStudentCount() }}</td></tr>
        <tr><th>Teachers Count</th><td>{{ $subject->getTeacherCount() }}</td></tr>
    </table>

    <a href="{{ route('subject.index') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
