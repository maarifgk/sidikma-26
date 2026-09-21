<?php

namespace App\Filament\Resources\Billings\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\Billings\BillingResource;
use App\Models\PaymentInvoice;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListBillings extends ListRecords
{
    protected static string $resource = BillingResource::class;

    protected static ?string $title = 'Pembayaran';

    public function exportExcel(): StreamedResponse
    {
        $records = BillingResource::getEloquentQuery()
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($records): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'No',
                'User',
                'Asal Madrasah',
                'Tahun Ajaran',
                'Jenis Pembayaran',
                'Nilai',
                'Status',
                'Created',
            ]);

            foreach ($records as $index => $invoice) {
                fputcsv($output, [
                    $index + 1,
                    $invoice->user?->name ?? $invoice->employee?->name ?? '-',
                    $invoice->school?->name ?? '-',
                    $invoice->academic_year,
                    $invoice->description,
                    (float) $invoice->amount,
                    $invoice->status === PaymentInvoice::STATUS_PAID ? 'Lunas' : 'Belum Lunas',
                    $invoice->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($output);
        }, 'pembayaran-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn (): StreamedResponse => $this->exportExcel()),
            CreateAction::make()
                ->label('Add')
                ->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false),
        ];
    }
}
