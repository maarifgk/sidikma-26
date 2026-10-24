<?php

namespace App\Http\Controllers;

use App\Models\LearningModule;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DownloadLearningModuleController extends Controller
{
    public function __invoke(LearningModule $learningModule): Response
    {
        Gate::authorize('view', $learningModule);
        abort_unless(Storage::disk(LearningModule::DISK)->exists($learningModule->file_path), 404);

        return Storage::disk(LearningModule::DISK)->download(
            $learningModule->file_path,
            $learningModule->original_name ?: basename($learningModule->file_path),
        );
    }
}
