<div class="teacher-mobile">
    @include('filament.app.mobile.header', ['employee'=>$employee])
    <section class="tm-hero"><img class="tm-hero-avatar" src="{{ $mobileAvatarUrl }}"><div><div class="tm-eyebrow">Dashboard Guru/Pegawai</div><h1>{{ $employee?->name ?? auth()->user()->name }}</h1><p>{{ $employee?->school?->name ?? '-' }} &bull; {{ $employee ? \App\Models\Employee::employmentStatusLabel($employee->employment_status) : 'Profil belum terhubung' }}</p></div></section>
    <section class="tm-section"><div class="tm-section-head"><h2>Menu</h2><small>7 akses</small></div><div class="tm-menu">
        @foreach([
            [\App\Filament\App\Pages\Dashboard::getUrl(panel:'app',isAbsolute:false),'heroicon-s-home','Dashboard'],
            [\App\Filament\App\Pages\MyProfile::getUrl(panel:'app',isAbsolute:false),'heroicon-s-user-group','Informasi'],
            [\App\Filament\App\Pages\MyAttendance::getUrl(panel:'app',isAbsolute:false),'heroicon-o-map-pin','Presensi'],
            [\App\Filament\App\Pages\MyAttendanceLeaveRequests::getUrl(panel:'app',isAbsolute:false),'heroicon-s-calendar-days','Izin'],
            [\App\Filament\App\Pages\MyPayments::getUrl(panel:'app',isAbsolute:false),'heroicon-s-credit-card','Pembayaran'],
            [\App\Filament\App\Pages\MyDecrees::getUrl(panel:'app',isAbsolute:false),'heroicon-s-document-arrow-down','File SK'],
            [\App\Filament\App\Pages\MyProfile::getUrl(panel:'app',isAbsolute:false),'heroicon-s-user','Profil'],
        ] as [$url,$icon,$label])<a href="{{ $url }}"><span><i class="tm-menu-icon"><x-filament::icon :icon="$icon" /></i>{{ $label }}</span></a>@endforeach
    </div></section>
    <section class="tm-section tm-stats"><article class="tm-card tm-stat"><span>Rekan Satu Lembaga</span><strong>{{ $employeeCount }}</strong><small>Guru dan pegawai</small></article><article class="tm-card tm-stat"><span>Pembayaran Lunas</span><strong>Rp{{ number_format($paidAmount,0,',','.') }}</strong><small>{{ $paidInvoiceCount }} pembayaran</small></article><article class="tm-card tm-stat"><span>Pembayaran SK</span><strong>{{ $paidInvoiceCount }}</strong><small>Pembayaran SK yang sudah lunas</small></article><article class="tm-card tm-stat"><span>File SK</span><strong>{{ $documentCount }}</strong><small>Pribadi dan sekolah</small></article></section>
    <section class="tm-section"><div class="tm-section-head"><h2>Rekan Guru/Pegawai</h2><small>{{ $schoolName }}</small></div><div class="tm-card tm-list">@forelse($employees as $colleague)<div class="tm-list-row"><div><strong>{{ $colleague['name'] }}</strong><span>{{ $colleague['position'] }}</span><small>{{ $colleague['status'] }}</small></div></div>@empty<div class="tm-empty">Belum ada rekan satu lembaga.</div>@endforelse</div></section>
    @include('filament.app.mobile.bottom-nav',['active'=>'dashboard'])
</div>
