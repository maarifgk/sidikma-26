@if(auth()->user()?->hasRole(\App\Models\User::ROLE_GURU_PEGAWAI))
    @include('filament.app.mobile.styles')
    <main class="teacher-mobile">
        @include('filament.app.mobile.header', ['schoolName' => $employee?->school?->name])
        <section class="tm-hero"><div><div class="tm-eyebrow">Surat Keputusan Saya</div><h1>{{ $employee?->name ?? $user?->name }}</h1><p>Daftar Surat Keputusan yang telah diterbitkan untuk akun Anda.</p></div></section>
        <section class="tm-section">
            <div class="tm-section-head"><h2>Daftar Surat Keputusan</h2><small>{{ $employeeDocuments->count() }} file</small></div>
            <div class="tm-card tm-list">
                @forelse($employeeDocuments as $document)
                    <article class="tm-list-row"><div><strong>{{ $document->decree_kind ?: 'SK Yayasan' }}</strong><span>{{ $document->decree_number ?: $document->original_name }}</span><span>{{ $document->decree_date?->format('d-m-Y') ?: '-' }} &middot; {{ explode(':', $document->document_type)[1] ?? '-' }}</span>@if($document->notes)<span>{{ $document->notes }}</span>@endif</div><a class="tm-primary-button tm-download" href="{{ route('documents.download', $document) }}">Download SK</a></article>
                @empty
                    <div class="tm-empty"><strong>Belum Ada Surat Keputusan</strong><br>Surat Keputusan yang telah diterbitkan oleh Admin Induk akan muncul pada halaman ini.</div>
                @endforelse
            </div>
        </section>
        @include('filament.app.mobile.bottom-nav', ['active' => 'decrees'])
    </main>
@endif

<div class="teacher-desktop sk-mine-page">
    <style>
        .sk-mine-page{display:grid;gap:1rem;color:#101828}.sk-mine-hero{overflow:hidden;border:1px solid #cde8db;border-radius:1rem;background:linear-gradient(135deg,#ecfdf3,#eff8ff);padding:1.35rem 1.5rem;box-shadow:0 1px 3px rgba(16,24,40,.06),0 4px 12px rgba(16,24,40,.04)}.sk-mine-hero small{color:#067647;font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.sk-mine-hero h1{margin-top:.35rem;font-size:1.4rem;font-weight:800}.sk-mine-hero p{margin-top:.3rem;color:#475467;font-size:.8rem}.sk-mine-card{overflow:hidden;border:1px solid #eaecf0;border-radius:1rem;background:#fff;box-shadow:0 1px 3px rgba(16,24,40,.06)}.sk-mine-card-head{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #eaecf0;padding:1rem 1.1rem}.sk-mine-card-head h2{font-size:1rem;font-weight:800}.sk-mine-table-wrap{overflow-x:auto}.sk-mine-table{width:100%;min-width:58rem;border-collapse:collapse}.sk-mine-table th,.sk-mine-table td{border-bottom:1px solid #eaecf0;padding:.8rem .9rem;text-align:left;font-size:.76rem}.sk-mine-table th{background:#f6f9fc;color:#475467;font-size:.68rem;text-transform:uppercase}.sk-mine-download{display:inline-flex;border-radius:.55rem;background:linear-gradient(135deg,#12643a,#1d6fa5);padding:.5rem .75rem;color:#fff;font-size:.7rem;font-weight:800}.sk-mine-empty{padding:3.5rem 1rem;color:#667085;text-align:center}.sk-mine-empty strong{display:block;margin-bottom:.35rem;color:#101828;font-size:1rem}
    </style>
    <header class="sk-mine-hero"><small>Surat Keputusan Saya</small><h1>{{ $employee?->name ?? $user?->name }}</h1><p>Daftar Surat Keputusan yang telah diterbitkan untuk akun Anda.</p></header>
    <section class="sk-mine-card"><header class="sk-mine-card-head"><h2>Daftar Surat Keputusan</h2><span>{{ $employeeDocuments->count() }} file</span></header><div class="sk-mine-table-wrap"><table class="sk-mine-table"><thead><tr><th>No</th><th>Jenis SK</th><th>Nomor SK</th><th>Tanggal SK</th><th>Tahun</th><th>Keterangan</th><th>File</th><th>Aksi</th></tr></thead><tbody>
        @forelse($employeeDocuments as $document)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $document->decree_kind ?: 'SK Yayasan' }}</td><td>{{ $document->decree_number ?: '-' }}</td><td>{{ $document->decree_date?->format('d-m-Y') ?: '-' }}</td><td>{{ explode(':', $document->document_type)[1] ?? '-' }}</td><td>{{ $document->notes ?: '-' }}</td><td>{{ $document->original_name }}</td><td><a class="sk-mine-download" href="{{ route('documents.download', $document) }}">Download SK</a></td></tr>
        @empty
            <tr><td colspan="8"><div class="sk-mine-empty"><strong>Belum Ada Surat Keputusan</strong>Surat Keputusan yang telah diterbitkan oleh Admin Induk akan muncul pada halaman ini.</div></td></tr>
        @endforelse
    </tbody></table></div></section>
</div>
