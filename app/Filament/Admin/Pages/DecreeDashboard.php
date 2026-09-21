<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Resources\DecreeSubmissions\DecreeSubmissionResource;
use App\Models\DecreeSubmission;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class DecreeDashboard extends Page
{
    protected static ?string $slug = 'sk-yayasan/dashboard';

    protected static ?string $title = 'Dashboard SK';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdminInduk()
            && Gate::allows('viewAny', DecreeSubmission::class);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.admin.pages.decree-dashboard')
                ->viewData(function (): array {
                    $query = DecreeSubmissionResource::getEloquentQuery();
                    $counts = (clone $query)->reorder()->selectRaw('status, COUNT(*) AS total')
                        ->groupBy('status')->pluck('total', 'status');

                    return [
                        'summary' => [
                            'Total Pengajuan SK' => $counts->sum(),
                            'Dalam Peninjauan' => $counts->get(DecreeSubmission::STATUS_UNDER_REVIEW, 0),
                            'Sedang Diproses' => $counts->get(DecreeSubmission::STATUS_PROCESSING, 0),
                            'Selesai' => $counts->get(DecreeSubmission::STATUS_COMPLETED, 0),
                        ],
                        'submissions' => $query->orderByDesc('submission_date')->orderByDesc('id')->limit(10)->get(),
                        'statuses' => DecreeSubmission::statusOptions(),
                        'listUrl' => DecreeSubmissionResource::getUrl('index', panel: 'admin', isAbsolute: false),
                    ];
                }),
        ]);
    }
}
