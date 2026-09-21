<?php

namespace App\Filament\Resources\Complaints\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\Complaints\ComplaintResource;

class ManageComplaints extends ListRecords
{
    protected static string $resource = ComplaintResource::class;

    protected static ?string $title = 'PENGADUAN PRIVAT GURU/PEGAWAI';
}
