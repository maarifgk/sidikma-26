<?php

namespace App\Filament\Resources\TreasuryTransactions\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use App\Models\TreasuryTransaction;
use Filament\Actions\Action;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListTreasuryTransactions extends ListRecords
{
    protected static string $resource = TreasuryTransactionResource::class;

    protected static ?string $title = 'DATA BENDAHARA';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.resources.treasury-transactions.summary')
                    ->viewData(fn (): array => $this->summaryData()),
                EmbeddedTable::make(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('inputIncome')
                ->label('Input Pemasukan')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(fn (): string => TreasuryTransactionResource::getUrl(
                    'create',
                    ['type' => TreasuryTransaction::TYPE_INCOME],
                    panel: 'admin',
                    isAbsolute: false,
                )),
            Action::make('inputExpense')
                ->label('Input Pengeluaran')
                ->icon('heroicon-o-plus')
                ->color('danger')
                ->url(fn (): string => TreasuryTransactionResource::getUrl(
                    'create',
                    ['type' => TreasuryTransaction::TYPE_EXPENSE],
                    panel: 'admin',
                    isAbsolute: false,
                )),
        ];
    }

    /** @return array<string, mixed> */
    private function summaryData(): array
    {
        $query = TreasuryTransactionResource::getEloquentQuery();
        $totalIncome = (float) (clone $query)
            ->where('transaction_type', TreasuryTransaction::TYPE_INCOME)
            ->sum('amount');
        $totalExpense = (float) (clone $query)
            ->where('transaction_type', TreasuryTransaction::TYPE_EXPENSE)
            ->sum('amount');

        return [
            'balance' => $totalIncome - $totalExpense,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'incomeRecap' => $this->categoryRecap(TreasuryTransaction::TYPE_INCOME),
            'expenseRecap' => $this->categoryRecap(TreasuryTransaction::TYPE_EXPENSE),
        ];
    }

    /** @return array<int, array{label: string, total: float}> */
    private function categoryRecap(string $type): array
    {
        $totals = TreasuryTransactionResource::getEloquentQuery()
            ->where('transaction_type', $type)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return collect(TreasuryTransaction::categoryOptions($type))
            ->map(fn (string $label, string $category): array => [
                'label' => $label,
                'total' => (float) ($totals[$category] ?? 0),
            ])
            ->filter(fn (array $item): bool => $item['total'] > 0)
            ->values()
            ->all();
    }
}
