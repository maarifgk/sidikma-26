@php($applicationSetting = \App\Models\ApplicationSetting::current())

<footer
    aria-label="Informasi aplikasi"
    style="display: flex; width: 100%; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem 1rem; padding: 1rem 1.5rem; font-size: 0.75rem;"
>
    <span>{{ $applicationSetting->copyright_text }} {{ now('Asia/Jakarta')->year }}, {{ $applicationSetting->owner_name }}</span>

    <span style="display: flex; gap: 1.5rem;">
        @if ($applicationSetting->phone)
            <span>WhatsApp: {{ $applicationSetting->phone }}</span>
        @endif
        <span>Versi: {{ $applicationSetting->version ?: config('app.version') }}</span>
    </span>
</footer>
