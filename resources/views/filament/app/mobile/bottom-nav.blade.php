@php
    $items = [
        ['dashboard', \App\Filament\App\Pages\Dashboard::getUrl(panel:'app', isAbsolute:false), 'heroicon-s-home', 'Dashboard'],
        ['attendance', \App\Filament\App\Pages\MyAttendance::getUrl(panel:'app', isAbsolute:false), 'heroicon-o-map-pin', 'Presensi'],
        ['complaints', \App\Filament\App\Pages\MyComplaints::getUrl(panel:'app', isAbsolute:false), 'heroicon-s-chat-bubble-left-right', 'Pengaduan'],
        ['profile', \App\Filament\App\Pages\MyProfile::getUrl(panel:'app', isAbsolute:false), 'heroicon-s-user', 'Profil'],
        ['leave', \App\Filament\App\Pages\MyAttendanceLeaveRequests::getUrl(panel:'app', isAbsolute:false), 'heroicon-s-calendar-days', 'Izin'],
    ];
@endphp
<nav class="tm-bottom" aria-label="Navigasi guru dan pegawai">
    @foreach($items as [$key,$url,$icon,$label])<a href="{{ $url }}" @class(['active'=>$active===$key])><span><x-filament::icon :icon="$icon" />{{ $label }}</span></a>@endforeach
</nav>
