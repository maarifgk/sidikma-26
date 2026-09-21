<?php
namespace App\Http\Controllers;
use App\Models\FoundationSkDocument;use Illuminate\Support\Facades\Storage;
class FoundationSkDocumentDownloadController extends Controller { public function __invoke(FoundationSkDocument $document) { $this->authorize('view',$document);abort_unless(Storage::disk('documents')->exists($document->file_path),404);return Storage::disk('documents')->download($document->file_path,$document->original_filename); } }
