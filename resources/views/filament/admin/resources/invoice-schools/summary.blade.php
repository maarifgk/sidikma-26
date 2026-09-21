<div style="display: grid; gap: 1rem;">
    <div style="display: flex; justify-content: flex-end; align-items: center; gap: 0.5rem; font-weight: 600;">
        <x-filament::icon icon="heroicon-o-calendar-days" style="width: 1.1rem; height: 1.1rem;" />
        {{ $academicYear === 'all' ? 'SEMUA TAHUN AJARAN' : $academicYear }}
    </div>

    <div class="fi-section" style="padding: 1rem 1.25rem;">
        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem;">Filter Tahun Ajaran</div>
        <div style="display: flex; align-items: center; gap: 0.75rem; max-width: 46rem;">
            <select
                wire:model="yearFilter"
                style="flex: 1; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.625rem 0.75rem; background: transparent;"
            >
                @foreach ($academicYearOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <x-filament::button wire:click="applyAcademicYear" icon="heroicon-o-magnifying-glass">
                Cari
            </x-filament::button>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); gap: 1rem;">
        @foreach ([
            ['label' => 'Pembayaran Lunas', 'value' => $paidInvoices, 'icon' => 'heroicon-o-check-circle'],
            ['label' => 'Belum Lunas', 'value' => $unpaidInvoices, 'icon' => 'heroicon-o-clock'],
            ['label' => 'Total Guru & Pegawai', 'value' => $totalEmployees, 'icon' => 'heroicon-o-user-group'],
            ['label' => 'Asal Sekolah/Madrasah', 'value' => $totalSchools, 'icon' => 'heroicon-o-building-office-2'],
        ] as $card)
            <div class="fi-section" style="padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon :icon="$card['icon']" style="width: 2.25rem; height: 2.25rem;" />
                <div>
                    <span style="display: block; font-size: 0.8rem; opacity: 0.7;">{{ $card['label'] }}</span>
                    <strong style="display: block; font-size: 1.35rem;">{{ number_format($card['value'], 0, ',', '.') }}</strong>
                </div>
            </div>
        @endforeach
    </div>
</div>
