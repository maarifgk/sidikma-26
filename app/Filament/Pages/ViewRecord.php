<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use Filament\Resources\Pages\ViewRecord as BaseViewRecord;

abstract class ViewRecord extends BaseViewRecord
{
    use HasAdministrationFormFeedback;
}
