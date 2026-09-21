<style>
    .teacher-mobile { display: none; }
    .tp-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.tp-hero,.tp-card{border:1px solid #e2e8f0;border-radius:1rem;background:#fff;box-shadow:0 8px 24px rgba(30,64,110,.07)}.tp-hero{margin-bottom:1rem;padding:1.4rem;background:linear-gradient(135deg,#1558b8,#1680bd);color:#fff}.tp-hero h1{margin-top:.25rem;font-size:1.5rem;font-weight:800}.tp-card{padding:1.2rem}.tp-card h2{margin-bottom:.8rem;font-size:1rem;font-weight:800}.tp-row{display:flex;justify-content:space-between;gap:1rem;padding:.75rem 0;border-bottom:1px solid #e5eaf1}.tp-row:last-child{border:0}.tp-row span{color:#71819c}.tp-row strong{text-align:right}.tp-file{display:flex;align-items:center;justify-content:space-between;gap:1rem}.tp-button{border-radius:.65rem;background:#1455bd;padding:.65rem 1rem;color:#fff;font-weight:700}.tp-empty{padding:2rem;text-align:center;color:#71819c}
    @media (min-width: 0px) {
        body:has(.teacher-mobile) { background: #edf4ff; }
        html:has(.teacher-mobile),
        body:has(.teacher-mobile) { min-height: 100%; height: auto; overflow-x: hidden; overflow-y: auto; }
        body:has(.teacher-mobile) .fi-sidebar,
        body:has(.teacher-mobile) .fi-topbar,
        body:has(.teacher-mobile) .fi-header { display: none !important; }
        body:has(.teacher-mobile) .fi-main { min-height:100dvh !important; height:auto !important; margin:0 !important; padding:0 !important; overflow:visible !important; }
        body:has(.teacher-mobile) .fi-main-ctn { width:100% !important; min-height:100dvh !important; height:auto !important; overflow:visible !important; }
        body:has(.teacher-mobile) .fi-page, body:has(.teacher-mobile) .fi-page-content { min-height:100dvh !important; height:auto !important; overflow:visible !important; }
        body:has(.teacher-mobile) .fi-page-content { padding:0 !important; }
        .teacher-mobile { --tm-blue:#1267ba; --tm-deep:#10213f; --tm-muted:#7082a1; display:block; width:100%; max-width:480px; min-height:100dvh; height:auto; margin:0 auto; padding:12px 18px 128px; overflow:visible; color:var(--tm-deep); background:linear-gradient(155deg,#f2f7ff 0%,#e5efff 100%); box-shadow:0 0 42px rgba(30,64,110,.12); font-family:Inter,ui-sans-serif,sans-serif; }
        .teacher-desktop, body:has(.teacher-mobile) .ma-page { display:none !important; }
        .tm-header{display:flex;align-items:center;gap:12px;margin-bottom:18px}.tm-logo{width:56px;height:56px;border-radius:18px;background:#fff;object-fit:contain;padding:8px}.tm-brand{flex:1}.tm-brand strong{display:block;font-size:18px}.tm-brand span{color:var(--tm-muted);font-size:14px}.tm-logout{border:0;background:none;color:#526684;font-weight:600}
        .tm-hero{display:flex;align-items:center;gap:16px;border-radius:22px;background:linear-gradient(135deg,#0d54b7,#167cc0);padding:20px 24px;color:#fff;box-shadow:0 18px 38px rgba(17,91,180,.16)}.tm-hero-avatar{width:78px;height:78px;border:3px solid #0d418e;border-radius:24px;object-fit:cover}.tm-eyebrow{font-size:13px;text-transform:uppercase;opacity:.9}.tm-hero h1{margin:4px 0;font-size:20px;line-height:1.2;font-weight:800}.tm-hero p{font-size:14px;line-height:1.45}
        .tm-section{margin-top:20px}.tm-section-head{display:flex;align-items:end;justify-content:space-between;margin-bottom:10px}.tm-section-head h2{font-size:18px;font-weight:800}.tm-section-head small{color:var(--tm-muted);font-size:10px}.tm-card{border:1px solid rgba(70,95,135,.08);border-radius:18px;background:#fff;box-shadow:0 10px 28px rgba(49,82,133,.07)}
        .tm-menu{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}.tm-menu a{display:grid;min-height:72px;place-items:center;border-radius:12px;background:#fff;padding:8px 4px;text-align:center;font-size:10px;font-weight:700;box-shadow:0 8px 20px rgba(49,82,133,.08)}.tm-menu svg{width:20px;height:20px;color:#125aba}.tm-menu-icon{display:grid;width:32px;height:32px;place-items:center;border-radius:9px;background:#e8f0ff}
        .tm-stats{display:grid;grid-template-columns:1fr 1fr;gap:10px}.tm-stat{padding:18px}.tm-stat span,.tm-stat small{display:block;color:var(--tm-muted);font-size:12px}.tm-stat strong{display:block;margin:4px 0;font-size:23px}.tm-list{padding:8px 14px}.tm-list-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid #e3e8f0}.tm-list-row:last-child{border-bottom:0}.tm-list-row strong{display:block;font-size:13px}.tm-list-row span,.tm-list-row small{display:block;color:var(--tm-muted);font-size:11px;line-height:1.4}.tm-badge{border-radius:999px;padding:8px 10px!important;font-size:11px!important;font-weight:700}.tm-success{background:#e3f4ee;color:#168466!important}.tm-danger{background:#fee9eb;color:#dc3948!important}.tm-primary-button{display:inline-flex;align-items:center;border-radius:14px;background:#0d51c5;padding:12px 18px;color:#fff;font-weight:800}.tm-empty{padding:38px 20px;text-align:center;color:var(--tm-muted);font-size:13px}.tm-detail{padding:10px 18px}.tm-detail-row{display:grid;grid-template-columns:minmax(110px,.8fr) 1.4fr;gap:14px;padding:12px 0;border-bottom:1px solid #e3e8f0}.tm-detail-row:last-child{border:0}.tm-detail-row span{color:var(--tm-muted);font-size:13px}.tm-detail-row strong{text-align:right;font-size:13px;overflow-wrap:anywhere}
        .tm-bottom{position:fixed;z-index:50;bottom:12px;left:50%;display:grid;width:min(calc(100% - 36px),444px);transform:translateX(-50%);grid-template-columns:repeat(5,1fr);border-radius:18px;background:#fff;padding:8px;box-shadow:0 15px 45px rgba(35,64,110,.18)}.tm-bottom a{display:grid;min-height:62px;place-items:center;border-radius:16px;color:#657795;text-align:center;font-size:9px;font-weight:700}.tm-bottom svg{width:18px;height:18px;margin:auto}.tm-bottom a.active{background:#1370c7;color:#fff}.tm-clock{display:inline-flex;gap:7px;margin:14px 0 2px;border-radius:999px;background:#fff;padding:7px 12px;color:#0750b2;font-weight:800}.tm-attendance-times{display:grid;grid-template-columns:1fr 1fr;gap:14px}.tm-time{padding:18px}.tm-time span{color:var(--tm-muted);font-size:13px}.tm-time strong{display:block;margin-top:7px;font-size:25px}.tm-download{white-space:nowrap}

        @media (max-width: 380px) {
            .teacher-mobile { padding: 12px 14px 136px; }
            .tm-header { gap: 9px; }
            .tm-logo { width: 52px; height: 52px; border-radius: 15px; }
            .tm-brand strong { font-size: 17px; }
            .tm-brand span { font-size: 12px; }
            .tm-logout { font-size: 12px; }
            .tm-hero { gap: 12px; padding: 18px 16px; }
            .tm-hero-avatar { width: 68px; height: 68px; border-radius: 20px; }
            .tm-hero h1 { font-size: 19px; }
            .tm-hero p { font-size: 13px; overflow-wrap: anywhere; }
            .tm-menu { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
            .tm-menu a { min-height: 78px; font-size: 11px; }
            .tm-stats { grid-template-columns: 1fr; }
            .tm-stat { min-height: 112px; }
            .tm-attendance-times { grid-template-columns: 1fr; }
            .tm-detail-row { grid-template-columns: 1fr; gap: 5px; }
            .tm-detail-row strong { text-align: left; }
            .tm-bottom { bottom:max(8px,env(safe-area-inset-bottom));left:50%;width:calc(100% - 20px);padding:6px; }
            .tm-bottom a { min-height: 58px; font-size: 8px; }
        }
    }
    @media (min-width:381px) {
        body:has(.teacher-mobile){background:linear-gradient(155deg,#f2f7ff 0%,#e5efff 100%)}
        .teacher-mobile{max-width:620px;padding-top:22px;background:transparent;box-shadow:none}
        .tm-header{gap:14px;margin-bottom:22px}.tm-brand strong{font-size:19px}
        .tm-hero{gap:18px;padding:24px}
        .tm-menu{gap:14px}.tm-menu a{min-height:106px;border-radius:18px;padding:12px 6px;font-size:12px;background:rgba(255,255,255,.94)}
        .tm-bottom{width:min(calc(100% - 36px),584px)}
    }
</style>
