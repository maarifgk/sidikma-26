<?php

namespace App\Filament\App\Pages;

use App\Models\Document;
use App\Models\Employee;
use App\Models\School;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class MyDecrees extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowDown;
    protected static ?string $navigationLabel = 'File SK Saya';
    protected static ?string $slug = 'file-sk-saya';
    protected string $view = 'filament.app.pages.my-decrees';
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(User::ROLE_GURU_PEGAWAI) ?? false;
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $employee = $user?->employee()->with('school')->first();

        $employeeDocuments = $user
            ? Document::query()
                ->whereMorphedTo('owner', $user)
                ->where('document_type', 'like', 'foundation_decree:%')
                ->where('status', Document::STATUS_ACTIVE)
                ->latest('decree_date')
                ->latest()
                ->get()
            : collect();
        $schoolDocuments = collect();

        return compact('user', 'employee', 'employeeDocuments', 'schoolDocuments');
    }
}
