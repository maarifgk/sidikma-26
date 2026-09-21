@php
    $currentHour = (int) now('Asia/Jakarta')->format('H');
    $greeting = $currentHour < 11 ? 'Selamat Pagi' : ($currentHour < 15 ? 'Selamat Siang' : ($currentHour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
    $userName = auth()->user()?->name ?? 'Pengguna';
    $initials = collect(preg_split('/\s+/', trim($userName)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp

<div class="ar-page" x-data="{ search: '' }">
    <style>
        .ar-page{--ar-blue:#0f56c5;--ar-green:#16a34a;--ar-ink:#172b4d;--ar-muted:#64748b;display:grid;gap:1.25rem;color:var(--ar-ink)}
        .ar-hero{display:flex;align-items:center;justify-content:space-between;gap:1.5rem;border:1px solid rgba(255,255,255,.18);border-radius:14px;background:linear-gradient(125deg,#0f4fb7,#176acb);padding:1.25rem 1.5rem;color:#fff;box-shadow:0 12px 28px rgba(15,86,197,.17)}
        .ar-heading,.ar-user,.ar-card-title,.ar-actions,.ar-actions-left,.ar-exports,.ar-selfies,.ar-location{display:flex;align-items:center}.ar-heading{min-width:0;gap:.9rem}.ar-heading-icon{display:grid;width:2.8rem;height:2.8rem;flex:none;place-items:center;border-radius:.75rem;background:rgba(255,255,255,.14)}.ar-heading-icon svg,.ar-card-title svg{width:1.25rem;height:1.25rem}.ar-title{font-size:1.55rem;font-weight:800;line-height:1.2}.ar-sub{max-width:44rem;margin-top:.3rem;color:#d7e7fb;font-size:.78rem;line-height:1.55}.ar-hero-side{display:flex;flex:none;align-items:center;gap:1rem}.ar-user{justify-content:flex-end;gap:.65rem;text-align:right}.ar-greeting{display:block;color:#d9e8fb;font-size:.68rem}.ar-user strong{display:block;max-width:13rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.8rem}.ar-avatar{display:grid;width:2.45rem;height:2.45rem;place-items:center;border:2px solid rgba(255,255,255,.52);border-radius:50%;background:#fff;color:var(--ar-blue);font-size:.72rem;font-weight:900}.ar-dashboard{display:inline-flex;height:42px;align-items:center;gap:.45rem;border:1px solid rgba(255,255,255,.48);border-radius:.55rem;padding:0 .85rem;color:#fff;font-size:.73rem;font-weight:800;transition:.2s ease}.ar-dashboard svg,.ar-button svg,.ar-date svg,.ar-location svg,.ar-search svg{width:1rem;height:1rem;flex:none}.ar-dashboard:hover{background:rgba(255,255,255,.13)}
        .ar-panel,.ar-card{border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 5px 18px rgba(37,64,97,.055)}.ar-panel{padding:1.35rem 1.45rem}.ar-panel-title{margin-bottom:1rem;color:#334155;font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.055em}.ar-filter-grid{display:grid;grid-template-columns:minmax(180px,1.25fr) repeat(4,minmax(135px,.78fr));gap:1rem}.ar-field label{display:block;margin-bottom:.42rem;color:#64748b;font-size:.65rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.ar-input{width:100%;height:44px;border:1px solid #cfd9e5;border-radius:8px;background:#fff;padding:0 .72rem;color:#334155;font-size:.78rem;outline:0;transition:.2s ease}.ar-input:hover{border-color:#aebdce}.ar-input:focus{border-color:var(--ar-blue);box-shadow:0 0 0 3px rgba(15,86,197,.1)}.ar-input:disabled{cursor:not-allowed;background:#f1f5f9;color:#94a3b8}.ar-filter-grid-secondary{display:grid;max-width:42rem;grid-template-columns:repeat(2,minmax(190px,1fr));gap:1rem;margin-top:1rem}.ar-actions{justify-content:space-between;gap:1rem;margin-top:1.15rem;padding-top:1rem;border-top:1px solid #edf1f5}.ar-actions-left,.ar-exports{flex-wrap:wrap;gap:.65rem}.ar-button{display:inline-flex;min-height:42px;cursor:pointer;align-items:center;justify-content:center;gap:.45rem;border:1px solid transparent;border-radius:8px;padding:.62rem .9rem;font-size:.72rem;font-weight:800;transition:.2s ease}.ar-button:focus-visible,.ar-dashboard:focus-visible,.ar-selfie:focus-visible{outline:3px solid rgba(15,86,197,.22);outline-offset:2px}.ar-button-primary{background:var(--ar-blue);color:#fff;box-shadow:0 5px 12px rgba(15,86,197,.14)}.ar-button-primary:hover{background:#0c48a6}.ar-button-export{border-color:#86d7a3;background:#fff;color:#15803d}.ar-button-export:hover{background:#f0fdf4;border-color:#22c55e}.ar-period-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-top:1rem}.ar-period{display:inline-flex;border:1px solid #cfe1fb;border-radius:7px;background:#eff6ff;padding:.46rem .72rem;color:#1758af;font-size:.68rem;font-weight:800;letter-spacing:.015em}
        .ar-card{min-width:0;overflow:hidden}.ar-card-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.1rem 1.25rem;border-bottom:1px solid #e8edf3}.ar-card-title{gap:.55rem;font-size:1rem;font-weight:800}.ar-card-title span{display:grid;width:2rem;height:2rem;place-items:center;border-radius:.55rem;background:#eff6ff;color:var(--ar-blue)}.ar-search{display:flex;width:min(100%,22rem);height:42px;align-items:center;gap:.5rem;border:1px solid #d2dce8;border-radius:8px;padding:0 .7rem;color:#8290a3;transition:.2s ease}.ar-search:focus-within{border-color:var(--ar-blue);box-shadow:0 0 0 3px rgba(15,86,197,.09)}.ar-search input{width:100%;border:0;background:transparent;color:#334155;font-size:.75rem;outline:0}.ar-table-wrap{overflow-x:auto}.ar-table{width:100%;min-width:86rem;border-collapse:collapse}.ar-table th{background:#f8fafc;padding:.75rem .85rem;border-bottom:1px solid #dfe6ee;color:#64748b;font-size:.62rem;font-weight:800;letter-spacing:.045em;text-align:left;text-transform:uppercase;white-space:nowrap}.ar-table td{padding:.85rem;border-bottom:1px solid #edf1f5;color:#475569;font-size:.73rem;line-height:1.45;vertical-align:middle}.ar-table tbody tr{transition:background .18s ease}.ar-table tbody tr:hover{background:#f8fafc}.ar-date{display:flex;align-items:center;gap:.4rem;color:#334155;font-weight:700;white-space:nowrap}.ar-date svg{color:#6c8fbd}.ar-name{color:#24364d;font-weight:700}.ar-badge{display:inline-flex;border-radius:999px;padding:.3rem .55rem;font-size:.63rem;font-weight:900;white-space:nowrap}.ar-status-hadir{background:#dcfce7;color:#16713b}.ar-status-terlambat{background:#fef3c7;color:#b45309}.ar-status-izin{background:#dbeafe;color:#1d4ed8}.ar-status-sakit{background:#f3e8ff;color:#7e22ce}.ar-status-alpha,.ar-status-tidak_hadir,.ar-status-absent{background:#fee2e2;color:#b91c1c}.ar-status-default{background:#eef2f6;color:#526174}.ar-time{color:#334155;font-variant-numeric:tabular-nums;font-weight:600;white-space:nowrap}.ar-location{max-width:9.5rem;align-items:flex-start;gap:.35rem;color:#52657b;font-size:.69rem;overflow-wrap:anywhere}.ar-location svg{margin-top:.08rem;color:#dc4c4c}.ar-notes{display:-webkit-box;max-width:14rem;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:3;line-height:1.5}.ar-selfies{align-items:stretch;flex-direction:column;gap:.38rem}.ar-selfie{display:inline-flex;min-height:32px;cursor:pointer;align-items:center;justify-content:center;gap:.35rem;border:1px solid #8bb3ea;border-radius:6px;padding:.35rem .55rem;color:#175bb7;font-size:.67rem;font-weight:800;transition:.2s ease}.ar-selfie svg{width:.9rem;height:.9rem}.ar-selfie:hover{background:#eff6ff}.ar-selfie-out{border-color:#b7a3ed;color:#6941c6}.ar-selfie-out:hover{background:#f5f3ff}.ar-empty{padding:3rem!important;text-align:center!important;color:#94a3b8!important}
        .dark .ar-panel,.dark .ar-card,.dark .ar-input,.dark .ar-button-export,.dark .ar-search{border-color:#364152;background:#171b22;color:#cbd5e1}.dark .ar-card-head,.dark .ar-actions{border-color:#303947}.dark .ar-table th{background:#202630;color:#aeb9c7}.dark .ar-table td{border-color:#303844;color:#c4ceda}.dark .ar-table tbody tr:hover{background:#202630}.dark .ar-card-title,.dark .ar-name,.dark .ar-date,.dark .ar-time{color:#e2e8f0}
        @media(max-width:1199px){.ar-filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.ar-actions{align-items:flex-start;flex-direction:column}.ar-exports{width:100%}}
        @media(max-width:767px){.ar-page{gap:1rem}.ar-hero{align-items:flex-start;flex-direction:column;padding:1.1rem}.ar-hero-side{width:100%;justify-content:space-between}.ar-title{font-size:1.3rem}.ar-panel{padding:1rem}.ar-filter-grid,.ar-filter-grid-secondary{max-width:none;grid-template-columns:1fr}.ar-actions-left,.ar-exports{display:grid;width:100%;grid-template-columns:1fr}.ar-button{width:100%}.ar-period-row{align-items:stretch;flex-direction:column}.ar-period{justify-content:center;text-align:center}.ar-card-head{align-items:stretch;flex-direction:column}.ar-search{width:100%}}
        @media(max-width:430px){.ar-heading-icon{display:none}.ar-hero-side{align-items:flex-end;flex-direction:column}.ar-user{align-self:stretch}.ar-dashboard{width:100%;justify-content:center}}
    </style>

    <section class="ar-hero">
        <div class="ar-heading">
            <span class="ar-heading-icon"><x-filament::icon icon="heroicon-o-calendar-days" /></span>
            <div>
                <h1 class="ar-title">Laporan Presensi</h1>
                <p class="ar-sub">Filter dan export laporan harian, mingguan, bulanan, atau rentang kustom untuk seluruh sekolah.</p>
            </div>
        </div>

        <div class="ar-hero-side">
            <div class="ar-user">
                <div>
                    <span class="ar-greeting">{{ $greeting }},</span>
                    <strong>{{ mb_strtoupper($userName) }}</strong>
                </div>
                <span class="ar-avatar">{{ $initials ?: 'U' }}</span>
            </div>
            <a href="{{ $dashboardUrl }}" class="ar-dashboard">
                <x-filament::icon icon="heroicon-o-home" />
                Dashboard
            </a>
        </div>
    </section>

    <section class="ar-panel" aria-labelledby="ar-filter-title">
        <h2 id="ar-filter-title" class="ar-panel-title">Filter Presensi</h2>

        <div class="ar-filter-grid">
            <div class="ar-field">
                <label for="ar-school">Madrasah / Sekolah</label>
                <select id="ar-school" wire:model.live="selectedSchoolId" class="ar-input">
                    <option value="">Semua sekolah</option>
                    @foreach($schoolOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="ar-field">
                <label for="ar-type">Jenis Laporan</label>
                <select id="ar-type" wire:model.live="reportType" class="ar-input">
                    <option value="custom">Kustom</option>
                    <option value="daily">Harian</option>
                    <option value="weekly">Mingguan</option>
                    <option value="monthly">Bulanan</option>
                </select>
            </div>

            <div class="ar-field">
                <label for="ar-reference">Tanggal Acuan</label>
                <input id="ar-reference" type="date" wire:model.live="referenceDate" class="ar-input">
            </div>

            <div class="ar-field">
                <label for="ar-from">Dari</label>
                <input id="ar-from" type="date" wire:model.live="dateFrom" class="ar-input" @disabled($reportType !== 'custom')>
            </div>

            <div class="ar-field">
                <label for="ar-to">Sampai</label>
                <input id="ar-to" type="date" wire:model.live="dateTo" class="ar-input" @disabled($reportType !== 'custom')>
            </div>
        </div>

        <div class="ar-filter-grid-secondary">
            <div class="ar-field">
                <label for="ar-user">User</label>
                <select id="ar-user" wire:model.live="selectedEmployeeId" class="ar-input">
                    <option value="">Semua User</option>
                    @foreach($employeeOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="ar-field">
                <label for="ar-status">Status</label>
                <select id="ar-status" wire:model.live="selectedStatus" class="ar-input">
                    <option value="">Semua</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="ar-actions">
            <div class="ar-actions-left">
                <button type="button" wire:click="$refresh" class="ar-button ar-button-primary">
                    <x-filament::icon icon="heroicon-o-funnel" />
                    Filter
                </button>
                <button type="button" wire:click="exportReport" wire:loading.attr="disabled" class="ar-button ar-button-export">
                    <x-filament::icon icon="heroicon-o-arrow-down-tray" />
                    Export Sesuai Filter
                </button>
            </div>

            <div class="ar-exports">
                <button type="button" wire:click="exportReportType('daily')" wire:loading.attr="disabled" class="ar-button ar-button-export">
                    <x-filament::icon icon="heroicon-o-calendar" />
                    Export Harian
                </button>
                <button type="button" wire:click="exportReportType('weekly')" wire:loading.attr="disabled" class="ar-button ar-button-export">
                    <x-filament::icon icon="heroicon-o-calendar-days" />
                    Export Mingguan
                </button>
                <button type="button" wire:click="exportReportType('monthly')" wire:loading.attr="disabled" class="ar-button ar-button-export">
                    <x-filament::icon icon="heroicon-o-calendar-days" />
                    Export Bulanan
                </button>
            </div>
        </div>

        <div class="ar-period-row">
            <span class="ar-period">PERIODE AKTIF: {{ $fromLabel }} S/D {{ $toLabel }}</span>
        </div>
    </section>

    <section class="ar-card" aria-labelledby="ar-data-title">
        <header class="ar-card-head">
            <h2 id="ar-data-title" class="ar-card-title">
                <span><x-filament::icon icon="heroicon-o-calendar-days" /></span>
                Data Presensi
            </h2>
            <label class="ar-search">
                <x-filament::icon icon="heroicon-o-magnifying-glass" />
                <input type="search" x-model.debounce.200ms="search" placeholder="Cari nama, sekolah, atau keterangan..." aria-label="Cari data presensi">
            </label>
        </header>

        <div class="ar-table-wrap">
            <table class="ar-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Sekolah</th>
                        <th>Nama</th>
                        <th>Status</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Lokasi Masuk</th>
                        <th>Lokasi Pulang</th>
                        <th>Keterangan</th>
                        <th>Selfie</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        @php
                            $statusKey = strtolower((string) $record->status);
                            $statusClass = in_array($statusKey, ['hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'tidak_hadir', 'absent'], true) ? 'ar-status-'.$statusKey : 'ar-status-default';
                            $searchText = mb_strtolower(($record->school?->name ?? '').' '.($record->employee?->name ?? '').' '.($record->notes ?? '').' '.($statusOptions[$record->status] ?? $record->status));
                        @endphp
                        <tr x-show="!search || @js($searchText).includes(search.toLowerCase())">
                            <td>
                                <span class="ar-date">
                                    <x-filament::icon icon="heroicon-o-calendar-days" />
                                    {{ $record->attendance_date?->format('d-m-Y') ?? '-' }}
                                </span>
                            </td>
                            <td>{{ $record->school?->name ?? '-' }}</td>
                            <td class="ar-name">{{ $record->employee?->name ?? '-' }}</td>
                            <td><span class="ar-badge {{ $statusClass }}">{{ strtoupper($statusOptions[$record->status] ?? $record->status) }}</span></td>
                            <td><span class="ar-time">{{ $record->check_in_at?->timezone('Asia/Jakarta')->format('H:i:s') ?? '-' }}</span></td>
                            <td><span class="ar-time">{{ $record->check_out_at?->timezone('Asia/Jakarta')->format('H:i:s') ?? '-' }}</span></td>
                            <td>
                                @if(filled($record->check_in_latitude) && filled($record->check_in_longitude))
                                    <span class="ar-location">
                                        <x-filament::icon icon="heroicon-o-map-pin" />
                                        <span>{{ $record->check_in_latitude }},<br>{{ $record->check_in_longitude }}</span>
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if(filled($record->check_out_latitude) && filled($record->check_out_longitude))
                                    <span class="ar-location">
                                        <x-filament::icon icon="heroicon-o-map-pin" />
                                        <span>{{ $record->check_out_latitude }},<br>{{ $record->check_out_longitude }}</span>
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td><span class="ar-notes" title="{{ $record->notes }}">{{ $record->notes ?: '-' }}</span></td>
                            <td>
                                <div class="ar-selfies">
                                    @if($record->check_in_selfie_path)
                                        <a class="ar-selfie" target="_blank" href="{{ route('attendance.selfie.view', [$record, 'masuk']) }}">
                                            <x-filament::icon icon="heroicon-o-camera" />
                                            Masuk
                                        </a>
                                    @endif

                                    @if($record->check_out_selfie_path)
                                        <a class="ar-selfie ar-selfie-out" target="_blank" href="{{ route('attendance.selfie.view', [$record, 'pulang']) }}">
                                            <x-filament::icon icon="heroicon-o-camera" />
                                            Pulang
                                        </a>
                                    @endif

                                    @if(! $record->check_in_selfie_path && ! $record->check_out_selfie_path)
                                        <span>-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="ar-empty">Belum ada data presensi pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
