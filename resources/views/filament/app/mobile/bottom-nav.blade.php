@php
    $items = [
        ['dashboard', \App\Filament\App\Pages\Dashboard::getUrl(panel:'app', isAbsolute:false), 'heroicon-s-home', 'Dashboard'],
        ['attendance', \App\Filament\App\Pages\MyAttendance::getUrl(panel:'app', isAbsolute:false), 'heroicon-o-map-pin', 'Presensi'],
        ['leave', \App\Filament\App\Pages\MyAttendanceLeaveRequests::getUrl(panel:'app', isAbsolute:false), 'heroicon-s-calendar-days', 'Izin'],
        ['profile', \App\Filament\App\Pages\MyProfile::getUrl(panel:'app', isAbsolute:false), 'heroicon-s-user', 'Profil'],
    ];
@endphp
<nav class="tm-bottom" aria-label="Navigasi guru dan pegawai">
    @foreach($items as [$key,$url,$icon,$label])<a href="{{ $url }}" @class(['active'=>$active===$key])><span><x-filament::icon :icon="$icon" />{{ $label }}</span></a>@endforeach
</nav>
