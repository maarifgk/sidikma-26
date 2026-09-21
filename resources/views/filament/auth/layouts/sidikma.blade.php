@php
    $setting = \App\Models\ApplicationSetting::current();
    $logoUrl = filled($setting->logo_path)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($setting->logo_path)
        : asset('images/default-avatar.svg');
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <style>
        :root { color-scheme: light; }
        body { background: #075d32 !important; }
        .sidikma-auth { min-height: 100vh; display: grid; grid-template-columns: minmax(420px, .82fr) minmax(0, 1.18fr); padding: clamp(1rem, 1.8vw, 1.75rem); gap: clamp(1.5rem, 3.6vw, 4rem); background: linear-gradient(135deg, #034b2a 0%, #08783d 54%, #00673c 100%); }
        .sidikma-auth__brand { position: relative; isolation: isolate; overflow: hidden; display: flex; align-items: center; justify-content: center; padding: clamp(2rem, 4vw, 4.5rem); color: #fff; }
        .sidikma-auth__brand::before, .sidikma-auth__brand::after { content: ''; position: absolute; z-index: -1; border: 1px solid rgba(255,255,255,.14); border-radius: 999px; background: rgba(255,255,255,.045); }
        .sidikma-auth__brand::before { width: 34rem; height: 34rem; right: -13rem; top: -16rem; }
        .sidikma-auth__brand::after { width: 24rem; height: 24rem; right: -9rem; bottom: -9rem; }
        .sidikma-auth__brand-content { width: min(100%, 760px); text-align: center; }
        .sidikma-auth__illustration { display: block; width: min(100%, 570px); max-height: 52vh; margin: 2.5rem auto 0; object-fit: contain; filter: drop-shadow(0 25px 35px rgba(0,44,25,.22)); }
        .sidikma-auth__welcome { font-size: clamp(1.8rem, 2.65vw, 2.8rem); font-weight: 850; letter-spacing: .06em; }
        .sidikma-auth__brand h1 { max-width: 680px; margin: .8rem auto 0; font-size: clamp(1.15rem, 1.55vw, 1.55rem); font-weight: 500; line-height: 1.45; letter-spacing: 0; color: rgba(255,255,255,.92); }
        .sidikma-auth__form-side { display: flex; align-items: center; justify-content: center; min-width: 0; padding: clamp(2rem, 4.2vw, 4.5rem); border-radius: 22px; background: rgba(255,255,255,.98); box-shadow: 0 24px 70px rgba(0,42,22,.28); }
        .sidikma-auth__form-wrap { width: min(100%, 500px); }
        .sidikma-auth__portal { display: inline-flex; align-items: center; gap: .45rem; margin-bottom: 1rem; padding: .45rem .8rem; border: 1px solid #d9e9e2; border-radius: 999px; background: #edf8f2; color: #176742; font-size: .78rem; font-weight: 700; }
        .sidikma-auth__form-logo { display: block; width: min(100%, 320px); height: 126px; margin: .5rem auto 1rem; object-fit: contain; object-position: center; }
        .sidikma-auth .fi-simple-main-ctn, .sidikma-auth .fi-simple-main { width: 100%; padding: 0; background: transparent; }
        .sidikma-auth .fi-simple-main { max-width: none; }
        .sidikma-auth .fi-simple-page { padding: 0; box-shadow: none; background: transparent; }
        .sidikma-auth .fi-header-heading { color: #08351f; font-size: 1.9rem; letter-spacing: -.025em; }
        .sidikma-auth .fi-header-subheading { color: #64748b; line-height: 1.65; }
        .sidikma-auth .fi-input-wrp { min-height: 52px; border-radius: 10px; }
        .sidikma-auth .fi-btn { min-height: 52px; border-radius: 10px; color: #000 !important; font-weight: 750; background: linear-gradient(110deg, #13a448, #007438); box-shadow: 0 12px 24px rgba(8,124,59,.2); transition: transform .18s ease, filter .18s ease; }
        .sidikma-auth .fi-btn svg { color: #000 !important; }
        .sidikma-auth .fi-btn:hover { filter: brightness(.91); transform: translateY(-1px); }
        .sidikma-auth .fi-link { color: #08783d; }
        @media (max-width: 860px) {
            body { background: #f6faf7 !important; }
            .sidikma-auth { display: block; min-height: 100dvh; padding: 0; background: #f6faf7; }
            .sidikma-auth__brand { display: none; }
            .sidikma-auth__form-side { min-height: 100dvh; padding: 2rem 1.25rem; border-radius: 0; box-shadow: none; }
            .sidikma-auth__form-logo { width: 240px; height: 96px; }
        }
        @media (prefers-reduced-motion: reduce) { .sidikma-auth .fi-btn { transition: none; } }
    </style>

    <div class="sidikma-auth">
        <section class="sidikma-auth__form-side">
            <div class="sidikma-auth__form-wrap">
                <div class="sidikma-auth__portal">
                    <x-filament::icon icon="heroicon-o-lock-closed" class="h-4 w-4" />
                    <span>Portal Login</span>
                </div>
                <img class="sidikma-auth__form-logo" src="{{ $logoUrl }}" alt="Logo SIDIKMA">
                <main id="fi-main-content" tabindex="-1">{{ $slot }}</main>
            </div>
        </section>

        <aside class="sidikma-auth__brand" aria-label="Informasi SIDIKMA">
            <div class="sidikma-auth__brand-content">
                <div class="sidikma-auth__welcome">WELCOME TO</div>
                <h1>Sistem Data dan Informasi Kelembagaan Ma'arif NU Kabupaten Gunungkidul</h1>
                <img class="sidikma-auth__illustration" src="{{ asset('images/sidikma-login-illustration-transparent.png') }}" alt="Ilustrasi portal data pendidikan">
            </div>
        </aside>
    </div>
</x-filament-panels::layout.base>
