<?php

namespace App\Http\Controllers;

use App\Models\DecreeProposal;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DecreeProposalDocumentController extends Controller
{
    public function __invoke(DecreeProposal $proposal, string $document): Response|BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('view', $proposal), 403);

        $allowedDocuments = [
            'photo_path',
            'diploma_path',
            'application_letter_path',
            'mwc_recommendation_request_path',
            'service_statement_path',
            'teaching_certificate_path',
            'educator_certificate_path',
            'task_assignment_certificate_path',
        ];

        abort_unless(in_array($document, $allowedDocuments, true), 404);

        $path = $proposal->getAttribute($document);
        $disk = Storage::disk('decree-proposals');

        abort_if(blank($path) || ! $disk->exists($path), 404, 'Dokumen tidak ditemukan.');

        $absolutePath = realpath($disk->path($path));
        $root = realpath(config('filesystems.disks.decree-proposals.root'));

        abort_unless($absolutePath !== false && $root !== false, 404);
        abort_unless(str_starts_with(
            strtolower($absolutePath),
            strtolower($root.DIRECTORY_SEPARATOR),
        ), 404);

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $headers = [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)) {
            return response()->file($absolutePath, $headers + [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.basename($absolutePath).'"',
            ]);
        }

        return response()->download($absolutePath, basename($absolutePath), $headers + [
            'Content-Type' => $mime,
        ]);
    }
}
