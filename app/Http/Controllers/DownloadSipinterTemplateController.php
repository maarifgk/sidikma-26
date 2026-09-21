<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class DownloadSipinterTemplateController extends Controller
{
    public function __invoke(): Response
    {
        abort_unless(auth()->user()?->can('document.create'), 403);

        return response($this->buildPdf(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="template-surat-permohonan-sipinter.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function buildPdf(): string
    {
        $content = implode("\n", [
            'BT',
            '/F1 16 Tf',
            '50 790 Td',
            '(TEMPLATE SURAT PERMOHONAN) Tj',
            '/F1 12 Tf',
            '0 -24 Td',
            '(UPDATE DATA SIPINTER) Tj',
            '0 -44 Td',
            '(Kepada Yth.) Tj',
            '0 -18 Td',
            '(Ketua LP. Ma\'arif NU PCNU Gunungkidul) Tj',
            '0 -18 Td',
            '(di Gunungkidul) Tj',
            '0 -36 Td',
            '(Dengan hormat,) Tj',
            '0 -24 Td',
            '(Kami mengajukan pembaruan data SIPINTER dengan rincian:) Tj',
            '0 -28 Td',
            '(Sekolah/Madrasah      : ............................................................) Tj',
            '0 -22 Td',
            '(NPSN                    : ............................................................) Tj',
            '0 -22 Td',
            '(Alamat                  : ............................................................) Tj',
            '0 -22 Td',
            '(Status Tanah            : ............................................................) Tj',
            '0 -22 Td',
            '(Pengelolaan             : ............................................................) Tj',
            '0 -40 Td',
            '(Demikian permohonan ini kami sampaikan untuk diproses sebagaimana mestinya.) Tj',
            '0 -60 Td',
            '(Gunungkidul, ............................) Tj',
            '0 -70 Td',
            '(Kepala Madrasah/Sekolah) Tj',
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
