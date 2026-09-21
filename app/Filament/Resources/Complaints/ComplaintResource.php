<?php

namespace App\Filament\Resources\Complaints;

use App\Filament\Resources\Complaints\Pages\ManageComplaints;
use App\Models\Complaint;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ComplaintResource extends Resource
{
    protected static ?string $model = Complaint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Pengaduan';

    protected static ?string $pluralModelLabel = 'Pengaduan Privat';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->with(['submitter', 'school', 'handler'])
            ->when(! ($user instanceof User && $user->isAdminInduk()), fn (Builder $query) => $query->whereRaw('1 = 0'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->label('Nomor')->searchable()->copyable(),
                TextColumn::make('created_at')->label('Tanggal')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('submitter.name')->label('Pelapor')->searchable(),
                TextColumn::make('school.name')->label('Sekolah')->placeholder('-')->wrap(),
                TextColumn::make('category')->label('Kategori')->formatStateUsing(fn (string $state) => Complaint::categoryOptions()[$state] ?? $state)->wrap(),
                TextColumn::make('subject')->label('Judul')->searchable()->wrap(),
                TextColumn::make('description')->label('Kronologi')->limit(80)->wrap()->tooltip(fn (Complaint $record) => $record->description),
                TextColumn::make('attachments')->label('Lampiran')->state(fn (Complaint $record): string => count($record->attachments ?? []).' file')->badge(),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state) => Complaint::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        Complaint::STATUS_RESOLVED => 'success', Complaint::STATUS_REJECTED => 'danger', Complaint::STATUS_IN_REVIEW => 'warning', default => 'gray'
                    }),
            ])
            ->filters([SelectFilter::make('status')->options(Complaint::statusOptions())])
            ->recordActions([
                Action::make('attachments')
                    ->label('Lampiran')
                    ->icon('heroicon-o-paper-clip')
                    ->visible(fn (Complaint $record): bool => filled($record->attachments))
                    ->modalHeading('Lampiran Pengaduan')
                    ->modalContent(fn (Complaint $record) => view('filament.admin.resources.complaints.attachments', ['record' => $record]))
                    ->modalSubmitAction(false),
                Action::make('respond')
                    ->label('Tanggapi')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->fillForm(fn (Complaint $record) => ['status' => $record->status, 'admin_response' => $record->admin_response])
                    ->schema([
                        Select::make('status')->label('Status')->options(Complaint::statusOptions())->required()->native(false),
                        Textarea::make('admin_response')->label('Tanggapan Privat untuk Pelapor')->required()->rows(6)->maxLength(10000),
                    ])
                    ->action(function (Complaint $record, array $data, Action $action): void {
                        abort_unless(auth()->user()?->isAdminInduk(), 403);
                        $record->update($data + ['handled_by' => auth()->id(), 'handled_at' => now()]);
                        $action->successNotificationTitle('Tanggapan privat tersimpan')->success();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum ada pengaduan privat')
            ->emptyStateDescription('Pengaduan guru dan pegawai akan tampil di sini.');
    }

    public static function getPages(): array
    {
        return ['index' => ManageComplaints::route('/')];
    }
}
