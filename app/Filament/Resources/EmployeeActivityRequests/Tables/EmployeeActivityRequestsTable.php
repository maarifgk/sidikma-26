<?php

namespace App\Filament\Resources\EmployeeActivityRequests\Tables;

use App\Models\Employee;
use App\Models\EmployeeActivityRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeActivityRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('employee_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('school_name')
                    ->label('Asal Madrasah')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('inactive_date')
                    ->label('Tanggal Mulai Tidak Aktif')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('request_letter_action')
                    ->label('Surat Keterangan Penonaktifan')
                    ->state(fn (EmployeeActivityRequest $record): string => Storage::disk(EmployeeActivityRequest::DISK)->exists($record->request_letter_path) ? 'Lihat' : 'Belum tersedia')
                    ->badge()
                    ->color(fn (EmployeeActivityRequest $record): string => Storage::disk(EmployeeActivityRequest::DISK)->exists($record->request_letter_path) ? 'primary' : 'gray')
                    ->alignCenter()
                    ->url(fn (EmployeeActivityRequest $record): ?string => Storage::disk(EmployeeActivityRequest::DISK)->exists($record->request_letter_path)
                        ? Storage::disk(EmployeeActivityRequest::DISK)->temporaryUrl($record->request_letter_path, now()->addMinutes(5)) : null)
                    ->openUrlInNewTab(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(
                        fn (string $state): string => EmployeeActivityRequest::statusOptions()[$state] ?? $state,
                    )
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(EmployeeActivityRequest::statusOptions()),
            ])
            ->recordActions([
                Action::make('process')
                    ->label('proses')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Proses permohonan nonaktif?')
                    ->modalDescription('Guru/pegawai akan diubah menjadi tidak aktif pada Master Data.')
                    ->visible(fn (EmployeeActivityRequest $record): bool => $record->status === EmployeeActivityRequest::STATUS_SUBMITTED && filled($record->employee_id))
                    ->action(function (EmployeeActivityRequest $record, Action $action): void {
                        $user = auth()->user();
                        abort_unless($user instanceof User, 403);

                        DB::transaction(function () use ($record, $user): void {
                            $request = EmployeeActivityRequest::query()
                                ->lockForUpdate()
                                ->findOrFail($record->getKey());

                            if ($request->status !== EmployeeActivityRequest::STATUS_SUBMITTED) {
                                return;
                            }

                            $employee = Employee::query()
                                ->lockForUpdate()
                                ->findOrFail($request->employee_id);
                            $employee->update(['is_active' => false]);

                            $request->update([
                                'status' => EmployeeActivityRequest::STATUS_COMPLETED,
                                'processed_by' => $user->getKey(),
                                'processed_at' => now(),
                            ]);
                        });

                        $action->successNotificationTitle('Permohonan berhasil diproses')->success();
                    }),
                DeleteAction::make()
                    ->label('Delete')
                    ->color('danger'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada permohonan nonaktif')
            ->emptyStateDescription('Klik Ajukan untuk menambahkan permohonan nonaktif guru atau pegawai.')
            ->emptyStateIcon('heroicon-o-user-minus');
    }
}
