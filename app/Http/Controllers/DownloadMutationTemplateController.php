<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class DownloadMutationTemplateController extends Controller
{
    public function __invoke(): Response
    {
        abort_unless(auth()->user()?->can('approval.create'), 403);

        return response($this->buildPdf(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="template-surat-mutasi-guru-pegawai.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function buildPdf(): string
    {
        $content = implode("\n", [
            'BT',
            '/F1 16 Tf',
            '50 790 Td',
            '(TEMPLATE SURAT PERMOHONAN MUTASI) Tj',
            '/F1 12 Tf',
            '0 -24 Td',
            '(GURU / PEGAWAI) Tj',
            '0 -44 Td',
            '(Kepada Yth.) Tj',
            '0 -18 Td',
            '(Ketua LP. Ma\'arif NU PCNU Gunungkidul) Tj',
            '0 -36 Td',
            '(Dengan hormat,) Tj',
            '0 -24 Td',
            '(Kami mengajukan permohonan mutasi guru/pegawai berikut:) Tj',
            '0 -28 Td',
            '(Nama                    : ............................................................) Tj',
            '0 -22 Td',
            '(EWANUGK                 : ............................................................) Tj',
            '0 -22 Td',
            '(Madrasah/Sekolah Asal   : ............................................................) Tj',
            '0 -22 Td',
            '(Madrasah/Sekolah Tujuan : ............................................................) Tj',
            '0 -22 Td',
            '(Alasan Mutasi           : ............................................................) Tj',
            '0 -40 Td',
            '(Demikian permohonan ini disampaikan untuk ditindaklanjuti.) Tj',
            '0 -60 Td',
            '(Gunungkidul, ............................) Tj',
            '0 -70 Td',
            '(Kepala Madrasah/Sekolah Asal) Tj',
            'ET',
        ]);

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            5 => '<< /Length '.strlen($content)." >>\nstream\n{$content}\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";

        for ($number = 1; $number <= 5; $number++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$number])."\n";
        }

        return $pdf
            ."trailer\n<< /Size 6 /Root 1 0 R >>\n"
            ."startxref\n{$xrefOffset}\n%%EOF";
    }
}
