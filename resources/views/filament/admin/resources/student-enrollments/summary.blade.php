<div class="se-overview">
    <style>
        .se-overview{display:grid;gap:20px;color:#102338}
        .se-year{display:flex;justify-content:flex-end;align-items:center;gap:8px;font-size:16px}
        .se-year select{min-width:170px;height:48px;padding:0 38px 0 16px;border:1px solid #cfd8e3;border-radius:10px;background-color:#f8fafc;color:#102338}
        .se-year select:focus{outline:2px solid #00c994;outline-offset:2px}
        .se-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}
        .se-stat{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:118px;padding:24px 16px;border:1px solid #e6ebf0;border-radius:18px;background:#fff;box-shadow:0 2px 7px #1023380c;text-align:center}
        .se-stat strong{display:block;font-size:30px;line-height:1.2;font-weight:750;font-variant-numeric:tabular-nums}
        .se-stat span{margin-top:5px;font-size:16px;line-height:1.4}
        .fi-page:has(.se-overview){gap:30px}
        .fi-page:has(.se-overview) .fi-header-heading{color:#0b1728;font-size:clamp(1.65rem,2.5vw,2.25rem);font-weight:750;letter-spacing:-.03em}
        .fi-page:has(.se-overview) .fi-header-subheading{max-width:none;color:#526b87;font-size:17px}
        .fi-page:has(.se-overview) .fi-header .fi-btn{min-height:48px;padding-inline:20px;border-radius:12px;background:#00ca98;color:#06382e;box-shadow:none}
        .fi-page:has(.se-overview) .fi-header .fi-btn:hover{background:#00b689}
        .fi-page:has(.se-overview) .fi-ta-ctn{overflow:hidden;border:1px solid #e6ebf0;border-radius:18px;background:#fff;box-shadow:0 2px 7px #1023380c}
        .fi-page:has(.se-overview) .fi-ta-header-toolbar{min-height:82px;padding:20px 30px;border-bottom:0}
        .fi-page:has(.se-overview) .fi-ta-search-field .fi-input-wrp{min-width:280px;border:1px solid #f2f5f8;border-radius:12px;box-shadow:none}
        .fi-page:has(.se-overview) .fi-ta-table thead{background:linear-gradient(100deg,#ecfcf3,#eff8ff)}
        .fi-page:has(.se-overview) .fi-ta-header-cell{height:62px;background:transparent;color:#466481;font-size:13px;font-weight:700}
        .fi-page:has(.se-overview) .fi-ta-cell{height:70px;padding-block:12px;border-color:#e7edf3}
        .fi-page:has(.se-overview) .fi-ta-text-item{font-size:16px;color:#102338}
        .fi-page:has(.se-overview) .fi-ta-cell-total .fi-ta-text-item{font-weight:750}
        .fi-page:has(.se-overview) .fi-pagination{min-height:84px;padding:16px 30px;background:#fafcff;border-top:1px solid #e7edf3}
        .dark .se-overview{color:#e2e8f0}.dark .se-stat,.dark .se-year select,.dark .fi-page:has(.se-overview) .fi-ta-ctn{background:#171f2a;border-color:#334155;color:#e2e8f0}
        .dark .fi-page:has(.se-overview) :is(.fi-header-heading,.fi-ta-text-item){color:#e2e8f0}
        .dark .fi-page:has(.se-overview) .fi-header-subheading{color:#94a3b8}
        .dark .fi-page:has(.se-overview) .fi-ta-table thead{background:#20372f}
        .dark .fi-page:has(.se-overview) .fi-ta-header-cell{color:#c6e4d8}
        .dark .fi-page:has(.se-overview) :is(.fi-pagination,.fi-input-wrp,.fi-select-input){background:#202c3b!important;color:#e2e8f0;border-color:#334155}
        @media(max-width:1000px){.se-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:540px){.se-stats{gap:12px}.se-stat{min-height:112px;padding:18px 10px}.se-stat span{font-size:14px}.se-year{justify-content:space-between;font-size:14px}.se-year select{min-width:150px}.fi-page:has(.se-overview) .fi-header-subheading{font-size:14px}.fi-page:has(.se-overview) .fi-ta-search-field .fi-input-wrp{min-width:0}.fi-page:has(.se-overview) .fi-ta-header-toolbar{padding:16px}}
        .se-heading{display:flex;align-items:center;gap:26px}
        body:not(:has(.teacher-mobile)) .fi-main:has(.se-overview){background:radial-gradient(circle at 72% -80px,#dff5ed99 0,180px,transparent 181px),radial-gradient(circle at 105% 90px,#e0f2ee88 0,200px,transparent 201px),linear-gradient(140deg,#f1faf6,#f7faff 70%)}
        .dark body:not(:has(.teacher-mobile)) .fi-main:has(.se-overview){background:#111c2a}
        .se-heading-icon{display:grid;place-items:center;flex:none;width:88px;height:88px;border:1px solid #cef1e3;border-radius:30px;background:#f3fdf8;box-shadow:0 6px 16px #06956b12;color:#00a364}
        .se-heading-icon svg{width:48px;height:48px}
        .se-table-heading{display:flex;align-items:center;gap:14px;color:#101c38;font-size:19px;font-weight:750}
        .se-table-heading svg{width:28px;height:28px;color:#00a364;flex:none}
        .se-year-select{display:flex;align-items:center;position:relative}
        .se-year-select>svg{position:absolute;left:16px;width:22px;height:22px;color:#52617b;pointer-events:none}
        .se-year .se-year-select select{padding-left:48px;min-width:204px;background-color:#fff}
        .se-stats{gap:22px}
        .se-stat{--se-accent:#00a567;--se-icon:#d9f8ea;--se-bg:#effbf5;position:relative;overflow:hidden;flex-direction:row;justify-content:flex-start;gap:26px;min-height:132px;padding:24px 20px;border:2px solid #ffffffc9;background:linear-gradient(115deg,var(--se-bg),#fff);box-shadow:0 5px 14px #1023380d;text-align:left}
        .se-stat::after{content:"";position:absolute;width:140px;height:140px;right:-55px;bottom:-70px;border-radius:50%;background:var(--se-icon);opacity:.65;pointer-events:none}
        .se-stat-blue{--se-accent:#087eff;--se-icon:#d6efff;--se-bg:#eff8ff}
        .se-stat-orange{--se-accent:#ff961a;--se-icon:#ffecd9;--se-bg:#fff7f0}
        .se-stat-purple{--se-accent:#8055f5;--se-icon:#e8dfff;--se-bg:#f7f3ff}
        .se-stat-icon{display:grid;place-items:center;flex:none;width:76px;height:76px;border-radius:28px;background:var(--se-icon);color:var(--se-accent);z-index:1}
        .se-stat-icon svg{width:40px;height:40px}
        .se-stat-data{position:relative;z-index:1}
        .se-stat strong{font-size:38px}
        .se-stat span{display:block;font-size:14px}
        .fi-page:has(.se-overview) .fi-header-heading{font-size:clamp(1.5rem,2.25vw,2.2rem)}
        .fi-page:has(.se-overview) .fi-header .fi-btn{background:linear-gradient(110deg,#008858,#00a26d);color:white}
        .fi-page:has(.se-overview) .fi-ta-header-ctn{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center}
        .fi-page:has(.se-overview) .fi-ta-header{border-bottom:0;padding:24px 22px;background:transparent}
        .fi-page:has(.se-overview) .fi-ta-header-toolbar{min-height:80px;padding:18px 22px}
        .fi-page:has(.se-overview) .fi-ta-search-field .fi-input-wrp{min-width:330px;border:1px solid #dbe4ef}
        .fi-page:has(.se-overview) .fi-ta-table thead{background:#eaf9f3}
        .fi-page:has(.se-overview) .fi-ta-cell-total .fi-ta-text-item{display:flex;justify-content:center;align-items:center;min-width:64px;min-height:60px;padding:12px;border-radius:12px;background:#eafaf2;font-weight:750}
        .fi-page:has(.se-overview) .fi-ta-actions{gap:8px}
        .fi-page:has(.se-overview) .fi-ta-actions .fi-btn{border-radius:12px;padding:9px 12px;font-size:14px}
        .fi-page:has(.se-overview) .fi-ta-actions .fi-color-success{background:#ecfbf4}
        .fi-page:has(.se-overview) .fi-ta-actions .fi-color-info{background:#edf7ff}
        .fi-page:has(.se-overview) .fi-ta-actions .fi-color-danger{background:#fff0f0}
        @media(min-width:768px){.fi-page:has(.se-overview) .fi-pagination{grid-template-columns:1fr auto auto;column-gap:24px}}
        .fi-page:has(.se-overview) .fi-pagination-item.fi-active .fi-pagination-item-btn{background:#00986a;border-radius:10px}
        .fi-page:has(.se-overview) .fi-pagination-item.fi-active .fi-pagination-item-label{color:#fff}
        .dark .se-heading-icon{background:#18382c;border-color:#285940}
        .dark .se-stat{background:linear-gradient(115deg,#202d3c,#172330);border-color:#334155}
        .dark .se-stat::after{opacity:.1}.dark .se-stat-icon{background:#273c4a}
        .dark .se-table-heading{color:#e2e8f0}
        .dark .fi-page:has(.se-overview) .fi-ta-header{background:#171f2a!important}
        .dark .fi-page:has(.se-overview) .fi-ta-cell-total .fi-ta-text-item{background:#1c4031}
        .dark .fi-page:has(.se-overview) .fi-ta-actions .fi-btn{background:#202c3b}
        @media(max-width:1250px){.se-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:640px){.se-heading{gap:14px}.se-heading-icon{width:56px;height:56px;border-radius:18px}.se-heading-icon svg{width:32px;height:32px}.se-stat{flex-direction:column;align-items:flex-start;gap:14px;padding:18px 14px}.se-stat-icon{width:52px;height:52px;border-radius:18px}.se-stat-icon svg{width:28px;height:28px}.se-stat strong{font-size:30px}.se-year .se-year-select select{min-width:174px}.fi-page:has(.se-overview) .fi-ta-header-ctn{grid-template-columns:1fr}.fi-page:has(.se-overview) .fi-ta-header{padding-bottom:0}.fi-page:has(.se-overview) .fi-ta-search-field .fi-input-wrp{min-width:0;width:100%}}
    </style>
    <label class="se-year"><span>Tahun Pelajaran</span>
        <span class="se-year-select"><x-filament::icon icon="heroicon-o-calendar-days" /><select wire:model.live="academicYear">
            @foreach ($academicYearOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select></span>
    </label>
    <section class="se-stats" aria-label="Ringkasan jumlah siswa">
        @foreach ([
            ['label' => 'Total Siswa', 'value' => $totalStudents, 'icon' => 'heroicon-s-user-group', 'class' => ''],
            ['label' => 'Madrasah Sudah Mengisi', 'value' => $completedSchools, 'icon' => 'heroicon-s-building-office-2', 'class' => 'se-stat-blue'],
            ['label' => 'Madrasah Belum Mengisi', 'value' => $incompleteSchools, 'icon' => 'heroicon-s-document-text', 'class' => 'se-stat-orange'],
            ['label' => 'Total Madrasah', 'value' => $totalSchools, 'icon' => 'heroicon-s-building-office-2', 'class' => 'se-stat-purple'],
        ] as $stat)
            <article class="se-stat {{ $stat['class'] }}">
                <div class="se-stat-icon"><x-filament::icon :icon="$stat['icon']" /></div>
                <div class="se-stat-data"><strong>{{ number_format($stat['value'], 0, ',', '.') }}</strong><span>{{ $stat['label'] }}</span></div>
            </article>
        @endforeach
    </section>
</div>
