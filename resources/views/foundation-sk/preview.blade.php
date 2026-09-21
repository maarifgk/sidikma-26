<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $template->name }}</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
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
        @media (max-width: 640px) { .sk-page { margin: 0 auto; } }
    </style>
</head>
<body><main class="sk-page">{!! $html !!}</main></body>
</html>
