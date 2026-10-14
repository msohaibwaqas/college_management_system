@extends('layouts.app')

@section('title', 'Color Settings')

@section('content')
<div class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-palette me-2"></i>Color Settings</h1>
        <p class="page-subtitle">Customize your website's color scheme</p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-paint-brush me-2"></i>Customize Colors</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('settings.colors.update') }}" method="POST" id="colorForm">
                        @csrf
                        
                        <div class="row g-4">
                            {{-- Primary Color --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-circle text-primary me-2"></i>Primary Color
                                </label>
                                <div class="input-group">
                                    <input type="color" 
                                           class="form-control form-control-color" 
                                           id="color_primary" 
                                           name="color_primary" 
                                           value="{{ old('color_primary', $settings->color_primary ?? '#006159') }}"
                                           onchange="updatePreview()">
                                    <input type="text" 
                                           class="form-control" 
                                           id="color_primary_text"
                                           value="{{ old('color_primary', $settings->color_primary ?? '#006159') }}"
                                           pattern="^#[0-9A-Fa-f]{6}$"
                                           onchange="syncColorPicker('primary', this.value)">
                                </div>
                                <small class="text-muted">Used for headers, primary buttons, and main accents</small>
                            </div>

                            {{-- Secondary Color --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-circle me-2" style="color: #2e9f82;"></i>Secondary Color
                                </label>
                                <div class="input-group">
                                    <input type="color" 
                                           class="form-control form-control-color" 
                                           id="color_secondary" 
                                           name="color_secondary" 
                                           value="{{ old('color_secondary', $settings->color_secondary ?? '#2e9f82') }}"
                                           onchange="updatePreview()">
                                    <input type="text" 
                                           class="form-control" 
                                           id="color_secondary_text"
                                           value="{{ old('color_secondary', $settings->color_secondary ?? '#2e9f82') }}"
                                           pattern="^#[0-9A-Fa-f]{6}$"
                                           onchange="syncColorPicker('secondary', this.value)">
                                </div>
                                <small class="text-muted">Used for secondary elements and hover states</small>
                            </div>

                            {{-- Highlight Color --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-circle me-2" style="color: #d6ec8c;"></i>Highlight Color
                                </label>
                                <div class="input-group">
                                    <input type="color" 
                                           class="form-control form-control-color" 
                                           id="color_highlight" 
                                           name="color_highlight" 
                                           value="{{ old('color_highlight', $settings->color_highlight ?? '#d6ec8c') }}"
                                           onchange="updatePreview()">
                                    <input type="text" 
                                           class="form-control" 
                                           id="color_highlight_text"
                                           value="{{ old('color_highlight', $settings->color_highlight ?? '#d6ec8c') }}"
                                           pattern="^#[0-9A-Fa-f]{6}$"
                                           onchange="syncColorPicker('highlight', this.value)">
                                </div>
                                <small class="text-muted">Used for highlights and special emphasis</small>
                            </div>

                            {{-- Success Color --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-circle text-success me-2"></i>Success Color
                                </label>
                                <div class="input-group">
                                    <input type="color" 
                                           class="form-control form-control-color" 
                                           id="color_success" 
                                           name="color_success" 
                                           value="{{ old('color_success', $settings->color_success ?? '#68c170') }}"
                                           onchange="updatePreview()">
                                    <input type="text" 
                                           class="form-control" 
                                           id="color_success_text"
                                           value="{{ old('color_success', $settings->color_success ?? '#68c170') }}"
                                           pattern="^#[0-9A-Fa-f]{6}$"
                                           onchange="syncColorPicker('success', this.value)">
                                </div>
                                <small class="text-muted">Used for success messages and positive indicators</small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetPreview()">
                                <i class="fas fa-undo me-2"></i>Reset Preview
                            </button>
                        </div>
                    </form>

                    <form action="{{ route('settings.colors.reset') }}" method="POST" class="mt-3" onsubmit="return confirm('Are you sure you want to reset all colors to defaults?')">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fas fa-sync-alt me-2"></i>Reset to Defaults
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Live Preview --}}
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 80px;">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Live Preview</h5>
                </div>
                <div class="card-body">
                    <div id="preview-container">
                        {{-- Header Preview --}}
                        <div class="mb-3 p-3 text-white rounded" id="preview-header" style="background-color: {{ $settings->color_primary ?? '#006159' }}">
                            <h5 class="mb-1">Page Header</h5>
                            <p class="mb-0 small opacity-75">This is how your headers will look</p>
                        </div>

                        {{-- Button Preview --}}
                        <div class="mb-3">
                            <button class="btn text-white" id="preview-button" style="background-color: {{ $settings->color_primary ?? '#006159' }}">
                                Primary Button
                            </button>
                        </div>

                        {{-- Badge Preview --}}
                        <div class="mb-3">
                            <span class="badge" id="preview-badge-secondary" style="background-color: {{ $settings->color_secondary ?? '#2e9f82' }}">
                                Secondary Badge
                            </span>
                            <span class="badge" id="preview-badge-highlight" style="background-color: {{ $settings->color_highlight ?? '#d6ec8c' }}; color: #333;">
                                Highlight Badge
                            </span>
                            <span class="badge" id="preview-badge-success" style="background-color: {{ $settings->color_success ?? '#68c170' }}">
                                Success Badge
                            </span>
                        </div>

                        {{-- Card Preview --}}
                        <div class="border rounded p-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle me-2" id="preview-icon" style="width: 40px; height: 40px; background-color: {{ $settings->color_primary ?? '#006159' }}; opacity: 0.1;"></div>
                                <div>
                                    <h6 class="mb-0" id="preview-text" style="color: {{ $settings->color_primary ?? '#006159' }}">Card Title</h6>
                                    <small class="text-muted">Card subtitle</small>
                                </div>
                            </div>
                            <p class="small text-muted mb-0">This shows how your colors will appear on cards and other elements.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function syncColorPicker(colorName, hexValue) {
    if (/^#[0-9A-Fa-f]{6}$/.test(hexValue)) {
        document.getElementById('color_' + colorName).value = hexValue;
        document.getElementById('color_' + colorName + '_text').value = hexValue;
        updatePreview();
    }
}

function updatePreview() {
    const primary = document.getElementById('color_primary').value;
    const secondary = document.getElementById('color_secondary').value;
    const highlight = document.getElementById('color_highlight').value;
    const success = document.getElementById('color_success').value;

    // Update text inputs
    document.getElementById('color_primary_text').value = primary;
    document.getElementById('color_secondary_text').value = secondary;
    document.getElementById('color_highlight_text').value = highlight;
    document.getElementById('color_success_text').value = success;

    // Update preview elements
    document.getElementById('preview-header').style.backgroundColor = primary;
    document.getElementById('preview-button').style.backgroundColor = primary;
    document.getElementById('preview-text').style.color = primary;
    document.getElementById('preview-icon').style.backgroundColor = primary;
    document.getElementById('preview-badge-secondary').style.backgroundColor = secondary;
    document.getElementById('preview-badge-highlight').style.backgroundColor = highlight;
    document.getElementById('preview-badge-success').style.backgroundColor = success;
}

function resetPreview() {
    document.getElementById('color_primary').value = '{{ $settings->color_primary ?? '#006159' }}';
    document.getElementById('color_secondary').value = '{{ $settings->color_secondary ?? '#2e9f82' }}';
    document.getElementById('color_highlight').value = '{{ $settings->color_highlight ?? '#d6ec8c' }}';
    document.getElementById('color_success').value = '{{ $settings->color_success ?? '#68c170' }}';
    updatePreview();
}
</script>
@endsection
