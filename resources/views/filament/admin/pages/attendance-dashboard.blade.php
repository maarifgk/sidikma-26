<div class="at-page">
    <style>
        .at-page { display:grid; gap:1rem; color:#60738a; }
        .at-hero { border-radius:.8rem; background:linear-gradient(135deg,#124ab6,#1760c9); padding:1.2rem; color:#fff; }
        .at-hero-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; }
        .at-title { font-size:1.45rem; font-weight:800; }
        .at-subtitle { margin-top:.25rem; color:#bfd3f3; font-size:.8rem; }
        .at-actions { display:flex; gap:.6rem; }
        .at-button { display:inline-flex; border:1px solid rgba(255,255,255,.45); border-radius:.45rem; padding:.6rem .9rem; color:#fff; font-size:.76rem; font-weight:800; }
        .at-filter { margin-top:1.1rem; border-radius:.6rem; background:#fff; padding:1rem; color:#5d7189; }
        .at-filter label { display:block; margin-bottom:.4rem; font-size:.7rem; font-weight:800; text-transform:uppercase; }
        .at-input { width:min(100%,40rem); border:1px solid #cad6e3; border-radius:.45rem; padding:.65rem .75rem; background:#fff; }
        .at-metrics { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:1rem; }
        .at-card { border:1px solid #e4eaf0; border-radius:.7rem; background:#fff; box-shadow:0 6px 16px rgba(22,48,78,.07); }
        .at-metric { padding:1rem; }
        .at-metric span { color:#9aa9ba; font-size:.74rem; }
        .at-metric strong { display:block; margin-top:.35rem; color:#516a85; font-size:1.5rem; }
        .at-content { display:grid; grid-template-columns:2fr 1fr; gap:1rem; }
        .at-section-head { padding:1rem; border-bottom:1px solid #e7ecf1; color:#536b84; font-size:1rem; font-weight:800; }
        .at-chart { display:flex; align-items:flex-end; gap:.8rem; height:16rem; padding:1.2rem; }
        .at-bar-item { display:flex; flex:1; height:100%; align-items:center; justify-content:flex-end; flex-direction:column; gap:.35rem; }
        .at-bar { width:70%; min-width:1.2rem; border-radius:.35rem .35rem 0 0; background:linear-gradient(#3f7bd7,#b8cef0); }
        .at-bar-item small { font-size:.6rem; text-align:center; }
        .at-latest { padding:0 1rem; }
        .at-latest-row { padding:.85rem 0; border-bottom:1px solid #edf1f4; }
        .at-latest-row strong { color:#536b84; font-size:.78rem; }
        .at-latest-row span { display:block; margin-top:.2rem; font-size:.67rem; }
        .at-map-wrap { overflow:hidden; }
        .at-map { width:100%; height:540px; background:#e9eef4; }
        .at-map-empty { display:grid; min-height:15rem; place-items:center; padding:2rem; text-align:center; color:#8190a2; }
        .at-badge { float:right; border-radius:.25rem; background:#e7f8de; padding:.2rem .4rem; color:#51b930; font-size:.62rem; font-weight:800; }
        .dark .at-card,.dark .at-filter,.dark .at-input { border-color:#364152; background:#171b22; color:#c1ccda; }
        @media(max-width:1050px){.at-metrics{grid-template-columns:repeat(3,1fr)}.at-content{grid-template-columns:1fr}} @media(max-width:650px){.at-metrics{grid-template-columns:repeat(2,1fr)}.at-hero-head{flex-direction:column}}
    </style>

    <section class="at-hero">
        <div class="at-hero-head"><div><h1 class="at-title">Dashboard Presensi</h1><p class="at-subtitle">Monitoring kehadiran guru dan pegawai seluruh sekolah pada {{ $todayLabel }}.</p></div><div class="at-actions"><a href="{{ $reportUrl }}" class="at-button">Laporan</a><a href="{{ $settingsUrl }}" class="at-button">Pengaturan</a></div></div>
        <div class="at-filter"><label>Madrasah / Sekolah</label><select wire:model.live="selectedSchoolId" class="at-input"><option value="">Semua sekolah</option>@foreach($schoolOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
    </section>

    <section class="at-metrics">@foreach($metrics as $metric)<article class="at-card at-metric"><span>{{ $metric['label'] }}</span><strong>{{ $metric['value'] }}</strong></article>@endforeach</section>

    <section class="at-content">
        <article class="at-card"><header class="at-section-head">Grafik Presensi 7 Hari</header><div class="at-chart">@foreach($sevenDays as $day)<div class="at-bar-item"><strong>{{ $day['count'] }}</strong><div class="at-bar" style="height:{{ $day['height'] }}%"></div><small>{{ $day['label'] }}</small></div>@endforeach</div></article>
        <article class="at-card"><header class="at-section-head">Aktivitas Terbaru</header><div class="at-latest">@forelse($latestRecords as $record)<div class="at-latest-row"><span class="at-badge">{{ strtoupper($statusOptions[$record->status] ?? $record->status) }}</span><strong>{{ $record->employee?->name ?? '-' }}</strong><span>{{ $record->school?->name ?? '-' }}</span><span>Masuk {{ $record->check_in_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }} &middot; Pulang {{ $record->check_out_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }}</span></div>@empty<div class="at-latest-row">Belum ada aktivitas presensi.</div>@endforelse</div></article>
    </section>

    <section class="at-card"><header class="at-section-head">Ringkasan Harian</header><div class="at-latest"><div class="at-latest-row"><strong>Tanggal · Hadir · Terlambat · Izin · Ditolak · Fake GPS · Pulang Awal</strong></div>@forelse($dailyCharts as $day)<div class="at-latest-row"><span>{{ $day['label'] }} · {{ $day['present'] }} · {{ $day['late'] }} · {{ $day['permit'] }} · {{ $day['rejected'] }} · {{ $day['fake_gps'] }} · {{ $day['early'] }}</span></div>@empty<div class="at-latest-row">Belum ada data grafik.</div>@endforelse</div></section>

    <section class="at-card at-map-wrap">
        <header class="at-section-head">Peta Lokasi Presensi User <small style="display:block;margin-top:.25rem;color:#8b9aab;font-size:.68rem;font-weight:500">Menampilkan titik lokasi presensi masuk hari ini dan radius sekolah.</small></header>
        <div wire:ignore id="attendance-map-{{ $this->selectedSchoolId ?: 'all' }}" class="at-map"></div>
        @if($mapSchools->isEmpty() && $mapAttendances->isEmpty())
            <div class="at-map-empty">Belum ada koordinat sekolah atau lokasi presensi. Peta tetap ditampilkan; atur koordinat sekolah pada Pengaturan Presensi.</div>
        @endif
    </section>
</div>

<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script>
        (() => {
            const schools = @js($mapSchools);
            const attendances = @js($mapAttendances);
            const renderMap = () => {
                const element = document.getElementById(@js('attendance-map-'.($this->selectedSchoolId ?: 'all')));
                if (!element || !window.L) return;
                if (element._leaflet_id) element._leaflet_id = null;
                const map = L.map(element, { scrollWheelZoom: true, maxZoom: 19, zoomSnap: 1, zoomAnimation: false, fadeAnimation: false });
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, maxNativeZoom: 19, detectRetina: false, updateWhenZooming: false, updateWhenIdle: true, keepBuffer: 2, crossOrigin: true, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
                const bounds = [];
                schools.forEach((school) => {
                    const point = [school.latitude, school.longitude]; bounds.push(point);
                    L.marker(point).addTo(map).bindPopup(document.createTextNode('Lokasi '+school.name));
                    if (school.polygon && school.polygon.length >= 3) {
                        const polygonPoints = school.polygon.map(item => [item.latitude,item.longitude]);
                        polygonPoints.forEach(item => bounds.push(item));
                        L.polygon(polygonPoints, { color:'#1757bb',fillColor:'#5a91df',fillOpacity:.15, smoothFactor:0 }).addTo(map);
                    } else {
                        L.circle(point, { radius: school.radius, color: '#1757bb', fillColor: '#5a91df', fillOpacity: .12 }).addTo(map);
                    }
                });
                attendances.forEach((item) => {
                    const point = [item.latitude, item.longitude]; bounds.push(point);
                    const popup = document.createElement('div');
                    [item.name, item.type+' · '+item.school, 'Masuk: '+(item.checkIn || '-'), 'Pulang: '+(item.checkOut || '-'), 'Status: '+item.status, 'Jarak: '+item.distance].forEach((text, index) => {
                        const line = document.createElement(index === 0 ? 'strong' : 'div'); line.textContent = text; popup.appendChild(line);
                    });
                    L.circleMarker(point, { radius: 8, color: '#078844', fillColor: '#16a35b', fillOpacity: .9 }).addTo(map).bindPopup(popup);
                });
                bounds.length ? map.fitBounds(bounds, { padding: [35,35], maxZoom: 17 }) : map.setView([-7.8,110.36],10);
                setTimeout(() => map.invalidateSize(), 150);
            };
            document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', renderMap, { once:true }) : renderMap();
        })();
    </script>
