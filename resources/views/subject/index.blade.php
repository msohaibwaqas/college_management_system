@extends('layouts.app')
@section('title','Subjects')

@section('content')
<div class="container mt-4">
    <h1>Subjects</h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="mb-3 d-flex justify-content-between">
        <form class="d-flex" method="GET" action="{{ route('subject.index') }}">
            <input type="text" name="search_name" value="{{ $searchName }}" placeholder="Name" class="form-control me-2">
            <input type="text" name="search_code" value="{{ $searchCode }}" placeholder="Code" class="form-control me-2">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        <a href="{{ route('subject.create') }}" class="btn btn-success">Add Subject</a>
    </div>

    <table class="table table-bordered table-striped align-middle">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Code</th>
                <th>Type</th>
                <th>Class Level</th>
                <th>Total Marks (11/12)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjects as $subject)
            <tr>
                <td>{{ $subject->id }}</td>
                <td>{{ $subject->formatted_name }}</td>
                <td>{{ $subject->code }}</td>
                <td>{{ $subject->type }}</td>
                <td>{{ $subject->class_level }}</td>
                <td>{{ $subject->total_11 }}/{{ $subject->total_12 }}</td>
                <td>
                    <a href="{{ route('subject.show', $subject) }}" class="btn btn-sm btn-info">View</a>
                    <a href="{{ route('subject.edit', $subject) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('subject.destroy', $subject) }}" method="POST" class="d-inline-block">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this subject?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $subjects->links('pagination::bootstrap-5') }}
</div>
@endsection
