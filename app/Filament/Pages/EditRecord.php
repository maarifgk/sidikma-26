<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use Filament\Resources\Pages\EditRecord as BaseEditRecord;

abstract class EditRecord extends BaseEditRecord
{
    use HasAdministrationFormFeedback;
}
