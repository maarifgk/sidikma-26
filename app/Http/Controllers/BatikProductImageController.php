<?php

namespace App\Http\Controllers;

use App\Models\BatikProduct;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class BatikProductImageController extends Controller
{
    public function __invoke(BatikProduct $product): Response
    {
        $path = $product->image_path;
        $disk = Storage::disk('public');

        abort_unless(filled($path) && $disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
