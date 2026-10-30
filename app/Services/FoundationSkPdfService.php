<?php

namespace App\Services;

use App\Models\FoundationSkTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FoundationSkPdfService
{
    public function __construct(private readonly ?FoundationSkMpdfRenderer $renderer = null) {}

    /** @return array<string, string> */
    public static function placeholderDefinitions(): array
    {
        return [
            'nama_lengkap' => 'Nama lengkap user',
            'email' => 'Email user',
            'nis' => 'Kode pegawai / EWANUGK / NIS',
            'nuptk' => 'NUPTK/NPK',
            'nip' => 'NIP',
            'tempat_lahir' => 'Tempat lahir',
            'tgl_lahir' => 'Tanggal lahir format Indonesia',
            'tmt' => 'TMT format Indonesia',
            'alamat' => 'Alamat dari basis data',
            'alamat_html' => 'Alamat dengan line break HTML',
            'nama_kelas' => 'Nama madrasah/sekolah',
            'nama_jurusan' => 'Status atau keterangan kepegawaian',
            'ketugasan' => 'Jabatan atau ketugasan',
            'periode_sk' => 'Periode SK yayasan',
            'tmt_mulai' => 'Tanggal mulai penugasan',
            'prodi_studi' => 'Program studi',
            'nomor_sk' => 'Nomor SK',
            'tanggal_sk' => 'Tanggal penetapan SK',
            'tanggal_mulai' => 'Tanggal mulai berlaku',
            'tanggal_selesai' => 'Tanggal selesai berlaku',
            'masa_aktif' => 'Masa berlaku atau masa tugas',
            'gaji_pokok' => 'Nominal gaji pokok',
            'tunjangan_lain' => 'Nominal tunjangan lain',
            'tanggal_cetak' => 'Tanggal cetak dokumen',
            'waktu_cetak' => 'Waktu dan jam cetak',
            'tahun' => 'Tahun dokumen',
            'bulan' => 'Bulan dokumen',
            'logo_url' => 'URL logo kop surat',
        ];
    }

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

        $hasBuilder = array_key_exists('builder_data', $template->getAttributes())
            && $template->getAttribute('builder_data') !== null;

        if (! is_array($builder) || ! $hasBuilder) {
            return $this->renderLegacy($template, $user, $values);
        }

        $builder = array_replace_recursive(FoundationSkTemplate::defaultBuilderData(), $builder);
        $header = $builder['header'];
        $decision = $builder['decision'];
        $sections = $builder['sections'];
        $fonts = $builder['fonts'];
        $signature = $builder['signature'];
        $copies = $builder['copies'];
        $map = $this->placeholderMap($user, $values);
        $map['logo_url'] = $this->logoUrl($header['logo_path'] ?? null);

        $body = '<header class="sk-letterhead"><table><tr>'
            .'<td class="sk-logo"><img src="'.$this->escape($map['logo_url']).'" alt="Logo"></td><td>'
            .'<div class="sk-top" style="font-size:'.$this->fontSize($header['font_top_text'] ?? 13).'">'.$this->escape($header['top_text'] ?: 'PENGURUS CABANG NAHDLATUL ULAMA GUNUNGKIDUL').'</div>'
            .'<h1 style="font-size:'.$this->fontSize($header['font_institution_name'] ?? 26).'">'.$this->escape($header['institution_name'] ?: 'LEMBAGA PENDIDIKAN MA\'ARIF NU').'</h1>'
            .'<div style="font-size:'.$this->fontSize($header['font_address'] ?? 11).'">'.$this->escape($header['address'] ?: 'Jln. Tentara Pelajar, Trimulyo I, Kepek, Wonosari, Gunungkidul-55813').'</div>'
            .'<div style="font-size:'.$this->fontSize($header['font_contact'] ?? 11).'">'.$this->escape(trim(($header['whatsapp'] ?: '085229747609').' '.($header['email'] ?: 'maarifgunungkidul@gmail.com'))).'</div>'
            .'</td><td class="sk-mark"></td></tr></table></header>';

        $body .= '<main class="sk-content" style="font-size:'.$this->fontSize($fonts['body'] ?? 11).'">'
            .'<h2 style="font-size:'.$this->fontSize($decision['font_title'] ?? 13).'">'.$this->escape($decision['title'] ?: 'SURAT KEPUTUSAN KETUA LP MA\'ARIF NU GUNUNGKIDUL').'</h2>'
            .'<div class="sk-number" style="font-size:'.$this->fontSize($decision['font_number'] ?? 12).'">'.$this->replacePlaceholders((string) ($decision['number'] ?: 'Nomor : {{nomor_sk}}'), $map).'</div>'
            .'<div class="sk-opening" style="font-size:'.$this->fontSize($decision['font_opening'] ?? 11).'">'.$this->replacePlaceholders((string) ($decision['opening'] ?? ''), $map).'</div>'
            .$this->renderSection('Menimbang', $sections['menimbang'] ?? '', $map)
            .$this->renderSection('Mengingat', $sections['mengingat'] ?? '', $map)
            .$this->renderSection('Memperhatikan', $sections['memperhatikan'] ?? '', $map)
            .$this->renderSection('MEMUTUSKAN', $sections['memutuskan'] ?? '', $map)
            .$this->renderSection('Menetapkan', $sections['menetapkan'] ?? '', $map);

        if (trim((string) ($sections['judul_tengah'] ?? '')) !== '') {
            $body .= '<h3 class="sk-centered-title" style="font-size:'.$this->fontSize($fonts['judul_tengah'] ?? 11).'">'.$this->replacePlaceholders((string) $sections['judul_tengah'], $map).'</h3>';
        }

        $body .= '<table class="sk-user-data" style="font-size:'.$this->fontSize($fonts['data_user'] ?? $fonts['body'] ?? 11).'">'
            .'<tr><td>Nama</td><td>: '.$this->replacePlaceholders('{{nama_lengkap}}', $map).'</td></tr>'
            .'<tr><td>NIS/NIP/NUPTK</td><td>: '.$this->replacePlaceholders('{{nis}} / {{nip}} / {{nuptk}}', $map).'</td></tr>'
            .'<tr><td>Sekolah</td><td>: '.$this->replacePlaceholders('{{nama_sekolah}}', $map).'</td></tr>'
            .'</table>'
            .$this->renderSignature($signature, $fonts, $map)
            .$this->renderCopies($copies, $fonts, $map)
            .'</main>';

        return $this->document($template, $body, (string) ($template->custom_css ?? ''));
    }

    public function pdf(FoundationSkTemplate $template, ?User $user = null, array $values = []): string
    {
        return ($this->renderer ?? new FoundationSkMpdfRenderer)->render($this->renderHtml($template, $user, $values), $template);
    }

    private function renderLegacy(FoundationSkTemplate $template, ?User $user, array $values): string
    {
        return $this->document($template, $this->replacePlaceholders((string) ($template->content ?? $template->html_template), $this->placeholderMap($user, $values)), (string) ($template->custom_css ?? $template->css_template));
    }

    private function placeholderMap(?User $user, array $values): array
    {
        $employee = $user?->employee;
        $school = $employee?->school;
        $assignment = $employee?->currentAssignment;
        $position = $assignment?->position;
        $now = Carbon::now();
        $period = (string) ($values['periode'] ?? '-');
        $year = $values['tahun'] ?? (preg_match('/\b(20\d{2})\b/', $period, $matches) ? (int) $matches[1] : $now->year);
        $address = $employee?->address ?: '-';
        $startDate = $assignment?->start_date;
        $endDate = $assignment?->end_date;

        return array_merge([
            'nama_lengkap' => $user?->nama_lengkap ?: ($user?->name ?: '-'),
            'email' => $user?->email ?: '-',
            'nis' => $employee?->employee_code ?: ($user?->nis ?: '-'),
            'nip' => $employee?->nip ?: '-',
            'nuptk' => $employee?->nuptk ?: '-',
            'nama_sekolah' => $school?->name ?: ($user?->nama_kelas ?: '-'),
            'tempat_lahir' => $employee?->birth_place ?: ($user?->tempat_lahir ?: '-'),
            'tgl_lahir' => $this->formatDate($employee?->birth_date ?: $user?->tgl_lahir),
            'tmt' => $this->formatDate($startDate),
            'alamat' => $address,
            'alamat_html' => nl2br($this->escape($address)),
            'nama_kelas' => $school?->name ?: ($user?->nama_kelas ?: '-'),
            'nama_jurusan' => $user?->nama_jurusan ?: ($employee?->employment_status ?: '-'),
            'ketugasan' => $position?->name ?: '-',
            'periode_sk' => $assignment?->decree_period ?: $period,
            'tmt_mulai' => $this->formatDate($startDate),
            'prodi_studi' => $employee?->program_study ?: '-',
            'tanggal_sk' => $this->formatDate($assignment?->decree_date ?: ($values['tanggal_sk'] ?? null)),
            'tanggal_mulai' => $this->formatDate($startDate),
            'tanggal_selesai' => $this->formatDate($endDate),
            'masa_aktif' => $this->activePeriod($startDate, $endDate),
            'gaji_pokok' => $this->numberValue($employee?->gaji_pokok ?? $user?->gaji_pokok),
            'tunjangan_lain' => $this->numberValue($employee?->tunjangan_lain ?? $user?->tunjangan_lain),
            'tanggal_cetak' => $now->locale('id')->translatedFormat('d F Y'),
            'waktu_cetak' => $now->format('H:i'),
            'tahun' => $year,
            'bulan' => $now->locale('id')->translatedFormat('F'),
            'nomor_sk' => '0001/SK/YYS/'.$year,
            'teks_nomor_sk' => '-',
        ], $values);
    }

    private function renderSection(string $label, mixed $value, array $map): string
    {
        if (trim((string) $value) === '') {
            return '';
        }

        return '<section class="sk-section"><div class="sk-section-label">'.$this->escape($label).':</div><div class="sk-section-text">'.$this->replacePlaceholders((string) $value, $map).'</div></section>';
    }

    private function renderSignature(array $signature, array $fonts, array $map): string
    {
        $items = array_filter($signature, fn (mixed $value): bool => is_string($value) && trim($value) !== '');

        if ($items === []) {
            return '';
        }

        $text = array_map(fn (mixed $value): string => $this->replacePlaceholders((string) $value, $map), $items);

        return '<div class="signature" style="font-size:'.$this->fontSize($fonts['signature'] ?? 11).'">'.implode('<br>', $text).'</div>';
    }

    private function renderCopies(array $copies, array $fonts, array $map): string
    {
        if (trim((string) ($copies['title'] ?? '')) === '' && trim((string) ($copies['items'] ?? '')) === '') {
            return '';
        }

        return '<div class="copies" style="font-size:'.$this->fontSize($fonts['copies'] ?? 11).'"><strong>'.$this->replacePlaceholders((string) ($copies['title'] ?? 'Tembusan'), $map).'</strong><div class="copies-items">'.$this->replacePlaceholders((string) ($copies['items'] ?? ''), $map).'</div></div>';
    }

    private function replacePlaceholders(string $text, array $map): string
    {
        return (string) preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function (array $match) use ($map): string {
            $key = strtolower($match[1]);

            return $key === 'alamat_html'
                ? (string) ($map[$key] ?? '-')
                : $this->escape((string) ($map[$key] ?? '-'));
        }, $text);
    }

    private function escape(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function document(FoundationSkTemplate $template, string $body, string $css): string
    {
        $size = in_array(strtoupper((string) $template->paper_size), ['A4', 'F4', 'LEGAL', 'LETTER'], true) ? strtoupper((string) $template->paper_size) : 'A4';
        $orientation = $template->orientation === 'landscape' ? 'landscape' : 'portrait';
        $safeCss = str_ireplace('</style', '<\\/style', $css);
        $cssSize = $size === 'F4' ? '215mm 330mm' : $size;
        $base = '@page{size:'.$cssSize.' '.$orientation.';margin:0}*{box-sizing:border-box}html,body{margin:0;padding:0;background:#fff;color:#111;font-family:"DejaVu Serif","Times New Roman",serif}body{font-size:11pt}.sk-page{min-height:100vh;padding:14mm 20mm 16mm}.sk-letterhead{border-bottom:1.2pt solid #111;padding-bottom:2mm;margin-bottom:8mm}.sk-letterhead table{width:100%;border-collapse:collapse;table-layout:fixed}.sk-letterhead td{vertical-align:middle;text-align:center}.sk-logo{width:48mm}.sk-logo img{display:block;width:44.45mm;height:28.95mm;object-fit:contain;margin:auto}.sk-mark{width:10mm}.sk-top{font-weight:700}.sk-letterhead h1{line-height:1.05;margin:1mm 0;font-weight:700}.sk-letterhead div{line-height:1.15}.sk-content{line-height:1.15}.sk-content p,.sk-opening{margin:2.5mm 0;text-align:justify}.sk-content h2{text-align:center;margin:0 0 2mm;font-weight:700}.sk-number{text-align:center;margin-bottom:7mm}.sk-section{margin:3mm 0;text-align:justify}.sk-section-label{font-weight:700}.sk-section-text,.copies-items{white-space:pre-line}.sk-centered-title{text-align:center;margin:5mm 0}.sk-content table{width:100%;border-collapse:collapse}.sk-content td{vertical-align:top;padding:0 1mm}.sk-content td:first-child{width:32mm}.signature{margin-left:55%;margin-top:10mm;page-break-inside:avoid;white-space:pre-line}.copies{margin-top:8mm;page-break-inside:avoid}.sk-content img{max-width:100%;height:auto}@media print{html,body{background:#fff}}';

        return '<!doctype html><html><head><meta charset="utf-8"><style>'.$base.$safeCss.'</style></head><body><main class="sk-page">'.$body.'</main></body></html>';
    }

    private function logoUrl(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        if (preg_match('#^(?:https?:|file:|//)#i', $path) === 1) {
            return '';
        }

        $path = preg_replace('#^/?storage/#i', '', $path) ?: $path;

        try {
            $disk = Storage::disk('public');

            if ($disk->exists($path)) {
                $mime = (string) ($disk->mimeType($path) ?: '');

                if (str_starts_with($mime, 'image/')) {
                    return 'data:'.$mime.';base64,'.base64_encode((string) $disk->get($path));
                }
            }
        } catch (Throwable) {
        }

        return '';
    }

    private function fontSize(mixed $value): string
    {
        return max(6, min(72, (int) $value)).'pt';
    }

    private function formatDate(mixed $value): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->locale('id')->translatedFormat('d F Y');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private function activePeriod(mixed $startDate, mixed $endDate): string
    {
        $start = $this->formatDate($startDate);
        $end = $this->formatDate($endDate);

        return $start === '-' && $end === '-' ? '-' : $start.' - '.$end;
    }

    private function numberValue(mixed $value): string
    {
        return blank($value) ? '-' : number_format((float) $value, 0, ',', '.');
    }
}
