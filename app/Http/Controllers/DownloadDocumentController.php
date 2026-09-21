<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadDocumentController extends Controller
{
    public function __invoke(Document $document): StreamedResponse
    {
        Gate::authorize('download', $document);

        abort_unless($document->disk === Document::PRIVATE_DISK, 404);

        $disk = Storage::disk(Document::PRIVATE_DISK);

        abort_unless($disk->exists($document->path), 404);

        $fileName = basename(str_replace('\\', '/', $document->original_name));

        return $disk->download($document->path, $fileName, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
