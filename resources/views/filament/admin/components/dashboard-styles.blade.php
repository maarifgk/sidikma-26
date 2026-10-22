<style>
    /* Respond to the content width, including changes when the sidebar opens. */
    .md-dashboard { container:admin-dashboard / inline-size;min-width:0;--md-ink:#101828;--md-muted:#667085;--md-green:#079455; }
    .md-dashboard * { box-sizing:border-box; }
    .md-dashboard :is(section,article,header,.md-top > div) { min-width:0; }
    .md-dashboard .md-top { flex-wrap:wrap;gap:1.25rem; }
    .md-eyebrow { display:block;margin-bottom:.4rem;color:#067647;font-size:.65rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase; }
    .md-dashboard .md-top h1 { margin:0;font-size:clamp(1.25rem,2.5vw,1.65rem);line-height:1.3;letter-spacing:-.025em; }
    .md-dashboard .md-top p { max-width:38rem;font-size:.8125rem;line-height:1.65; }
    .md-dashboard .md-clock { flex-shrink:0;padding-inline-start:1.25rem;border-inline-start:1px solid #cce4df; }
    .md-dashboard .md-clock strong { font-size:1.65rem;font-variant-numeric:tabular-nums; }
    .md-dashboard .md-clock span { display:block;margin-top:.25rem;line-height:1.5; }
    .md-dashboard .md-finance { grid-template-columns:minmax(0,.8fr) minmax(0,1.2fr);gap:1rem; }
    .md-dashboard .md-payments { grid-column:1/-1; }
    .md-dashboard .md-card-head { padding:1rem 1.2rem;text-align:start; }
    .md-dashboard :is(.md-card-head h2,.md-panel-title,.md-submission-head h2) { font-size:.875rem;font-weight:650;text-transform:none;line-height:1.5; }
    .md-dashboard .md-card-head p { font-size:.75rem;line-height:1.5; }
    .md-dashboard .md-money { min-height:0; }
    .md-dashboard .md-money-item { padding:1.5rem 1rem; }
    .md-dashboard .md-money-item + * { border-color:#eaecf0; }
    .md-dashboard .md-money-label { color:#667085;font-size:.65rem;letter-spacing:.06em; }
    .md-dashboard .md-money-year { margin:.35rem 0 .65rem;font-size:.75rem;font-weight:500;line-height:1.5; }
    .md-dashboard .md-money-year br { display:none; }
    .md-dashboard .md-money-value { color:#067647;font-size:clamp(1.15rem,2.2vw,1.65rem);font-weight:700;overflow-wrap:anywhere;font-variant-numeric:tabular-nums; }
    .md-dashboard .md-money-item:last-child .md-money-value { color:#b54708; }
    .md-dashboard .md-chart { height:270px;gap:clamp(3px,1vw,10px); }
    .md-dashboard .md-middle { grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem; }
    .md-dashboard .md-donut-card { grid-column:1/-1; }
    body:not(:has(.teacher-mobile)) .md-dashboard .md-panel-title { padding:1rem 1.2rem;border-bottom:1px solid #eaecf0;background:#fff;color:var(--md-ink);text-align:start; }
    .md-dashboard .md-list-row { padding:.85rem 1.2rem;gap:.75rem;line-height:1.5; }
    .md-dashboard .md-list-row strong { text-align:end;font-size:.75rem;font-variant-numeric:tabular-nums; }
    .md-dashboard .md-list-row:last-child { border-bottom:0;background:#f9fafb; }
    .md-dashboard .md-donuts { min-height:0;grid-template-columns:repeat(3,minmax(0,1fr));padding:1.25rem;gap:1.5rem 1rem; }
    .md-dashboard .md-table { min-width:420px; }
    .md-dashboard .md-table td { line-height:1.6;vertical-align:top; }
    .md-dashboard .md-table td:nth-child(3) { white-space:nowrap;font-variant-numeric:tabular-nums; }
    .md-dashboard .md-table td:last-child { color:#067647;font-weight:600; }
    .md-dashboard .md-table tr:last-child td { border-bottom:0; }
    .md-dashboard .md-assignment-section-head { grid-template-columns:1fr; }
    .md-dashboard .md-assignment-tools { flex-wrap:wrap;justify-content:space-between; }
    .md-dashboard .md-assignment-search { flex:1;min-width:min(100%,240px);max-width:380px; }
    .md-dashboard .md-assignment-search input { min-width:0; }
    .md-dashboard .md-assignment-title strong { overflow-wrap:anywhere;line-height:1.5; }
    .md-dashboard .md-admin-stats { grid-template-columns:repeat(3,minmax(0,1fr)); }
    .md-dashboard .md-mini { border:1px solid #eaecf0;background:#f9fafb; }
    .md-dashboard .md-mini.warning { border-color:#fedf89;background:#fffaeb; }
    .md-dashboard .md-submissions { grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem; }
    .md-dashboard .md-submission-head { gap:1rem;border-bottom:1px solid #eaecf0;background:#fff;color:var(--md-ink); }
    .md-dashboard .md-submission-head a { flex-shrink:0;color:#067647; }
    .md-dashboard .md-request { align-items:start;line-height:1.5; }
    .md-dashboard .md-request strong { overflow-wrap:anywhere; }
    .md-dashboard .md-request:hover { background:#f9fafb; }
    .md-dashboard :is(.md-request,.md-assignment-title,.md-submission-head a):focus-visible { outline:2px solid #079455;outline-offset:-3px; }
    .md-dashboard .md-badge { max-width:9rem;text-align:center; }
    @container admin-dashboard (min-width:1200px) {
        .md-dashboard .md-finance { grid-template-columns:minmax(0,.85fr) minmax(0,1.25fr) minmax(0,1.4fr); }
        .md-dashboard .md-payments { grid-column:auto; }
        .md-dashboard .md-middle { grid-template-columns:minmax(0,1fr) minmax(0,1fr) minmax(0,1.4fr); }
        .md-dashboard .md-donut-card { grid-column:auto; }
    }
    @container admin-dashboard (max-width:700px) {
        .md-dashboard .md-clock { width:100%;padding: .85rem 0 0;border-inline-start:0;border-top:1px solid #cce4df;text-align:start; }
        .md-dashboard :is(.md-finance,.md-middle,.md-submissions,.md-assignment-grid) { grid-template-columns:minmax(0,1fr); }
        .md-dashboard :is(.md-payments,.md-donut-card) { grid-column:auto; }
        .md-dashboard .md-money { grid-template-columns:repeat(2,minmax(0,1fr));grid-template-rows:auto; }
        .md-dashboard .md-money-item + * { border-top:0;border-inline-start:1px solid #eaecf0; }
        .md-dashboard .md-assignment-schools { grid-template-columns:minmax(0,1fr); }
        .md-dashboard .md-assignment-search { max-width:none; }
        .md-dashboard .md-assignment-tools { display:flex; }
        .md-dashboard .md-assignment-summary { width:100%; }
        .md-dashboard .md-assignment-summary-item { flex:1; }
        .md-dashboard .md-assignment--open { grid-column:auto; }
    }
    @container admin-dashboard (max-width:420px) {
        .md-dashboard .md-admin-stats { grid-template-columns:minmax(0,1fr); }
        .md-dashboard .md-donuts { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .md-dashboard .md-request { grid-template-columns:minmax(0,1fr); }
        .md-dashboard .md-badge { justify-self:start; }
        .md-dashboard .md-money { grid-template-columns:minmax(0,1fr); }
        .md-dashboard .md-money-item + * { border-inline-start:0;border-top:1px solid #eaecf0; }
    }
</style>
