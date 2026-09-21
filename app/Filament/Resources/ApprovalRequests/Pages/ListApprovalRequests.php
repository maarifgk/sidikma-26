<?php

namespace App\Filament\Resources\ApprovalRequests\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\ApprovalRequests\ApprovalRequestResource;

class ListApprovalRequests extends ListRecords
{
    protected static string $resource = ApprovalRequestResource::class;
}
