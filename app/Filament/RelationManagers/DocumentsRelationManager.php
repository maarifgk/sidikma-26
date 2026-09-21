<?php

namespace App\Filament\RelationManagers;

use App\Models\ApprovalRequest;
use App\Models\Document;
use App\Models\User;
use App\Services\ApplicationNotificationService;
use App\Services\ApprovalWorkflow;
use App\Services\DocumentStorage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Dokumen';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('document_type')
                    ->label('Jenis Dokumen')
                    ->options(DocumentStorage::documentTypeOptions())
                    ->required()
                    ->native(false),
                FileUpload::make('path')
                    ->label('File Dokumen')
                    ->disk(Document::PRIVATE_DISK)
                    ->directory(fn (): string => DocumentStorage::directoryFor($this->getOwnerRecord()))
                    ->visibility('private')
                    ->acceptedFileTypes(DocumentStorage::ALLOWED_MIME_TYPES)
                    ->maxSize(DocumentStorage::MAX_SIZE_KB)
                    ->storeFileNamesIn('original_name')
                    ->downloadable(false)
                    ->openable(false)
                    ->previewable(false)
                    ->required()
                    ->helperText('PDF, JPG, PNG, DOC, DOCX, XLS, atau XLSX. Maksimal 1000 MB.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withoutGlobalScopes([SoftDeletingScope::class]))
            ->columns([
                TextColumn::make('original_name')
                    ->label('Nama File')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('document_type')
                    ->label('Jenis Dokumen')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => DocumentStorage::documentTypeOptions()[$state] ?? $state,
                    )
                    ->sortable(),
                TextColumn::make('size')
                    ->label('Ukuran')
                    ->formatStateUsing(fn (int $state): string => self::formatBytes($state))
                    ->sortable(),
                TextColumn::make('uploadedBy.name')
                    ->label('Diunggah Oleh')
                    ->placeholder('Pengguna dihapus')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Document::STATUS_ACTIVE => 'Aktif',
                        Document::STATUS_ARCHIVED => 'Diarsipkan',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tanggal Unggah')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('mime_type')
                    ->label('MIME Type')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('checksum')
                    ->label('Checksum SHA-256')
                    ->limit(16)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->label('Jenis Dokumen')
                    ->options(DocumentStorage::documentTypeOptions()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Document::STATUS_ACTIVE => 'Aktif',
                        Document::STATUS_ARCHIVED => 'Diarsipkan',
                    ]),
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Unggah Dokumen')
                    ->createAnother(false)
                    ->using(function (array $data): Document {
                        $path = is_string($data['path'] ?? null) ? $data['path'] : '';
                        $owner = $this->getOwnerRecord();
                        $user = auth()->user();

                        abort_unless($user instanceof User, 403);

                        $storage = app(DocumentStorage::class);

                        try {
                            $attributes = $storage->prepareCreateData($data, $owner, $user);

                            return DB::transaction(
                                fn (): Model => $this->getRelationship()->create($attributes),
                            );
                        } catch (Throwable $exception) {
                            if ($path !== '') {
                                $storage->deleteUnreferenced($path, $owner);
                            }

                            throw $exception;
                        }
                    }),
                Action::make('remindIncompleteDocuments')
                    ->label('Kirim Pengingat Kelengkapan')
                    ->icon('heroicon-o-document-minus')
                    ->color('warning')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Dokumen yang Belum Lengkap')
                            ->placeholder('Contoh: Ijazah dan SK pengangkatan belum diunggah.')
                            ->rows(4)
                            ->required()
                            ->maxLength(5000),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn (): bool => Gate::allows('create', Document::class))
                    ->action(function (array $data, Action $action): void {
                        Gate::authorize('create', Document::class);

                        $user = auth()->user();
                        abort_unless($user instanceof User, 403);

                        $recipientCount = app(ApplicationNotificationService::class)
                            ->notifyIncompleteDocuments(
                                $this->getOwnerRecord(),
                                $user,
                                $data['notes'],
                            );

                        $action
                            ->successNotificationTitle("Pengingat dikirim ke {$recipientCount} pengguna")
                            ->success();
                    }),
            ])
            ->recordActions([
                Action::make('submitApproval')
                    ->label('Ajukan Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan Pengajuan')
                            ->rows(4)
                            ->maxLength(5000),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn (Document $record): bool => ! $record->trashed()
                        && Gate::allows('create', ApprovalRequest::class)
                        && ! $record->approvalRequests()
                            ->whereIn('status', ApprovalRequest::openStatuses())
                            ->exists())
                    ->action(function (Document $record, array $data, Action $action): void {
                        $user = auth()->user();
                        abort_unless($user instanceof User, 403);

                        app(ApprovalWorkflow::class)->createAndSubmit(
                            $record,
                            $user,
                            $data['notes'] ?? null,
                        );

                        $action->successNotificationTitle('Dokumen berhasil diajukan')->success();
                    }),
                Action::make('download')
                    ->label('Unduh')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Document $record): string => route('documents.download', $record))
                    ->visible(fn (Document $record): bool => ! $record->trashed()
                        && (auth()->user()?->can('download', $record) ?? false)),
                EditAction::make()
                    ->label('Ubah Metadata')
                    ->schema([
                        Select::make('document_type')
                            ->label('Jenis Dokumen')
                            ->options(DocumentStorage::documentTypeOptions())
                            ->required()
                            ->native(false),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                Document::STATUS_ACTIVE => 'Aktif',
                                Document::STATUS_ARCHIVED => 'Diarsipkan',
                            ])
                            ->required()
                            ->native(false),
                    ]),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada dokumen')
            ->emptyStateDescription('Unggah dokumen yang berkaitan dengan data ini.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return number_format($bytes / 1_048_576, 2, ',', '.').' MB';
        }

        return number_format($bytes / 1024, 2, ',', '.').' KB';
    }
}
