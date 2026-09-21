@php($setting = \App\Models\ApplicationSetting::current())
<header class="tm-header">
    <img class="tm-logo" src="{{ asset(auth()->user()?->hasRole(\App\Models\User::ROLE_GURU_PEGAWAI) ? 'images/sidikma-teacher-logo.png' : 'images/sidikma-header-logo.png') }}" alt="Logo SIDIKMA Gunungkidul" style="width:96px;height:64px;flex-shrink:0;padding:0;border-radius:8px;object-fit:contain;{{ auth()->user()?->hasRole(\App\Models\User::ROLE_GURU_PEGAWAI) ? 'background:transparent' : '' }}">
    <a class="tm-brand" href="{{ \App\Filament\App\Pages\MyProfile::getUrl(panel:'app', isAbsolute:false) }}" aria-label="Buka profil saya"><strong>SIDIKMA Mobile</strong><span>{{ $employee?->school?->name ?? 'Madrasah/Sekolah' }} · Profil</span></a>
    <form method="POST" action="{{ filament()->getLogoutUrl() }}">@csrf<button class="tm-logout" type="submit">Logout</button></form>
</header>
