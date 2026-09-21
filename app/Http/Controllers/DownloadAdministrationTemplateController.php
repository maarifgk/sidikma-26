<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequirement;
use App\Models\CorrespondenceType;
use App\Models\DecreeRequirement;
use App\Models\MutationRequirement;
use App\Models\ProposalRequirement;
use App\Models\SipinterRequirement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadAdministrationTemplateController extends Controller
{
    public function __invoke(string $type, int $record): StreamedResponse
    {
        abort_unless(auth()->user()?->can('document.create'), 403);

        [$model, $disk] = match ($type) {
            'decree' => [DecreeRequirement::class, 'decree-proposals'],
            'sipinter' => [SipinterRequirement::class, 'sipinter-updates'],
            'mutation' => [MutationRequirement::class, 'employee-mutations'],
            'activity' => [ActivityRequirement::class, 'employee-activities'],
            'correspondence' => [CorrespondenceType::class, 'correspondence-requests'],
            'proposal' => [ProposalRequirement::class, 'proposal-requests'],
            default => abort(404),
        };

        /** @var Model&object{template_path: ?string} $template */
        $template = $model::query()->findOrFail($record);
        abort_if(blank($template->template_path) || str_starts_with($template->template_path, 'http'), 404);
        abort_unless(Storage::disk($disk)->exists($template->template_path), 404);

        return Storage::disk($disk)->download(
            $template->template_path,
            basename($template->template_path),
        );
    }
}
