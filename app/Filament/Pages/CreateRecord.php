<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use Filament\Resources\Pages\CreateRecord as BaseCreateRecord;

abstract class CreateRecord extends BaseCreateRecord
{
    use HasAdministrationFormFeedback;
}
