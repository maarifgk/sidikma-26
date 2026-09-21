<style>
    :root {
        --ui-green-50:#ecfdf3;--ui-green-100:#d1fadf;--ui-green-600:#079455;--ui-green-700:#067647;--ui-green-800:#05603a;
        --ui-blue-50:#eff8ff;--ui-blue-100:#d1e9ff;--ui-blue-600:#1570ef;--ui-blue-700:#175cd3;
        --ui-yellow-100:#fef3c7;--ui-yellow-300:#fde68a;--ui-yellow-400:#facc15;--ui-yellow-900:#713f12;
        --ui-ink:#101828;--ui-muted:#475467;--ui-border:#eaecf0;--ui-border-strong:#d0d5dd;
        --ui-surface:#fff;--ui-canvas:#f6f9fc;--ui-danger:#d92d20;--ui-radius:.9rem;--ui-shadow:0 1px 3px rgba(16,24,40,.06),0 4px 12px rgba(16,24,40,.04);
    }

    body:not(:has(.teacher-mobile)) { color:var(--ui-ink); }
    body:not(:has(.teacher-mobile)) .fi-main { background:linear-gradient(180deg,#edf8f5 0,var(--ui-canvas) 12rem); }
    body:not(:has(.teacher-mobile)) .fi-main-ctn { min-width:0; }
    body:not(:has(.teacher-mobile)) .fi-page { gap:1.25rem; }
    body:not(:has(.teacher-mobile)) .fi-page-header { gap:.85rem; }
    body:not(:has(.teacher-mobile)) .fi-header-heading { color:var(--ui-ink);font-size:clamp(1.45rem,2vw,2rem);font-weight:750;letter-spacing:-.025em; }
    body:not(:has(.teacher-mobile)) .fi-header-subheading { max-width:52rem;color:var(--ui-muted);font-size:.875rem;line-height:1.6; }
    body:not(:has(.teacher-mobile)) .fi-breadcrumbs { color:#8291a5;font-size:.75rem; }

    body:not(:has(.teacher-mobile)) .fi-sidebar { border-inline-end:0;background:linear-gradient(180deg,var(--ui-green-800),#04704a 55%,#05603a);box-shadow:8px 0 24px rgba(5,96,58,.12); }
    body:not(:has(.teacher-mobile)) .fi-topbar { border-bottom:1px solid rgba(23,104,181,.1);background:rgba(255,255,255,.92);box-shadow:0 4px 18px rgba(18,59,115,.045);backdrop-filter:blur(12px); }
    body:not(:has(.teacher-mobile)) .fi-sidebar-item-btn { border-radius:.65rem;transition:background-color .18s ease,color .18s ease,box-shadow .18s ease; }
    body:not(:has(.teacher-mobile)) .fi-sidebar-item-btn:hover { background:rgba(255,255,255,.11); }
    body:not(:has(.teacher-mobile)) .fi-sidebar-item.fi-active>.fi-sidebar-item-btn { background:var(--ui-yellow-400);box-shadow:0 5px 13px rgba(113,63,18,.18); }
    body:not(:has(.teacher-mobile)) .fi-sidebar-item.fi-sidebar-item-has-active-child-items>.fi-sidebar-item-btn { background:rgba(250,204,21,.16); }
    body:not(:has(.teacher-mobile)) .fi-sidebar-group-label { color:rgba(255,255,255,.62);font-size:.68rem;font-weight:750;letter-spacing:.08em; }
    body:not(:has(.teacher-mobile)) .fi-sidebar :is(.fi-sidebar-item-label,.fi-sidebar-item-btn>.fi-icon) { color:rgba(255,255,255,.9); }
    body:not(:has(.teacher-mobile)) .fi-sidebar .fi-sidebar-item.fi-active>.fi-sidebar-item-btn :is(.fi-sidebar-item-label,.fi-icon) { color:var(--ui-yellow-900); }
    body:not(:has(.teacher-mobile)) .fi-sidebar .fi-sidebar-item.fi-sidebar-item-has-active-child-items>.fi-sidebar-item-btn :is(.fi-sidebar-item-label,.fi-icon) { color:var(--ui-yellow-300); }
    /* The original 3:2 logo includes vertical breathing room in its canvas. */
    body:not(:has(.teacher-mobile)) .fi-sidebar-header { position:relative;height:104px;min-height:104px;justify-content:center;padding:2px 16px;border-bottom:1px solid var(--ui-border);background:#fff; }
    body:not(:has(.teacher-mobile)) .fi-sidebar-header-logo-ctn { flex:0 1 80%;min-width:0; }
    body:not(:has(.teacher-mobile)) .fi-sidebar-header-logo-ctn a { display:flex;align-items:center;justify-content:center; }
    body:not(:has(.teacher-mobile)) .fi-sidebar-header .fi-logo { display:block;width:150px;max-width:100%;height:auto!important;object-fit:contain;margin-inline:auto;color:var(--ui-green-800); }
    body:not(:has(.teacher-mobile)) .fi-sidebar-header > .fi-icon-btn { position:absolute;inset-inline-start:12px;top:50%;transform:translateY(-50%); }
    body:not(:has(.teacher-mobile)) .fi-sidebar-nav { padding-top:16px; }
    @media (min-width:64rem) {
        /* Filament renders the desktop sidebar brand inside the existing topbar. */
        body.fi-body-has-topbar:not(:has(.teacher-mobile)) .fi-topbar { min-height:104px; }
        body.fi-body-has-topbar:not(:has(.teacher-mobile)) .fi-sidebar { top:104px;height:calc(100dvh - 104px); }
        body:not(:has(.teacher-mobile)) .fi-topbar-start { position:relative;flex-shrink:0;justify-content:center;width:var(--sidebar-width);height:103px;margin-inline-start:-16px;margin-inline-end:16px;padding-inline:16px;background:#fff; }
        body:not(:has(.teacher-mobile)) .fi-topbar-start > a { display:flex;align-items:center;justify-content:center;max-width:80%; }
        body:not(:has(.teacher-mobile)) .fi-topbar-start .fi-logo { display:block;width:150px;max-width:100%;height:auto!important;object-fit:contain;margin-inline:0; }
        body:not(:has(.teacher-mobile)) .fi-topbar-collapse-sidebar-btn-ctn { position:absolute;inset-inline-start:12px;top:50%;transform:translateY(-50%); }
        body:not(:has(.teacher-mobile)):has(.fi-sidebar:not(.fi-sidebar-open)) .fi-topbar-start { width:var(--collapsed-sidebar-width); }
        body:not(:has(.teacher-mobile)):has(.fi-sidebar:not(.fi-sidebar-open)) .fi-topbar-start > :is(a,.fi-logo) { display:none; }
        body:not(:has(.teacher-mobile)):has(.fi-sidebar:not(.fi-sidebar-open)) .fi-topbar-collapse-sidebar-btn-ctn { inset-inline-start:50%;transform:translate(-50%,-50%); }
    }
    @media (max-width:63.999rem) {
        body:not(:has(.teacher-mobile)) .fi-sidebar { max-width:calc(100vw - 3rem); }
        body:not(:has(.teacher-mobile)) .fi-sidebar-header .fi-logo { width:clamp(135px,18vw,150px); }
    }
    body:not(:has(.teacher-mobile)) .fi-sidebar-footer { border-top:1px solid rgba(255,255,255,.1);padding-top:.75rem; }

    /* Submenu cukup memakai penanda titik, tanpa garis penghubung atau lengkung aktif. */
    body:not(:has(.teacher-mobile)) .fi-sidebar-item-btn:has(> .fi-sidebar-item-grouped-border) { background:transparent;box-shadow:none; }
    body:not(:has(.teacher-mobile)) .fi-sidebar-item-btn:has(> .fi-sidebar-item-grouped-border):hover { background:rgba(255,255,255,.1); }
    body:not(:has(.teacher-mobile)) .fi-sidebar-item.fi-active > .fi-sidebar-item-btn:has(> .fi-sidebar-item-grouped-border) { background:var(--ui-yellow-400);box-shadow:0 5px 13px rgba(113,63,18,.18); }
    body:not(:has(.teacher-mobile)) :is(.fi-sidebar-item-grouped-border-part-not-first,.fi-sidebar-item-grouped-border-part-not-last) { display:none; }
    body:not(:has(.teacher-mobile)) .fi-sidebar-item-grouped-border-part { background:rgba(255,255,255,.55); }
    body:not(:has(.teacher-mobile)) .fi-sidebar-item.fi-active .fi-sidebar-item-grouped-border-part { background:var(--ui-yellow-900); }

    /* Kedua dashboard memakai identitas visual hijau-biru yang sama. */
    body:not(:has(.teacher-mobile)) .md-dashboard .md-top,
    body:not(:has(.teacher-mobile)) .sd-dashboard .sd-hero {
        border:1px solid #ccebdc;border-radius:1rem;background:linear-gradient(115deg,#ecfdf3 0%,#eff8ff 62%,#d1e9ff 100%);box-shadow:var(--ui-shadow);color:var(--ui-ink);
    }
    body:not(:has(.teacher-mobile)) .md-dashboard .md-top { position:relative;overflow:hidden;padding:1.35rem 1.5rem; }
    body:not(:has(.teacher-mobile)) .md-dashboard .md-top::after { position:absolute;width:15rem;height:15rem;right:-5rem;top:-8rem;border-radius:50%;background:rgba(7,148,85,.08);content:""; }
    body:not(:has(.teacher-mobile)) .md-dashboard .md-top p,
    body:not(:has(.teacher-mobile)) .md-dashboard .md-clock span { color:var(--ui-muted);opacity:1; }
    body:not(:has(.teacher-mobile)) .md-dashboard .md-clock { position:relative;z-index:1;color:var(--ui-blue-700); }
    body:not(:has(.teacher-mobile)) .md-dashboard .md-card,
    body:not(:has(.teacher-mobile)) .sd-dashboard :is(.sd-stat,.sd-panel) { border:1px solid var(--ui-border);border-radius:var(--ui-radius);box-shadow:var(--ui-shadow); }
    body:not(:has(.teacher-mobile)) .md-dashboard .md-panel-title { background:linear-gradient(90deg,var(--ui-green-700),var(--ui-blue-600)); }

    body:not(:has(.teacher-mobile)) :is(.fi-section,.fi-ta-ctn,.ui-card) { border:1px solid var(--ui-border);border-radius:var(--ui-radius);background:var(--ui-surface);box-shadow:var(--ui-shadow); }
    body:not(:has(.teacher-mobile)) .fi-section-header { border-bottom:1px solid #e8eef4;padding-block:1rem; }
    body:not(:has(.teacher-mobile)) .fi-section-header-heading { color:var(--ui-ink);font-weight:700; }
    body:not(:has(.teacher-mobile)) .fi-section-header-description { color:var(--ui-muted);line-height:1.55; }
    body:not(:has(.teacher-mobile)) .fi-section-content { padding-block:1.15rem; }

    body:not(:has(.teacher-mobile)) :is(.fi-input-wrp,.ui-input,.ui-select,.ui-textarea) { border-color:var(--ui-border-strong);border-radius:.6rem;background:#fff;box-shadow:0 1px 2px rgba(23,38,61,.025);transition:border-color .16s ease,box-shadow .16s ease,background-color .16s ease; }
    body:not(:has(.teacher-mobile)) :is(.fi-input-wrp,.ui-input,.ui-select):focus-within,
    body:not(:has(.teacher-mobile)) :is(.ui-input,.ui-select,.ui-textarea):focus { border-color:var(--ui-blue-600);box-shadow:0 0 0 3px rgba(23,104,181,.12);outline:0; }
    body:not(:has(.teacher-mobile)) :is(.fi-input,.fi-select-input,.ui-input,.ui-select) { min-height:2.65rem; }
    body:not(:has(.teacher-mobile)) .fi-fo-field-label-content,
    body:not(:has(.teacher-mobile)) .ui-label { color:#33455e;font-size:.78rem;font-weight:650;letter-spacing:.01em; }
    /* Form Data Kepala Madrasah/Sekolah: padat, responsif, dan terisolasi dari resource lain. */
    body:not(:has(.teacher-mobile)) .fi-main:has(.school-head-form) { padding-inline:clamp(1.25rem,2vw,1.5rem); }
    body:not(:has(.teacher-mobile)) .fi-page:has(.school-head-form) { width:100%;max-width:none;gap:1rem; }
    body:not(:has(.teacher-mobile)) .school-head-form {
        align-items:start;
        gap:1rem;
    }
    body:not(:has(.teacher-mobile)) .school-head-section {
        align-self:start;
        height:auto;
        min-height:0;
    }
    body:not(:has(.teacher-mobile)) .school-head-section > .fi-section {
        height:auto;
        min-height:0;
        overflow:hidden;
        border:1px solid #e2e8f0;
        border-radius:.75rem;
        background:#fff;
        box-shadow:0 1px 3px rgba(15,23,42,.04);
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-section-header {
        min-height:3.25rem;
        align-items:center;
        border-bottom:1px solid #e5e7eb;
        padding:.875rem 1rem;
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-section-header-heading {
        color:var(--ui-green-700);
        font-size:.95rem;
        font-weight:650;
        line-height:1.35;
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-section-header-description {
        margin-top:.2rem;
        color:#64748b;
        font-size:.75rem;
        line-height:1.45;
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-section-header > .fi-icon {
        color:var(--ui-green-700);
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-section-content {
        padding:1rem;
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-sc {
        gap:.875rem;
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-fo-field {
        min-width:0;
        gap:.375rem;
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-fo-field-label-content {
        color:#334155;
        font-size:.8125rem;
        font-weight:650;
        line-height:1.35;
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-fo-field-label-required-mark { color:#dc2626; }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-input-wrp {
        min-height:2.875rem;
        border:1px solid #111827!important;
        border-radius:.5rem;
        background:#fff;
        box-shadow:none!important;
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-input-wrp:hover { border-color:#000!important; }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-input-wrp:focus-within {
        border-color:#000!important;
        box-shadow:0 0 0 2px rgba(0,0,0,.14)!important;
    }
    body:not(:has(.teacher-mobile)) .school-head-section :is(.fi-input,.fi-select-input) {
        min-height:2.75rem;
        padding-inline:.875rem;
        font-size:.875rem;
    }
    body:not(:has(.teacher-mobile)) .school-head-section--profile .fi-input-wrp {
        min-height:3.5rem;
    }
    body:not(:has(.teacher-mobile)) .school-head-section--profile :is(.fi-input,.fi-select-input) {
        min-height:3.375rem;
        padding-block:.75rem;
        font-size:.9rem;
    }
    body:not(:has(.teacher-mobile)) .school-head-section textarea.fi-input {
        min-height:4.75rem;
        padding-block:.7rem;
        line-height:1.5;
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-fo-repeater {
        gap:.75rem;
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-fo-repeater-items { gap:.75rem; }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-fo-repeater-item {
        overflow:hidden;
        border:1px solid #dce5ec;
        border-radius:.625rem;
        background:#fbfdfc;
        box-shadow:none;
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-fo-repeater-item-header {
        min-height:2.75rem;
        border-bottom-color:#e2e8f0;
        background:#f8fafc;
        padding:.55rem .75rem;
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-fo-repeater-item-content { padding:.875rem; }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-fo-repeater-add {
        justify-content:flex-start;
        margin-top:0;
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-fo-repeater-add .fi-btn {
        min-height:2.5rem;
        border-color:#a7d8be;
        border-radius:.5rem;
        background:#fff;
        padding-inline:.9rem;
        color:var(--ui-green-700);
        font-size:.8rem;
        font-weight:650;
        box-shadow:none;
    }
    body:not(:has(.teacher-mobile)) .school-head-section .fi-fo-repeater-add .fi-btn:hover {
        border-color:#6bc397;
        background:var(--ui-green-50);
        box-shadow:0 2px 6px rgba(7,118,71,.08);
    }
    body:not(:has(.teacher-mobile)) .school-head-section--document .fi-fo-file-upload-input-ctn,
    body:not(:has(.teacher-mobile)) .school-head-section--document .filepond--panel-root {
        border-radius:.5rem;
        background:#f8fafc;
    }
    body:not(:has(.teacher-mobile)) .school-head-section--document .fi-fo-file-upload-input-ctn {
        min-height:8.25rem;
        border:1px dashed #cbd5e1;
        transition:background-color .16s ease,border-color .16s ease;
    }
    body:not(:has(.teacher-mobile)) .school-head-section--document .fi-fo-file-upload-input-ctn:hover {
        border-color:#7bc49f;
        background:#f0fdf4;
    }
    body:not(:has(.teacher-mobile)) .fi-page:has(.school-head-form) .fi-sc-actions[id*="form-actions"] {
        margin-top:0;
    }
    body:not(:has(.teacher-mobile)) .fi-page:has(.school-head-form) .fi-sc-actions[id*="form-actions"] .fi-ac {
        flex-wrap:wrap;
        gap:.625rem;
    }
    body:not(:has(.teacher-mobile)) .fi-page:has(.school-head-form) .fi-sc-actions[id*="form-actions"] .fi-btn {
        min-height:2.75rem;
        border-radius:.5rem;
        padding-inline:1.25rem;
    }
    body:not(:has(.teacher-mobile)) .fi-fo-field-wrp-error-message,
    body:not(:has(.teacher-mobile)) .ui-error { color:var(--ui-danger);font-size:.73rem; }

    body:not(:has(.teacher-mobile)) .fi-btn { min-height:2.4rem;border-radius:.6rem;font-weight:650;transition:transform .16s ease,box-shadow .16s ease,background-color .16s ease; }
    body:not(:has(.teacher-mobile)) .fi-btn:hover { box-shadow:0 4px 10px rgba(23,55,89,.12); }
    body:not(:has(.teacher-mobile)) .fi-btn:active { transform:translateY(1px); }
    .ui-btn { display:inline-flex;min-height:2.65rem;align-items:center;justify-content:center;gap:.45rem;border:1px solid transparent;border-radius:.6rem;padding:.6rem 1rem;font-size:.8rem;font-weight:700;transition:background-color .16s ease,border-color .16s ease,box-shadow .16s ease; }
    .ui-btn:focus-visible { outline:3px solid rgba(23,104,181,.2);outline-offset:2px; }
    .ui-btn-primary { background:var(--ui-green-600);color:#fff; }
    .ui-btn-primary:hover { background:var(--ui-green-700);box-shadow:0 4px 12px rgba(8,116,67,.18); }
    .ui-btn-secondary { border-color:var(--ui-border-strong);background:#fff;color:#465a74; }
    .ui-btn-secondary:hover { border-color:#aebfd0;background:#f8fafc; }
    .ui-btn-danger { background:#dc3545;color:#fff; }
    .ui-btn-danger:hover { background:#bd2635; }

    body:not(:has(.teacher-mobile)) .fi-ta-ctn { overflow:hidden; }
    body:not(:has(.teacher-mobile)) .fi-ta-header { border-radius:var(--ui-radius) var(--ui-radius) 0 0;background:#fff; }
    body:not(:has(.teacher-mobile)) .fi-ta-table { min-width:100%; }
    body:not(:has(.teacher-mobile)) .fi-ta-table>thead>tr { background:linear-gradient(90deg,var(--ui-green-50),var(--ui-blue-50)); }
    body:not(:has(.teacher-mobile)) .fi-ta-header-cell { border-bottom:1px solid var(--ui-border);color:#40536d;font-size:.7rem;font-weight:750;letter-spacing:.035em;text-transform:uppercase; }
    body:not(:has(.teacher-mobile)) .fi-ta-row { transition:background-color .16s ease; }
    body:not(:has(.teacher-mobile)) .fi-ta-row:nth-child(even) { background:#fbfcfe; }
    body:not(:has(.teacher-mobile)) .fi-ta-row:hover { background:#f4f8fc; }
    body:not(:has(.teacher-mobile)) .fi-ta-cell { border-bottom-color:#e7edf3; }
    body:not(:has(.teacher-mobile)) .fi-pagination { border-top-color:var(--ui-border);background:#fbfcfe; }
    body:not(:has(.teacher-mobile)) .fi-ta-empty-state { padding-block:3.5rem; }
    body:not(:has(.teacher-mobile)) .fi-ta-empty-state-heading { color:var(--ui-ink);font-weight:700; }

    body:not(:has(.teacher-mobile)) :is(.fi-modal-window,.fi-modal-content) { border-radius:1rem; }
    body:not(:has(.teacher-mobile)) .fi-modal-window { border:1px solid var(--ui-border);box-shadow:0 24px 65px rgba(15,34,59,.18); }
    body:not(:has(.teacher-mobile)) .fi-modal-header { border-bottom:1px solid #edf1f5; }
    body:not(:has(.teacher-mobile)) .fi-modal-footer { border-top:1px solid #edf1f5;background:#fafcfe; }
    body:not(:has(.teacher-mobile)) .fi-dropdown-panel { border:1px solid var(--ui-border);border-radius:.7rem;box-shadow:0 12px 35px rgba(15,34,59,.13); }
    body:not(:has(.teacher-mobile)) .fi-badge { border-radius:999px;font-weight:650; }

    .ui-page { display:grid;gap:1.25rem; }
    .ui-page-intro { display:flex;align-items:center;justify-content:space-between;gap:1rem;border:1px solid #dbe8f2;border-radius:var(--ui-radius);background:linear-gradient(110deg,var(--ui-green-50),var(--ui-blue-50));padding:1.1rem 1.25rem; }
    .ui-page-intro h2 { color:var(--ui-ink);font-size:1rem;font-weight:750; }
    .ui-page-intro p { margin-top:.25rem;color:var(--ui-muted);font-size:.78rem;line-height:1.5; }
    .ui-card { overflow:hidden; }
    .ui-card-head { display:flex;align-items:start;justify-content:space-between;gap:1rem;border-bottom:1px solid #e8eef4;padding:1rem 1.15rem; }
    .ui-card-head h2 { color:var(--ui-ink);font-size:.92rem;font-weight:750; }
    .ui-card-head p { margin-top:.2rem;color:var(--ui-muted);font-size:.75rem; }
    .ui-card-body { padding:1.15rem; }
    .ui-filter-grid { display:grid;grid-template-columns:minmax(13rem,1fr) minmax(13rem,1fr) auto;align-items:end;gap:1rem; }
    .ui-field { display:grid;gap:.4rem;min-width:0; }
    .ui-input,.ui-select,.ui-textarea { width:100%;padding:.55rem .8rem;color:var(--ui-ink);font-size:.82rem; }
    .ui-actions { display:flex;flex-wrap:wrap;align-items:center;gap:.65rem; }
    .ui-filter-actions { display:flex;gap:.55rem; }
    .ui-edit-grid { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem; }
    .ui-form-grid { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem; }
    .ui-span-full { grid-column:1/-1; }
    .ui-table-wrap { width:100%;overflow-x:auto; }
    .ui-table { width:100%;min-width:42rem;border-collapse:separate;border-spacing:0; }
    .ui-table th { border-bottom:1px solid var(--ui-border);background:linear-gradient(90deg,var(--ui-green-50),var(--ui-blue-50));padding:.78rem 1rem;color:#40536d;font-size:.7rem;font-weight:750;letter-spacing:.035em;text-align:left;text-transform:uppercase; }
    .ui-table td { border-bottom:1px solid #e8eef4;padding:.82rem 1rem;color:#40536d;font-size:.8rem; }
    .ui-table tbody tr:nth-child(even) { background:#fbfcfe; }
    .ui-table tbody tr:hover { background:#f4f8fc; }
    .ui-table tbody tr:last-child td { border-bottom:0; }
    .ui-text-right { text-align:right!important; }
    .ui-money { color:#263c58;font-variant-numeric:tabular-nums;font-weight:650;white-space:nowrap; }
    .ui-badge { display:inline-flex;align-items:center;border-radius:999px;padding:.25rem .55rem;font-size:.69rem;font-weight:700; }
    .ui-badge-success { background:#e3f4ea;color:#087443; }
    .ui-badge-muted { background:#edf1f5;color:#65758b; }
    .ui-empty { padding:2.5rem 1.25rem!important;color:var(--ui-muted)!important;text-align:center!important; }
    .ui-fee-group+.ui-fee-group { border-top:1px solid var(--ui-border); }
    .ui-fee-heading,.ui-fee-row { display:grid;grid-template-columns:minmax(16rem,1fr) minmax(10rem,.38fr);gap:1rem;padding:.8rem 1.15rem; }
    .ui-fee-heading { background:linear-gradient(90deg,var(--ui-green-50),var(--ui-blue-50));color:#40536d;font-size:.7rem;font-weight:750;letter-spacing:.035em;text-transform:uppercase; }
    .ui-fee-row { border-top:1px solid #e8eef4;color:#40536d;font-size:.82rem; }
    .ui-fee-row:hover { background:#fafcfe; }

    /* Compatibility layer for custom Livewire pages that predate the shared UI classes. */
    body:not(:has(.teacher-mobile)) .fi-page :is(.at-card,.al-card,.ar-card,.as-card,.fd-card,.ma-card,.ml-card,.sd-card) {
        border:1px solid var(--ui-border);border-radius:var(--ui-radius);background:var(--ui-surface);box-shadow:var(--ui-shadow);
    }
    body:not(:has(.teacher-mobile)) .fi-page :is(.al-head,.ar-head,.as-head,.fd-head,.ml-head) {
        border:1px solid #dbe8f2;border-radius:var(--ui-radius);background:linear-gradient(110deg,var(--ui-green-50),var(--ui-blue-50));box-shadow:none;
    }
    body:not(:has(.teacher-mobile)) .fi-page :is(.al-title,.ar-title,.as-title,.fd-title,.ml-title) { color:var(--ui-ink);letter-spacing:-.02em; }
    body:not(:has(.teacher-mobile)) .fi-page :is(.al-sub,.ar-sub,.as-sub,.fd-sub,.ml-sub) { color:var(--ui-muted);line-height:1.55; }
    body:not(:has(.teacher-mobile)) .fi-page :is(.al-input,.al-note,.ar-input,.as-input,.fd-input,.ma-file,.ml-input) {
        min-height:2.65rem;border:1px solid var(--ui-border-strong);border-radius:.6rem;background:#fff;padding:.55rem .8rem;
    }
    body:not(:has(.teacher-mobile)) .fi-page :is(.al-input,.al-note,.ar-input,.as-input,.fd-input,.ma-file,.ml-input):focus {
        border-color:var(--ui-blue-600);box-shadow:0 0 0 3px rgba(23,104,181,.12);outline:0;
    }
    body:not(:has(.teacher-mobile)) .fi-page :is(.al-table,.ar-table,.fd-table,.ma-table,.ml-table,.sd-table) th {
        border-bottom:1px solid var(--ui-border);background:linear-gradient(90deg,var(--ui-green-50),var(--ui-blue-50));color:#40536d;font-weight:750;letter-spacing:.035em;
    }
    body:not(:has(.teacher-mobile)) .fi-page :is(.al-table,.ar-table,.fd-table,.ma-table,.ml-table,.sd-table) td { border-bottom-color:#e8eef4; }
    body:not(:has(.teacher-mobile)) .fi-page :is(.al-table,.ar-table,.fd-table,.ma-table,.ml-table,.sd-table) tbody tr:nth-child(even) { background:#fbfcfe; }
    body:not(:has(.teacher-mobile)) .fi-page :is(.al-table,.ar-table,.fd-table,.ma-table,.ml-table,.sd-table) tbody tr:hover { background:#f4f8fc; }
    body:not(:has(.teacher-mobile)) .fi-page :is(.al-badge,.ar-badge,.ml-badge) { border-radius:999px;padding:.25rem .55rem; }

    .fi-ta-actions:has(.batik-order-row-action) { flex-direction:column;align-items:stretch;gap:.35rem; }
    .fi-ta-actions:has(.batik-order-row-action) .batik-order-row-action { justify-content:center; }

    .dark body:not(:has(.teacher-mobile)) .fi-main { background:linear-gradient(180deg,#111b29,#111827 13rem); }
    .dark body:not(:has(.teacher-mobile)) :is(.fi-section,.fi-ta-ctn,.ui-card,.fi-sidebar,.fi-topbar) { border-color:#334155;background:#171f2c; }
    .dark body:not(:has(.teacher-mobile)) :is(.fi-header-heading,.fi-section-header-heading,.ui-card-head h2,.ui-page-intro h2) { color:#edf4fb; }
    .dark body:not(:has(.teacher-mobile)) :is(.ui-input,.ui-select,.ui-textarea) { border-color:#3a485b;background:#111827;color:#e5edf7; }

    @media(max-width:900px) {
        .ui-filter-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .ui-filter-actions { grid-column:1/-1; }
    }
    @media(max-width:640px) {
        body:not(:has(.teacher-mobile)) .fi-main { padding-inline:.75rem; }
        body:not(:has(.teacher-mobile)) .fi-page { gap:1rem; }
        body:not(:has(.teacher-mobile)) .fi-section-content { padding:1rem; }
        .ui-page-intro,.ui-card-head { align-items:stretch;flex-direction:column; }
        .ui-card-body { padding:1rem; }
        .ui-filter-grid { grid-template-columns:1fr; }
        .ui-edit-grid,.ui-form-grid { grid-template-columns:1fr; }
        .ui-span-full { grid-column:auto; }
        .ui-filter-actions { grid-column:auto; }
        .ui-filter-actions .ui-btn { flex:1; }
        .ui-fee-heading,.ui-fee-row { grid-template-columns:minmax(10rem,1fr) auto;padding-inline:1rem; }
        body:not(:has(.teacher-mobile)) .fi-main:has(.school-head-form) { padding-inline:.75rem; }
        body:not(:has(.teacher-mobile)) .school-head-form { gap:.75rem; }
        body:not(:has(.teacher-mobile)) .school-head-section .fi-section-header,
        body:not(:has(.teacher-mobile)) .school-head-section .fi-section-content { padding:.875rem; }
        body:not(:has(.teacher-mobile)) .school-head-section .fi-sc { grid-template-columns:minmax(0,1fr)!important; }
        body:not(:has(.teacher-mobile)) .school-head-section .fi-sc > * { grid-column:1/-1!important; }
        body:not(:has(.teacher-mobile)) .fi-page:has(.school-head-form) .fi-sc-actions[id*="form-actions"] .fi-btn { flex:1; }
    }
    @media(prefers-reduced-motion:reduce) { *,*::before,*::after { scroll-behavior:auto!important;transition-duration:.01ms!important;animation-duration:.01ms!important;animation-iteration-count:1!important; } }
</style>
