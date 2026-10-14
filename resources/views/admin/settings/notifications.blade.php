@extends('layouts.app')

@section('title', 'Notification Settings')

@section('content')
<div class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-bell me-2"></i>Notification Settings</h1>
        <p class="page-subtitle">Configure SMS and Email gateways for the institution</p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Gateway Configuration</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('settings.notifications.update') }}" method="POST">
                        @csrf
                        
                        <h6 class="border-bottom pb-2 mb-3 mt-2 text-primary">SMS Gateway Configuration</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Global SMS Delivery</label>
                                <select name="sms_enabled" class="form-select">
                                    <option value="1" {{ ($settings['sms_enabled'] ?? '0') == '1' ? 'selected' : '' }}>Enabled</option>
                                    <option value="0" {{ ($settings['sms_enabled'] ?? '0') == '0' ? 'selected' : '' }}>Disabled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">SMS Provider</label>
                                <select name="sms_gateway" class="form-select">
                                    <option value="jazz" {{ ($settings['sms_gateway'] ?? '') == 'jazz' ? 'selected' : '' }}>Jazz (Mobilink)</option>
                                    <option value="ufone" {{ ($settings['sms_gateway'] ?? '') == 'ufone' ? 'selected' : '' }}>Ufone</option>
                                    <option value="zong" {{ ($settings['sms_gateway'] ?? '') == 'zong' ? 'selected' : '' }}>Zong</option>
                                    <option value="msg91" {{ ($settings['sms_gateway'] ?? '') == 'msg91' ? 'selected' : '' }}>MSG91 / Outbound API</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">API Key</label>
                                <input type="password" name="sms_api_key" class="form-control" value="{{ $settings['sms_api_key'] ?? '' }}" placeholder="Enter provider API key">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Sender ID / Mask</label>
                                <input type="text" name="sms_sender_id" class="form-control" value="{{ $settings['sms_sender_id'] ?? '' }}" placeholder="e.g. MYCOLLEGE">
                            </div>
                        </div>

                        <h6 class="border-bottom pb-2 mb-3 mt-4 text-primary">Email Configuration (SMTP)</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Global Email Delivery</label>
                                <select name="email_enabled" class="form-select">
                                    <option value="1" {{ ($settings['email_enabled'] ?? '0') == '1' ? 'selected' : '' }}>Enabled</option>
                                    <option value="0" {{ ($settings['email_enabled'] ?? '0') == '0' ? 'selected' : '' }}>Disabled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">SMTP Host</label>
                                <input type="text" name="smtp_host" class="form-control" value="{{ $settings['smtp_host'] ?? '' }}" placeholder="smtp.gmail.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">SMTP Port</label>
                                <input type="number" name="smtp_port" class="form-control" value="{{ $settings['smtp_port'] ?? '587' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Encryption</label>
                                <select name="smtp_encryption" class="form-select">
                                    <option value="tls" {{ ($settings['smtp_encryption'] ?? '') == 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="" {{ empty($settings['smtp_encryption']) ? 'selected' : '' }}>None</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">SMTP User</label>
                                <input type="text" name="smtp_user" class="form-control" value="{{ $settings['smtp_user'] ?? '' }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">SMTP Password</label>
                                <input type="password" name="smtp_password" class="form-control" value="{{ $settings['smtp_password'] ?? '' }}">
                            </div>
                        </div>

                        <h6 class="border-bottom pb-2 mb-3 mt-4 text-primary">Automation Thresholds</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Attendance Warning Threshold (%)</label>
                                <div class="input-group">
                                    <input type="number" name="attendance_warning_threshold" class="form-control" value="{{ $settings['attendance_warning_threshold'] ?? '75' }}" min="1" max="100">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">Triggers low attendance alert</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Fee Reminder (Days Before Due)</label>
                                <div class="input-group">
                                    <input type="number" name="fee_reminder_days" class="form-control" value="{{ $settings['fee_reminder_days'] ?? '7' }}" min="1" max="30">
                                    <span class="input-group-text">Days</span>
                                </div>
                                <div class="form-text">Triggers pending fee reminder</div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Save Configurations
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <h5 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Configuration Info</h5>
                    <p class="small text-muted">
                        These credentials are used by the Notification System to dispatch alerts to staff, students, and parents.
                    </p>
                    <hr>
                    <p class="small text-muted mb-1"><strong>SMS Gateways:</strong> Integration logic connects to telecom APIs. If API keys are invalid, messages will fail to send and register in logs.</p>
                    <p class="small text-muted mb-0"><strong>Email Config:</strong> Ensure you use "App Passwords" for Gmail or Microsoft 365, not your login password.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
