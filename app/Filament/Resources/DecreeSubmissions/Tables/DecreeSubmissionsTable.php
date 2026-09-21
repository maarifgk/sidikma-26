<?php

namespace App\Filament\Resources\DecreeSubmissions\Tables;

use App\Models\DecreeSubmission;
use App\Models\DecreeSubmissionType;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DecreeSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('submission_number')->label('Nomor Pengajuan')->searchable()->sortable(),
            TextColumn::make('submission_date')->label('Tanggal Pengajuan')->date('d M Y')->sortable(),
            TextColumn::make('school.name')->label('Sekolah/Madrasah')->searchable()->sortable(),
            TextColumn::make('employee.name')->label('Nama Guru')->searchable()->sortable(),
            TextColumn::make('type.name')->label('Jenis SK')->sortable(),
            TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => DecreeSubmission::statusOptions()[$state] ?? $state)->color(fn (string $state): string => match ($state) {
                DecreeSubmission::STATUS_COMPLETED => 'success', DecreeSubmission::STATUS_REJECTED => 'danger', DecreeSubmission::STATUS_REVISION_REQUIRED => 'warning', DecreeSubmission::STATUS_PROCESSING => 'info', default => 'gray'
            }),
            TextColumn::make('completed_at')->label('Tanggal Selesai')->dateTime('d M Y H:i')->placeholder('-')->sortable(),
        ])->filters([
            SelectFilter::make('school_id')->label('Sekolah/Madrasah')->relationship('school', 'name')->searchable()->preload(),
            SelectFilter::make('status')->label('Status')->options(DecreeSubmission::statusOptions()),
            SelectFilter::make('decree_submission_type_id')->label('Jenis SK')->options(fn (): array => DecreeSubmissionType::query()->orderBy('name')->pluck('name', 'id')->all()),
            Filter::make('submission_date')->schema([DatePicker::make('from')->label('Dari Tanggal'), DatePicker::make('until')->label('Sampai Tanggal')])->query(fn (Builder $query, array $data): Builder => $query->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('submission_date', '>=', $date))->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('submission_date', '<=', $date))),
        ])->recordActions([
            ViewAction::make()->label('Detail'),
            EditAction::make()->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false),
            DeleteAction::make()->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false),
        ])->defaultSort('submission_date', 'desc')->emptyStateHeading('Belum ada pengajuan SK')->emptyStateDescription('Pengajuan yang dibuat sekolah/madrasah akan tampil di sini.');
    }
}
