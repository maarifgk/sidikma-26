<?php

use App\Http\Controllers\DownloadAdministrationTemplateController;
use App\Http\Controllers\AttendanceLocationSearchController;
use App\Http\Controllers\DownloadDecreeSubmissionResultController;
use App\Http\Controllers\DownloadDocumentController;
use App\Http\Controllers\DownloadMutationTemplateController;
use App\Http\Controllers\DownloadSipinterTemplateController;
use App\Http\Controllers\MapTileController;
use App\Http\Controllers\MapProviderTileController;
use App\Http\Controllers\ViewAttendanceSelfieController;
use App\Http\Controllers\ViewComplaintAttachmentController;
use App\Models\DecreeCorrectionRequest;
use App\Http\Controllers\FoundationSkDocumentDownloadController;
use App\Http\Controllers\FoundationSkTemplateController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\LocalPaymentController;
use App\Http\Controllers\SafeLocalPaymentController;

Route::get('/', function () {
    return redirect('/admin/login');
});
Route::middleware('auth')->group(function (): void {
    Route::get('/payments/local/{invoice}', [LocalPaymentController::class, 'show'])->name('payments.local.show');
    Route::post('/payments/local/{invoice}', [LocalPaymentController::class, 'create'])->name('payments.local.create');
    Route::post('/payments/local/callback', [SafeLocalPaymentController::class, 'callback'])->name('payments.local.callback');
});

Route::get('/documents/{document}/download', DownloadDocumentController::class)
    ->middleware('auth')
    ->name('documents.download');

Route::get('/administration-templates/{type}/{record}/download', DownloadAdministrationTemplateController::class)
    ->whereIn('type', ['decree', 'sipinter', 'mutation', 'activity', 'correspondence', 'proposal'])
    ->whereNumber('record')
    ->middleware('auth')
    ->name('administration-templates.download');

Route::get('/decree-submissions/{decreeSubmission}/download', DownloadDecreeSubmissionResultController::class)
    ->middleware('auth')
    ->name('decree-submissions.download');

Route::get('/foundation-sk/documents/{document}/download', FoundationSkDocumentDownloadController::class)
    ->middleware('auth')
    ->name('foundation-sk.documents.download');
Route::get('/foundation-sk/templates/{template}/preview', [FoundationSkTemplateController::class, 'preview'])->middleware('auth')->name('foundation-sk.templates.preview');
Route::post('/foundation-sk/templates/{template}/generate', [FoundationSkTemplateController::class, 'generate'])->middleware('auth')->name('foundation-sk.templates.generate');
Route::post('/foundation-sk/templates/{template}/batch', [FoundationSkTemplateController::class, 'batch'])->middleware('auth')->name('foundation-sk.templates.batch');

Route::get('/sipinter/template-surat-permohonan', DownloadSipinterTemplateController::class)
    ->middleware('auth')
    ->name('sipinter.template.download');

Route::get('/mutasi/template-surat-permohonan', DownloadMutationTemplateController::class)
    ->middleware('auth')
    ->name('mutations.template.download');

Route::get('/attendance-records/{attendanceRecord}/selfie/{slot}', ViewAttendanceSelfieController::class)
    ->middleware('auth')
    ->name('attendance.selfie.view');
Route::get('/map-tiles/{z}/{x}/{y}.png', MapTileController::class)
    ->whereNumber(['z', 'x', 'y'])
    ->middleware('auth')
    ->name('map-tiles.show');
Route::get('/map-provider-tiles/{provider}/{z}/{x}/{y}.png', MapProviderTileController::class)
    ->whereIn('provider', ['detail', 'satellite'])
    ->whereNumber(['z', 'x', 'y'])
    ->middleware('auth')
    ->name('map-provider-tiles.show');
Route::get('/attendance/location-search', AttendanceLocationSearchController::class)
    ->middleware(['auth', 'throttle:20,1'])
    ->name('attendance.location-search');
Route::get('/complaints/{complaint}/attachments/{attachment}', ViewComplaintAttachmentController::class)
    ->whereNumber('attachment')
    ->middleware('auth')
    ->name('complaints.attachments.view');
Route::get('/decree-corrections/{record}/download/{type}', function (DecreeCorrectionRequest $record, string $type) {
    abort_unless(auth()->user()?->can('view', $record), 403);
    $path = match ($type) {
        'old' => $record->old_decree_path, 'supporting' => $record->supporting_document_path, 'result' => $record->corrected_decree_path, default => null
    };
    abort_if(blank($path) || ! Storage::disk('documents')->exists($path), 404);

    return Storage::disk('documents')->download($path);
})->middleware('auth')->name('decree-corrections.download');
