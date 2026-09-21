<?php

namespace App\Filament\Resources\Billings\Pages;

use App\Filament\Resources\Billings\BillingResource;
use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\PaymentInvoice;
use App\Models\PaymentType;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CreateBilling extends Page
{
    protected static string $resource = BillingResource::class;

    protected static ?string $title = 'Pembayaran';

    public string $academicYear = '';

    public string $schoolId = '';

    public string $paymentType = '';

    public bool $hasSearched = false;

    public int $step = 1;

    /** @var array<int, int|string> */
    public array $selectedUserIds = [];

    public int|float|string $amount = '';

    public string $notes = '';

    /** @var array<int, array{id: int, label: string}> */
    public array $availableTargets = [];

    public function mount(): void
    {
        abort_unless(BillingResource::canCreate(), 403);

        $this->academicYear = StudentEnrollment::currentAcademicYear();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.admin.resources.billings.create')
                ->viewData(fn (): array => [
                    'academicYearOptions' => $this->academicYearOptions(),
                    'schoolOptions' => $this->schoolOptions(),
                    'paymentTypeOptions' => $this->paymentTypeOptions(),
                    'availableTargets' => $this->availableTargets,
                ]),
        ]);
    }

    public function updatedPaymentType(): void
    {
        $this->resetSearchResults();
    }

    public function updatedAcademicYear(): void
    {
        $this->resetSearchResults();
    }

    public function updatedSchoolId(): void
    {
        $this->resetSearchResults();
    }

    public function searchTargets(): void
    {
        $this->validateSearch();
        $this->loadAvailableTargets();
        $this->hasSearched = true;
        $this->step = 2;
        $this->selectedUserIds = [];

        if ($this->availableTargets === []) {
            Notification::make()
                ->title('Tidak ada akun yang dapat ditagih')
                ->body('Semua akun pada madrasah ini sudah memiliki pembayaran yang dipilih.')
                ->warning()
                ->send();
        }
    }

    public function backToSearch(): void
    {
        $this->step = 1;
        $this->hasSearched = false;
        $this->availableTargets = [];
        $this->selectedUserIds = [];
        $this->resetValidation();
    }

    public function addBills(): void
    {
        abort_unless(BillingResource::canCreate(), 403);

        $this->validateSearch();
        $this->loadAvailableTargets();
        $availableUserIds = collect($this->availableTargets)->pluck('id')->map(fn (mixed $id): int => (int) $id);

        $validated = $this->validate([
            'selectedUserIds' => ['required', 'array', 'min:1'],
            'selectedUserIds.*' => ['integer', Rule::in($availableUserIds->all())],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'selectedUserIds' => 'guru/pegawai',
            'amount' => 'nominal',
            'notes' => 'keterangan',
        ]);

        $school = School::query()->findOrFail($this->schoolId);
        $createdInvoices = collect();

        DB::transaction(function () use ($availableUserIds, $createdInvoices, $school, $validated): void {
            foreach (collect($validated['selectedUserIds'])->map(fn (mixed $id): int => (int) $id)->unique() as $userId) {
                if (! $availableUserIds->contains($userId)) {
                    continue;
                }

                $target = User::query()->with('employee')->findOrFail($userId);
                $invoice = PaymentInvoice::query()->firstOrCreate([
                    'user_id' => $target->getKey(),
                    'school_id' => $school->getKey(),
                    'academic_year' => $this->academicYear,
                    'description' => $this->paymentType,
                ], [
                    'foundation_id' => $school->foundation_id,
                    'employee_id' => $target->employee?->school_id === $school->getKey()
                        ? $target->employee->getKey()
                        : null,
                    'invoice_number' => PaymentInvoice::generateInvoiceNumber(),
                    'amount' => $validated['amount'],
                    'status' => PaymentInvoice::STATUS_UNPAID,
                    'notes' => $validated['notes'] ?: null,
                ]);

                if ($invoice->wasRecentlyCreated) {
                    $createdInvoices->push($invoice);
                }
            }
        });

        foreach ($createdInvoices as $invoice) {
            Notification::make()
                ->title('Pembayaran baru')
                ->body($invoice->description.' — Rp '.number_format((float) $invoice->amount, 0, ',', '.'))
                ->icon('heroicon-o-document-minus')
                ->warning()
                ->sendToDatabase($invoice->user);
        }

        if ($createdInvoices->isEmpty()) {
            $this->loadAvailableTargets();
            $this->selectedUserIds = [];

            Notification::make()
                ->title('Pembayaran tidak ditambahkan')
                ->body('Akun yang dipilih sudah mempunyai pembayaran yang sama.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title($createdInvoices->count().' pembayaran berhasil ditambahkan')
            ->success()
            ->send();

        $this->redirect(BillingResource::getUrl(panel: 'admin', isAbsolute: false), navigate: true);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    private function validateSearch(): void
    {
        $this->validate([
            'academicYear' => ['required', Rule::in(array_keys($this->academicYearOptions()))],
            'schoolId' => ['required', 'integer', Rule::exists('schools', 'id')->where('is_active', true)],
            'paymentType' => ['required', Rule::in(array_keys($this->paymentTypeOptions()))],
        ], [], [
            'academicYear' => 'tahun ajaran',
            'schoolId' => 'asal madrasah',
            'paymentType' => 'jenis pembayaran',
        ]);
    }

    private function loadAvailableTargets(): void
    {
        $existingInvoices = PaymentInvoice::query()
            ->where('school_id', $this->schoolId)
            ->where('academic_year', $this->academicYear)
            ->where('description', $this->paymentType);
        $existingUserIds = (clone $existingInvoices)
            ->whereNotNull('user_id')
            ->pluck('user_id');
        $legacyEmployeeIds = (clone $existingInvoices)
            ->whereNull('user_id')
            ->whereNotNull('employee_id')
            ->pluck('employee_id');

        if ($legacyEmployeeIds->isNotEmpty()) {
            $existingUserIds = $existingUserIds->merge(
                Employee::query()
                    ->whereKey($legacyEmployeeIds)
                    ->whereNotNull('user_id')
                    ->pluck('user_id'),
            );
        }

        $this->availableTargets = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('name', [
                User::ROLE_ADMIN_SEKOLAH_MADRASAH,
                User::ROLE_GURU_PEGAWAI,
            ]))
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('memberships', fn (Builder $membershipQuery): Builder => $membershipQuery
                        ->active()
                        ->where('school_id', $this->schoolId))
                    ->orWhereHas('employee', fn (Builder $employeeQuery): Builder => $employeeQuery
                        ->where('school_id', $this->schoolId)
                        ->where('is_active', true));
            })
            ->whereNotIn('id', $existingUserIds->unique())
            ->with('employee')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->getKey(),
                'label' => collect([$user->employee?->employee_code, $user->name])
                    ->filter()
                    ->join(' / '),
            ])
            ->all();
    }

    /** @return array<string, string> */
    private function academicYearOptions(): array
    {
        return AcademicYear::activeOptions() + StudentEnrollment::academicYearOptions();
    }

    /** @return array<int, string> */
    private function schoolOptions(): array
    {
        return School::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<string, string> */
    private function paymentTypeOptions(): array
    {
        return PaymentType::activeOptions(Foundation::application());
    }

    private function resetSearchResults(): void
    {
        $this->hasSearched = false;
        $this->step = 1;
        $this->availableTargets = [];
        $this->selectedUserIds = [];
    }
}
