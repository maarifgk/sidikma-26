@php
    $items = [
        ['dashboard', \App\Filament\App\Pages\Dashboard::getUrl(panel:'app', isAbsolute:false), 'heroicon-s-home', 'Dashboard'],
        ['attendance', \App\Filament\App\Pages\MyAttendance::getUrl(panel:'app', isAbsolute:false), 'heroicon-o-map-pin', 'Presensi'],
        ['payments', \App\Filament\App\Pages\MyPayments::getUrl(panel:'app', isAbsolute:false), 'heroicon-s-credit-card', 'Pembayaran'],
        ['decrees', \App\Filament\App\Pages\MyDecrees::getUrl(panel:'app', isAbsolute:false), 'heroicon-s-document-arrow-down', 'File SK'],
        ['complaints', \App\Filament\App\Pages\MyComplaints::getUrl(panel:'app', isAbsolute:false), 'heroicon-s-chat-bubble-left-right', 'Pengaduan'],
    ];
@endphp
<nav class="tm-bottom" aria-label="Navigasi guru dan pegawai">
    @foreach($items as [$key,$url,$icon,$label])<a href="{{ $url }}" @class(['active'=>$active===$key])><span><x-filament::icon :icon="$icon" />{{ $label }}</span></a>@endforeach
</nav>
