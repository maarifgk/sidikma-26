@php
    $record = $template;
    $get = static fn (string $key, mixed $default = null): mixed => data_get($template, $key, $default);
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $template->name }} - Preview</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        html, body { margin: 0; min-height: 100%; background: #e5e7eb; }
        body { padding: 24px; }
        .foundation-sk-preview { min-height: 0 !important; border: 0 !important; box-shadow: none !important; background: transparent !important; padding: 0 !important; }
        .foundation-sk-preview > div:first-child { display: none !important; }
        .preview-scroll-area { overflow: visible !important; border: 0 !important; background: transparent !important; padding: 0 !important; }
        .preview-scroll-area > div { width: auto !important; height: auto !important; }
        .preview-document { box-shadow: 0 2px 14px rgba(0,0,0,.2) !important; margin: 0 auto !important; transform: none !important; }
        /* Dompdf belum mendukung CSS grid/:has seperti browser. Gunakan tabel
           untuk mempertahankan kolom label, titik dua, dan isi tetap sejajar. */
        .sk-section-row { display: block !important; position: relative !important; width: 100% !important; margin: 3px 0 !important; padding-left: 19% !important; }
        .sk-section-row > .sk-section-label { position: absolute !important; left: 0 !important; top: 0 !important; width: 16% !important; text-align: left !important; }
        .sk-section-row > .sk-section-colon { position: absolute !important; left: 16% !important; top: 0 !important; width: 3% !important; text-align: center !important; }
        .sk-section-row > .sk-section-content { display: block !important; width: 100% !important; text-align: justify !important; }
        .sk-data-row { display: block !important; position: relative !important; width: 100% !important; margin: 1px 0 !important; padding-left: 27% !important; }
        .sk-data-row > span:first-child { position: absolute !important; left: 0 !important; top: 0 !important; width: 24% !important; }
        .sk-data-row > span:nth-child(2) { position: absolute !important; left: 24% !important; top: 0 !important; width: 3% !important; text-align: center !important; }
        .sk-data-row > span:last-child { display: block !important; width: 100% !important; }
        .sk-signature-preview { display: table !important; table-layout: fixed !important; }
        .sk-signature-preview > div { display: table-row !important; }
        .sk-signature-preview > div > span, .sk-signature-preview > div > b { display: table-cell !important; vertical-align: top !important; }
        @media print { html, body { background: #fff; } body { padding: 0; } .preview-document { box-shadow: none !important; } }
        /* The old renderer rules are intentionally replaced below. */
        /*
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #e5e7eb; }
        body { font-family: Arial, sans-serif; color: #111; }
        .sk-page { width: 210mm; min-height: 297mm; margin: 20px auto; padding: 20mm 25.4mm 14.5mm; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,.18); }
        .sk-document { font-family: "Times New Roman", serif; font-size: 11pt; line-height: 1.15; }
        .sk-letterhead { border-bottom: 1.2pt solid #111; padding-bottom: 2mm; margin-bottom: 8mm; }
        .sk-letterhead table { width:100%; border-collapse:collapse; table-layout:fixed; }
        .sk-letterhead td { vertical-align:middle; text-align:center; }
        .sk-logo { width:48mm; }.sk-logo img { display:block; width:44.45mm; height:28.95mm; object-fit:contain; margin:auto; }
        .sk-letterhead h1 { font-size:16pt; line-height:1.05; margin:1mm 0; }.sk-letterhead div { font-size:9pt; line-height:1.15; }
        .sk-document p { margin: 2.5mm 0; text-align:justify; }
        .sk-document section { margin: 3mm 0; }
        .sk-document ol { margin: 5px 0 10px; padding-left: 28px; }
        .sk-document li { padding-left: 4px; margin: 4px 0; }
        .sk-center { text-align: center; margin: 20px 0 14px; }
        .sk-document table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .sk-document td { padding: 2px 4px; vertical-align: top; }
        .sk-document td:first-child { width: 31%; }
        .signature { margin: 25px 0 25px 56%; white-space: nowrap; }
        @media print { html, body { background: #fff; } .sk-page { margin: 0; box-shadow: none; } }
        @media (max-width: 640px) { .sk-page { margin: 0 auto; } } */
    </style>
</head>
<body>
    @include('filament.foundation-sk.template-editor-preview', ['record' => $record, 'get' => $get])
</body>
</html>
