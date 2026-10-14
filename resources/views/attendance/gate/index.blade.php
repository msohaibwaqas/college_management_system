@extends('layouts.app')

@section('title', 'Main Gate Attendance')

@section('content')
<div class="row">
    <!-- Scanner Input Column -->
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-primary"><i class="fas fa-barcode"></i> Scan Student ID</h4>
                <p class="card-description">Please scan the student's ID card barcode to mark arrival or departure.</p>
                <div class="form-group text-center mt-4">
                    <div class="input-group input-group-lg search-box">
                        <input type="text" id="barcode-input" class="form-control text-center" 
                               placeholder="Waiting for scanner input..." autofocus autocomplete="off" 
                               style="font-size: 24px; letter-spacing: 2px; height: 60px;">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button" id="submit-btn" onclick="processScan()" style="width: 100px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-arrow-right" id="btn-icon"></i>
                                <div class="spinner-border spinner-border-sm d-none" id="btn-spinner" role="status"></div>
                            </button>
                        </div>
                    </div>
                    <div class="mt-2 text-right">
                        <span id="js-ready-badge" class="badge badge-pill badge-outline-warning" style="font-size: 10px;">Initializing...</span>
                        <div id="debug-info" class="badge badge-outline-dark d-none" style="font-size: 10px;">Last: <span id="last-input-val"></span></div>
                    </div>
                </div>

                <!-- Loading Spinner Overlay (hidden by default) -->
                <div id="loading-overlay" class="position-absolute d-none" style="top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.8); z-index:10; display:flex; align-items:center; justify-content:center; border-radius:inherit;">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                        <h5 class="mt-3 text-primary">Processing Scan...</h5>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <div id="scan-status" class="alert d-none" role="alert">
                        <h4 class="alert-heading mb-1" id="status-heading">Status</h4>
                        <p class="mb-0" id="status-message">Message goes here</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Preview Column -->
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-info"><i class="fas fa-user-circle"></i> Last Scanned Student</h4>
                <div class="text-center mt-4 d-none" id="student-preview">
                    <img src="" id="student-photo" class="img-fluid rounded-circle shadow-sm" alt="Student Photo" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #eee;">
                    
                    <h3 class="mt-3 mb-1 font-weight-bold text-dark" id="student-name">Student Name</h3>
                    <p class="text-muted mb-2 font-weight-medium" id="student-class">Class Name</p>
                    
                    <div class="d-inline-flex mx-auto align-items-center justify-content-center px-4 py-2 rounded-pill mt-2" id="time-badge" style="background-color: #f8f9fa;">
                        <i class="fas fa-clock mr-2 text-primary"></i>
                        <h4 class="mb-0 text-dark font-weight-bold" id="action-time">00:00 AM</h4>
                    </div>
                </div>
                <div class="text-center text-muted mt-5" id="no-preview-text">
                    <i class="fas fa-id-card fa-4x mb-3 text-light"></i>
                    <h5>No student scanned yet</h5>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    console.log('Attendance Gate Script Loading...');
    
    // Global references for easier access
    const UI = {
        input: () => document.getElementById('barcode-input'),
        overlay: () => document.getElementById('loading-overlay'),
        icon: () => document.getElementById('btn-icon'),
        spinner: () => document.getElementById('btn-spinner'),
        submitBtn: () => document.getElementById('submit-btn'),
        debugInfo: () => document.getElementById('debug-info'),
        lastInput: () => document.getElementById('last-input-val'),
        statusBox: () => document.getElementById('scan-status'),
        statusHead: () => document.getElementById('status-heading'),
        statusMsg: () => document.getElementById('status-message'),
        readyBadge: () => document.getElementById('js-ready-badge'),
        preview: () => document.getElementById('student-preview'),
        noPreview: () => document.getElementById('no-preview-text'),
        timeBadge: () => document.getElementById('time-badge'),
    };

    function processScan() {
        try {
            const input = UI.input();
            if (!input) return alert('Error: Input field missing!');
            
            const val = input.value.trim();
            console.log('Button clicked, scanning:', val);

            if (!val) {
                alert('Please type a Student ID or Roll Number first.');
                return;
            }

            // Update UI state
            const overlay = UI.overlay();
            if (overlay) overlay.classList.remove('d-none');
            
            const btn = UI.submitBtn();
            const icon = UI.icon();
            const spinner = UI.spinner();
            if (btn) btn.disabled = true;
            if (icon) icon.classList.add('d-none');
            if (spinner) spinner.classList.remove('d-none');
            input.disabled = true;

            const debug = UI.debugInfo();
            const debugVal = UI.lastInput();
            if (debug) debug.classList.remove('d-none');
            if (debugVal) debugVal.textContent = val;

            const status = UI.statusBox();
            if (status) status.classList.add('d-none');

            console.log('Sending fetch request to server...');
            
            fetch('{{ route("attendance.gate.scan") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ student_id: val })
            })
            .then(async response => {
                console.log('Server response code:', response.status);
                
                if (response.status === 419) {
                    alert('Session expired. Please refresh the page (F5).');
                    throw new Error('CSRF Token Mismatch (419)');
                }
                
                if (!response.ok) {
                    const text = await response.text();
                    console.error('Server error details:', text);
                    throw new Error('Server returned ' + response.status);
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Data received:', data);
                handleScanResult(data);
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('SCAN FAILED: ' + error.message);
                showError(error.message);
            })
            .finally(() => {
                // Restore UI
                if (input) {
                    input.value = '';
                    input.disabled = false;
                    input.focus();
                }
                if (btn) btn.disabled = false;
                if (icon) icon.classList.remove('d-none');
                if (spinner) spinner.classList.add('d-none');
                if (overlay) overlay.classList.add('d-none');
            });

        } catch (e) {
            console.error('JS Crash in processScan:', e);
            alert('App Error: ' + e.message);
        }
    }

    function handleScanResult(data) {
        const status = UI.statusBox();
        if (!status) return;

        status.className = 'alert mt-3 ' + (data.success ? (data.action === 'in' ? 'alert-success' : 'alert-info') : 'alert-danger');
        
        const head = UI.statusHead();
        if (head) {
            head.innerHTML = data.success ? '<i class="fas fa-check-circle"></i> Access Granted' : '<i class="fas fa-times-circle"></i> Scan Failed';
            head.className = 'alert-heading h4 mb-1 ' + (data.success ? 'text-' + (data.action === 'in' ? 'success' : 'info') : 'text-danger');
        }

        const msg = UI.statusMsg();
        if (msg) msg.textContent = data.message;

        if (data.success) {
            const badge = UI.timeBadge();
            if (badge) {
                badge.style.backgroundColor = data.action === 'in' ? '#d4edda' : '#d1ecf1';
                badge.style.color = data.action === 'in' ? '#155724' : '#0c5460';
            }
            updatePreview(data);
        }

        status.classList.remove('d-none');
        setTimeout(() => status.classList.add('d-none'), 5000);
    }

    function showError(message) {
        handleScanResult({ success: false, message: message });
    }

    function updatePreview(data) {
        const noPrev = UI.noPreview();
        const prev = UI.preview();
        if (noPrev) noPrev.classList.add('d-none');
        if (prev) prev.classList.remove('d-none');

        const photo = document.getElementById('student-photo');
        const name = document.getElementById('student-name');
        const sClass = document.getElementById('student-class');
        const sTime = document.getElementById('action-time');

        if (photo) photo.src = data.student.photo;
        if (name) name.textContent = data.student.name;
        if (sClass) sClass.textContent = data.student.class;
        if (sTime) sTime.textContent = data.time;
    }

    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Content Loaded. Initializing app...');
        
        const badge = UI.readyBadge();
        if (badge) {
            badge.textContent = 'System Online';
            badge.className = 'badge badge-pill badge-outline-success';
        }

        const input = UI.input();
        if (input) {
            input.focus();
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    processScan();
                }
            });
        }

        document.addEventListener('click', (e) => {
            if (e.target.tagName !== 'INPUT' && !e.target.closest('button')) {
                const input = UI.input();
                if (input) input.focus();
            }
        });
        
        console.log('App initialization complete.');
    });
</script>
@endsection
