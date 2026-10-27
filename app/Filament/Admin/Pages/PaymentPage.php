<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Resources\Billings\BillingResource;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\MidtransTransaction;
use App\Models\PaymentFeeItem;
use App\Models\PaymentInvoice;
use App\Models\School;
use App\Models\User;
use App\Services\Payments\MidtransPaymentService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

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

    #[Locked]
    public ?int $paymentEmployeeId = null;

    public string $paymentMethod = 'online';

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
                        'paymentEmployee' => $this->paymentEmployee(),
                        'unpaidInvoices' => $this->unpaidInvoices(),
                        'paymentMethod' => $this->paymentMethod,
                    ]),
            ]);
    }

    public function updatedSchoolId(): void
    {
        $this->employeeId = null;
    }

    public function searchPayments(): void
    {
        $this->paymentEmployeeId = null;
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
            'paymentEmployeeId',
        ]);
        $this->resetValidation();
    }

    public function openPayment(int $employeeId): void
    {
        abort_unless(static::canAccess(), 403);
        $this->resetValidation();
        $this->paymentMethod = 'online';
        $this->paymentEmployeeId = $employeeId;
        abort_unless($this->paymentEmployee() !== null, 403);
        $this->dispatch('open-modal', id: 'payment-checkout');
    }

    public function closePayment(): void
    {
        $this->dispatch('close-modal', id: 'payment-checkout');
        $this->paymentEmployeeId = null;
    }

    public function payOnline(int $invoiceId, MidtransPaymentService $service): void
    {
        abort_unless(static::canAccess(), 403);
        $this->validate(['paymentMethod' => ['required', Rule::in(['online'])]]);
        $invoice = $this->unpaidInvoices()->firstWhere('id', $invoiceId);
        abort_unless($invoice instanceof PaymentInvoice, 403);
        $checkout = $service->checkout($invoice);
        $this->dispatch('close-modal', id: 'payment-checkout');
        $this->dispatch('midtrans-checkout', ...$checkout);
    }

    public function refreshOnlinePayment(string $orderId, MidtransPaymentService $service): void
    {
        abort_unless(static::canAccess(), 403);
        $transaction = MidtransTransaction::query()->where('order_id', $orderId)
            ->whereIn('source', ['midtrans_sandbox', 'midtrans_production'])->firstOrFail();
        Gate::authorize('view', $transaction->invoice);
        try {
            $transaction = $service->synchronize($orderId);
            Notification::make()->title($transaction->transaction_status === 'paid'
                ? 'Pembayaran terverifikasi. Tagihan sudah lunas.'
                : 'Pembayaran belum terkonfirmasi lunas. Status akan diperbarui otomatis.')->info()->send();
        } catch (\Throwable) {
            Notification::make()->title('Status belum dapat diperiksa. Silakan cek kembali nanti.')->warning()->send();
        }
    }

    private function paymentEmployee(): ?Employee
    {
        if ($this->paymentEmployeeId === null || ! static::canAccess()) {
            return null;
        }

        return Employee::query()
            ->where('foundation_id', Foundation::application()->getKey())
            ->whereIn('school_id', array_keys($this->schoolOptions()))
            ->find($this->paymentEmployeeId);
    }

    private function unpaidInvoices(): Collection
    {
        $employee = $this->paymentEmployee();

        if ($employee === null) {
            return collect();
        }

        return BillingResource::getEloquentQuery()
            ->where('foundation_id', $employee->foundation_id)
            ->where('status', PaymentInvoice::STATUS_UNPAID)
            ->where(function (Builder $query) use ($employee): void {
                $query->where('employee_id', $employee->getKey());

                if ($employee->user_id !== null) {
                    $query->orWhere('user_id', $employee->user_id);
                }
            })
            ->latest()
            ->get();
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
            ->whereIn('school_id', array_keys($this->schoolOptions()))
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
