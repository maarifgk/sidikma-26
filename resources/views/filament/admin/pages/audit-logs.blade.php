<div class="audit-page ui-page">
    <style>
        .audit-page{min-width:0}.audit-page .audit-intro-icon{display:grid;width:2.75rem;height:2.75rem;flex:none;place-items:center;border-radius:.8rem;background:#fff;color:var(--ui-green-700);box-shadow:0 2px 8px rgba(16,24,40,.08)}.audit-page .audit-intro-icon svg{width:1.35rem;height:1.35rem}.audit-page .audit-summary{display:flex;align-items:center;gap:.55rem;white-space:nowrap}.audit-page .audit-summary strong{font-size:1.15rem;color:var(--ui-green-700)}.audit-page .audit-summary span{font-size:.72rem;color:var(--ui-muted)}.audit-page .audit-filter-grid{display:grid;grid-template-columns:repeat(2,minmax(10rem,1fr)) minmax(12rem,1.25fr) minmax(12rem,1.25fr);gap:.9rem}.audit-page .audit-filter-actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--ui-border)}.audit-page .audit-table{min-width:75rem}.audit-page .audit-table th,.audit-page .audit-table td{vertical-align:top}.audit-page .audit-table th:first-child,.audit-page .audit-table td:first-child{padding-inline-start:1.15rem}.audit-page .audit-table th:last-child,.audit-page .audit-table td:last-child{padding-inline-end:1.15rem}.audit-page .audit-time{display:grid;gap:.15rem;white-space:nowrap}.audit-page .audit-time strong{color:var(--ui-ink);font-size:.78rem;font-variant-numeric:tabular-nums}.audit-page .audit-time span{color:var(--ui-muted);font-size:.68rem}.audit-page .audit-person{display:grid;gap:.12rem;max-width:13rem}.audit-page .audit-person strong{color:var(--ui-ink);font-size:.78rem;overflow-wrap:anywhere}.audit-page .audit-person span{color:var(--ui-muted);font-size:.68rem}.audit-page .audit-action{display:inline-flex;max-width:13rem;align-items:center;border-radius:999px;padding:.3rem .6rem;background:#eaf7ef;color:#087443;font-size:.68rem;font-weight:700;line-height:1.35;overflow-wrap:anywhere}.audit-page .audit-action.is-warning{background:#fff4d9;color:#946200}.audit-page .audit-action.is-danger{background:#fee7e7;color:#b42318}.audit-page .audit-entity{display:grid;gap:.15rem}.audit-page .audit-entity strong{color:#344054;font-size:.76rem}.audit-page .audit-entity span{color:var(--ui-muted);font-size:.68rem}.audit-page .audit-meta{max-width:14rem;color:var(--ui-muted);font-size:.7rem;line-height:1.55;overflow-wrap:anywhere}.audit-page .audit-details summary{display:inline-flex;cursor:pointer;align-items:center;border:1px solid var(--ui-border-strong);border-radius:.5rem;padding:.35rem .6rem;background:#fff;color:#475467;font-size:.68rem;font-weight:650;list-style:none}.audit-page .audit-details summary::-webkit-details-marker{display:none}.audit-page .audit-details summary:hover{border-color:#98a2b3;background:#f9fafb}.audit-page .audit-details[open] summary{border-color:#86c7a3;background:var(--ui-green-50);color:var(--ui-green-700)}.audit-page .audit-details pre{width:min(32rem,70vw);max-height:18rem;margin-top:.55rem;overflow:auto;border-radius:.65rem;background:#17202d;padding:.8rem;color:#d9e5f2;font-size:.68rem;line-height:1.55;white-space:pre-wrap}.audit-page .audit-alert{display:flex;align-items:flex-start;gap:.75rem;border:1px solid #fedf89;border-radius:var(--ui-radius);background:#fffaeb;padding:1rem 1.15rem;color:#7a2e0e}.audit-page .audit-alert svg{width:1.25rem;height:1.25rem;flex:none;margin-top:.1rem}.audit-page .audit-alert strong{display:block;font-size:.82rem}.audit-page .audit-alert span{display:block;margin-top:.2rem;font-size:.75rem;line-height:1.55}.audit-page .audit-empty{display:grid;justify-items:center;gap:.45rem;padding:3rem 1.25rem;color:var(--ui-muted);text-align:center}.audit-page .audit-empty svg{width:2rem;height:2rem;color:#98a2b3}.audit-page .audit-empty strong{color:#344054;font-size:.85rem}.audit-page .audit-empty span{font-size:.75rem}.audit-page [wire\:loading]{opacity:.7}@media(max-width:900px){.audit-page .audit-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.audit-page .audit-filter-grid{grid-template-columns:1fr}.audit-page .audit-filter-actions{display:grid;grid-template-columns:1fr 1fr}.audit-page .audit-summary{align-self:flex-start}.audit-page .ui-page-intro{align-items:flex-start}}
    </style>

    <header class="ui-page-intro">
        <div class="flex min-w-0 items-center gap-3">
            <span class="audit-intro-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12h6M9 16h6M10 2h4a2 2 0 0 1 2 2v1h2a2 2 0 0 1 2 2v13H4V7a2 2 0 0 1 2-2h2V4a2 2 0 0 1 2-2Z"/><path d="M9 5h6"/></svg>
            </span>
            <div class="min-w-0">
                <h2>Audit Log Presensi</h2>
                <p>Riwayat aktivitas dan perubahan data presensi dalam 30 hari terakhir.</p>
            </div>
        </div>
        @if ($tableAvailable)
            <div class="audit-summary"><strong>{{ number_format($logs->count()) }}</strong><span>aktivitas ditampilkan</span></div>
        @endif
    </header>

    @if (! $tableAvailable)
        <div role="alert" class="audit-alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.9 2.2 18a2 2 0 0 0 1.7 3h16.2a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
            <div><strong>Audit log belum tersedia</strong><span>Struktur database untuk fitur ini belum dipasang. Jalankan migrasi aplikasi, lalu muat ulang halaman.</span></div>
        </div>
    @else
        <section class="ui-card">
            <header class="ui-card-head">
                <div><h2>Filter aktivitas</h2><p>Persempit hasil berdasarkan periode, sekolah, tindakan, atau jenis data.</p></div>
            </header>
            <form wire:submit="$refresh" class="ui-card-body">
                <div class="audit-filter-grid">
                    <label class="ui-field"><span class="ui-label">Tanggal mulai</span><input class="ui-input" type="date" wire:model="dateFrom"></label>
                    <label class="ui-field"><span class="ui-label">Tanggal selesai</span><input class="ui-input" type="date" wire:model="dateTo"></label>
                    <label class="ui-field"><span class="ui-label">Sekolah/Madrasah</span><select class="ui-select" wire:model="selectedSchoolId"><option value="">Semua sekolah</option>@foreach ($schoolOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
                    <label class="ui-field"><span class="ui-label">Tindakan</span><input class="ui-input" type="search" wire:model="actionFilter" placeholder="Contoh: approved"></label>
                    <label class="ui-field"><span class="ui-label">Jenis data</span><input class="ui-input" type="search" wire:model="entityType" placeholder="Contoh: Attendance"></label>
                </div>
                <div class="audit-filter-actions">
                    <button class="ui-btn ui-btn-secondary" type="button" wire:click="resetFilters">Atur ulang</button>
                    <button class="ui-btn ui-btn-primary" type="submit" wire:loading.attr="disabled">Terapkan filter</button>
                </div>
            </form>
        </section>

        <section class="ui-card">
            <header class="ui-card-head">
                <div><h2>Riwayat aktivitas</h2><p>Menampilkan maksimal 300 aktivitas terbaru sesuai filter.</p></div>
                <span class="ui-badge ui-badge-success">{{ number_format($logs->count()) }} data</span>
            </header>
            @if ($logs->isEmpty())
                <div class="audit-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                    <strong>Aktivitas tidak ditemukan</strong><span>Coba ubah periode atau filter pencarian.</span>
                </div>
            @else
                <div class="ui-table-wrap">
                    <table class="ui-table audit-table">
                        <thead><tr><th>Waktu</th><th>Pengguna</th><th>Sekolah</th><th>Tindakan</th><th>Data</th><th>Alamat IP</th><th>Perangkat</th><th>Detail</th></tr></thead>
                        <tbody>
                            @foreach ($logs as $log)
                                @php
                                    $actionClass = str_contains($log->action, 'reject') || str_contains($log->action, 'delete') ? 'is-danger' : (str_contains($log->action, 'pending') || str_contains($log->action, 'submitted') ? 'is-warning' : '');
                                @endphp
                                <tr>
                                    <td><div class="audit-time"><strong>{{ $log->created_at?->timezone(config('attendance.timezone'))->format('d M Y') }}</strong><span>{{ $log->created_at?->timezone(config('attendance.timezone'))->format('H:i:s') }} WIB</span></div></td>
                                    <td><div class="audit-person"><strong>{{ $log->actor?->name ?? 'Sistem' }}</strong><span>{{ $log->target?->name ? 'Target: '.$log->target->name : 'Aktivitas sistem' }}</span></div></td>
                                    <td><div class="audit-person"><strong>{{ $log->school?->name ?? 'Lintas sekolah' }}</strong><span>{{ $log->employee?->name ?? '—' }}</span></div></td>
                                    <td><span class="audit-action {{ $actionClass }}">{{ \Illuminate\Support\Str::headline($log->action) }}</span></td>
                                    <td><div class="audit-entity"><strong>{{ class_basename($log->entity_type) }}</strong><span>ID #{{ $log->entity_id ?? '—' }}</span></div></td>
                                    <td><span class="audit-meta">{{ $log->ip_address ?? '—' }}</span></td>
                                    <td><span class="audit-meta" title="{{ $log->user_agent }}">{{ \Illuminate\Support\Str::limit($log->user_agent, 48) ?: '—' }}</span></td>
                                    <td><details class="audit-details"><summary>Lihat detail</summary><pre>{{ json_encode(['sebelum' => $log->old_values, 'sesudah' => $log->new_values], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</div>
