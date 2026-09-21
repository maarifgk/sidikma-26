<?php

namespace App\Filament\Resources\InvoiceSchools\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\InvoiceSchools\InvoiceSchoolResource;
use App\Models\Employee;
use App\Models\PaymentInvoice;
use App\Models\StudentEnrollment;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListInvoiceSchools extends ListRecords
{
    protected static string $resource = InvoiceSchoolResource::class;

    protected static ?string $title = "Invoice Pembayaran LP. Ma'arif NU PCNU Gunungkidul";

    #[Url(as: 'year')]
    public string $academicYear = 'all';

    public string $yearFilter = 'all';

    public function mount(): void
    {
        parent::mount();

        $this->yearFilter = $this->validAcademicYear($this->academicYear)
            ? $this->academicYear
            : 'all';
        $this->academicYear = $this->yearFilter;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.resources.invoice-schools.summary')
                    ->viewData(fn (): array => [
                        'academicYear' => $this->academicYear,
                        'academicYearOptions' => $this->academicYearOptions(),
                        ...$this->summaryData(),
                    ]),
                EmbeddedTable::make(),
            ]);
    }

    public function applyAcademicYear(): void
    {
        $this->academicYear = $this->validAcademicYear($this->yearFilter)
            ? $this->yearFilter
            : 'all';

        $this->resetTable();
    }

    protected function getTableQuery(): Builder
    {
        return InvoiceSchoolResource::getEloquentQuery();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /** @return array<string, string> */
    private function academicYearOptions(): array
    {
        $years = collect(StudentEnrollment::academicYearOptions());

        PaymentInvoice::query()
            ->distinct()
            ->pluck('academic_year')
            ->each(fn (string $year) => $years->put($year, $year));

        return ['all' => '-- Semua Tahun Ajaran --'] + $years->sortKeysDesc()->all();
    }

    private function validAcademicYear(string $year): bool
    {
        return array_key_exists($year, $this->academicYearOptions());
    }

    /** @return array<string, int> */
    private function summaryData(): array
    {
        $schoolQuery = InvoiceSchoolResource::getEloquentQuery();
        $schoolIds = (clone $schoolQuery)->pluck('id');
        $invoiceQuery = PaymentInvoice::query()
            ->whereIn('school_id', $schoolIds)
            ->when(
                $this->academicYear !== 'all',
                fn (Builder $query): Builder => $query->where('academic_year', $this->academicYear),
            );

        return [
            'paidInvoices' => (clone $invoiceQuery)->where('status', PaymentInvoice::STATUS_PAID)->count(),
            'unpaidInvoices' => (clone $invoiceQuery)->where('status', PaymentInvoice::STATUS_UNPAID)->count(),
            'totalEmployees' => Employee::query()
                ->whereIn('school_id', $schoolIds)
                ->where('is_active', true)
                ->count(),
            'totalSchools' => (clone $schoolQuery)->count(),
        ];
    }
}
