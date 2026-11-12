<?php

namespace App\Http\Controllers;

use App\Models\BatikProduct;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class BatikProductImageController extends Controller
{
    public function __invoke(BatikProduct $product): Response
    {
        abort_unless(auth()->check(), 403);

        $path = $product->image_path;

        abort_unless(filled($path) && Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path), [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
