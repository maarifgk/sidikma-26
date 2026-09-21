<?php

namespace App\Services;

use App\Models\{FoundationSkTemplate, User};
use Illuminate\Support\Str;

class FoundationSkPdfService
{
    public function generateOne(FoundationSkTemplate $template, User $user, string $periode, FoundationSkNumberService $numbers, array $values = []): string
    {
        return $numbers->nextFor($periode, fn (string $number): string => $this->pdf($template, $user, $values + ['nomor_sk' => $number, 'periode' => $periode]), $values);
    }

    public function generateBatch(FoundationSkTemplate $template, iterable $users, string $periode, FoundationSkNumberService $numbers, array $values = []): array
    {
        $users = is_array($users) ? $users : iterator_to_array($users);
        return $numbers->nextFor($periode, function (string $first) use ($template, $users, $periode, $values, $numbers): array {
            $files = [$this->pdf($template, $users[0] ?? null, $values + ['nomor_sk' => $first, 'periode' => $periode])];
            foreach (array_slice($users, 1) as $user) {
                $files[] = $this->pdf($template, $user, $values + ['nomor_sk' => $numbers->next($periode, $values), 'periode' => $periode]);
            }
            return $files;
        }, $values);
    }

    public function renderHtml(FoundationSkTemplate $template, ?User $user = null, array $values = []): string
    {
        $builder = $template->builder_data;
        $hasBuilder = is_array($builder) && ($template->getRawOriginal('builder_data') !== null);
        if (! $hasBuilder) {
            return $this->renderLegacy($template, $user, $values);
        }

        $header = $builder['header'] ?? [];
        $header['logo_path'] = $header['logo_path'] ?? null;
        $decision = $builder['decision'] ?? [];
        $sections = $builder['sections'] ?? [];
        $fonts = $builder['fonts'] ?? [];
        $map = $this->placeholderMap($user, $values);
        $section = fn (string $key, string $label): string => ! empty($sections[$key]) ? '<p><strong>'.$label.':</strong> '.$this->replacePlaceholders($sections[$key], $map).'</p>' : '';
        $logo = $header['logo_path'] ?? '';
        $logoUrl = $logo ? asset('storage/'.$logo) : asset('images/sk-reference-logo.png');
        $body = '<header class="sk-letterhead"><table><tr><td class="sk-logo"><img src="'.$logoUrl.'" alt="Logo"></td><td><div class="sk-top">'.$this->escape($header['top_text'] ?: 'PENGURUS CABANG NAHDLATUL ULAMA GUNUNGKIDUL').'</div><h1>'.$this->escape($header['institution_name'] ?: 'LEMBAGA PENDIDIKAN MA\'ARIF NU').'</h1><div>'.$this->escape($header['address'] ?: 'Jln. Tentara Pelajar, Trimulyo I, Kepek, Wonosari, Gunungkidul-55813').'</div><div>'.$this->escape(trim(($header['whatsapp'] ?: '085229747609').' '.($header['email'] ?: 'maarifgunungkidul@gmail.com'))).'</div></td><td class="sk-mark"></td></tr></table></header><main class="sk-content" style="font-size:'.((int) ($fonts['body'] ?? 11)).'pt"><h2>'.$this->escape($decision['title'] ?: 'SURAT KEPUTUSAN KETUA LP MA\'ARIF NU GUNUNGKIDUL').'</h2><div class="sk-number">'.$this->replacePlaceholders($decision['number'] ?: 'Nomor : {{nomor_sk}}', $map).'</div><p>'.$this->replacePlaceholders($decision['opening'] ?? '', $map).'</p>'.$section('menimbang', 'Menimbang').$section('mengingat', 'Mengingat').$section('memperhatikan', 'Memperhatikan').$section('memutuskan', 'MEMUTUSKAN').$section('menetapkan', 'Menetapkan').'<p>Nama: '.$this->escape($map['nama_lengkap']).'<br>Sekolah: '.$this->escape($map['nama_sekolah']).'</p></main>';
        return $this->document($template, $body, $template->custom_css ?? '');
    }

    public function pdf(FoundationSkTemplate $template, ?User $user = null, array $values = []): string
    {
        $text = preg_replace('/\s+/', ' ', trim(strip_tags($this->renderHtml($template, $user, $values))));
        return $this->minimalPdf(Str::limit($text, 3000, ''));
    }

    private function renderLegacy(FoundationSkTemplate $template, ?User $user, array $values): string
    {
        return $this->document($template, $this->replacePlaceholders((string) ($template->content ?? $template->html_template), $this->placeholderMap($user, $values)), (string) ($template->custom_css ?? $template->css_template));
    }

    private function placeholderMap(?User $user, array $values): array
    {
        $employee = $user?->employee;
        return array_merge(['nama_lengkap' => $user?->name ?? '-', 'email' => $user?->email ?? '-', 'nis' => $employee?->nis ?? '-', 'nip' => $employee?->nip ?? '-', 'nuptk' => $employee?->nuptk ?? '-', 'nama_sekolah' => $employee?->school?->name ?? '-', 'jabatan' => '-', 'periode' => $values['periode'] ?? '-', 'tahun' => $values['tahun'] ?? now()->year, 'nomor_sk' => $values['nomor_sk'] ?? '0001/SK/YYS/2026', 'teks_nomor_sk' => $values['teks_nomor_sk'] ?? '-'], $values);
    }

    private function replacePlaceholders(string $text, array $map): string { return preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', fn ($m) => $this->escape((string) ($map[$m[1]] ?? '-')), $text); }
    private function escape(mixed $value): string { return e((string) ($value ?? '-')); }
    private function document(FoundationSkTemplate $template, string $body, string $css): string { $size = in_array(strtoupper((string) $template->paper_size), ['A4', 'F4', 'LEGAL', 'LETTER'], true) ? strtoupper((string) $template->paper_size) : 'A4'; $orientation = $template->orientation === 'landscape' ? 'landscape' : 'portrait'; $base = '@page{size:'.$size.' '.$orientation.';margin:0}*{box-sizing:border-box}html,body{margin:0;padding:0;background:#fff;color:#111;font-family:"Times New Roman",serif}body{font-size:11pt}.sk-letterhead{border-bottom:1.2pt solid #111;padding-bottom:2mm;margin-bottom:8mm}.sk-letterhead table{width:100%;border-collapse:collapse;table-layout:fixed}.sk-letterhead td{vertical-align:middle;text-align:center}.sk-logo{width:48mm}.sk-logo img{display:block;width:44.45mm;height:28.95mm;object-fit:contain;margin:auto}.sk-mark{width:10mm}.sk-top{font-size:11pt;font-weight:700}.sk-letterhead h1{font-size:16pt;line-height:1.05;margin:1mm 0;font-weight:700}.sk-letterhead div{font-size:9pt;line-height:1.15}.sk-content{line-height:1.15}.sk-content p{margin:2.5mm 0;text-align:justify}.sk-content h2{text-align:center;font-size:12pt;margin:0 0 2mm;font-weight:700}.sk-number{text-align:center;margin-bottom:7mm}.sk-content section{margin:3mm 0}.sk-content ol{margin:1mm 0 2mm;padding-left:10mm}.sk-content li{margin:1mm 0}.sk-content table{width:100%;border-collapse:collapse}.sk-content td{vertical-align:top;padding:0 1mm}.sk-content td:first-child{width:32mm}.signature{margin-left:55%;margin-top:10mm;page-break-inside:avoid}.sk-content img{max-width:100%;height:auto}@media print{html,body{background:#fff}}'.$css; return '<!doctype html><html><head><meta charset="utf-8"><style>'.$base.'</style></head><body>'.$body.'</body></html>'; }
    private function minimalPdf(string $text): string { $text=str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$text);$stream="BT /F1 12 Tf 50 780 Td (".$text.") Tj ET";$o=["<< /Type /Catalog /Pages 2 0 R >>","<< /Type /Pages /Kids [3 0 R] /Count 1 >>","<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>","<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>","<< /Length ".strlen($stream)." >>\nstream\n{$stream}\nendstream"];$pdf="%PDF-1.4\n";$offs=[];foreach($o as $i=>$v){$offs[$i+1]=strlen($pdf);$pdf.=($i+1)." 0 obj\n{$v}\nendobj\n";}$xref=strlen($pdf);$pdf.="xref\n0 6\n0000000000 65535 f \n";foreach($offs as $x)$pdf.=sprintf('%010d 00000 n \n',$x);return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF"; }
}
