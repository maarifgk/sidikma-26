<?php

namespace App\Filament\App\Pages;

use App\Models\{DecreeTemplate, FoundationSkDocument, FoundationSkTemplate};
use App\Models\Document;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class SchoolDecrees extends Page
{
    private const DOCUMENT_PREFIX = 'foundation_decree:';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'HOMES';

    protected static ?string $navigationLabel = 'SK Yayasan';

    protected static ?string $slug = 'sk-yayasan';

    protected static bool $shouldRegisterNavigation = false;

    public string $documentSearch = '';

    public string $selectedYear = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH)
            && auth()->user()->can('document.view')
            && auth()->user()->can('sk-yayasan.view');
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.app.pages.school-decrees')
                ->viewData(fn (): array => $this->pageData()),
        ]);
    }

    /** @return array<string, mixed> */
    private function pageData(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $schoolIds = $user->accessibleSchoolIds();
        $foundationDocuments = $this->foundationDocuments($schoolIds->all());
        $legacyDocuments = $this->documents($schoolIds->all());
        $allDocuments = $legacyDocuments->concat($foundationDocuments)->sortByDesc('uploadedAt')->values();

        return [
            'totalDocuments' => $allDocuments->count(),
            'linkedUsers' => $allDocuments->pluck('user')->unique()->count(),
            'yearOptions' => $allDocuments
                ->pluck('year')
                ->filter(fn (string $year): bool => $year !== '-')
                ->unique()
                ->sortDesc()
                ->values(),
            'documents' => $allDocuments
                ->when(filled($this->selectedYear), fn (Collection $documents): Collection => $documents->where('year', $this->selectedYear))
                ->when(filled(trim($this->documentSearch)), fn (Collection $documents): Collection => $documents->filter(function (array $document): bool {
                    $needle = str($this->documentSearch)->lower()->toString();
                    return str(implode(' ', $document))->lower()->contains($needle);
                })->values()),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function foundationDocuments(array $schoolIds): Collection
    {
        $templates = FoundationSkTemplate::query()->pluck('name', 'id');

        return FoundationSkDocument::query()
            ->where(function (Builder $query) use ($schoolIds): void {
                $query
                    ->whereHas('user.employee', fn (Builder $query): Builder => $query->whereIn('school_id', $schoolIds))
                    ->orWhereHas('user.memberships', fn (Builder $query): Builder => $query->active()->whereIn('school_id', $schoolIds));
            })
            ->with(['user.employee.school', 'user.memberships.school'])
            ->latest()
            ->get()
            ->map(fn (FoundationSkDocument $document): array => [
                'user' => $document->user?->name ?? 'User tidak tersedia',
                'identifier' => $document->user?->employee?->employee_code
                    ?? $document->user?->employee?->nip
                    ?? $document->user?->employee?->nuptk
                    ?? '-',
                'school' => $document->user?->employee?->school?->name
                    ?? $document->user?->memberships?->first(fn ($membership): bool => in_array($membership->school_id, $schoolIds, true))?->school?->name
                    ?? '-',
                'year' => (string) $document->tahun_sk,
                'template' => $templates->get($document->sk_template_id, '-'),
                'file' => $document->original_filename,
                'uploadedAt' => $document->created_at?->format('d-m-Y H:i'),
                'downloadUrl' => route('foundation-sk.documents.download', $document),
            ]);
    }

    /**
     * @param  array<int, int>  $schoolIds
     * @return Collection<int, int>
     */
    private function accessibleOwnerIds(array $schoolIds): Collection
    {
        return User::query()
            ->where(function (Builder $query) use ($schoolIds): void {
                $query
                    ->whereHas('employee', fn (Builder $query): Builder => $query->whereIn('school_id', $schoolIds))
                    ->orWhereHas('memberships', fn (Builder $query): Builder => $query
                        ->active()
                        ->whereIn('school_id', $schoolIds));
            })
            ->pluck('id');
    }

    /**
     * @param  array<int, int>  $schoolIds
     * @return Collection<int, array<string, mixed>>
     */
    private function documents(array $schoolIds): Collection
    {
        $search = trim($this->documentSearch);
        $ownerIds = $this->accessibleOwnerIds($schoolIds);
        $templates = DecreeTemplate::query()->pluck('name', 'id');

        return Document::query()
            ->where('document_type', 'like', self::DOCUMENT_PREFIX.'%')
            ->where('owner_type', User::class)
            ->whereIn('owner_id', $ownerIds)
            ->where('status', Document::STATUS_ACTIVE)
            ->when(filled($this->selectedYear), fn (Builder $query): Builder => $query
                ->where('document_type', 'like', self::DOCUMENT_PREFIX.$this->selectedYear.'%'))
            ->when(filled($search), function (Builder $query) use ($search): void {
                $matchingOwners = User::query()
                    ->where(function (Builder $query) use ($search): void {
                        $query
                            ->whereLike('name', "%{$search}%")
                            ->orWhereLike('email', "%{$search}%")
                            ->orWhereHas('employee', fn (Builder $query): Builder => $query
                                ->whereLike('employee_code', "%{$search}%")
                                ->orWhereLike('nik', "%{$search}%")
                                ->orWhereLike('nip', "%{$search}%")
                                ->orWhereLike('nuptk', "%{$search}%")
                                ->orWhereHas('school', fn (Builder $query): Builder => $query->whereLike('name', "%{$search}%")));
                    })
                    ->select('id');
                $query->where(function (Builder $query) use ($search, $matchingOwners): void {
                    $query
                        ->whereLike('original_name', "%{$search}%")
                        ->orWhereLike('document_type', "%{$search}%")
                        ->orWhereIn('owner_id', $matchingOwners);
                });
            })
            ->with(['owner.employee.school', 'owner.memberships.school'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (Document $document) use ($schoolIds, $templates): array {
                /** @var User|null $owner */
                $owner = $document->owner;
                $school = in_array($owner?->employee?->school_id, $schoolIds, true)
                    ? $owner?->employee?->school
                    : $owner?->memberships?->first(
                        fn ($membership): bool => in_array($membership->school_id, $schoolIds, true),
                    )?->school;
                $templateId = $this->documentTemplateId($document->document_type);

                return [
                    'user' => $owner?->name ?? 'User tidak tersedia',
                    'identifier' => $owner?->employee?->employee_code
                        ?? $owner?->employee?->nip
                        ?? $owner?->employee?->nuptk
                        ?? '-',
                    'school' => $school?->name ?? '-',
                    'year' => $this->documentYear($document->document_type),
                    'template' => $templates->get($templateId, '-'),
                    'file' => $document->original_name,
                    'uploadedAt' => $document->created_at?->format('d-m-Y H:i'),
                    'downloadUrl' => route('documents.download', $document),
                ];
            });
    }

    private function documentYear(string $documentType): string
    {
        return explode(':', $documentType)[1] ?? '-';
    }

    private function documentTemplateId(string $documentType): ?int
    {
        $parts = explode(':', $documentType);

        return ($parts[2] ?? null) === 'template' && isset($parts[3])
            ? (int) $parts[3]
            : null;
    }
}
