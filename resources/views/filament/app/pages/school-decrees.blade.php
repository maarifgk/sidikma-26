<div class="sd-page">
    <style>
        .sd-page { display: grid; gap: 1rem; color: #5c7088; }
        .sd-card { border: 1px solid #e2e8f0; border-radius: .8rem; background: #fff; box-shadow: 0 8px 20px rgba(25, 53, 84, .06); }
        .sd-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.1rem; }
        .sd-title { color: #24384d; font-size: 1.35rem; font-weight: 800; }
        .sd-subtitle { margin-top: .2rem; color: #91a0b2; font-size: .76rem; }
        .sd-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .sd-stat { padding: 1rem; }
        .sd-stat-label { color: #91a0b2; font-size: .72rem; }
        .sd-stat-value { margin-top: .4rem; color: #24384d; font-size: 1.55rem; font-weight: 800; }
        .sd-filter { display: grid; grid-template-columns: .55fr 1.45fr; gap: .8rem; padding: 1rem; border-top: 1px solid #e4eaf0; }
        .sd-field label { display: block; margin-bottom: .38rem; font-size: .68rem; font-weight: 800; text-transform: uppercase; }
        .sd-input { width: 100%; border: 1px solid #cfd9e5; border-radius: .45rem; background: #fff; padding: .65rem .75rem; font-size: .8rem; }
        .sd-table-wrap { overflow-x: auto; }
        .sd-table { width: 100%; min-width: 65rem; border-collapse: collapse; }
        .sd-table th, .sd-table td { padding: .78rem .85rem; border-bottom: 1px solid #e6ebf0; text-align: left; font-size: .75rem; }
        .sd-table th { background: #f3faf7; color: #536b65; font-size: .65rem; letter-spacing: .05em; text-transform: uppercase; }
        .sd-download { display: inline-flex; border-radius: .42rem; background: #164db9; padding: .45rem .75rem; color: #fff; font-size: .7rem; font-weight: 800; }
        .sd-empty { padding: 2.7rem 1rem; color: #91a0b2; text-align: center; }
        .dark .sd-card, .dark .sd-input { border-color: #364152; background: #171b22; }
        .dark .sd-title, .dark .sd-stat-value { color: #e5edf7; }
        .dark .sd-table th { background: #202832; color: #c1ccda; }
        @media (max-width: 800px) { .sd-stats, .sd-filter { grid-template-columns: 1fr; } }
    </style>

    <section class="sd-card">
        <header class="sd-head">
            <div><h1 class="sd-title">SK Yayasan</h1><p class="sd-subtitle">Dokumen SK guru dan pegawai yang telah diunggah oleh admin induk untuk madrasah Anda.</p></div>
        </header>
        <div class="sd-filter">
            <div class="sd-field"><label>Tahun SK</label><select wire:model.live="selectedYear" class="sd-input"><option value="">Semua tahun</option>@foreach ($yearOptions as $year)<option value="{{ $year }}">{{ $year }}</option>@endforeach</select></div>
            <div class="sd-field"><label>Pencarian</label><input type="search" wire:model.live.debounce.300ms="documentSearch" class="sd-input" placeholder="Cari nama, EWANUGK, sekolah, tahun, atau file SK..."></div>
        </div>
    </section>

    <section class="sd-stats">
        <article class="sd-card sd-stat"><div class="sd-stat-label">Total Dokumen SK</div><div class="sd-stat-value">{{ number_format($totalDocuments, 0, ',', '.') }}</div></article>
        <article class="sd-card sd-stat"><div class="sd-stat-label">Guru/Pegawai Terhubung</div><div class="sd-stat-value">{{ number_format($linkedUsers, 0, ',', '.') }}</div></article>
    </section>

    <section class="sd-card sd-table-wrap">
        <table class="sd-table">
            <thead><tr><th>Nama Guru/Pegawai</th><th>EWANUGK/NIP/NUPTK</th><th>Asal Madrasah</th><th>Tahun</th><th>Template</th><th>Nama File</th><th>Diunggah</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($documents as $document)
                    <tr><td><strong>{{ $document['user'] }}</strong></td><td>{{ $document['identifier'] }}</td><td>{{ $document['school'] }}</td><td>{{ $document['year'] }}</td><td>{{ $document['template'] }}</td><td>{{ $document['file'] }}</td><td>{{ $document['uploadedAt'] }}</td><td><a href="{{ $document['downloadUrl'] }}" class="sd-download">Download SK</a></td></tr>
                @empty
                    <tr><td colspan="8" class="sd-empty">Belum ada dokumen SK untuk madrasah ini atau data tidak cocok dengan pencarian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
