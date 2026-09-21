<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use Filament\Resources\Pages\ListRecords as BaseListRecords;

abstract class ListRecords extends BaseListRecords
{
    use HasAdministrationFormFeedback;
}
