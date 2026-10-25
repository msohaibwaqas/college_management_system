@extends('layouts.app')

@section('title', 'Dynamic Color Scheme Settings')

@section('content')
<div class="container-fluid py-4">
    {{-- Page Header Banner --}}
    <div class="card border-0 shadow-sm mb-4 report-hero-card">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="badge bg-white text-primary px-3 py-1 mb-2 fw-bold text-uppercase" style="letter-spacing: 0.5px; border-radius: 20px;">
                        <i class="fas fa-palette me-1"></i> Global Dynamic Theming
                    </span>
                    <h2 class="fw-bold text-white mb-1">
                        <i class="fas fa-paint-brush me-2"></i>Unified Color Scheme
                    </h2>
                    <p class="text-white-50 mb-0 small">
                        Dynamically customize the entire color palette across public pages, internal panels, reports, tables, cards, and buttons.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('home') }}" target="_blank" class="btn btn-light btn-sm px-3 shadow-sm rounded-pill fw-semibold text-primary">
                        <i class="fas fa-external-link-alt me-1"></i> View Public Site
                    </a>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-sm px-3 rounded-pill">
                        <i class="fas fa-arrow-left me-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Left Column: Color Controls & Presets --}}
        <div class="col-lg-7">
            {{-- Curated One-Click Presets --}}
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-magic me-2 text-primary"></i>Curated Theme Presets
                    </h5>
                    <small class="text-muted">Click any preset to preview and apply an executive, harmonious color combination</small>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        {{-- Preset 1: Emerald & Mint (Default Sophisticated) --}}
                        <div class="col-md-6 col-12">
                            <div class="p-3 border rounded-3 preset-card h-100" style="cursor: pointer; transition: all 0.2s;"
                                 onclick="applyPreset('#006159', '#2e9f82', '#d6ec8c', '#68c170', 'Emerald & Mint (College Default)')">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold small text-dark">Emerald & Mint</span>
                                    <span class="badge bg-primary-subtle text-primary small">Recommended</span>
                                </div>
                                <div class="d-flex gap-1 rounded overflow-hidden" style="height: 18px;">
                                    <div style="flex: 2; background: #006159;" title="Primary #006159"></div>
                                    <div style="flex: 1.5; background: #2e9f82;" title="Secondary #2e9f82"></div>
                                    <div style="flex: 1; background: #d6ec8c;" title="Highlight #d6ec8c"></div>
                                    <div style="flex: 1; background: #68c170;" title="Success #68c170"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Preset 2: Oxford Navy & Amber --}}
                        <div class="col-md-6 col-12">
                            <div class="p-3 border rounded-3 preset-card h-100" style="cursor: pointer; transition: all 0.2s;"
                                 onclick="applyPreset('#1e3a8a', '#3b82f6', '#f59e0b', '#10b981', 'Oxford Navy & Amber')">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold small text-dark">Oxford Navy & Amber</span>
                                </div>
                                <div class="d-flex gap-1 rounded overflow-hidden" style="height: 18px;">
                                    <div style="flex: 2; background: #1e3a8a;"></div>
                                    <div style="flex: 1.5; background: #3b82f6;"></div>
                                    <div style="flex: 1; background: #f59e0b;"></div>
                                    <div style="flex: 1; background: #10b981;"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Preset 3: Imperial Maroon & Gold --}}
                        <div class="col-md-6 col-12">
                            <div class="p-3 border rounded-3 preset-card h-100" style="cursor: pointer; transition: all 0.2s;"
                                 onclick="applyPreset('#7f1d1d', '#b91c1c', '#fbbf24', '#15803d', 'Imperial Maroon & Gold')">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold small text-dark">Imperial Maroon & Gold</span>
                                </div>
                                <div class="d-flex gap-1 rounded overflow-hidden" style="height: 18px;">
                                    <div style="flex: 2; background: #7f1d1d;"></div>
                                    <div style="flex: 1.5; background: #b91c1c;"></div>
                                    <div style="flex: 1; background: #fbbf24;"></div>
                                    <div style="flex: 1; background: #15803d;"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Preset 4: Forest Emerald & Mint --}}
                        <div class="col-md-6 col-12">
                            <div class="p-3 border rounded-3 preset-card h-100" style="cursor: pointer; transition: all 0.2s;"
                                 onclick="applyPreset('#064e3b', '#059669', '#a7f3d0', '#10b981', 'Forest Emerald & Mint')">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold small text-dark">Forest Emerald & Mint</span>
                                </div>
                                <div class="d-flex gap-1 rounded overflow-hidden" style="height: 18px;">
                                    <div style="flex: 2; background: #064e3b;"></div>
                                    <div style="flex: 1.5; background: #059669;"></div>
                                    <div style="flex: 1; background: #a7f3d0;"></div>
                                    <div style="flex: 1; background: #10b981;"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Preset 5: Executive Slate & Sky --}}
                        <div class="col-12">
                            <div class="p-3 border rounded-3 preset-card" style="cursor: pointer; transition: all 0.2s;"
                                 onclick="applyPreset('#0f172a', '#0284c7', '#38bdf8', '#22c55e', 'Executive Slate & Sky')">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold small text-dark">Executive Slate & Sky</span>
                                </div>
                                <div class="d-flex gap-1 rounded overflow-hidden" style="height: 18px;">
                                    <div style="flex: 2; background: #0f172a;"></div>
                                    <div style="flex: 1.5; background: #0284c7;"></div>
                                    <div style="flex: 1; background: #38bdf8;"></div>
                                    <div style="flex: 1; background: #22c55e;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Custom Color Inputs Form --}}
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-sliders-h me-2 text-primary"></i>Custom Palette Controls</h5>
                    <span id="activePresetBadge" class="badge bg-light text-muted small">Custom Palette</span>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('settings.colors.update') }}" method="POST" id="colorForm">
                        @csrf
                        
                        <div class="row g-4">
                            {{-- Primary Color --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex justify-content-between">
                                    <span><i class="fas fa-circle text-primary me-2"></i>Primary Theme Color</span>
                                    <span class="text-muted small">Main Accent</span>
                                </label>
                                <div class="input-group">
                                    <input type="color" 
                                           class="form-control form-control-color" 
                                           id="color_primary" 
                                           name="color_primary" 
                                           value="{{ old('color_primary', $settings->color_primary ?? '#006159') }}"
                                           onchange="updatePreview()">
                                    <input type="text" 
                                           class="form-control font-monospace" 
                                           id="color_primary_text"
                                           value="{{ old('color_primary', $settings->color_primary ?? '#006159') }}"
                                           pattern="^#[0-9A-Fa-f]{6}$"
                                           oninput="syncColorPicker('primary', this.value)">
                                </div>
                                <small class="text-muted">Controls navbars, headers, primary buttons, active sidebar tabs, and report banners</small>
                            </div>

                            {{-- Secondary Color --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex justify-content-between">
                                    <span><i class="fas fa-circle text-secondary me-2"></i>Secondary Accent</span>
                                    <span class="text-muted small">Supporting Accent</span>
                                </label>
                                <div class="input-group">
                                    <input type="color" 
                                           class="form-control form-control-color" 
                                           id="color_secondary" 
                                           name="color_secondary" 
                                           value="{{ old('color_secondary', $settings->color_secondary ?? '#2e9f82') }}"
                                           onchange="updatePreview()">
                                    <input type="text" 
                                           class="form-control font-monospace" 
                                           id="color_secondary_text"
                                           value="{{ old('color_secondary', $settings->color_secondary ?? '#2e9f82') }}"
                                           pattern="^#[0-9A-Fa-f]{6}$"
                                           oninput="syncColorPicker('secondary', this.value)">
                                </div>
                                <small class="text-muted">Controls secondary buttons, subtle borders, focus outlines, and sidebar icon accents</small>
                            </div>

                            {{-- Highlight Color --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex justify-content-between">
                                    <span><i class="fas fa-circle me-2" style="color: #d6ec8c;"></i>Highlight / Glow Color</span>
                                    <span class="text-muted small">Contrast Tag</span>
                                </label>
                                <div class="input-group">
                                    <input type="color" 
                                           class="form-control form-control-color" 
                                           id="color_highlight" 
                                           name="color_highlight" 
                                           value="{{ old('color_highlight', $settings->color_highlight ?? '#d6ec8c') }}"
                                           onchange="updatePreview()">
                                    <input type="text" 
                                           class="form-control font-monospace" 
                                           id="color_highlight_text"
                                           value="{{ old('color_highlight', $settings->color_highlight ?? '#d6ec8c') }}"
                                           pattern="^#[0-9A-Fa-f]{6}$"
                                           oninput="syncColorPicker('highlight', this.value)">
                                </div>
                                <small class="text-muted">Used for prominent contrast highlights, hero text accents, and login badges</small>
                            </div>

                            {{-- Success Color --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex justify-content-between">
                                    <span><i class="fas fa-circle text-success me-2"></i>Success Color</span>
                                    <span class="text-muted small">Status & Metrics</span>
                                </label>
                                <div class="input-group">
                                    <input type="color" 
                                           class="form-control form-control-color" 
                                           id="color_success" 
                                           name="color_success" 
                                           value="{{ old('color_success', $settings->color_success ?? '#68c170') }}"
                                           onchange="updatePreview()">
                                    <input type="text" 
                                           class="form-control font-monospace" 
                                           id="color_success_text"
                                           value="{{ old('color_success', $settings->color_success ?? '#68c170') }}"
                                           pattern="^#[0-9A-Fa-f]{6}$"
                                           oninput="syncColorPicker('success', this.value)">
                                </div>
                                <small class="text-muted">Controls positive badges, pass rates, paid status indicators, and success actions</small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="fas fa-save me-2"></i>Apply & Save System Palette
                            </button>
                            <button type="button" class="btn btn-outline-secondary px-3" onclick="resetPreview()">
                                <i class="fas fa-undo me-2"></i>Reset Preview
                            </button>
                        </div>
                    </form>

                    <form action="{{ route('settings.colors.reset') }}" method="POST" class="mt-4 pt-3 border-top" onsubmit="return confirm('Reset all colors across the entire project to default Emerald & Mint?')">
                        @csrf
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-semibold text-danger">Reset to Defaults</h6>
                                <small class="text-muted">Reverts to the default collegiate palette (#006159, #2e9f82, #d6ec8c, #68c170)</small>
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-danger px-3">
                                <i class="fas fa-sync-alt me-1"></i>Reset Defaults
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right Column: Interactive Real-Time Live Preview --}}
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 sticky-top" style="top: 85px; border-radius: 14px;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-eye me-2 text-primary"></i>Live Interface Preview</h5>
                    <span class="badge bg-success-subtle text-success small">Real-Time Simulation</span>
                </div>
                <div class="card-body p-4">
                    {{-- Preview 1: Header / Navbar --}}
                    <div class="p-3 text-white rounded-3 mb-3 shadow-sm" id="preview-header" 
                         style="background-color: {{ $settings->color_primary ?? '#006159' }}; transition: background-color 0.2s;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-university"></i>
                                <span class="fw-bold">College Management</span>
                            </div>
                            <span class="badge" id="preview-header-badge" 
                                  style="background-color: {{ $settings->color_highlight ?? '#d6ec8c' }}; color: #111;">
                                Active Session
                            </span>
                        </div>
                    </div>

                    {{-- Preview 2: Buttons Toolbar --}}
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold mb-1">BUTTONS & ACTIONS</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-sm text-white" id="preview-button-primary" 
                                    style="background-color: {{ $settings->color_primary ?? '#006159' }};">
                                <i class="fas fa-plus me-1"></i> Primary Action
                            </button>
                            <button class="btn btn-sm text-white" id="preview-button-secondary" 
                                    style="background-color: {{ $settings->color_secondary ?? '#2e9f82' }};">
                                <i class="fas fa-filter me-1"></i> Secondary
                            </button>
                            <button class="btn btn-sm" id="preview-button-outline" 
                                    style="color: {{ $settings->color_primary ?? '#006159' }}; border: 1px solid {{ $settings->color_primary ?? '#006159' }};">
                                Outline Button
                            </button>
                        </div>
                    </div>

                    {{-- Preview 3: Badges & Tags --}}
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold mb-1">BADGES & TAGS</label>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge" id="preview-badge-primary" style="background-color: {{ $settings->color_primary ?? '#006159' }};">
                                Primary Tag
                            </span>
                            <span class="badge" id="preview-badge-secondary" style="background-color: {{ $settings->color_secondary ?? '#2e9f82' }};">
                                Secondary Tag
                            </span>
                            <span class="badge" id="preview-badge-highlight" style="background-color: {{ $settings->color_highlight ?? '#d6ec8c' }}; color: #222;">
                                Highlight Tag
                            </span>
                            <span class="badge" id="preview-badge-success" style="background-color: {{ $settings->color_success ?? '#68c170' }};">
                                Active / Success
                            </span>
                        </div>
                    </div>

                    {{-- Preview 4: Executive Report KPI Metric Card --}}
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold mb-1">REPORT KPI CARD</label>
                        <div class="p-3 border rounded-3 bg-white shadow-sm" id="preview-kpi" 
                             style="border-left: 4px solid {{ $settings->color_primary ?? '#006159' }} !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small text-uppercase fw-bold">Executive Pass Rate</span>
                                    <h4 class="fw-bold mb-0 mt-1" id="preview-kpi-text" style="color: {{ $settings->color_primary ?? '#006159' }};">94.8%</h4>
                                </div>
                                <div class="p-2 rounded-circle" id="preview-kpi-icon" style="background-color: {{ $settings->color_secondary ?? '#2e9f82' }}22; color: {{ $settings->color_secondary ?? '#2e9f82' }};">
                                    <i class="fas fa-chart-line fa-lg"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Preview 5: Data Table --}}
                    <div>
                        <label class="form-label text-muted small fw-bold mb-1">UNIFIED DATA TABLE</label>
                        <div class="table-responsive border rounded-3 overflow-hidden shadow-sm">
                            <table class="table table-sm table-striped mb-0 text-center" style="font-size: 0.82rem;">
                                <thead id="preview-table-head" style="background-color: {{ $settings->color_primary ?? '#006159' }}; color: #fff;">
                                    <tr>
                                        <th class="py-2">Roll No</th>
                                        <th class="py-2 text-start">Student Name</th>
                                        <th class="py-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="font-monospace fw-bold">1042</td>
                                        <td class="text-start">Ayesha Khan</td>
                                        <td><span class="badge" id="preview-table-status" style="background-color: {{ $settings->color_success ?? '#68c170' }};">Enrolled</span></td>
                                    </tr>
                                    <tr>
                                        <td class="font-monospace fw-bold">1043</td>
                                        <td class="text-start">Zainab Fatima</td>
                                        <td><span class="badge" style="background-color: {{ $settings->color_secondary ?? '#2e9f82' }};">Promoted</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.preset-card:hover {
    border-color: var(--col-primary) !important;
    background-color: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}
</style>

<script>
function syncColorPicker(colorName, hexValue) {
    if (/^#[0-9A-Fa-f]{6}$/.test(hexValue)) {
        document.getElementById('color_' + colorName).value = hexValue;
        document.getElementById('color_' + colorName + '_text').value = hexValue;
        updatePreview();
    }
}

function applyPreset(primary, secondary, highlight, success, presetName) {
    document.getElementById('color_primary').value = primary;
    document.getElementById('color_primary_text').value = primary;
    document.getElementById('color_secondary').value = secondary;
    document.getElementById('color_secondary_text').value = secondary;
    document.getElementById('color_highlight').value = highlight;
    document.getElementById('color_highlight_text').value = highlight;
    document.getElementById('color_success').value = success;
    document.getElementById('color_success_text').value = success;

    document.getElementById('activePresetBadge').innerText = presetName;
    document.getElementById('activePresetBadge').className = 'badge bg-primary-subtle text-primary small';

    updatePreview();
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

    // Header & Badges
    document.getElementById('preview-header').style.backgroundColor = primary;
    document.getElementById('preview-header-badge').style.backgroundColor = highlight;
    document.getElementById('preview-badge-primary').style.backgroundColor = primary;
    document.getElementById('preview-badge-secondary').style.backgroundColor = secondary;
    document.getElementById('preview-badge-highlight').style.backgroundColor = highlight;
    document.getElementById('preview-badge-success').style.backgroundColor = success;

    // Buttons
    document.getElementById('preview-button-primary').style.backgroundColor = primary;
    document.getElementById('preview-button-secondary').style.backgroundColor = secondary;
    document.getElementById('preview-button-outline').style.color = primary;
    document.getElementById('preview-button-outline').style.borderColor = primary;

    // KPI Card
    document.getElementById('preview-kpi').style.borderLeftColor = primary;
    document.getElementById('preview-kpi-text').style.color = primary;
    document.getElementById('preview-kpi-icon').style.color = secondary;
    document.getElementById('preview-kpi-icon').style.backgroundColor = secondary + '22';

    // Table
    document.getElementById('preview-table-head').style.backgroundColor = primary;
    document.getElementById('preview-table-status').style.backgroundColor = success;
}

function resetPreview() {
    document.getElementById('color_primary').value = '{{ $settings->color_primary ?? "#006159" }}';
    document.getElementById('color_secondary').value = '{{ $settings->color_secondary ?? "#2e9f82" }}';
    document.getElementById('color_highlight').value = '{{ $settings->color_highlight ?? "#d6ec8c" }}';
    document.getElementById('color_success').value = '{{ $settings->color_success ?? "#68c170" }}';
    updatePreview();
}
</script>
@endsection
