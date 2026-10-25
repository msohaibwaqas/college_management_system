@php
    $themeColors = \App\Models\InstituteSetting::getColors();
    $primary = $themeColors['primary'] ?? '#006159';
    $secondary = $themeColors['secondary'] ?? '#2e9f82';
    $highlight = $themeColors['highlight'] ?? '#d6ec8c';
    $success = $themeColors['success'] ?? '#68c170';
@endphp
<style id="dynamic-theme-style">
:root {
    /* ── Base Theme Palette (Dynamically Controlled via Admin Settings) ── */
    --col-primary: {{ $primary }};
    --col-secondary: {{ $secondary }};
    --col-accent: {{ $secondary }};
    --col-highlight: {{ $highlight }};
    --col-success: {{ $success }};

    /* ── Semantic Mappings ── */
    --primary-color: var(--col-primary);
    --secondary-color: var(--col-secondary);
    --accent-color: var(--col-secondary);
    --col-sidebar-bg: var(--col-primary);

    /* ── Mathematical Color Variations via CSS color-mix ── */
    --col-hover: color-mix(in srgb, {{ $primary }} 85%, white);
    --col-active: color-mix(in srgb, {{ $primary }} 75%, black);
    --col-dark: color-mix(in srgb, {{ $primary }} 65%, black);
    --col-deep-dark: color-mix(in srgb, {{ $primary }} 28%, #040912);
    --col-mint: color-mix(in srgb, {{ $secondary }} 55%, white);
    --col-light-bg: color-mix(in srgb, {{ $primary }} 9%, white);
    --col-subtle: color-mix(in srgb, {{ $primary }} 5%, white);
    --col-primary-subtle: color-mix(in srgb, {{ $primary }} 10%, white);
    --col-secondary-subtle: color-mix(in srgb, {{ $secondary }} 12%, white);
    --col-highlight-subtle: color-mix(in srgb, {{ $highlight }} 18%, white);
    --col-success-subtle: color-mix(in srgb, {{ $success }} 12%, white);
    --col-border: color-mix(in srgb, {{ $primary }} 18%, #e2e8f0);
    --col-card-border: color-mix(in srgb, {{ $primary }} 12%, #e5e7eb);

    /* ── Table & Report Theme Tokens ── */
    --col-table-head-bg: color-mix(in srgb, {{ $primary }} 90%, black);
    --col-table-head-text: #ffffff;
    --col-table-hover: color-mix(in srgb, {{ $primary }} 5%, #ffffff);
    --col-table-stripe: color-mix(in srgb, {{ $primary }} 2.5%, #ffffff);
}

/* ── Typography & Global Elements ── */
body {
    font-family: 'Outfit', sans-serif;
    color: #334155;
}

/* ── Buttons Unified Palette ── */
.btn-primary {
    background-color: var(--col-primary) !important;
    border-color: var(--col-primary) !important;
    color: #ffffff !important;
    box-shadow: 0 2px 4px color-mix(in srgb, var(--col-primary) 30%, transparent);
}
.btn-primary:hover, .btn-primary:focus, .btn-primary:active, .btn-primary.active {
    background-color: var(--col-dark) !important;
    border-color: var(--col-dark) !important;
    color: #ffffff !important;
}
.btn-outline-primary {
    color: var(--col-primary) !important;
    border-color: var(--col-primary) !important;
    background-color: transparent !important;
}
.btn-outline-primary:hover, .btn-outline-primary:focus, .btn-outline-primary:active {
    background-color: var(--col-primary) !important;
    border-color: var(--col-primary) !important;
    color: #ffffff !important;
}

.btn-secondary {
    background-color: var(--col-secondary) !important;
    border-color: var(--col-secondary) !important;
    color: #ffffff !important;
}
.btn-secondary:hover, .btn-secondary:focus, .btn-secondary:active {
    background-color: color-mix(in srgb, var(--col-secondary) 85%, black) !important;
    border-color: color-mix(in srgb, var(--col-secondary) 85%, black) !important;
    color: #ffffff !important;
}
.btn-outline-secondary {
    color: var(--col-secondary) !important;
    border-color: var(--col-secondary) !important;
}
.btn-outline-secondary:hover {
    background-color: var(--col-secondary) !important;
    color: #ffffff !important;
}

.btn-success {
    background-color: var(--col-success) !important;
    border-color: var(--col-success) !important;
    color: #ffffff !important;
}
.btn-success:hover {
    background-color: color-mix(in srgb, var(--col-success) 85%, black) !important;
    border-color: color-mix(in srgb, var(--col-success) 85%, black) !important;
}

/* ── Utility Classes & Text ── */
.bg-primary {
    background-color: var(--col-primary) !important;
    color: #ffffff !important;
}
.text-primary {
    color: var(--col-primary) !important;
}
.border-primary {
    border-color: var(--col-primary) !important;
}
.bg-primary-subtle {
    background-color: var(--col-primary-subtle) !important;
    color: var(--col-primary) !important;
}

.bg-secondary {
    background-color: var(--col-secondary) !important;
    color: #ffffff !important;
}
.text-secondary {
    color: var(--col-secondary) !important;
}
.border-secondary {
    border-color: var(--col-secondary) !important;
}
.bg-secondary-subtle {
    background-color: var(--col-secondary-subtle) !important;
    color: var(--col-secondary) !important;
}

.link-primary {
    color: var(--col-primary) !important;
}
.link-primary:hover {
    color: var(--col-dark) !important;
}

/* ── Cards & Containers ── */
.card-header.bg-primary,
.card-header-theme {
    background-color: var(--col-primary) !important;
    color: #ffffff !important;
    border-bottom: none !important;
}
.card {
    border-color: var(--col-card-border);
}

/* ── Form Controls & Focus ── */
.form-control:focus,
.form-select:focus {
    border-color: var(--col-secondary) !important;
    box-shadow: 0 0 0 0.25rem color-mix(in srgb, var(--col-secondary) 25%, transparent) !important;
}
.form-check-input:checked {
    background-color: var(--col-primary) !important;
    border-color: var(--col-primary) !important;
}
.form-range::-webkit-slider-thumb {
    background-color: var(--col-primary) !important;
}

/* ── Navs, Tabs & Pagination ── */
.nav-pills .nav-link.active,
.nav-pills .show > .nav-link {
    background-color: var(--col-primary) !important;
    color: #ffffff !important;
}
.nav-tabs .nav-link.active {
    color: var(--col-primary) !important;
    border-bottom: 2px solid var(--col-primary) !important;
    font-weight: 600;
}
.page-item.active .page-link {
    background-color: var(--col-primary) !important;
    border-color: var(--col-primary) !important;
    color: #ffffff !important;
}
.page-link {
    color: var(--col-primary);
}
.page-link:hover {
    color: var(--col-dark);
}

/* ── Badges ── */
.badge.bg-primary {
    background-color: var(--col-primary) !important;
    color: #ffffff !important;
}
.badge.bg-secondary {
    background-color: var(--col-secondary) !important;
    color: #ffffff !important;
}

/* ── Unified Tables & Report Data Tables ── */
.table thead th,
.table-theme thead th,
thead.table-dark th,
.table-dark th {
    background-color: var(--col-table-head-bg) !important;
    color: var(--col-table-head-text) !important;
    border-color: color-mix(in srgb, var(--col-table-head-bg) 80%, black) !important;
    font-weight: 600;
    letter-spacing: 0.3px;
    vertical-align: middle;
}
.table-primary,
.table-primary > th,
.table-primary > td {
    background-color: var(--col-primary-subtle) !important;
    color: var(--col-dark) !important;
}
.table-hover > tbody > tr:hover > * {
    background-color: var(--col-table-hover) !important;
}
.table-striped > tbody > tr:nth-of-type(odd) > * {
    background-color: var(--col-table-stripe);
}

/* ── Sidebar (Admin & Internal Views) ── */
.sidebar {
    background: linear-gradient(180deg, var(--col-deep-dark) 0%, color-mix(in srgb, var(--col-primary) 32%, #040912) 100%) !important;
    border-right: 1px solid color-mix(in srgb, var(--col-primary) 22%, rgba(255,255,255,0.06)) !important;
}
.sidebar-brand {
    background: var(--col-deep-dark) !important;
    border-bottom: 1px solid color-mix(in srgb, var(--col-primary) 22%, rgba(255,255,255,0.08)) !important;
}
.sidebar-brand i {
    color: var(--col-secondary) !important;
}
.sidebar-menu a i {
    color: var(--col-secondary) !important;
}
.sidebar-menu a:hover {
    background: color-mix(in srgb, var(--col-primary) 25%, transparent) !important;
    color: #ffffff !important;
}
.sidebar-menu a.active {
    background: color-mix(in srgb, var(--col-primary) 42%, transparent) !important;
    color: #ffffff !important;
    border-left: 4px solid var(--col-secondary) !important;
}
.sidebar-menu > li {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
}
.submenu {
    background: rgba(0, 0, 0, 0.28) !important;
}
.submenu a:hover {
    background: color-mix(in srgb, var(--col-primary) 32%, transparent) !important;
    color: #ffffff !important;
}
.submenu a.active {
    color: var(--col-mint) !important;
    background: color-mix(in srgb, var(--col-primary) 48%, transparent) !important;
    border-left: 3px solid var(--col-secondary) !important;
}
.submenu a.active::before {
    background: var(--col-secondary) !important;
}

/* ── Topbar ── */
.topbar {
    border-bottom: 1px solid var(--col-border) !important;
    background: #ffffff !important;
}

/* ── Public Layout & Navbar Elements ── */
.navbar {
    background-color: var(--col-primary) !important;
}
.navbar.scrolled {
    background-color: rgba(5, 15, 30, 0.75) !important;
    backdrop-filter: blur(16px) saturate(180%) !important;
    -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
    box-shadow: 0 4px 30px rgba(0,0,0,0.3) !important;
}
.public-footer {
    background: linear-gradient(180deg, color-mix(in srgb, var(--col-primary) 24%, #071322) 0%, #030811 100%) !important;
}
.footer-wave-path {
    fill: color-mix(in srgb, var(--col-primary) 24%, #071322);
}
.public-footer::before {
    background: linear-gradient(90deg, var(--col-highlight), var(--col-secondary), var(--col-highlight)) !important;
}
.section-title::after {
    background-color: var(--col-secondary) !important;
}
.nav-link:hover, .nav-link.active {
    color: var(--col-highlight) !important;
}
.nav-link::after {
    background-color: var(--col-highlight) !important;
}
#backToTop {
    background: var(--col-primary) !important;
}
#backToTop:hover {
    background: var(--col-dark) !important;
}
.btn-public-login {
    background-color: var(--col-highlight) !important;
    color: color-mix(in srgb, var(--col-primary) 80%, black) !important;
    font-weight: 600;
}
.btn-public-login:hover {
    background-color: color-mix(in srgb, var(--col-highlight) 85%, white) !important;
    color: #000 !important;
}

/* ── Unified Report Components ── */
.report-hero-card {
    border-radius: 14px;
    background: linear-gradient(135deg, var(--col-deep-dark) 0%, var(--col-primary) 100%) !important;
    color: #ffffff !important;
    border: none !important;
}
.report-kpi-card {
    border-radius: 12px;
    border: 1px solid var(--col-card-border);
    border-left: 4px solid var(--col-primary) !important;
    background: #ffffff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.report-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.06);
}
.report-kpi-card.secondary {
    border-left-color: var(--col-secondary) !important;
}
.report-kpi-card.highlight {
    border-left-color: var(--col-highlight) !important;
}
.report-kpi-card.success {
    border-left-color: var(--col-success) !important;
}
</style>
