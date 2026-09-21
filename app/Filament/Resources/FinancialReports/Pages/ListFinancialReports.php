<?php

namespace App\Filament\Resources\FinancialReports\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\FinancialReports\FinancialReportResource;
use App\Models\PaymentInvoice;
use App\Models\School;
use App\Models\StudentEnrollment;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListFinancialReports extends ListRecords
{
    protected static string $resource = FinancialReportResource::class;

    protected static ?string $title = 'Laporan Keuangan';

    #[Url(as: 'year')]
    public string $academicYear = 'all';

    #[Url(as: 'school')]
    public string $schoolId = 'all';

    #[Url(as: 'payment')]
    public string $paymentType = 'all';

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.admin.resources.financial-reports.filters')
                ->viewData(fn (): array => [
                    'academicYearOptions' => $this->academicYearOptions(),
                    'schoolOptions' => $this->schoolOptions(),
                    'paymentTypeOptions' => $this->paymentTypeOptions(),
                ]),
            EmbeddedTable::make(),
        ]);
    }

    public function updatedAcademicYear(): void
    {
        $this->resetTable();
    }

    public function updatedSchoolId(): void
    {
        $this->resetTable();
    }

    public function updatedPaymentType(): void
    {
        $this->resetTable();
    }

    public function exportExcel(): StreamedResponse
    {
        $records = $this->filteredQuery()->get();
        $fileName = 'laporan-keuangan-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($records): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'No',
                'Nama',
                'Tahun',
                'Jenis Pembayaran',
                'Nilai',
                'Metode Pembayaran',
                'Status',
                'Keterangan',
                'Created',
            ]);

            foreach ($records as $index => $invoice) {
                fputcsv($output, [
                    $index + 1,
                    $invoice->employee?->name ?? $invoice->school?->name ?? '-',
                    $invoice->academic_year,
                    $invoice->description,
                    (float) $invoice->amount,
                    $invoice->paymentMethodLabel(),
                    $invoice->status === PaymentInvoice::STATUS_PAID ? 'Lunas' : 'Belum Lunas',
                    $invoice->notes ?? '-',
                    $invoice->created_at?->format('d-m-Y H:i'),
                ]);
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function getTableQuery(): Builder
    {
        return $this->filteredQuery();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    private function filteredQuery(): Builder
    {
        return FinancialReportResource::getEloquentQuery()
            ->when(
                $this->academicYear !== 'all',
                fn (Builder $query): Builder => $query->where('academic_year', $this->academicYear),
            )
            ->when(
                $this->schoolId !== 'all',
                fn (Builder $query): Builder => $query->where('school_id', $this->schoolId),
            )
            ->when(
                $this->paymentType !== 'all',
                fn (Builder $query): Builder => $query->where('description', $this->paymentType),
            );
    }

    /** @return array<string, string> */
    private function academicYearOptions(): array
    {
        $years = collect(StudentEnrollment::academicYearOptions());

        FinancialReportResource::getEloquentQuery()
            ->distinct()
            ->pluck('academic_year')
            ->each(fn (string $year) => $years->put($year, $year));

        return ['all' => '--Pilih--'] + $years->sortKeysDesc()->all();
    }

    /** @return array<string, string> */
    private function schoolOptions(): array
    {
        $schoolIds = FinancialReportResource::getEloquentQuery()
            ->distinct()
            ->pluck('school_id');

        return ['all' => '--Pilih--'] + School::query()
            ->whereKey($schoolIds)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn (string $name, int $id): array => [(string) $id => $name])
            ->all();
    }

    /** @return array<string, string> */
    private function paymentTypeOptions(): array
    {
        return ['all' => '--Pilih--'] + FinancialReportResource::getEloquentQuery()
            ->whereNotNull('description')
            ->distinct()
            ->orderBy('description')
            ->pluck('description', 'description')
            ->all();
    }
}
