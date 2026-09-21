<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\Foundation;
use App\Models\School;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    protected static ?string $title = 'Riwayat Penugasan';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_position_id')
                    ->label('Ketugasan')
                    ->options(fn (): array => EmployeePosition::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false),
                Hidden::make('foundation_id')
                    ->default(fn (): int => $this->getOwnerRecord()->foundation_id
                        ?? Foundation::application()->getKey())
                    ->dehydrateStateUsing(fn (): int => $this->getOwnerRecord()->foundation_id
                        ?? Foundation::application()->getKey()),
                Select::make('school_id')
                    ->label('Sekolah/Madrasah')
                    ->options(fn (): array => School::query()
                        ->where('foundation_id', $this->getOwnerRecord()->foundation_id
                            ?? Foundation::application()->getKey())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->nullable()
                    ->native(false),
                Select::make('status')
                    ->label('Status Penugasan')
                    ->options(self::statusOptions())
                    ->default(EmployeeAssignment::STATUS_ACTIVE)
                    ->required()
                    ->native(false),
                DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->required(),
                DatePicker::make('end_date')
                    ->label('Tanggal Selesai')
                    ->afterOrEqual('start_date'),
                TextInput::make('decree_number')
                    ->label('Nomor SK')
                    ->maxLength(100),
                DatePicker::make('decree_date')
                    ->label('Tanggal SK'),
                Toggle::make('is_primary')
                    ->label('Penugasan Utama')
                    ->default(false),
                Textarea::make('notes')
                    ->label('Keterangan')
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('decree_number')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withoutGlobalScopes([SoftDeletingScope::class]))
            ->columns([
                TextColumn::make('position.name')
                    ->label('Ketugasan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('school.name')
                    ->label('Sekolah/Madrasah')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->placeholder('Belum ditentukan')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => self::statusOptions()[$state] ?? $state,
                    )
                    ->sortable(),
                IconColumn::make('is_primary')
                    ->label('Utama')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('decree_number')
                    ->label('Nomor SK')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('decree_date')
                    ->label('Tanggal SK')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('employee_position_id')
                    ->label('Ketugasan')
                    ->relationship('position', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('school_id')
                    ->label('Sekolah/Madrasah')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Status Penugasan')
                    ->options(self::statusOptions()),
                TernaryFilter::make('is_primary')
                    ->label('Penugasan Utama')
                    ->placeholder('Semua penugasan')
                    ->trueLabel('Utama')
                    ->falseLabel('Tambahan'),
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Penugasan'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada riwayat penugasan')
            ->emptyStateDescription('Tambahkan ketugasan dan periode penugasan untuk pegawai ini.')
            ->emptyStateIcon('heroicon-o-briefcase');
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return [
            EmployeeAssignment::STATUS_ACTIVE => 'Aktif',
            EmployeeAssignment::STATUS_COMPLETED => 'Selesai',
            EmployeeAssignment::STATUS_INACTIVE => 'Tidak Aktif',
        ];
    }
}
