<div class="sk-dashboard">
    <style>
        .sk-dashboard{display:grid;gap:1.5rem}.sk-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}.sk-card{padding:1.25rem;background:#fff;border:1px solid #e2e8f0;border-radius:12px}.sk-label{color:#64748b;font-size:.875rem}.sk-number{margin-top:.5rem;color:#164db9;font-size:2rem;font-weight:700}.sk-toolbar{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1rem}.sk-toolbar h2{font-weight:700}.sk-dashboard a{color:#164db9;font-weight:600}.sk-table-wrap{overflow:auto}.sk-table{width:100%;border-collapse:collapse;text-align:left;font-size:.875rem}.sk-table th,.sk-table td{padding:.9rem;border-bottom:1px solid #e2e8f0}.sk-table th{color:#64748b;white-space:nowrap}.sk-empty{text-align:center;color:#64748b}.sk-status{display:inline-block;padding:.25rem .5rem;border-radius:6px;background:#eff6ff;color:#164db9;white-space:nowrap}.dark .sk-card{background:#171b22;border-color:#364152}.dark .sk-table th,.dark .sk-table td{border-color:#364152}.dark .sk-number,.dark .sk-dashboard a{color:#93c5fd}@media(max-width:900px){.sk-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:500px){.sk-summary{grid-template-columns:1fr}}
    </style>
    <section class="sk-summary" aria-label="Ringkasan pengajuan SK">
        @foreach ($summary as $label => $value)
            <article class="sk-card"><p class="sk-label">{{ $label }}</p><p class="sk-number">{{ number_format($value, 0, ',', '.') }}</p></article>
        @endforeach
    </section>
    <section class="sk-card">
        <div class="sk-toolbar"><h2>Pengajuan Terbaru</h2><a href="{{ $listUrl }}">Lihat Semua</a></div>
        <div class="sk-table-wrap">
            <table class="sk-table">
                <thead><tr><th>Nama Guru/Pegawai</th><th>Madrasah/Sekolah</th><th>Tanggal Pengajuan</th><th>Jenis Pengajuan SK</th><th>Status</th><th>Detail</th></tr></thead>
                <tbody>
                    @forelse ($submissions as $submission)
                        <tr>
                            <td>{{ $submission->employee?->name ?? '-' }}</td>
                            <td>{{ $submission->school?->name ?? '-' }}</td>
                            <td>{{ $submission->submission_date?->format('d-m-Y') ?? '-' }}</td>
                            <td>{{ $submission->type?->name ?? '-' }}</td>
                            <td><span class="sk-status">{{ $statuses[$submission->status] ?? $submission->status }}</span></td>
                            <td>@can('view', $submission)<a href="{{ \App\Filament\Resources\DecreeSubmissions\DecreeSubmissionResource::getUrl('view', ['record' => $submission], panel: 'admin', isAbsolute: false) }}">Detail</a>@endcan</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="sk-empty">Belum ada pengajuan SK.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
