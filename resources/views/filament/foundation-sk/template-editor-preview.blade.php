@php
    $value = static function (string $key, mixed $fallback = null) use ($get, $record): mixed { if (isset($get) && is_callable($get)) return $get($key) ?? $fallback; return data_get($record, $key, $fallback); };
    $builder = $value('builder_data', $record?->builder_data ?? \App\Models\FoundationSkTemplate::defaultBuilderData()) ?: [];
    $header = $builder['header'] ?? []; $decision = $builder['decision'] ?? []; $sections = $builder['sections'] ?? [];
    if (filled($sections['menetapkan'] ?? null)) {
        $sections['menetapkan'] = preg_replace('/;\s*(?::\s*)+/', '; ', (string) $sections['menetapkan']);
        $sections['menetapkan'] = preg_replace('/:\s*(?=(?:Kedua|Ketiga)\s*:)/i', '; ', $sections['menetapkan']);
        $sections['menetapkan'] = preg_replace('/\s+(?=(?:Kedua|Ketiga)\s*:)/i', '; ', $sections['menetapkan']);
    }
    $content = $value('content', $record?->content ?? ''); $customCss = $value('custom_css', $record?->custom_css ?? '');
    $content = str_replace(['Pertama : Pertama :', 'Pertama: Pertama:', 'Pertama :  Pertama :'], 'Pertama :', (string) $content);
    $bodyFont = $value('builder_data.fonts.body', data_get($builder, 'fonts.body', 11));
    $sectionFont = $value('builder_data.fonts.section_title', data_get($builder, 'fonts.section_title', 11));
    $titleFont = $value('builder_data.decision.font_title', data_get($builder, 'decision.font_title', 13));
    $numberFont = $value('builder_data.decision.font_number', data_get($builder, 'decision.font_number', 12));
    $openingFont = $value('builder_data.decision.font_opening', data_get($builder, 'decision.font_opening', 11));
    $size = $value('paper_size', $record?->paper_size ?? 'A4'); $orientation = $value('orientation', $record?->orientation ?? 'portrait');
    $logoPath = $header['logo_path'] ?? null; if (is_array($logoPath)) $logoPath = array_values($logoPath)[0] ?? null; if (filled($logoPath) && ! str_contains($logoPath, '/')) $logoPath = 'foundation-sk/logos/'.$logoPath;
    $logoUrl = filled($logoPath) && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath) ? route('foundation-sk.template-asset', ['path' => $logoPath]) : null;
    $contactLogo = static function ($path): ?string {
        if (is_array($path)) $path = array_values($path)[0] ?? null;
        if (filled($path) && ! str_contains($path, '/')) $path = 'foundation-sk/contact-logos/'.$path;
        return filled($path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($path) ? route('foundation-sk.template-asset', ['path' => $path]) : null;
    };
    $whatsappLogoUrl = $contactLogo($header['whatsapp_logo_path'] ?? null);
    $emailLogoUrl = $contactLogo($header['email_logo_path'] ?? null);
    $stampPath = $builder['signature']['stamp_path'] ?? null;
    if (is_array($stampPath)) $stampPath = array_values($stampPath)[0] ?? null;
    if (filled($stampPath) && ! str_contains($stampPath, '/')) $stampPath = 'foundation-sk/signature-stamps/'.$stampPath;
    $stampUrl = filled($stampPath) && \Illuminate\Support\Facades\Storage::disk('public')->exists($stampPath) ? route('foundation-sk.template-asset', ['path' => $stampPath]) : null;
    $signatureLocation = trim((string) ($builder['signature']['ditetapkan_di'] ?? ''));
    if (preg_match('/:\s*(.+)$/', $signatureLocation, $locationMatch)) $signatureLocation = trim($locationMatch[1]);
    if (! filled($signatureLocation) || str_contains(strtolower($signatureLocation), 'pada tanggal')) $signatureLocation = 'Gunungkidul';
    $signatureDateLabel = trim((string) ($builder['signature']['label_tanggal'] ?? ''));
    $signatureDateLabel = rtrim($signatureDateLabel, " :");
    if (! filled($signatureDateLabel) || str_contains(strtolower($signatureDateLabel), 'gunungkidul')) $signatureDateLabel = 'Pada Tanggal';
    $dimensions = match ($size) { 'F4' => [816, 1248], 'Legal', 'LEGAL' => [816, 1344], 'LETTER' => [816, 1056], default => [794, 1123] }; if ($orientation === 'landscape') $dimensions = [$dimensions[1], $dimensions[0]];
@endphp
<div x-data="{ zoom: 100, zoomIn() { this.zoom = Math.min(200, this.zoom + 10) }, zoomOut() { this.zoom = Math.max(50, this.zoom - 10) }, reset() { this.zoom = 100 } }" class="foundation-sk-preview flex min-h-[700px] min-w-0 flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex justify-end"><div class="flex items-center gap-1"><button type="button" class="preview-zoom-button" x-on:click="zoomOut()">−</button><span class="min-w-[52px] text-center text-sm font-medium" x-text="`${zoom}%`">100%</span><button type="button" class="preview-zoom-button" x-on:click="zoomIn()">+</button><button type="button" class="preview-reset-button" x-on:click="reset()">Reset</button></div></div>
    <div class="preview-scroll-area min-h-0 flex-1 overflow-auto rounded-lg border border-gray-200 bg-gray-100 p-6"><div class="mx-auto" style="width: {{ $dimensions[0] }}px; height: {{ $dimensions[1] }}px"><div class="preview-document origin-top shadow-lg" style="width: {{ $dimensions[0] }}px; min-height: {{ $dimensions[1] }}px" x-bind:style="`transform: scale(${zoom / 100}); transform-origin: top center`">
        @if ($customCss)<style>{!! $customCss !!}</style>@endif
            <div class="sk-preview-page" style="position:relative;padding:42px 64px;font-family:serif;font-size:{{ $bodyFont }}px;background:white;min-height:{{ $dimensions[1] }}px;color:#111827">
            @if ($logoUrl)<img class="sk-preview-logo" src="{{ $logoUrl }}" alt="Logo kop surat">@endif
            <div class="sk-preview-top">{{ $header['top_text'] ?: 'KEPALA SURAT YAYASAN' }}</div>
            <div class="sk-preview-institution">{{ $header['institution_name'] ?: 'YAYASAN CONTOH' }}</div>
            <div class="sk-preview-address">{{ $header['address'] ?: 'Alamat lembaga' }}</div>
            @if ($header['whatsapp'] || $header['email'])<div class="sk-preview-contacts">@if ($header['whatsapp'])<div><span>{{ $header['whatsapp'] }}</span><span class="contact-icon whatsapp-icon">◉</span></div>@endif @if ($header['email'])<div><span>{{ $header['email'] }}</span><span class="contact-icon email-icon">✉</span></div>@endif</div>@endif
            <hr><div style="text-align:center;font-weight:700;font-size:{{ $titleFont }}px!important">{{ $decision['title'] ?: 'SURAT KEPUTUSAN' }}</div><div style="text-align:center;font-size:{{ $numberFont }}px!important">{{ $decision['number'] ?: 'Nomor: 0001/SK/YYS/2026' }}</div>
            @if (false && filled($content))
                <div style="font-size:{{ $bodyFont }}px!important">{!! $content !!}</div>
            @else
                <p>{{ $decision['opening'] ?: 'Dengan ini menetapkan keputusan sebagai berikut:' }}</p>
                @foreach (['menimbang'=>'Menimbang','mengingat'=>'Mengingat','memperhatikan'=>'Memperhatikan','memutuskan'=>'Memutuskan','menetapkan'=>'Menetapkan'] as $key=>$label)
                    @if (!empty($sections[$key]) || $key === 'memperhatikan' || ($key === 'memutuskan' && filled($sections['judul_tengah'] ?? null)))
                        @if ($key === 'memutuskan')
                            <div class="sk-decision-heading" style="font-size:{{ $builder['fonts']['judul_tengah'] ?? $sectionFont }}px!important">{{ $sections['judul_tengah'] ?: 'MEMUTUSKAN' }}</div>
                        @else
                            @if ($key === 'menetapkan')<div class="sk-section-row sk-menetapkan-heading" style="font-size:{{ $sectionFont }}px!important"><span class="sk-section-label">Menetapkan</span><span class="sk-section-colon">:</span><span class="sk-section-content"></span></div>@endif
                            @foreach (array_values(array_filter(array_map(static fn ($part) => preg_replace('/^:\s*/', '', trim($part)), explode(';', $sections[$key])))) as $partIndex => $part)
                                @php $cleanPart = $key === 'menetapkan' ? preg_replace('/^(?:Pertama|Kedua|Ketiga)\s*:\s*/i', '', $part) : $part; $cleanPart = preg_replace("/\\n{2,}/", "\n", (string) $cleanPart); $cleanPart = preg_replace('/(Guru tersebut di bawah ini\s*:?)\s*(?=\\d+\.)/i', "$1\n", $cleanPart); $formattedPart = e($cleanPart); if ($key === 'menetapkan') { $formattedPart = preg_replace_callback('/^(\d+\.\s*)([^:\n]+?)\s*:\s*(.+)$/m', fn ($m) => '<div class="sk-data-row"><span>'.e($m[1]).e(trim($m[2])).'</span><span>:</span><span>'.e(trim($m[3])).'</span></div>', $cleanPart); } @endphp
                                <div class="sk-section-row {{ $key === 'mengingat' ? 'sk-mengingat-row' : '' }}" style="font-size:{{ $sectionFont }}px!important"><span class="sk-section-label">{{ $key === 'menetapkan' ? (['Pertama','Kedua','Ketiga'][$partIndex] ?? ($partIndex + 1).'.') : ($partIndex === 0 ? $label : '') }}</span><span class="sk-section-colon">{{ $key === 'mengingat' && $partIndex > 0 ? '' : ':' }}</span><span class="sk-section-content" style="text-align:justify">@if ($key === 'mengingat'){{ $partIndex + 1 }}. @endif{!! $formattedPart !!}@if (!$loop->last);@endif</span></div>
                            @endforeach
                        @endif
                    @endif
                @endforeach
            @endif
            <div class="sk-signature-preview" style="font-size:{{ $builder['fonts']['signature'] ?? $bodyFont }}px!important">
                <div><span>Ditetapkan di</span><b>:</b><span>{{ $signatureLocation }}</span></div>
                <div><span>{{ $signatureDateLabel }}</span><b>:</b><span>{{ $builder['signature']['tanggal_penetapan'] ?: '<<Tanggal Penetapan>>' }}</span></div>
                <div><span>{{ $builder['signature']['baris_pengurus'] ?: 'Pengurus LP Ma’arif NU Kab. Gunungkidul' }}</span></div>
                <div><span>{{ $builder['signature']['jabatan'] ?: 'Ketua,' }}</span></div>
                @if ($stampUrl)<img class="sk-signature-stamp" src="{{ $stampUrl }}" alt="Cap tanda tangan">@endif
                <div class="sk-signature-name">{{ $builder['signature']['nama'] ?: 'Drs. H. Sangkin, M.Pd.' }}</div>
            </div>
            <p>Nama: <strong>Nama Lengkap Contoh</strong><br>NIS/NIP/NUPTK: 0000000000<br>Sekolah: Sekolah Contoh</p>
        </div>
    </div></div>
</div>
<style>
.sk-preview-logo{position:absolute;top:44px;left:64px;max-width:155px;max-height:135px;object-fit:contain}.sk-preview-top,.sk-preview-institution,.sk-preview-address,.sk-preview-contacts{margin-left:150px;text-align:right}.sk-preview-top{font-family:"Bodoni 72","Bodoni MT",Didot,serif;font-size:{{ $header['font_top_text'] ?? 13 }}px;font-weight:600}.sk-preview-institution{font-family:Arial,sans-serif;font-weight:700;font-size:{{ $header['font_institution_name'] ?? 26 }}px;line-height:1.05}.sk-preview-address{font-family:"Bodoni 72","Bodoni MT",Didot,serif;font-weight:600;font-size:{{ $header['font_address'] ?? 11 }}px}.sk-preview-contacts{font-family:Arial,sans-serif;font-size:{{ $header['font_contact'] ?? 11 }}px;line-height:1.45}.sk-preview-contacts>div{display:flex;justify-content:flex-end;align-items:center;gap:7px}.contact-icon{display:inline-flex;width:22px;height:22px;align-items:center;justify-content:center;border-radius:50%;color:#fff;font-size:14px;font-weight:700}.whatsapp-icon{background:#16a34a}.email-icon{background:#15803d}.sk-preview-page hr{border:0;border-top:3px solid #15803d;margin:14px 0}.preview-zoom-button,.preview-reset-button{height:32px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;padding:0 10px;font-size:13px;color:#475569}.preview-zoom-button{width:32px;padding:0;font-size:20px}
.sk-preview-top,.sk-preview-institution,.sk-preview-address{color:#15803d}.sk-preview-contacts>div:first-child>span:first-child{color:#111827}.sk-preview-contacts>div:nth-child(2)>span:first-child{color:#2563eb}
.whatsapp-icon,.email-icon{font-size:0}.whatsapp-icon::before{content:'☎';font-size:13px}.email-icon::before{content:'✉';font-size:13px}
.whatsapp-icon{width:24px;height:24px;background:#16b83f url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='none' stroke='white' stroke-width='1.8' d='M12 3.5a8.5 8.5 0 0 0-7.3 12.8L3.5 20.5l4.3-1.2A8.5 8.5 0 1 0 12 3.5Z'/%3E%3Cpath fill='none' stroke='white' stroke-linecap='round' stroke-width='1.8' d='M9 8.2c-.5.1-.8.6-.7 1.1.4 2.1 2.3 4 4.4 4.4.5.1 1-.2 1.1-.7l.2-.6-1.4-.7-.7.7c-.9-.4-1.6-1.1-2-2l.7-.7-.7-1.4-.9-.1Z'/%3E%3C/svg%3E") center/17px 17px no-repeat}.email-icon{width:24px;height:24px;background:#087f45 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect x='4' y='7' width='16' height='11' rx='1' fill='none' stroke='white' stroke-width='1.8'/%3E%3Cpath fill='none' stroke='white' stroke-width='1.8' d='m5 8 7 5 7-5'/%3E%3C/svg%3E") center/17px 17px no-repeat}
.sk-preview-contacts>div{gap:4px}.whatsapp-icon,.email-icon{width:17px;height:17px;background-size:12px 12px}
.whatsapp-icon{background-color:#16b83f;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='white' d='M7.4 4.8c.4-.4 1-.5 1.5-.2l2 1.2c.5.3.7.9.5 1.4l-.7 1.7c.8 1.6 2 2.8 3.6 3.6l1.7-.7c.5-.2 1.1 0 1.4.5l1.2 2c.3.5.2 1.1-.2 1.5l-1.1 1.1c-.6.6-1.5.8-2.3.5-2.4-.9-4.6-2.3-6.4-4.1s-3.2-4-4.1-6.4c-.3-.8-.1-1.7.5-2.3l1.1-1.1Z'/%3E%3C/svg%3E") center/11px 11px no-repeat}
.whatsapp-icon{background-image:url('{{ $whatsappLogoUrl ?: "" }}');background-size:contain;background-color:transparent}.email-icon{background-image:url('{{ $emailLogoUrl ?: "" }}');background-size:contain;background-color:transparent}
.whatsapp-icon,.email-icon{background-color:transparent!important;background-repeat:no-repeat;background-position:center;box-shadow:none!important;border-radius:0!important}.whatsapp-icon::before,.email-icon::before{display:none!important}
.sk-preview-page hr{margin-top:4px!important}
.sk-preview-logo{max-width:190px!important;max-height:165px!important;top:38px!important}
.sk-preview-page hr~div,.sk-preview-page hr~p,.sk-preview-page hr~div p,.sk-preview-page hr~div table{font-family:Cambria,Georgia,"Times New Roman",serif!important;font-stretch:normal!important;letter-spacing:.1px!important;line-height:1.4!important;font-size:13px!important}.sk-preview-page hr+div{font-family:Georgia,"Times New Roman",serif!important;font-size:16px!important;font-weight:700!important;font-stretch:normal!important;letter-spacing:.15px!important;text-decoration:underline;line-height:1.25!important;transform:none!important}
.sk-preview-page hr+div{font-size:{{ $decision['font_title'] ?? 13 }}px!important;letter-spacing:-.15px!important;font-variant-ligatures:none!important;font-weight:800!important;line-height:1.35!important;transform:scale(1.04,1.14);transform-origin:center}
.sk-preview-contacts .whatsapp-icon,.sk-preview-contacts .email-icon{width:14px!important;height:14px!important;background-size:10px 10px!important}
.sk-preview-page hr+div+div{margin-bottom:16px!important}
.sk-preview-page hr~p{padding-left:150px!important;text-indent:-150px!important;text-align:left!important;margin:4px 0!important}.sk-preview-page hr~p strong{display:inline-block;width:140px!important;font-weight:400!important}.sk-preview-page hr~div p{padding-left:150px!important;text-indent:-150px!important;text-align:left!important}.sk-preview-page hr~div p strong{display:inline-block;width:140px!important}
.sk-preview-page hr~p,.sk-preview-page hr~div p{padding-left:150px!important;text-indent:0!important;text-align:left!important;margin:4px 0!important}.sk-preview-page hr~p strong,.sk-preview-page hr~div p strong{display:inline-block;width:140px!important;font-weight:400!important}
.sk-preview-page hr~p,.sk-preview-page hr~div p{padding-left:0!important}.sk-preview-page hr~p strong,.sk-preview-page hr~div p strong{width:140px!important}
.sk-preview-page hr~p:has(strong),.sk-preview-page hr~div p:has(strong){display:grid!important;grid-template-columns:180px minmax(0,1fr)!important;column-gap:0!important;padding-left:0!important;text-indent:0!important}.sk-preview-page hr~p:has(strong) strong,.sk-preview-page hr~div p:has(strong) strong{width:180px!important;display:block!important;text-align:right!important;padding-right:12px!important;font-weight:400!important}
.sk-preview-page hr~p:has(strong) strong,.sk-preview-page hr~div p:has(strong) strong{text-align:left!important}
.sk-section-row{display:grid;grid-template-columns:100px 16px minmax(0,1fr);column-gap:0;text-align:left;margin:4px 0;line-height:1.4}.sk-section-label{text-align:left}.sk-section-colon{text-align:center}.sk-section-content{min-width:0;text-align:justify;white-space:pre-line}
.sk-decision-heading{text-align:center;font-weight:800!important;margin:14px 0 8px;text-transform:uppercase}
.sk-section-row:has(.sk-section-label:first-child){align-items:start}.sk-section-row .sk-section-content{overflow-wrap:anywhere}.sk-section-row.sk-mengingat-row{margin-top:2px}.sk-section-row:not(.sk-mengingat-row){margin-bottom:5px}
.sk-section-row{margin-top:3px!important;margin-bottom:3px!important}.sk-section-content{line-height:1.35!important}
.sk-data-row{display:grid;grid-template-columns:250px 16px minmax(0,1fr);column-gap:0;text-align:left;line-height:1.35;margin:1px 0!important;white-space:normal}.sk-data-row>span:nth-child(2){text-align:center}.sk-data-row>span:last-child{text-align:left}
.sk-section-content:has(.sk-data-row){white-space:normal!important;line-height:1.35!important}
.sk-signature-preview{width:58%;margin:28px 0 0 auto;line-height:1.25}.sk-signature-preview>div{display:grid;grid-template-columns:155px 16px minmax(0,1fr);text-align:left}.sk-signature-preview>div:nth-child(3),.sk-signature-preview>div:nth-child(4){display:block}.sk-signature-name{font-weight:700;margin-top:34px!important}
.sk-signature-stamp{display:block;max-width:90px;max-height:70px;object-fit:contain;margin:2px 0 -20px 40px;position:relative;z-index:1}
.sk-preview-page hr~div:not(.sk-section-row),.sk-preview-page hr~p,.sk-preview-page hr~div p{font-size:{{ $builder['fonts']['body'] ?? 11 }}px!important}.sk-preview-page hr+div{font-size:{{ $decision['font_title'] ?? 13 }}px!important}.sk-section-row{font-size:{{ $builder['fonts']['section_title'] ?? 11 }}px!important}
.sk-preview-page hr+div+div{font-size:{{ $decision['font_number'] ?? 12 }}px!important}.sk-preview-page hr~p:first-of-type{font-size:{{ $decision['font_opening'] ?? 11 }}px!important}
.sk-preview-page hr~div:not(.sk-section-row),.sk-preview-page hr~p,.sk-preview-page hr~div p{font-size:{{ $bodyFont }}px!important}.sk-section-row{font-size:{{ $sectionFont }}px!important}.sk-preview-page hr+div{font-size:{{ $titleFont }}px!important}.sk-preview-page hr+div+div{font-size:{{ $numberFont }}px!important}.sk-preview-page hr~p:first-of-type{font-size:{{ $openingFont }}px!important}
.sk-preview-page hr~*{color:#111827!important}.sk-preview-page hr~* .contact-icon{color:#fff!important}
.whatsapp-icon{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='none' stroke='white' stroke-width='1.6' d='M12 4a8 8 0 0 0-6.8 12.2L4 20l3.9-1.1A8 8 0 1 0 12 4Z'/%3E%3Cpath fill='none' stroke='white' stroke-linecap='round' stroke-width='1.8' d='M9 8.5c-.4.1-.6.5-.5.9.4 1.8 1.8 3.5 3.5 4.1.4.2.9 0 1.1-.4l.3-.6-1.3-.7-.6.6c-.8-.4-1.4-1-1.8-1.8l.6-.6-.7-1.4-.6-.1Z'/%3E%3C/svg%3E") center/13px 13px no-repeat}
</style>
<script>
(() => {
    const syncSkFonts = () => {
        const titleInput = document.querySelector('input[name*="font_title"]');
        const numberInput = document.querySelector('input[name*="font_number"]');
        const bodyInput = document.querySelector('input[name*="fonts][body"]');
        const sectionInput = document.querySelector('input[name*="fonts][section_title"]');
        const page = document.querySelector('.sk-preview-page');
        if (!page) return;
        const title = page.querySelector('hr + div');
        const number = page.querySelector('hr + div + div');
        if (title && titleInput && Number(titleInput.value) > 0) title.style.setProperty('font-size', `${titleInput.value}px`, 'important');
        if (number && numberInput && Number(numberInput.value) > 0) number.style.setProperty('font-size', `${numberInput.value}px`, 'important');
        document.querySelectorAll('.sk-section-row').forEach((row) => { if (sectionInput && Number(sectionInput.value) > 0) row.style.setProperty('font-size', `${sectionInput.value}px`, 'important'); });
        const content = page.querySelector('hr ~ div:not(.sk-section-row)');
        if (content && bodyInput && Number(bodyInput.value) > 0) content.style.setProperty('font-size', `${bodyInput.value}px`, 'important');
    };
    document.addEventListener('input', syncSkFonts);
    document.addEventListener('change', syncSkFonts);
    document.addEventListener('livewire:navigated', syncSkFonts);
    document.addEventListener('livewire:init', () => Livewire.hook('morph.updated', syncSkFonts));
    new MutationObserver(syncSkFonts).observe(document.body, { childList: true, subtree: true });
    queueMicrotask(syncSkFonts);
})();
</script>
