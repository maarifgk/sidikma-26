<?php

namespace App\Filament\Resources\DecreeCorrectionRequests\Tables;

use App\Models\DecreeCorrectionRequest;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DecreeCorrectionRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('request_number')->label('Nomor')->searchable()->sortable(), TextColumn::make('request_date')->label('Tanggal Pengajuan')->date('d M Y')->sortable(),
            TextColumn::make('school.name')->label('Asal Sekolah/Madrasah')->searchable()->sortable(), TextColumn::make('decree_number')->label('Nomor SK')->searchable(), TextColumn::make('decree_date')->label('Tanggal SK')->date('d M Y'),
            TextColumn::make('subject_name')->label('Nama')->searchable(), TextColumn::make('correction_part')->label('Bagian Perbaikan')->formatStateUsing(fn ($state) => DecreeCorrectionRequest::correctionPartOptions()[$state] ?? $state),
            TextColumn::make('old_data')->label('Data Lama')->limit(35)->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false), TextColumn::make('new_data')->label('Data Baru')->limit(35)->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false),
            TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => DecreeCorrectionRequest::statusOptions()[$state] ?? $state)->color(fn ($state) => DecreeCorrectionRequest::statusColor($state)), TextColumn::make('updated_at')->label('Terakhir Diperbarui')->dateTime('d M Y H:i')->sortable(),
        ])->filters([
            SelectFilter::make('status')->options(DecreeCorrectionRequest::statusOptions()), SelectFilter::make('school_id')->label('Asal Sekolah/Madrasah')->relationship('school', 'name')->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false),
            SelectFilter::make('correction_part')->label('Bagian Perbaikan')->options(DecreeCorrectionRequest::correctionPartOptions()),
            Filter::make('periode')->schema([Select::make('month')->label('Bulan')->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => now()->month($m)->translatedFormat('F')])->all()), Select::make('year')->label('Tahun')->options(collect(range((int) now()->year, (int) now()->year - 5))->mapWithKeys(fn ($y) => [$y => $y])->all())])->query(fn (Builder $query, array $data): Builder => $query->when($data['month'] ?? null, fn ($q, $v) => $q->whereMonth('request_date', $v))->when($data['year'] ?? null, fn ($q, $v) => $q->whereYear('request_date', $v))),
        ])->recordActions([
            Action::make('processCorrection')
                ->label('Proses')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false)
                ->modalHeading('Proses Pengajuan Perbaikan SK')
                ->modalDescription('Periksa pengajuan, ubah status proses, dan unggah SK hasil perbaikan bila proses selesai.')
                ->modalSubmitActionLabel('Simpan')
                ->fillForm(fn (DecreeCorrectionRequest $record): array => [
                    'status' => $record->status === DecreeCorrectionRequest::STATUS_DRAFT
                        ? DecreeCorrectionRequest::STATUS_SUBMITTED
                        : $record->status,
                    'admin_notes' => $record->admin_notes,
                    'corrected_decree_path' => $record->corrected_decree_path,
                ])
                ->schema([
                    Select::make('status')
                        ->label('STATUS PENGAJUAN')
                        ->options([
                            DecreeCorrectionRequest::STATUS_SUBMITTED => 'Diajukan',
                            DecreeCorrectionRequest::STATUS_PROCESSING => 'Sedang Diproses',
                            DecreeCorrectionRequest::STATUS_REVISION => 'Minta Revisi',
                            DecreeCorrectionRequest::STATUS_APPROVED => 'Proses Selesai',
                            DecreeCorrectionRequest::STATUS_REJECTED => 'Ditolak',
                        ])
                        ->live()
                        ->required()
                        ->native(false),
                    Textarea::make('admin_notes')
                        ->label('CATATAN ADMIN INDUK')
                        ->rows(4)
                        ->required(fn (Get $get): bool => in_array($get('status'), [
                            DecreeCorrectionRequest::STATUS_REVISION,
                            DecreeCorrectionRequest::STATUS_REJECTED,
                        ], true)),
                    FileUpload::make('corrected_decree_path')
                        ->label('UPLOAD SK HASIL PERBAIKAN (PDF)')
                        ->disk('documents')
                        ->directory('decree-corrections/results')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(1024000)
                        ->visible(fn (Get $get): bool => $get('status') === DecreeCorrectionRequest::STATUS_APPROVED)
                        ->required(fn (Get $get): bool => $get('status') === DecreeCorrectionRequest::STATUS_APPROVED),
                ])
                ->action(function (DecreeCorrectionRequest $record, array $data): void {
                    $status = $data['status'];
                    $updates = [
                        'status' => $status,
                        'admin_notes' => $data['admin_notes'] ?? null,
                        'processed_by' => auth()->id(),
                        'processed_at' => now(),
                    ];

                    if ($status === DecreeCorrectionRequest::STATUS_APPROVED) {
                        $updates['corrected_decree_path'] = $data['corrected_decree_path'];
                        $updates['approved_by'] = auth()->id();
                        $updates['approved_at'] = now();
                    }

                    $record->update($updates);

                    Notification::make()
                        ->title('Status Perbaikan SK: '.(DecreeCorrectionRequest::statusOptions()[$status] ?? $status))
                        ->sendToDatabase($record->submitter);

                    Notification::make()->title('Pengajuan berhasil diproses')->success()->send();
                }),
            ViewAction::make()->label('Detail'),
            EditAction::make()
                ->label('Edit Data')
                ->visible(fn (DecreeCorrectionRequest $record): bool => auth()->user()?->isAdminInduk() ?? false),
        ])->defaultSort('request_date', 'desc')->emptyStateHeading('Belum ada pengajuan Perbaikan SK');
    }
}
