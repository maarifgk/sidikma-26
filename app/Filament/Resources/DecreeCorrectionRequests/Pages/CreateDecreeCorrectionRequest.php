<?php

namespace App\Filament\Resources\DecreeCorrectionRequests\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\DecreeCorrectionRequests\DecreeCorrectionRequestResource;
use App\Models\DecreeCorrectionRequest;

class CreateDecreeCorrectionRequest extends CreateRecord
{
    use HasAdministrationFormFeedback;

    protected static string $resource = DecreeCorrectionRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $schoolId = auth()->user()->accessibleSchoolIds()->first();
        abort_if(blank($schoolId), 403, 'Akun belum terhubung ke madrasah.');

        $data['school_id'] = $schoolId;
        $data['submitted_by'] = auth()->id();
        $data['request_number'] = DecreeCorrectionRequest::generateRequestNumber();
        $data['request_date'] = today();
        $data['status'] = DecreeCorrectionRequest::STATUS_DRAFT;

        return $data;
    }
}
