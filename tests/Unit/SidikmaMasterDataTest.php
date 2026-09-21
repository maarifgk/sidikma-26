<?php

namespace Tests\Unit;

use App\Support\SidikmaMasterData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SidikmaMasterDataTest extends TestCase
{
    #[DataProvider('paymentTypes')]
    public function test_it_consolidates_legacy_payment_names(string $legacy, string $expected): void
    {
        $this->assertSame($expected, SidikmaMasterData::generalPaymentType($legacy));
    }

    /** @return array<string, array{string, string}> */
    public static function paymentTypes(): array
    {
        return [
            'general fee' => ['IURAN', 'IURAN'],
            'dated fee' => ['Pembayaran Iuran Januari-Juni 2025', 'IURAN'],
            'fee typo' => ['Keukurangan Pembayaran IURAN BULAN JULI- DESEMBER 2025', 'IURAN'],
            'batik period' => ['Pembayaran Batik Periode 2026', 'Pembayaran Batik'],
            'additional batik' => ['PEMBAYARAN TAMBAHAN PESANAN BATIK SISWA MI', 'Pembayaran Batik'],
            'new decree' => ['Pembayaran SK Yayasan Baru', 'Pembayaran SK'],
            'decree renewal' => ['Pembayaran SK Perpanjangan', 'Pembayaran SK'],
            'book' => ['Pembayaran Buku Ke-NU an', 'Pembayaran Buku Ke-NU-an'],
        ];
    }

    public function test_all_legacy_assignment_names_have_position_definitions(): void
    {
        $positions = SidikmaMasterData::employeePositionsByName();

        $this->assertCount(22, $positions);
        $this->assertSame('TENAGA-ADMINISTRASI', $positions['tenaga administrasi']['code']);
        $this->assertSame('KEPALA-SEKOLAH', $positions['kepala madrasah/sekolah']['code']);
        $this->assertSame('TIK-PRAKARYA', $positions['mengajar tik/prakarya']['code']);
    }
}
