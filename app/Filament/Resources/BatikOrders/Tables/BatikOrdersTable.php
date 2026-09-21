<?php

namespace App\Filament\Resources\BatikOrders\Tables;

use App\Models\BatikOrder;
use App\Models\BatikProduct;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BatikOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('school.name')
                    ->label('Asal Madrasah/Sekolah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ordered_at')
                    ->label('Tanggal')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('student_quantity')
                    ->label('Siswa')
                    ->state(fn (BatikOrder $record): float => $record->legacy_order_id !== null
                        ? (float) ($record->legacy_snapshot['siswa'] ?? 0)
                        : ($record->product?->audience === BatikProduct::AUDIENCE_STUDENT ? (float) $record->quantity : 0))
                    ->formatStateUsing(fn (mixed $state): string => self::number($state)),
                TextColumn::make('teacher_quantity')
                    ->label('Guru')
                    ->state(fn (BatikOrder $record): float => $record->legacy_order_id !== null
                        ? (float) ($record->legacy_snapshot['guru_2m'] ?? 0)
                            + (float) ($record->legacy_snapshot['guru_25m'] ?? 0)
                        : ($record->product?->audience === BatikProduct::AUDIENCE_TEACHER ? (float) $record->quantity : 0))
                    ->formatStateUsing(fn (mixed $state): string => self::number($state)),
                TextColumn::make('total_amount')
                    ->label('Total Pembayaran')
                    ->formatStateUsing(fn (mixed $state): string => 'Rp. '.number_format((float) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        BatikOrder::STATUS_COLLECTED => 'success',
                        BatikOrder::STATUS_READY => 'info',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => BatikOrder::statusOptions()[$state] ?? $state)
                    ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->isAdminInduk()),
                TextColumn::make('recipient_name')
                    ->label('Penerima')
                    ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->isAdminInduk())
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('legacy_notes')
                    ->label('Keterangan')
                    ->state(fn (BatikOrder $record): ?string => $record->legacy_snapshot['keterangan'] ?? null)
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('legacy_order_id')
                    ->label('ID Lama')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('accept')
                    ->label('Diterima')
                    ->button()
                    ->extraAttributes(['class' => 'batik-order-row-action'])
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->modalIcon('heroicon-o-question-mark-circle')
                    ->modalIconColor('info')
                    ->modalHeading('Konfirmasi Diterima')
                    ->modalDescription('Masukkan nama orang yang menerima pesanan batik.')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal')
                    ->schema([
                        TextInput::make('recipient_name')
                            ->label('Nama Penerima')
                            ->placeholder('Masukkan nama penerima')
                            ->required()
                            ->maxLength(150),
                    ])
                    ->fillForm(fn (BatikOrder $record): array => [
                        'recipient_name' => $record->recipient_name,
                    ])
                    ->action(function (BatikOrder $record, array $data): void {
                        $record->update([
                            'status' => BatikOrder::STATUS_COLLECTED,
                            'recipient_name' => trim($data['recipient_name']),
                        ]);
                    })
                    ->successNotificationTitle('Pesanan berhasil diterima')
                    ->visible(fn (BatikOrder $record): bool => auth()->user() instanceof User
                        && auth()->user()->isAdminInduk()
                        && $record->status !== BatikOrder::STATUS_COLLECTED),
                DeleteAction::make()
                    ->label('Delete')
                    ->button()
                    ->extraAttributes(['class' => 'batik-order-row-action'])
                    ->color('danger')
                    ->modalHeading('Hapus pesanan batik?')
                    ->modalDescription('Pesanan akan dihapus dan stok produk akan dikembalikan.')
                    ->successNotificationTitle('Pesanan berhasil dihapus')
                    ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->isAdminInduk()),
            ])
            ->recordActionsColumnLabel(fn (): ?string => auth()->user() instanceof User && auth()->user()->isAdminInduk()
                ? 'ACTION'
                : null)
            ->defaultSort('ordered_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Belum ada pemesanan batik')
            ->emptyStateDescription('Klik Pesan Sekarang pada produk untuk membuat pesanan.')
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }

    private static function number(mixed $value): string
    {
        $number = (float) $value;

        return floor($number) === $number
            ? number_format($number, 0, ',', '.')
            : number_format($number, 2, ',', '.');
    }
}
