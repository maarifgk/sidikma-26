<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewComplaintAttachmentController extends Controller
{
    public function __invoke(Complaint $complaint, int $attachment): StreamedResponse
    {
        Gate::authorize('view', $complaint);

        $file = $complaint->attachments[$attachment] ?? null;
        abort_unless(is_array($file) && filled($file['path'] ?? null), 404);

        $disk = Storage::disk(Complaint::DISK);
        abort_unless($disk->exists($file['path']), 404);

        return $disk->response($file['path'], $file['name'] ?? basename($file['path']), [
            'Content-Type' => $file['mime_type'] ?? $disk->mimeType($file['path']) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
