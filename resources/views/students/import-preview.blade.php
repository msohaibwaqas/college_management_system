@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-file-csv"></i> {{ __('Import Preview') }} — {{ $programType }} Students</span>
                    <div>
                        <a href="{{ route('students.import.download-validated') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download"></i> Download CSV with Status
                        </a>
                        <a href="{{ route('students.import') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Summary --}}
                    @if($errorCount === 0)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>All {{ $approvedCount }} row(s) approved!</strong> Press "Confirm Import" to upload.
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>{{ $approvedCount }} row(s) approved, {{ $errorCount }} row(s) have errors.</strong>
                            Rows with errors will be skipped during import. You can download the CSV with status comments to review.
                        </div>
                    @endif

                    {{-- Data Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm table-hover" style="font-size: 0.85rem;">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    @foreach($headers as $header)
                                        <th>{{ $header }}</th>
                                    @endforeach
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    <tr class="{{ $row['status'] === 'Approved' ? 'table-success' : 'table-danger' }}">
                                        <td>{{ $row['row_number'] }}</td>
                                        @foreach($row['original'] as $cell)
                                            <td>{{ $cell }}</td>
                                        @endforeach
                                        {{-- Pad empty cells if row has fewer columns than headers --}}
                                        @for($i = count($row['original']); $i < count($headers); $i++)
                                            <td></td>
                                        @endfor
                                        <td>
                                            @if($row['status'] === 'Approved')
                                                <span class="badge bg-success">Approved</span>
                                            @else
                                                <span class="badge bg-danger">Error</span>
                                                <small class="text-danger d-block">{{ $row['error'] }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex justify-content-between mt-3">
                        <a href="{{ route('students.import') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>

                        @if($approvedCount > 0)
                            <form method="POST" action="{{ route('students.import.confirm') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to import {{ $approvedCount }} approved row(s)?')">
                                    <i class="fas fa-upload"></i> Confirm Import ({{ $approvedCount }} rows)
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
