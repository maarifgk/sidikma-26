<?php

namespace App\Filament\Resources\InvoiceSchools\Pages;

use App\Filament\Pages\ViewRecord;
use App\Filament\Resources\InvoiceSchools\InvoiceSchoolResource;
use App\Models\PaymentInvoice;
use App\Models\School;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ViewInvoiceSchool extends ViewRecord
{
    protected static string $resource = InvoiceSchoolResource::class;

    #[Url(as: 'year')]
    public string $academicYear = 'all';

    #[Url]
    public string $mode = 'detail';

    public function getTitle(): string
    {
        return $this->mode === 'class' ? 'Pembayaran Kelas' : 'Detail Invoice';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.admin.resources.invoice-schools.detail')
                ->viewData(fn (): array => $this->viewData()),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    private function viewData(): array
    {
        /** @var School $school */
        $school = $this->getRecord();
        $query = $school->paymentInvoices()
            ->with('employee')
            ->when(
                $this->academicYear !== 'all',
                fn (Builder $query): Builder => $query->where('academic_year', $this->academicYear),
            );

        return [
            'school' => $school,
            'academicYear' => $this->academicYear,
            'mode' => $this->mode,
            'invoices' => (clone $query)->latest()->get(),
            'paidTotal' => (float) (clone $query)
                ->where('status', PaymentInvoice::STATUS_PAID)
                ->sum('amount'),
            'unpaidTotal' => (float) (clone $query)
                ->where('status', PaymentInvoice::STATUS_UNPAID)
                ->sum('amount'),
        ];
    }
}
