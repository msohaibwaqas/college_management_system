@extends('layouts.app')

@section('title', isset($template) ? 'Edit Template' : 'New Template')

@section('content')
<div class="main-content">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-file-alt me-2"></i>
            {{ isset($template) ? 'Edit Template: ' . $template->type : 'Create New Template' }}
        </h1>
        <p class="page-subtitle">Configure message body and active channels</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>Please fix the following errors:
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ isset($template) ? route('notification-templates.update', $template->id) : route('notification-templates.store') }}" method="POST">
                        @csrf
                        @if(isset($template))
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Template Code / Type <span class="text-danger">*</span></label>
                                <input type="text" name="type" class="form-control" value="{{ old('type', $template->type ?? '') }}" required placeholder="e.g. FEE-REMINDER-01" {{ isset($template) ? 'readonly' : '' }}>
                                <div class="form-text">A unique identifier used in the codebase.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Delivery Channels <span class="text-danger">*</span></label>
                                <input type="text" name="channel" class="form-control" value="{{ old('channel', $template->channel ?? 'sms,email,in-app') }}" required>
                                <div class="form-text">Comma-separated: sms, email, in-app</div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Email Subject (Optional)</label>
                                <input type="text" name="subject" class="form-control" value="{{ old('subject', $template->subject ?? '') }}" placeholder="Subject for email delivery">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Message Layout <span class="text-danger">*</span></label>
                                <textarea name="body" class="form-control" rows="6" required placeholder="Dear {student_name}, your fee of Rs. {amount} is pending.">{{ old('body', $template->body ?? '') }}</textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Supported Placeholders (Variables)</label>
                                <input type="text" name="placeholders" class="form-control" value="{{ old('placeholders', isset($template) ? implode(', ', $template->placeholders ?? []) : '') }}" placeholder="student_name, amount, due_date">
                                <div class="form-text">Comma-separated variables that will be dynamically replaced when sending.</div>
                            </div>

                            <div class="col-md-12 mt-4">
                                <div class="form-check form-switch shadow-sm border p-3 rounded bg-light">
                                    <input class="form-check-input ms-0 me-3 mt-1" type="checkbox" role="switch" id="isActiveSwitch" name="is_active" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }} style="transform: scale(1.3);">
                                    <label class="form-check-label fw-bold" for="isActiveSwitch">Template is Active (allow dispatching)</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end border-top pt-3">
                            <a href="{{ route('notification-templates.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>{{ isset($template) ? 'Update Template' : 'Save Template' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h5 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>How to Use Variables</h5>
                    <p class="small text-muted">
                        You can insert dynamic variables into the message body or subject line by wrapping them in curly braces like <code>{variable_name}</code>.
                    </p>
                    <p class="small text-muted">
                        <strong>Example:</strong> "Dear <code>{student_name}</code>, your admission to <code>{program_name}</code> is confirmed."
                    </p>
                    <hr>
                    <p class="small text-muted mb-0"><strong>Note:</strong> SMS gateways generally charge per 160 characters. Variables will expand to different lengths, so keep templates concise.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
