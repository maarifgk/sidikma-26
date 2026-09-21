@php
    $today = now('Asia/Jakarta');
    $dashboardUser = auth()->user();
    $dashboardScope = $dashboardUser instanceof \App\Models\User
        ? app(\App\Services\DashboardMetrics::class)->scopeLabel($dashboardUser)
        : 'Tidak tersedia';
@endphp

<x-filament::section>
    <div
        style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem 2rem;"
    >
        <div style="min-width: min(100%, 18rem);">
            <p style="margin-bottom: 0.25rem; font-size: 0.875rem;">Selamat datang di</p>
            <h1 style="font-size: 1.5rem; font-weight: 700; line-height: 1.25;">
                Dashboard Sistem Informasi Yayasan
            </h1>
            <p style="margin-top: 0.5rem; max-width: 48rem; font-size: 0.875rem;">
                Pusat pengelolaan administrasi yayasan, sekolah, pengguna, dan sumber daya manusia.
            </p>
            <p style="margin-top: 0.25rem; max-width: 48rem; font-size: 0.8rem; font-weight: 600;">
                Cakupan data: {{ $dashboardScope }}
            </p>
        </div>

        <time
            datetime="{{ $today->toDateString() }}"
            style="white-space: nowrap; font-size: 0.875rem; font-weight: 600;"
        >
            {{ $today->locale('id')->translatedFormat('l, d F Y') }}
        </time>
    </div>
</x-filament::section>
