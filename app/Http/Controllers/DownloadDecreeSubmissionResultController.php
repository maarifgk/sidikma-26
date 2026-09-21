<?php

namespace App\Http\Controllers;

use App\Models\DecreeSubmission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadDecreeSubmissionResultController extends Controller
{
    public function __invoke(DecreeSubmission $decreeSubmission): StreamedResponse
    {
        Gate::authorize('downloadResult', $decreeSubmission);
        $disk = Storage::disk('documents');
        abort_unless($disk->exists($decreeSubmission->result_file_path), 404);

        return $disk->download($decreeSubmission->result_file_path, $decreeSubmission->result_original_name ?? basename($decreeSubmission->result_file_path), [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
