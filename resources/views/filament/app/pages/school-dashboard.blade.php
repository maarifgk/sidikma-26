@php
    $schoolName = $school?->name ?? 'Madrasah/Sekolah Belum Ditugaskan';
    $accreditation = $school?->accreditation_status ?: '-';
@endphp

@if(auth()->user()?->hasRole(\App\Models\User::ROLE_GURU_PEGAWAI))
    @include('filament.app.mobile.styles')
    @include('filament.app.mobile.dashboard', ['employee' => $currentEmployee])
@endif

<div class="sd-dashboard teacher-desktop">
    <style>
        .sd-dashboard {
            --sd-ink: #17352b;
            --sd-muted: #718096;
            --sd-line: #dbe7e2;
            --sd-soft: #f6faf8;
            display: grid;
            gap: 1rem;
            color: var(--sd-ink);
        }
        .sd-hero {
            position: relative;
            min-height: 5.2rem;
            overflow: hidden;
            border: 0.45rem solid #174eb5;
            border-radius: 0.35rem;
            background:
                radial-gradient(circle at 16% 18%, rgba(223, 183, 74, .85) 0 7px, transparent 8px),
                radial-gradient(circle at 82% 72%, rgba(223, 183, 74, .7) 0 6px, transparent 7px),
                linear-gradient(135deg, #fff 0%, #f7fbff 52%, #fff 100%);
            box-shadow: inset 0 0 0 1px rgba(23, 78, 181, .08);
        }
        .sd-hero::before,
        .sd-hero::after {
            position: absolute;
            width: 13rem;
            height: 5rem;
            border: 2px solid rgba(217, 176, 62, .65);
            border-radius: 50%;
            content: '';
        }
        .sd-hero::before { top: -3.4rem; left: -1.5rem; transform: rotate(10deg); }
        .sd-hero::after { right: -1rem; bottom: -3.5rem; transform: rotate(-9deg); }
        .sd-hero-title {
            position: relative;
            z-index: 1;
            display: flex;
            min-height: 4.3rem;
            align-items: center;
            justify-content: center;
            padding: .65rem 1rem;
            color: #17436a;
            text-align: center;
            font-size: clamp(1.45rem, 3vw, 2.6rem);
            font-weight: 800;
            letter-spacing: .035em;
        }
        .sd-hero-title span { margin-left: .45rem; font-family: Georgia, serif; font-style: italic; font-weight: 500; letter-spacing: -.03em; }
        .sd-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
        .sd-stat, .sd-panel {
            border: 1px solid rgba(23, 53, 43, .08);
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 10px 24px rgba(22, 54, 43, .06);
        }
        .sd-stat { display: flex; min-height: 5.3rem; align-items: center; justify-content: space-between; padding: 1rem 1.15rem; }
        .sd-eyebrow { color: #8a9aab; font-size: .67rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .sd-stat-value { margin-top: .2rem; font-size: 1.35rem; font-weight: 800; }
        .sd-stat-note { margin-top: .1rem; color: var(--sd-muted); font-size: .72rem; }
        .sd-stat-icon { display: grid; width: 2.4rem; height: 2.4rem; place-items: center; border-radius: .75rem; background: #e6f8dc; color: #3ea91b; }
        .sd-stat:nth-child(1) .sd-stat-icon { background: #ece8ff; color: #6557e8; }
        .sd-stat:nth-child(3) .sd-stat-icon { background: #ddf7fb; color: #10abc3; }
        .sd-stat:nth-child(4) .sd-stat-icon { background: #fff0d2; color: #e79900; }
        .sd-icon { width: 1.25rem; height: 1.25rem; }
        .sd-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; align-items: stretch; }
        .sd-panel { min-height: 32rem; padding: 1rem; }
        .sd-panel-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .85rem; }
        .sd-panel-title { margin-top: .2rem; font-size: 1rem; font-weight: 800; }
        .sd-panel-subtitle { color: #8a9aab; font-size: .7rem; }
        .sd-button { display: inline-flex; align-items: center; gap: .3rem; border: 1px solid #c9ddd5; border-radius: .35rem; padding: .35rem .55rem; color: #177750; font-size: .7rem; font-weight: 700; }
        .sd-list { display: grid; gap: .65rem; }
        .sd-info, .sd-person, .sd-activity, .sd-grade-summary, .sd-grade {
            border: 1px solid var(--sd-line);
            border-radius: .8rem;
            background: #fff;
        }
        .sd-info { display: grid; grid-template-columns: 2.2rem 1fr; gap: .65rem; padding: .75rem; }
        .sd-info-icon { display: grid; width: 2rem; height: 2rem; place-items: center; border-radius: .55rem; background: #ebf6fa; color: #22799c; }
        .sd-info-label { color: #8a9aab; font-size: .68rem; }
        .sd-info-value { margin-top: .18rem; overflow-wrap: anywhere; color: #587087; font-size: .78rem; font-weight: 700; }
        .sd-grade-summary { padding: .85rem; background: linear-gradient(135deg, #f7fbfa, #fff); }
        .sd-grade-summary strong { display: block; margin-top: .2rem; font-size: 1.6rem; }
        .sd-grade { padding: .68rem .78rem; }
        .sd-grade-row { display: flex; align-items: end; justify-content: space-between; gap: .6rem; }
        .sd-grade-label { font-size: .76rem; font-weight: 700; }
        .sd-grade-meta { color: #8a9aab; font-size: .66rem; }
        .sd-progress { height: .38rem; margin-top: .55rem; overflow: hidden; border-radius: 999px; background: #e8efec; }
        .sd-progress span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #08743f, #207fae); }
        .sd-person { display: grid; grid-template-columns: 2.5rem 1fr; gap: .7rem; align-items: center; padding: .65rem .75rem; }
        .sd-avatar { width: 2.5rem; height: 2.5rem; border-radius: 50%; object-fit: cover; background: #edf2f0; }
        .sd-person-name { color: #5d7087; font-size: .78rem; font-weight: 800; }
        .sd-person-meta { margin-top: .12rem; color: #718096; font-size: .65rem; }
        .sd-activity { padding: .7rem .75rem; }
        .sd-activity-row { display: flex; align-items: center; justify-content: space-between; gap: .6rem; }
        .sd-activity-name { color: #60758b; font-size: .74rem; font-weight: 800; }
        .sd-badge { border-radius: .25rem; background: #e6f7ed; padding: .2rem .35rem; color: #24784d; font-size: .56rem; font-weight: 800; text-transform: uppercase; }
        .sd-activity-meta { margin-top: .35rem; color: #7d8f92; font-size: .62rem; }
        .sd-assignment { overflow: hidden; border: 1px solid var(--sd-line); border-radius: .8rem; background: linear-gradient(135deg, #f8fbff, #fff); }
        .sd-assignment-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .72rem .78rem; background: #edf6ff; }
        .sd-assignment-name { color: #587087; font-size: .76rem; font-weight: 800; }
        .sd-assignment-school { padding: .65rem .78rem; border-top: 1px solid var(--sd-line); }
        .sd-assignment-school-head { display: flex; justify-content: space-between; gap: .6rem; color: #6d8095; font-size: .65rem; font-weight: 800; }
        .sd-assignment-employees { margin-top: .3rem; color: #8a9aab; font-size: .63rem; line-height: 1.5; }
        .sd-assignment-count { flex: none; border-radius: .6rem; background: #dcecff; padding: .42rem .55rem; color: #1768b5; font-size: .7rem; font-weight: 800; }
        .sd-empty { display: grid; min-height: 9rem; place-items: center; border: 1px dashed var(--sd-line); border-radius: .8rem; color: #8a9aab; text-align: center; font-size: .75rem; }
        @media (max-width: 1280px) { .sd-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 820px) { .sd-stats, .sd-grid { grid-template-columns: 1fr; } .sd-panel { min-height: auto; } }
        .dark .sd-dashboard { --sd-ink: #e6f4ee; --sd-muted: #9caeb7; --sd-line: #33483f; --sd-soft: #17211d; }
        .dark .sd-stat, .dark .sd-panel, .dark .sd-info, .dark .sd-person, .dark .sd-activity, .dark .sd-grade, .dark .sd-grade-summary { background: #171d1a; }
        .dark .sd-info-value, .dark .sd-person-name { color: #c6d7df; }
    </style>

    <section class="sd-hero" aria-label="Dashboard sekolah">
        <div class="sd-hero-title">WELCOME TO <span>Dashboard SiDIKMa-GK</span></div>
    </section>

    <section class="sd-stats">
        <article class="sd-stat">
            <div><div class="sd-eyebrow">Total Siswa</div><div class="sd-stat-value">{{ number_format($totalStudents ?? 0, 0, ',', '.') }}</div><div class="sd-stat-note">{{ $activeClasses ?? 0 }} kelas aktif</div></div>
            <div class="sd-stat-icon"><x-filament::icon icon="heroicon-o-user-group" class="sd-icon" /></div>
        </article>
        <article class="sd-stat">
            <div><div class="sd-eyebrow">Guru/Pegawai</div><div class="sd-stat-value">{{ number_format($employeeCount ?? 0, 0, ',', '.') }}</div><div class="sd-stat-note">Tenaga aktif</div></div>
            <div class="sd-stat-icon"><x-filament::icon icon="heroicon-o-academic-cap" class="sd-icon" /></div>
        </article>
        <article class="sd-stat">
            <div><div class="sd-eyebrow">Total Akun Internal</div><div class="sd-stat-value">{{ number_format($accountCount ?? 0, 0, ',', '.') }}</div><div class="sd-stat-note">Guru dan operator</div></div>
            <div class="sd-stat-icon"><x-filament::icon icon="heroicon-o-identification" class="sd-icon" /></div>
        </article>
        <article class="sd-stat">
            <div><div class="sd-eyebrow">Akreditasi</div><div class="sd-stat-value">{{ $accreditation }}</div><div class="sd-stat-note">Status lembaga</div></div>
            <div class="sd-stat-icon"><x-filament::icon icon="heroicon-o-sun" class="sd-icon" /></div>
        </article>
    </section>

    <section class="sd-grid">
        <article class="sd-panel">
            <header class="sd-panel-head">
                <div><div class="sd-eyebrow">Profil Lembaga</div><h2 class="sd-panel-title">Informasi Madrasah/Sekolah</h2><p class="sd-panel-subtitle">Ringkasan identitas lembaga</p></div>
                @if ($schoolEditUrl ?? null)<a href="{{ $schoolEditUrl }}" class="sd-button">Edit</a>@endif
            </header>
            <div class="sd-list">
                @foreach ([
                    ['heroicon-o-building-office-2', 'Nama Institusi', $schoolName],
                    ['heroicon-o-identification', 'NPSN', $school?->npsn ?: '-'],
                    ['heroicon-o-calendar-days', 'Tahun Pelajaran', $academicYear ?? '-'],
                    ['heroicon-o-envelope', 'Email', $school?->email ?: '-'],
                    ['heroicon-o-map', 'Status Tanah', $school?->land_status ? strtoupper($school->land_status) : '-'],
                    ['heroicon-o-map-pin', 'Alamat', $school?->address ?: '-'],
                ] as [$icon, $label, $value])
                    <div class="sd-info">
                        <div class="sd-info-icon"><x-filament::icon :icon="$icon" class="sd-icon" /></div>
                        <div><div class="sd-info-label">{{ $label }}</div><div class="sd-info-value">{{ $value }}</div></div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="sd-panel">
            <header class="sd-panel-head"><div><div class="sd-eyebrow">Insight Akademik</div><h2 class="sd-panel-title">Ringkasan Rombel</h2><p class="sd-panel-subtitle">Total dan rata-rata siswa per kelas</p></div></header>
            <div class="sd-list">
                <div class="sd-grade-summary"><div class="sd-info-label">Rata-rata per kelas</div><strong>{{ $averageStudents ?? 0 }}</strong><div class="sd-panel-subtitle">siswa per kelas aktif</div></div>
                @forelse (($grades ?? collect()) as $grade)
                    <div class="sd-grade">
                        <div class="sd-grade-row"><div><div class="sd-grade-label">{{ $grade['label'] }}</div><div class="sd-grade-meta">{{ $grade['percentage'] }}% dari kelas terpadat</div></div><div class="sd-grade-meta">{{ $grade['total'] }} siswa</div></div>
                        <div class="sd-progress"><span style="width: {{ $grade['percentage'] }}%"></span></div>
                    </div>
                @empty
                    <div class="sd-empty">Belum ada data siswa.</div>
                @endforelse
            </div>
        </article>

        <article class="sd-panel">
            <header class="sd-panel-head"><div><div class="sd-eyebrow">Tim Internal</div><h2 class="sd-panel-title">Guru/Pegawai Se-Madrasah</h2><p class="sd-panel-subtitle">Snapshot tenaga aktif di lembaga</p></div></header>
            <div class="sd-list">
                @forelse (($employees ?? collect()) as $employee)
                    <a href="{{ $employee['url'] }}" class="sd-person">
                        <img src="{{ $employee['avatarUrl'] }}" alt="Foto {{ $employee['name'] }}" class="sd-avatar">
                        <div><div class="sd-person-name">{{ $employee['name'] }}</div><div class="sd-person-meta">{{ $employee['position'] }}</div><div class="sd-person-meta">{{ $employee['status'] }}</div></div>
                    </a>
                @empty
                    <div class="sd-empty">Belum ada guru atau pegawai aktif.</div>
                @endforelse
            </div>
        </article>

        <article class="sd-panel">
            <header class="sd-panel-head"><div><div class="sd-eyebrow">Rekap Ketugasan</div><h2 class="sd-panel-title">Jumlah Guru/Pegawai per Ketugasan</h2><p class="sd-panel-subtitle">Berdasarkan penugasan aktif di sekolah/madrasah</p></div></header>
            <div class="sd-list">
                @forelse (($assignmentCounts ?? collect()) as $assignment)
                    <article class="sd-assignment">
                        <header class="sd-assignment-head"><div class="sd-assignment-name">{{ $assignment['position'] }}</div><span class="sd-assignment-count">{{ number_format($assignment['total']) }} orang</span></header>
                        @foreach($assignment['schools'] as $school)
                            <div class="sd-assignment-school"><div class="sd-assignment-school-head"><span>{{ $school['name'] }}</span><span>{{ number_format($school['total']) }} orang</span></div><div class="sd-assignment-employees">{{ implode(', ', $school['employees']) }}</div></div>
                        @endforeach
                    </article>
                @empty
                    <div class="sd-empty">Belum ada data ketugasan aktif.</div>
                @endforelse
            </div>
        </article>

        <article class="sd-panel">
            <header class="sd-panel-head">
                <div><div class="sd-eyebrow">Aktivitas</div><h2 class="sd-panel-title">Aktivitas Terbaru</h2><p class="sd-panel-subtitle">Usulan terbaru dari lembaga</p></div>
                <a href="{{ $activitiesUrl ?? '#' }}" class="sd-button">Lihat Semua</a>
            </header>
            <div class="sd-list">
                @forelse (($activities ?? collect()) as $activity)
                    <div class="sd-activity">
                        <div class="sd-activity-row"><div class="sd-activity-name">● {{ $activity['name'] }}</div><span class="sd-badge">{{ $activity['status'] }}</span></div>
                        <div class="sd-activity-meta">{{ $activity['createdAt'] }} · {{ $activity['school'] }}</div>
                    </div>
                @empty
                    <div class="sd-empty">Belum ada aktivitas terbaru.</div>
                @endforelse
            </div>
        </article>
    </section>
</div>
