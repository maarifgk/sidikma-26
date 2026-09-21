<div class="ui-page">
    <section class="ui-page-intro">
        <div><h2>Informasi Pembayaran</h2><p>Lihat ketentuan dan nominal iuran LP Ma'arif NU PCNU Gunungkidul.</p></div>
    </section>

    <section class="ui-card">
        <header class="ui-card-head"><div><h2>Filter Data</h2><p>Pilih madrasah atau nama untuk menemukan data pembayaran.</p></div></header>
        <div class="ui-card-body">
            <form wire:submit="searchPayments" class="ui-filter-grid">
                <label class="ui-field">
                    <span class="ui-label">Asal Madrasah</span>
                    <select wire:model.live="schoolId" class="ui-select">
                        <option value="">Pilih madrasah</option>
                        @foreach ($schools as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                    </select>
                    @error('schoolId') <small class="ui-error">{{ $message }}</small> @enderror
                </label>
                <label class="ui-field">
                    <span class="ui-label">EWANUGK/Nama</span>
                    <select wire:model="employeeId" class="ui-select">
                        <option value="">Pilih nama</option>
                        @foreach ($employees as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                    </select>
                    @error('employeeId') <small class="ui-error">{{ $message }}</small> @enderror
                </label>
                <div class="ui-filter-actions">
                    <button type="submit" class="ui-btn ui-btn-primary" wire:loading.attr="disabled" wire:target="searchPayments">
                        <span wire:loading.remove wire:target="searchPayments">Cari</span><span wire:loading wire:target="searchPayments">Mencari...</span>
                    </button>
                    <button type="button" wire:click="refreshPayments" class="ui-btn ui-btn-secondary">Reset</button>
                </div>
            </form>
        </div>
    </section>

    <section class="ui-card">
        <header class="ui-card-head"><div><h2>Informasi Pembayaran Iuran</h2><p>LP Ma'arif NU PCNU Gunungkidul</p></div></header>
        @foreach ($feeSections as $section)
            <div class="ui-fee-group">
                <div class="ui-fee-heading"><span>{{ $section['heading'] }}</span><span class="ui-text-right">Nominal</span></div>
                @forelse ($section['items'] as $item)
                    <div class="ui-fee-row"><strong>{{ $item['label'] }}</strong><span class="ui-money ui-text-right">Rp {{ number_format($item['amount'], 0, ',', '.') }}</span></div>
                @empty
                    <div class="ui-empty">Belum ada informasi pada kelompok ini.</div>
                @endforelse
            </div>
        @endforeach
    </section>

    @if ($hasSearched)
        <section class="ui-card">
            <header class="ui-card-head"><div><h2>Hasil Pencarian Pembayaran</h2><p>Maksimal 100 data ditampilkan pada satu pencarian.</p></div></header>
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead><tr><th>No</th><th>EWANUGK</th><th>Nama</th><th>Asal Madrasah</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($results as $employee)
                            <tr>
                                <td>{{ $loop->iteration }}</td><td>{{ $employee->employee_code ?: '-' }}</td><td>{{ $employee->name }}</td><td>{{ $employee->school?->name ?: '-' }}</td>
                                <td><span class="ui-badge {{ $employee->is_active ? 'ui-badge-success' : 'ui-badge-muted' }}">{{ $employee->is_active ? 'Aktif' : 'Tidak Aktif' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="ui-empty">Data pembayaran tidak ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
