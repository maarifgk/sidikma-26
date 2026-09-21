<div class="fd-page">
    <style>
        .fd-page { display: grid; gap: 1rem; color: #5c7088; }
        .fd-card { border: 1px solid #e2e8f0; border-radius: .7rem; background: #fff; box-shadow: 0 8px 20px rgba(25, 53, 84, .06); }
        .fd-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem; }
        .fd-title { font-size: 1.1rem; font-weight: 800; }
        .fd-subtitle { margin-top: .2rem; color: #91a0b2; font-size: .72rem; }
        .fd-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
        .fd-stat { padding: 1rem; }
        .fd-stat-label { color: #91a0b2; font-size: .72rem; }
        .fd-stat-value { margin-top: .45rem; font-size: 1.55rem; font-weight: 800; }
        .fd-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; padding: 1rem; }
        .fd-upload-form { grid-template-columns: 1.3fr .5fr .75fr 1fr; }
        .fd-field { position: relative; }
        .fd-field label { display: block; margin-bottom: .4rem; font-size: .68rem; font-weight: 800; text-transform: uppercase; }
        .fd-input { width: 100%; border: 1px solid #cfd9e5; border-radius: .45rem; background: #fff; padding: .65rem .75rem; font-size: .8rem; }
        .fd-search-results { position: absolute; z-index: 20; top: calc(100% + .3rem); right: 0; left: 0; overflow: hidden; border: 1px solid #d8e1eb; border-radius: .5rem; background: #fff; box-shadow: 0 12px 28px rgba(25, 53, 84, .16); }
        .fd-result { display: block; width: 100%; padding: .65rem .75rem; border-bottom: 1px solid #edf1f5; text-align: left; }
        .fd-result:hover { background: #f2f7ff; }
        .fd-result strong { display: block; font-size: .78rem; }
        .fd-result span { color: #8a99aa; font-size: .68rem; }
        .fd-selected { display: flex; align-items: center; justify-content: space-between; gap: .5rem; min-height: 2.55rem; border: 1px solid #b8d6c7; border-radius: .45rem; background: #f0faf5; padding: .5rem .7rem; }
        .fd-selected strong { display: block; color: #24734f; font-size: .78rem; }
        .fd-selected span { font-size: .67rem; }
        .fd-clear { color: #c33; font-size: .7rem; font-weight: 700; }
        .fd-actions { grid-column: 1 / -1; }
        .fd-button { display: inline-flex; align-items: center; justify-content: center; border-radius: .42rem; background: #164db9; padding: .62rem 1rem; color: #fff; font-size: .76rem; font-weight: 800; }
        .fd-button-small { padding: .42rem .72rem; font-size: .68rem; }
        .fd-button-outline { border: 1px solid #596dff; background: #fff; color: #596dff; }
        .fd-button-muted { background: #8796a8; }
        .fd-button-import { background: #f5a400; }
        .fd-button-danger { background: #d92d20; }
        .fd-button-success { background: #12643a; }
        .fd-action-list { display: flex; flex-wrap: wrap; gap: .35rem; }
        .fd-filter-grid { display: grid; grid-template-columns: 1.4fr repeat(3, minmax(9rem, .6fr)); gap: .65rem; padding: 1rem; }
        .fd-notes { min-height: 5.5rem; resize: vertical; }
        .fd-status { display: inline-flex; border-radius: .25rem; padding: .2rem .45rem; background: #65d932; color: #fff; font-size: .66rem; font-weight: 800; }
        .fd-status-off { background: #94a3b8; }
        .fd-checkbox { display: flex; align-items: center; gap: .5rem; min-height: 2.55rem; }
        .fd-summary { margin: 0 1rem 1rem; border-radius: .45rem; background: #eef8f3; padding: .75rem; color: #26724f; font-size: .74rem; }
        .fd-warning { color: #e73c65; font-size: .68rem; }
        .fd-table-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem; border-bottom: 1px solid #e4eaf0; }
        .fd-table-search { width: min(100%, 23rem); }
        .fd-table-wrap { overflow-x: auto; }
        .fd-table { width: 100%; min-width: 58rem; border-collapse: collapse; }
        .fd-table th, .fd-table td { padding: .72rem .8rem; border-bottom: 1px solid #e6ebf0; text-align: left; font-size: .72rem; }
        .fd-table th { color: #718198; font-size: .64rem; letter-spacing: .05em; text-transform: uppercase; }
        .fd-file { display: inline-block; max-width: 30rem; overflow: hidden; color: #164db9; text-overflow: ellipsis; white-space: nowrap; text-decoration: underline; }
        .fd-empty { padding: 2.5rem 1rem; color: #91a0b2; text-align: center; font-size: .8rem; }
        .dark .fd-card, .dark .fd-input, .dark .fd-search-results, .dark .fd-button-outline { border-color: #364152; background: #171b22; }
        .dark .fd-page { color: #c1ccda; }
        @media (max-width: 1100px) { .fd-stats { grid-template-columns: repeat(2, 1fr); } .fd-filter-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 900px) { .fd-form { grid-template-columns: 1fr; } .fd-actions { grid-column: auto; } .fd-table-head { align-items: stretch; flex-direction: column; } .fd-table-search { width: 100%; } }
        @media (max-width: 600px) { .fd-stats, .fd-filter-grid { grid-template-columns: 1fr; } }
    </style>

    <section id="dashboard-sk" class="fd-card fd-head">
        <div><h1 class="fd-title">Manajemen Surat Keputusan (SK)</h1><p class="fd-subtitle">Kelola dan distribusikan file Surat Keputusan kepada guru/pegawai.</p></div>
    </section>

    <section class="fd-stats">
        <article class="fd-card fd-stat"><div class="fd-stat-label">Total SK</div><div class="fd-stat-value">{{ number_format($totalDocuments, 0, ',', '.') }}</div></article>
        <article class="fd-card fd-stat"><div class="fd-stat-label">SK Bulan Ini</div><div class="fd-stat-value">{{ number_format($currentMonthDocuments, 0, ',', '.') }}</div></article>
        <article class="fd-card fd-stat"><div class="fd-stat-label">Guru/Pegawai Mendapat SK</div><div class="fd-stat-value">{{ number_format($linkedUsers, 0, ',', '.') }}</div></article>
        <article class="fd-card fd-stat"><div class="fd-stat-label">SK Terbaru</div><div class="fd-stat-value" style="font-size:.95rem">{{ $latestDocument?->owner?->name ?? 'Belum ada' }}</div><div class="fd-subtitle">{{ $latestDocument?->created_at?->format('d-m-Y H:i') }}</div></article>
    </section>

    <section id="kelola-sk" class="fd-card">
        <header class="fd-table-head">
            <div><h2 class="fd-title">Template SK</h2><p class="fd-subtitle">Template yang dipakai untuk generate SK Yayasan.</p></div>
            <button type="button" wire:click="openCreateTemplate" class="fd-button fd-button-small">Tambah</button>
        </header>

        @if ($showTemplateForm)
            <form wire:submit="saveTemplate" class="fd-form">
                <div class="fd-field"><label>Nama Template</label><input type="text" wire:model="templateName" class="fd-input" placeholder="Contoh: SK Perpanjangan Tahun 2026">@error('templateName') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror</div>
                <div class="fd-field"><label>File Template</label><input type="file" wire:model="decreeTemplateFile" class="fd-input" accept=".pdf,.doc,.docx">@error('decreeTemplateFile') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror</div>
                <div class="fd-field"><label>Ukuran Kertas</label><select wire:model="templatePaperSize" class="fd-input"><option value="A4">A4</option><option value="F4">F4</option></select></div>
                <div class="fd-field"><label>Orientasi</label><select wire:model="templateOrientation" class="fd-input"><option value="portrait">Portrait</option><option value="landscape">Landscape</option></select></div>
                <div class="fd-field"><label>Status</label><label class="fd-checkbox"><input type="checkbox" wire:model="templateIsActive"> Template aktif</label></div>
                <div class="fd-actions"><button type="submit" class="fd-button" wire:loading.attr="disabled">Simpan Template</button> <button type="button" wire:click="cancelTemplateForm" class="fd-button fd-button-muted">Batal</button></div>
            </form>
        @endif

        <div class="fd-table-wrap">
            <table class="fd-table">
                <thead><tr><th>Nama</th><th>Status</th><th>Kertas</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr>
                            <td><strong>{{ $template->name }}</strong></td>
                            <td><span class="fd-status {{ $template->is_active ? '' : 'fd-status-off' }}">{{ $template->is_active ? 'AKTIF' : 'NONAKTIF' }}</span></td>
                            <td>{{ $template->paper_size }} / {{ ucfirst($template->orientation) }}</td>
                            <td><button type="button" wire:click="generateTemplate({{ $template->getKey() }})" class="fd-button fd-button-small">Generate</button> <button type="button" wire:click="openEditTemplate({{ $template->getKey() }})" class="fd-button fd-button-small fd-button-outline">Edit</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="fd-empty">Belum ada template SK. Klik Tambah untuk membuat template.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="fd-card">
        <div class="fd-head"><div><h2 class="fd-title">{{ $editingDocumentId ? 'Edit Surat Keputusan' : 'Upload Surat Keputusan' }}</h2><p class="fd-subtitle">Pilih guru/pegawai berdasarkan data asli, lalu unggah file PDF secara privat.</p></div></div>
        <form wire:submit="{{ $editingDocumentId ? 'updateDecree' : 'uploadDecree' }}" class="fd-form">
            <div class="fd-field">
                <label>Guru/Pegawai</label>
                @if ($selectedUser)
                    <div class="fd-selected"><div><strong>{{ $selectedUser->name }}</strong><span>{{ $selectedUser->email }}</span></div><button type="button" wire:click="clearSelectedUser" class="fd-clear">Ganti</button></div>
                @else
                    <input type="search" wire:model.live.debounce.300ms="userSearch" class="fd-input" placeholder="Ketik minimal 2 karakter untuk mencari user..." autocomplete="off">
                    @if (mb_strlen(trim($userSearch)) >= 2)
                        <div class="fd-search-results">
                            @forelse ($userResults as $result)
                                <button type="button" wire:click="selectUser({{ $result->getKey() }})" class="fd-result"><strong>{{ $result->name }}</strong><span>{{ $result->email }} · {{ $result->employee?->school?->name ?? $result->memberships->firstWhere('school_id', '!=', null)?->school?->name ?? '-' }}</span></button>
                            @empty
                                <div class="fd-result"><span>User tidak ditemukan.</span></div>
                            @endforelse
                        </div>
                    @endif
                @endif
                @error('selectedUserId') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
            </div>
            <div class="fd-field"><label>Madrasah/Sekolah</label><input type="text" class="fd-input" value="{{ $selectedUser?->employee?->school?->name ?? $selectedUser?->memberships?->firstWhere('school_id', '!=', null)?->school?->name ?? '' }}" placeholder="Terisi otomatis setelah memilih guru/pegawai" disabled></div>
            <div class="fd-field"><label>Jenis SK</label><input type="text" wire:model="decreeKind" class="fd-input" placeholder="Contoh: SK Pengangkatan">@error('decreeKind') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror</div>
            <div class="fd-field"><label>Nomor SK</label><input type="text" wire:model="decreeNumber" class="fd-input" placeholder="Nomor Surat Keputusan">@error('decreeNumber') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror</div>
            <div class="fd-field"><label>Tanggal SK</label><input type="date" wire:model="decreeDate" class="fd-input">@error('decreeDate') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror</div>
            <div class="fd-field"><label>Tahun</label><input type="text" class="fd-input" value="{{ $decreeDate ? substr($decreeDate, 0, 4) : '' }}" placeholder="Otomatis dari tanggal SK" disabled></div>
            <div class="fd-field"><label>Template SK</label><select wire:model="decreeTemplateId" class="fd-input"><option value="">Tanpa Template</option>@foreach ($templateOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
            <div class="fd-field"><label>File SK (PDF){{ $editingDocumentId ? ' — opsional jika tidak diganti' : '' }}</label><input type="file" wire:model="decreeFile" class="fd-input" accept="application/pdf">@error('decreeFile') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror</div>
            <div class="fd-field" style="grid-column:1/-1"><label>Keterangan</label><textarea wire:model="decreeNotes" class="fd-input fd-notes" placeholder="Keterangan opsional"></textarea>@error('decreeNotes') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror</div>
            <div class="fd-actions"><button type="submit" class="fd-button fd-button-success" wire:loading.attr="disabled">{{ $editingDocumentId ? 'Simpan Perubahan' : 'Upload & Hubungkan ke Guru/Pegawai' }}</button>@if($editingDocumentId) <button type="button" wire:click="cancelEditDecree" class="fd-button fd-button-muted">Batal</button>@endif</div>
        </form>
    </section>

    <section class="fd-card">
        <div class="fd-head"><div><h2 class="fd-title">Import Banyak File SK</h2><p class="fd-subtitle">Sistem mencocokkan nama file ke user berdasarkan nama, EWANUGK/NIS, NIK, NIP, atau NUPTK yang ada di nama file.</p></div></div>
        <form wire:submit="importBulkDecrees" class="fd-form">
            <div class="fd-field"><label>Tahun SK</label><input type="number" wire:model="bulkYear" class="fd-input" min="2000" max="2100">@error('bulkYear') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror</div>
            <div class="fd-field"><label>Template SK</label><select wire:model="bulkTemplateId" class="fd-input"><option value="">Tanpa Template</option>@foreach ($templateOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
            <div class="fd-field"><label>Batasi ke Sekolah/Madrasah</label><select wire:model="bulkSchoolId" class="fd-input"><option value="">Semua Sekolah/Madrasah</option>@foreach ($schoolOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
            <div class="fd-field"><label>File SK Banyak User</label><input type="file" wire:model="bulkFiles" class="fd-input" accept="application/pdf" multiple>@error('bulkFiles') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror @error('bulkFiles.*') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror</div>
            <div class="fd-actions"><p class="fd-subtitle">Contoh nama file: <span class="fd-warning">sk-heru-agung-nugroho-2026.pdf</span> atau <span class="fd-warning">sk-EWANUGK-SK-001-2026.pdf</span>.</p><button type="submit" class="fd-button fd-button-import" wire:loading.attr="disabled">Import &amp; Cocokkan Otomatis</button></div>
        </form>
        @if ($bulkImportSummary)
            <div class="fd-summary"><strong>Hasil impor:</strong> {{ $bulkImportSummary['imported'] }} berhasil, {{ $bulkImportSummary['skipped'] }} duplikat dilewati, {{ count($bulkImportSummary['unmatched']) }} tidak cocok.@if ($bulkImportSummary['unmatched'])<br>File tidak cocok: {{ implode(', ', $bulkImportSummary['unmatched']) }}@endif</div>
        @endif
    </section>

    <section class="fd-card">
        <header class="fd-table-head"><div><h2 class="fd-title">Daftar Surat Keputusan</h2><p class="fd-subtitle">Pencarian, filter, download, edit, dan hapus dokumen SK.</p></div></header>
        <div class="fd-filter-grid">
            <input type="search" wire:model.live.debounce.300ms="documentSearch" class="fd-input" placeholder="Cari nama, sekolah, nomor SK, atau file...">
            <select wire:model.live="documentYearFilter" class="fd-input"><option value="">Semua Tahun</option>@foreach($yearOptions as $year)<option value="{{ $year }}">{{ $year }}</option>@endforeach</select>
            <select wire:model.live="documentKindFilter" class="fd-input"><option value="">Semua Jenis SK</option>@foreach($kindOptions as $kind)<option value="{{ $kind }}">{{ $kind }}</option>@endforeach</select>
            <select wire:model.live="documentSchoolFilter" class="fd-input"><option value="">Semua Sekolah/Madrasah</option>@foreach($schoolOptions as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
        </div>
        <div class="fd-table-wrap">
            <table class="fd-table">
                <thead><tr><th>No</th><th>Nama Guru/Pegawai</th><th>Madrasah/Sekolah</th><th>Jenis SK</th><th>Nomor SK</th><th>Tanggal SK</th><th>Tahun</th><th>File SK</th><th>Tanggal Upload</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr><td>{{ $documents->firstItem() + $loop->index }}</td><td>{{ $document['user'] }}</td><td>{{ $document['school'] }}</td><td>{{ $document['kind'] }}</td><td>{{ $document['number'] }}</td><td>{{ $document['date'] }}</td><td>{{ $document['year'] }}</td><td><span class="fd-file" title="{{ $document['file'] }}">{{ $document['file'] }}</span></td><td>{{ $document['uploadedAt'] }}</td><td><div class="fd-action-list"><a href="{{ $document['downloadUrl'] }}" class="fd-button fd-button-small">Download</a><button type="button" wire:click="editDecree({{ $document['id'] }})" class="fd-button fd-button-small fd-button-outline">Edit</button><button type="button" wire:click="deleteDecree({{ $document['id'] }})" wire:confirm="Hapus data dan file SK ini?" class="fd-button fd-button-small fd-button-danger">Hapus</button></div></td></tr>
                    @empty
                        <tr><td colspan="10" class="fd-empty">Belum Ada Surat Keputusan. Dokumen yang diunggah Admin Induk akan muncul di sini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($documents->hasPages())<div style="padding:1rem">{{ $documents->links() }}</div>@endif
    </section>
</div>
