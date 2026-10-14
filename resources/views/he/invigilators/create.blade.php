@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2>HE Invigilator Auto-Assignment</h2>
            <a href="{{ route('he.invigilators.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5><i class="fas fa-magic"></i> Auto-Assign HE Invigilators</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('he.invigilators.auto_assign') }}">
                @csrf
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Invigilators Per Room</label>
                            <input type="number" name="invigilators_per_room" class="form-control" value="2" min="1" max="4" required>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i> This process will automatically assign invigilators based on rules and availability for HE exams. 
                    Existing auto-assigned HE records in the selected range will be replaced.
                </div>

                <button type="submit" class="btn btn-success btn-lg btn-block" onclick="return confirm('Are you sure you want to run auto-assignment for the selected range?')">
                    <i class="fas fa-play"></i> Run HE Auto-Assignment
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
