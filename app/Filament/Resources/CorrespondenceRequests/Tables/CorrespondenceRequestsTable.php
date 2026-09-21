<?php

namespace App\Filament\Resources\CorrespondenceRequests\Tables;

use App\Models\CorrespondenceRequest;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class CorrespondenceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('school_name')
                    ->label('Asal Madrasah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type_name')
                    ->label('Jenis')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('request_file_action')
                    ->label('File')
                    ->state(fn (CorrespondenceRequest $record): string => Storage::disk(CorrespondenceRequest::DISK)->exists($record->request_file_path) ? 'Lihat' : 'Belum tersedia')
                    ->badge()
                    ->color(fn (CorrespondenceRequest $record): string => Storage::disk(CorrespondenceRequest::DISK)->exists($record->request_file_path) ? 'primary' : 'gray')
                    ->alignCenter()
                    ->url(fn (CorrespondenceRequest $record): ?string => Storage::disk(CorrespondenceRequest::DISK)->exists($record->request_file_path)
                        ? Storage::disk(CorrespondenceRequest::DISK)->temporaryUrl($record->request_file_path, now()->addMinutes(5)) : null)
                    ->openUrlInNewTab(),
                TextColumn::make('response_file_action')
                    ->label('File Balasan Surat')
                    ->state(fn (CorrespondenceRequest $record): string => filled($record->response_file_path) && Storage::disk(CorrespondenceRequest::DISK)->exists($record->response_file_path) ? 'Unduh' : '-')
                    ->badge(fn (CorrespondenceRequest $record): bool => filled($record->response_file_path) && Storage::disk(CorrespondenceRequest::DISK)->exists($record->response_file_path))
                    ->color('primary')
                    ->alignCenter()
                    ->url(fn (CorrespondenceRequest $record): ?string => filled($record->response_file_path) && Storage::disk(CorrespondenceRequest::DISK)->exists($record->response_file_path)
                        ? Storage::disk(CorrespondenceRequest::DISK)->temporaryUrl($record->response_file_path, now()->addMinutes(5))
                        : null)
                    ->openUrlInNewTab(),
                TextColumn::make('process_status')
                    ->label('Ket. Proses')
                    ->formatStateUsing(
                        fn (string $state): string => CorrespondenceRequest::statusOptions()[$state] ?? $state,
                    )
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->searchable()
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('process_status')
                    ->label('Status Proses')
                    ->options(CorrespondenceRequest::statusOptions()),
            ])
            ->recordActions([
                Action::make('responseFile')
                    ->label('File Balasan Surat')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false)
                    ->fillForm(fn (CorrespondenceRequest $record): array => [
                        'response_file_path' => $record->response_file_path,
                    ])
                    ->schema([
                        FileUpload::make('response_file_path')
                            ->label('FILE BALASAN SURAT')
                            ->disk(CorrespondenceRequest::DISK)
                            ->visibility('private')
                            ->directory('response-files')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(1024000)
                            ->required()
                            ->downloadable()
                            ->helperText('Format PDF, maksimal 1000 MB.'),
                    ])
                    ->action(function (CorrespondenceRequest $record, array $data): void {
                        $record->update([
                            'response_file_path' => $data['response_file_path'],
                            'process_status' => CorrespondenceRequest::STATUS_COMPLETED,
                            'processed_by' => auth()->id(),
                            'processed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('File balasan surat berhasil disimpan')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->label('Delete')
                    ->color('danger'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada pengajuan persuratan')
            ->emptyStateDescription('Klik Ajukan untuk menambahkan pengajuan persuratan.')
            ->emptyStateIcon('heroicon-o-envelope');
    }
}
