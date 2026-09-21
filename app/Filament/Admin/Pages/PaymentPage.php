<?php

namespace App\Filament\Admin\Pages;

use App\Models\Employee;
use App\Models\Foundation;
use App\Models\PaymentFeeItem;
use App\Models\School;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class PaymentPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'pembayaran';

    protected static ?string $title = 'Pembayaran';

    public ?int $schoolId = null;

    public ?int $employeeId = null;

    public ?int $searchedSchoolId = null;

    public ?int $searchedEmployeeId = null;

    public bool $hasSearched = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('foundation.view') ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Cari data pembayaran, lihat informasi iuran, dan proses pembayaran dengan tampilan yang lebih rapi.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.pages.payment-page')
                    ->viewData(fn (): array => [
                        'schools' => $this->schoolOptions(),
                        'employees' => $this->employeeOptions(),
                        'feeSections' => $this->feeSections(),
                        'results' => $this->searchResults(),
                        'hasSearched' => $this->hasSearched,
                    ]),
            ]);
    }

    public function updatedSchoolId(): void
    {
        $this->employeeId = null;
    }

    public function searchPayments(): void
    {
        $foundationId = Foundation::application()->getKey();

        $this->validate([
            'schoolId' => [
                'nullable',
                Rule::exists('schools', 'id')->where(
                    fn ($query) => $query->where('foundation_id', $foundationId)->whereNull('deleted_at'),
                ),
            ],
            'employeeId' => [
                'nullable',
                Rule::exists('employees', 'id')->where(
                    fn ($query) => $query->where('foundation_id', $foundationId)->whereNull('deleted_at'),
                ),
            ],
        ]);

        $this->searchedSchoolId = $this->schoolId;
        $this->searchedEmployeeId = $this->employeeId;
        $this->hasSearched = true;
    }

    public function refreshPayments(): void
    {
        $this->reset([
            'schoolId',
            'employeeId',
            'searchedSchoolId',
            'searchedEmployeeId',
            'hasSearched',
        ]);
        $this->resetValidation();
    }

    /** @return array<int, string> */
    private function schoolOptions(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        return School::query()
            ->accessibleTo($user)
            ->where('foundation_id', Foundation::application()->getKey())
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<int, string> */
    private function employeeOptions(): array
    {
        return Employee::query()
            ->where('foundation_id', Foundation::application()->getKey())
            ->where('is_active', true)
            ->when($this->schoolId, fn (Builder $query, int $schoolId): Builder => $query->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'employee_code', 'name'])
            ->mapWithKeys(fn (Employee $employee): array => [
                $employee->getKey() => trim(($employee->employee_code ?: '-').' / '.$employee->name),
            ])
            ->all();
    }

    /** @return Collection<int, Employee> */
    private function searchResults(): Collection
    {
        if (! $this->hasSearched) {
            return collect();
        }

        return Employee::query()
            ->with('school')
            ->where('foundation_id', Foundation::application()->getKey())
            ->when(
                $this->searchedSchoolId,
                fn (Builder $query, int $schoolId): Builder => $query->where('school_id', $schoolId),
            )
            ->when(
                $this->searchedEmployeeId,
                fn (Builder $query, int $employeeId): Builder => $query->whereKey($employeeId),
            )
            ->orderBy('name')
            ->limit(100)
            ->get();
    }

    /** @return array<int, array{heading: string, items: array<int, array{label: string, amount: int}>}> */
    private function feeSections(): array
    {
        $items = PaymentFeeItem::ensureDefaults(Foundation::application());

        return collect(PaymentFeeItem::sectionOptions())
            ->map(fn (string $heading, string $section): array => [
                'heading' => $heading,
                'items' => $items
                    ->where('section', $section)
                    ->map(fn (PaymentFeeItem $item): array => [
                        'label' => $item->label,
                        'amount' => (float) $item->amount,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
