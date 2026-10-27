<?php

namespace App\Filament\Resources\Arrears\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\Arrears\ArrearsResource;

class ListArrears extends ListRecords
{
    protected static string $resource = ArrearsResource::class;

    protected static ?string $title = 'Tunggakan';

    public function getSubheading(): ?string
    {
        return 'Daftar tagihan belum lunas sesuai hak akses. Tagihan tanpa tanggal jatuh tempo tetap ditampilkan.';
    }
}
