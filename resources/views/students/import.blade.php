@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Import Students') }}</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('students.import.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="program_type">Program Type</label>
                            <select class="form-control @error('program_type') is-invalid @enderror" id="program_type" name="program_type" required>
                                <option value="HSSC" {{ old('program_type') == 'HSSC' ? 'selected' : '' }}>HSSC</option>
                                <option value="HE" {{ old('program_type') == 'HE' ? 'selected' : '' }}>Higher Education (BS/ADP)</option>
                            </select>
                            @error('program_type')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="file">CSV File</label>
                            <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" required accept=".csv, .txt">
                            @error('file')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="alert alert-info" id="hssc_instructions">
                            <strong>Expected CSV Format (HSSC):</strong><br>
                            <small>
                                Student Name, Father Name, CNIC, College Roll No, HSSC Reg No, Admission Year, Sub1 ID, Sub2 ID, Sub3 ID, Sub4 ID, Sub5 ID, Sub6 ID, Sub7 ID, Sub8 ID
                            </small>
                            <br><br>
                            <strong>Required Fields:</strong>
                            <ul>
                                <li><strong>Student Name</strong> - Full name of the student</li>
                                <li><strong>Father Name</strong> - Full name of the father</li>
                                <li><strong>CNIC</strong> - Student's CNIC (must be unique)</li>
                                <li><strong>College Roll No</strong> - College roll number (must be unique)</li>
                                <li><strong>HSSC Reg No</strong> - HSSC registration number (must be unique)</li>
                                <li><strong>Admission Year</strong> - Year of admission (e.g., 2025)</li>
                                <li><strong>Sub1 to Sub5</strong> - Compulsory Subject IDs</li>
                                <li><strong>Sub6 to Sub8</strong> - Elective Subject IDs</li>
                            </ul>
                            <strong>Note:</strong>
                            <ul>
                                <li>Date of Birth will default to 2000-01-01 for all imported students.</li>
                                <li>All students will be assigned to class 11 by default.</li>
                                <li>Download the <a href="{{ route('students.import.sample') }}" class="alert-link">HSSC sample CSV file</a> to see the correct format.</li>
                            </ul>
                        </div>

                        <div class="alert alert-info" id="he_instructions" style="display: none;">
                            <strong>Expected CSV Format (HE):</strong><br>
                            <small>
                                Email Address, College Roll Number, University Registration No., University Roll Number, Student Name, CNIC, Mobile No, Date of Birth, Father's / Guardian Name, Father's / Guardian Mobile Number, Degree ID, Semester, Admission Year
                            </small>
                            <br><br>
                            <strong>Required Fields:</strong>
                            <ul>
                                <li><strong>Email Address</strong> - Student's email</li>
                                <li><strong>College Roll Number</strong> - College roll number (must be unique)</li>
                                <li><strong>University Registration No.</strong> - Registration number at the university</li>
                                <li><strong>University Roll Number</strong> - Roll number at the university</li>
                                <li><strong>Student Name</strong> - Full name of the student</li>
                                <li><strong>CNIC</strong> - Student's CNIC (must be unique)</li>
                                <li><strong>Mobile No</strong> - Student's contact number</li>
                                <li><strong>Date of Birth</strong> - Student's DOB (e.g., YYYY-MM-DD or DD-MM-YYYY)</li>
                                <li><strong>Father's / Guardian Name</strong> - Full name of the father/guardian</li>
                                <li><strong>Father's / Guardian Mobile Number</strong> - Contact number of the father</li>
                                <li><strong>Degree ID</strong> - The ID of the BS/ADP Program (e.g., 1)</li>
                                <li><strong>Semester</strong> - Semester number (e.g., 1)</li>
                                <li><strong>Admission Year</strong> - Year of admission (e.g., 2025)</li>
                            </ul>
                            <strong>Note:</strong>
                            <ul>
                                <li>Subjects will be automatically enrolled based on the Degree ID and Semester.</li>
                                <li>Download the <a href="{{ route('students.import.sample.he') }}" class="alert-link">HE sample CSV file</a> to see the correct format.</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            {{ __('Upload & Import') }}
                        </button>
                        <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const programTypeSelect = document.getElementById('program_type');
        const hsscInstructions = document.getElementById('hssc_instructions');
        const heInstructions = document.getElementById('he_instructions');

        function toggleInstructions() {
            if (programTypeSelect.value === 'HE') {
                hsscInstructions.style.display = 'none';
                heInstructions.style.display = 'block';
            } else {
                hsscInstructions.style.display = 'block';
                heInstructions.style.display = 'none';
            }
        }

        programTypeSelect.addEventListener('change', toggleInstructions);
        
        // Initial call to set the correct state on page load
        toggleInstructions();
    });
</script>
@endsection
