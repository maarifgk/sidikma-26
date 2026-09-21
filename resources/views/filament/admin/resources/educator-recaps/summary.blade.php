<div class="er-overview">
    <style>
        .er-overview{display:grid;gap:20px;color:#102338}
        .er-year{display:flex;justify-content:flex-end;align-items:center;gap:8px;font-size:16px}
        .er-year select{min-width:170px;height:48px;padding:0 38px 0 16px;border:1px solid #cfd8e3;border-radius:10px;background-color:#f8fafc;color:#102338}
        .er-year select:focus{outline:2px solid #00c994;outline-offset:2px}
        .er-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}
        .er-stat{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:118px;padding:24px 16px;border:1px solid #e6ebf0;border-radius:18px;background:#fff;box-shadow:0 2px 7px #1023380c;text-align:center}
        .er-stat strong{display:block;font-size:30px;line-height:1.2;font-weight:750;font-variant-numeric:tabular-nums}
        .er-stat span{margin-top:5px;font-size:16px;line-height:1.4}
        .fi-page:has(.er-overview){gap:30px}
        .fi-page:has(.er-overview) .fi-header-heading{color:#0b1728;font-size:clamp(1.65rem,2.5vw,2.25rem);font-weight:750;letter-spacing:-.03em}
        .fi-page:has(.er-overview) .fi-header-subheading{max-width:none;color:#526b87;font-size:17px}
        .fi-page:has(.er-overview) .fi-header .fi-btn{min-height:48px;padding-inline:20px;border-radius:12px;background:#00ca98;color:#06382e;box-shadow:none}
        .fi-page:has(.er-overview) .fi-header .fi-btn:hover{background:#00b689}
        .fi-page:has(.er-overview) .fi-ta-ctn{overflow:hidden;border:1px solid #e6ebf0;border-radius:18px;background:#fff;box-shadow:0 2px 7px #1023380c}
        .fi-page:has(.er-overview) .fi-ta-header-toolbar{min-height:82px;padding:20px 30px;border-bottom:0}
        .fi-page:has(.er-overview) .fi-ta-search-field .fi-input-wrp{min-width:280px;border:1px solid #f2f5f8;border-radius:12px;box-shadow:none}
        .fi-page:has(.er-overview) .fi-ta-table thead{background:linear-gradient(100deg,#ecfcf3,#eff8ff)}
        .fi-page:has(.er-overview) .fi-ta-header-cell{height:62px;background:transparent;color:#466481;font-size:13px;font-weight:700}
        .fi-page:has(.er-overview) .fi-ta-cell{height:70px;padding-block:12px;border-color:#e7edf3}
        .fi-page:has(.er-overview) .fi-ta-text-item{font-size:16px;color:#102338}
        .fi-page:has(.er-overview) .fi-ta-cell-total .fi-ta-text-item{font-weight:750}
        .fi-page:has(.er-overview) .fi-pagination{min-height:84px;padding:16px 30px;background:#fafcff;border-top:1px solid #e7edf3}
        .dark .er-overview{color:#e2e8f0}.dark .er-stat,.dark .er-year select,.dark .fi-page:has(.er-overview) .fi-ta-ctn{background:#171f2a;border-color:#334155;color:#e2e8f0}
        .dark .fi-page:has(.er-overview) :is(.fi-header-heading,.fi-ta-text-item){color:#e2e8f0}
        .dark .fi-page:has(.er-overview) .fi-header-subheading{color:#94a3b8}
        .dark .fi-page:has(.er-overview) .fi-ta-table thead{background:#20372f}
        .dark .fi-page:has(.er-overview) .fi-ta-header-cell{color:#c6e4d8}
        .dark .fi-page:has(.er-overview) :is(.fi-pagination,.fi-input-wrp,.fi-select-input){background:#202c3b!important;color:#e2e8f0;border-color:#334155}
        @media(max-width:1000px){.er-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:540px){.er-stats{gap:12px}.er-stat{min-height:112px;padding:18px 10px}.er-stat span{font-size:14px}.er-year{justify-content:space-between;font-size:14px}.er-year select{min-width:150px}.fi-page:has(.er-overview) .fi-header-subheading{font-size:14px}.fi-page:has(.er-overview) .fi-ta-search-field .fi-input-wrp{min-width:0}.fi-page:has(.er-overview) .fi-ta-header-toolbar{padding:16px}}
        .er-heading{display:flex;align-items:center;gap:26px}
        body:not(:has(.teacher-mobile)) .fi-main:has(.er-overview){background:radial-gradient(circle at 72% -80px,#dff5ed99 0,180px,transparent 181px),radial-gradient(circle at 105% 90px,#e0f2ee88 0,200px,transparent 201px),linear-gradient(140deg,#f1faf6,#f7faff 70%)}
        .dark body:not(:has(.teacher-mobile)) .fi-main:has(.er-overview){background:#111c2a}
        .er-heading-icon{display:grid;place-items:center;flex:none;width:88px;height:88px;border:1px solid #cef1e3;border-radius:30px;background:#f3fdf8;box-shadow:0 6px 16px #06956b12;color:#00a364}
        .er-heading-icon svg{width:48px;height:48px}
        .er-table-heading{display:flex;align-items:center;gap:14px;color:#101c38;font-size:19px;font-weight:750}
        .er-table-heading svg{width:28px;height:28px;color:#00a364;flex:none}
        .er-year-select{display:flex;align-items:center;position:relative}
        .er-year-select>svg{position:absolute;left:16px;width:22px;height:22px;color:#52617b;pointer-events:none}
        .er-year .er-year-select select{padding-left:48px;min-width:204px;background-color:#fff}
        .er-stats{gap:22px}
        .er-stat{--er-accent:#00a567;--er-icon:#d9f8ea;--er-bg:#effbf5;position:relative;overflow:hidden;flex-direction:row;justify-content:flex-start;gap:26px;min-height:132px;padding:24px 20px;border:2px solid #ffffffc9;background:linear-gradient(115deg,var(--er-bg),#fff);box-shadow:0 5px 14px #1023380d;text-align:left}
        .er-stat::after{content:"";position:absolute;width:140px;height:140px;right:-55px;bottom:-70px;border-radius:50%;background:var(--er-icon);opacity:.65;pointer-events:none}
        .er-stat-blue{--er-accent:#087eff;--er-icon:#d6efff;--er-bg:#eff8ff}
        .er-stat-orange{--er-accent:#ff961a;--er-icon:#ffecd9;--er-bg:#fff7f0}
        .er-stat-purple{--er-accent:#8055f5;--er-icon:#e8dfff;--er-bg:#f7f3ff}
        .er-stat-icon{display:grid;place-items:center;flex:none;width:76px;height:76px;border-radius:28px;background:var(--er-icon);color:var(--er-accent);z-index:1}
        .er-stat-icon svg{width:40px;height:40px}
        .er-stat-data{position:relative;z-index:1}
        .er-stat{gap:18px}
        .er-stat-icon{width:64px;height:64px;border-radius:24px}
        .er-stat-icon svg{width:34px;height:34px}
        .er-stat strong{font-size:38px}
        .er-stat span{display:block;font-size:14px}
        .fi-page:has(.er-overview) .fi-header-heading{font-size:clamp(1.4rem,1.9vw,2rem)}
        .fi-page:has(.er-overview) .fi-header .fi-btn{background:linear-gradient(110deg,#008858,#00a26d);color:white}
        .fi-page:has(.er-overview) .fi-ta-header-ctn{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center}
        .fi-page:has(.er-overview) .fi-ta-header{border-bottom:0;padding:24px 22px;background:transparent}
        .fi-page:has(.er-overview) .fi-ta-header-toolbar{min-height:80px;padding:18px 22px}
        .fi-page:has(.er-overview) .fi-ta-search-field .fi-input-wrp{min-width:330px;border:1px solid #dbe4ef}
        .fi-page:has(.er-overview) .fi-ta-table thead{background:#eaf9f3}
        .fi-page:has(.er-overview) .fi-ta-header-cell-sort-btn{font-size:12px;gap:5px;white-space:normal}
        .fi-page:has(.er-overview) .fi-ta-cell-total .fi-ta-text-item{display:flex;justify-content:center;align-items:center;min-width:64px;min-height:60px;padding:12px;border-radius:12px;background:#eafaf2;font-weight:750}
        .fi-page:has(.er-overview) .fi-ta-actions{gap:8px}
        .fi-page:has(.er-overview) .fi-ta-actions .fi-btn{border-radius:12px;padding:9px 12px;font-size:14px}
        .fi-page:has(.er-overview) .fi-ta-actions .fi-color-success{background:#ecfbf4}
        .fi-page:has(.er-overview) .fi-ta-actions .fi-color-info{background:#edf7ff}
        .fi-page:has(.er-overview) .fi-ta-actions .fi-color-danger{background:#fff0f0}
        @media(min-width:768px){.fi-page:has(.er-overview) .fi-pagination{grid-template-columns:1fr auto auto;column-gap:24px}}
        .fi-page:has(.er-overview) .fi-pagination-item.fi-active .fi-pagination-item-btn{background:#00986a;border-radius:10px}
        .fi-page:has(.er-overview) .fi-pagination-item.fi-active .fi-pagination-item-label{color:#fff}
        .dark .er-heading-icon{background:#18382c;border-color:#285940}
        .dark .er-stat{background:linear-gradient(115deg,#202d3c,#172330);border-color:#334155}
        .dark .er-stat::after{opacity:.1}.dark .er-stat-icon{background:#273c4a}
        .dark .er-table-heading{color:#e2e8f0}
        .dark .fi-page:has(.er-overview) .fi-ta-header{background:#171f2a!important}
        .dark .fi-page:has(.er-overview) .fi-ta-cell-total .fi-ta-text-item{background:#1c4031}
        .dark .fi-page:has(.er-overview) .fi-ta-actions .fi-btn{background:#202c3b}
        @media(max-width:1250px){.er-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:640px){.er-heading{gap:14px}.er-heading-icon{width:56px;height:56px;border-radius:18px}.er-heading-icon svg{width:32px;height:32px}.er-stat{flex-direction:column;align-items:flex-start;gap:14px;padding:18px 14px}.er-stat-icon{width:52px;height:52px;border-radius:18px}.er-stat-icon svg{width:28px;height:28px}.er-stat strong{font-size:30px}.er-year .er-year-select select{min-width:174px}.fi-page:has(.er-overview) .fi-ta-header-ctn{grid-template-columns:1fr}.fi-page:has(.er-overview) .fi-ta-header{padding-bottom:0}.fi-page:has(.er-overview) .fi-ta-search-field .fi-input-wrp{min-width:0;width:100%}}
    </style>
    <label class="er-year"><span>Tahun Pelajaran</span>
        <span class="er-year-select"><x-filament::icon icon="heroicon-o-calendar-days" /><select wire:model.live="academicYear">
            @foreach ($academicYearOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select></span>
    </label>
    <section class="er-stats" aria-label="Ringkasan tenaga pendidik">
        @foreach ([
            ['label' => 'Total Tenaga (rekap)', 'value' => $totalEducators, 'icon' => 'heroicon-s-user-group', 'class' => ''],
            ['label' => 'Madrasah Sudah Mengisi', 'value' => $completedSchools, 'icon' => 'heroicon-s-building-office-2', 'class' => 'er-stat-blue'],
            ['label' => 'Madrasah Belum Mengisi', 'value' => $incompleteSchools, 'icon' => 'heroicon-s-document-text', 'class' => 'er-stat-orange'],
            ['label' => 'Total Madrasah', 'value' => $totalSchools, 'icon' => 'heroicon-s-building-office-2', 'class' => 'er-stat-purple'],
        ] as $stat)
            <article class="er-stat {{ $stat['class'] }}">
                <div class="er-stat-icon"><x-filament::icon :icon="$stat['icon']" /></div>
                <div class="er-stat-data"><strong>{{ number_format($stat['value'], 0, ',', '.') }}</strong><span>{{ $stat['label'] }}</span></div>
            </article>
        @endforeach
    </section>
</div>
