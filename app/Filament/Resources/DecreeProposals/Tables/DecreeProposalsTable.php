<?php

namespace App\Filament\Resources\DecreeProposals\Tables;

use App\Models\DecreeProposal;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\School;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class DecreeProposalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('employee.employee_code')
                    ->label('EWANUGK')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.school.name')
                    ->label('Asal Madrasah/Sekolah')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => DecreeProposal::statusOptions()[$state] ?? $state,
                    )
                    ->color(fn (string $state): string => match ($state) {
                        DecreeProposal::STATUS_COMPLETED => 'success',
                        DecreeProposal::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(DecreeProposal::statusOptions()),
            ])
            ->recordActions([
                Action::make('process')
                    ->label('Proses')
                    ->tooltip('Proses usulan SK baru')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->modalHeading('Proses Usulan SK Guru/Pegawai Baru')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn (): bool => auth()->user()?->isAdminInduk() ?? false)
                    ->fillForm(fn (DecreeProposal $record): array => [
                        'employee_code' => $record->employee?->employee_code,
                        'name' => $record->employee?->name,
                        'email' => $record->employee?->email,
                        'phone' => $record->employee?->phone,
                        'school_id' => $record->employee?->school_id,
                        'employment_status' => $record->employee?->employment_status,
                        'birth_place' => $record->employee?->birth_place,
                        'birth_date' => $record->employee?->birth_date?->toDateString(),
                        'assignment_start_date' => $record->employee?->currentAssignment?->start_date?->toDateString(),
                        'position_id' => $record->employee?->currentAssignment?->employee_position_id,
                        'nuptk' => $record->employee?->nuptk,
                        'last_education' => $record->employee?->last_education,
                        'program_study' => $record->employee?->program_study,
                        'status' => $record->status,
                    ])
                    ->schema([
                        TextInput::make('employee_code')->label('EWANUGK')->required()->maxLength(50),
                        TextInput::make('name')->label('NAMA LENGKAP')->required()->maxLength(255),
                        TextInput::make('email')->label('EMAIL')->email()->required()->maxLength(255),
                        TextInput::make('phone')->label('NOMOR TELEPON')->tel()->required()->maxLength(30),
                        Select::make('school_id')
                            ->label('ASAL MADRASAH/SEKOLAH')
                            ->options(fn (): array => School::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()->preload()->required()->native(false),
                        Select::make('employment_status')
                            ->label('STATUS KEPEGAWAIAN')
                            ->options(Employee::employmentStatusOptions())->required()->native(false),
                        TextInput::make('birth_place')->label('TEMPAT LAHIR')->required()->maxLength(100),
                        DatePicker::make('birth_date')->label('TANGGAL LAHIR')->required()->maxDate(now()),
                        DatePicker::make('assignment_start_date')->label('TMT (TERHITUNG MULAI TANGGAL)')->required(),
                        Select::make('position_id')
                            ->label('KETUGASAN')
                            ->options(fn (): array => EmployeePosition::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()->preload()->required()->native(false),
                        TextInput::make('nuptk')->label('NUPTK/NPK')->nullable()->maxLength(30),
                        TextInput::make('last_education')->label('PENDIDIKAN TERAKHIR DAN TAHUN LULUS')->required()->maxLength(100),
                        TextInput::make('program_study')->label('PROGRAM STUDI')->required()->maxLength(150),
                        Select::make('status')
                            ->label('STATUS PENGAJUAN')
                            ->options(DecreeProposal::statusOptions())->required()->native(false),
                    ])
                    ->action(function (DecreeProposal $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $employee = $record->employee()->lockForUpdate()->firstOrFail();
                            $position = EmployeePosition::query()->findOrFail($data['position_id']);

                            $employee->update([
                                'employee_code' => strtoupper(trim($data['employee_code'])),
                                'name' => $data['name'],
                                'email' => strtolower(trim($data['email'])),
                                'phone' => $data['phone'],
                                'school_id' => $data['school_id'],
                                'foundation_id' => School::query()->findOrFail($data['school_id'])->foundation_id,
                                'employment_status' => $data['employment_status'],
                                'employee_type' => in_array($data['employment_status'], ['GTY', 'GTT'], true)
                                    || $position->category === EmployeePosition::CATEGORY_TEACHING
                                    ? Employee::TYPE_GURU
                                    : Employee::TYPE_PEGAWAI,
                                'birth_place' => $data['birth_place'],
                                'birth_date' => $data['birth_date'],
                                'nuptk' => filled($data['nuptk'] ?? null) ? strtoupper(trim($data['nuptk'])) : null,
                                'last_education' => $data['last_education'],
                                'program_study' => $data['program_study'],
                            ]);

                            $employee->user?->update([
                                'name' => $data['name'],
                                'email' => strtolower(trim($data['email'])),
                                'phone_number' => $data['phone'],
                            ]);

                            $employee->currentAssignment?->update([
                                'employee_position_id' => $position->getKey(),
                                'foundation_id' => $employee->foundation_id,
                                'school_id' => $employee->school_id,
                                'start_date' => $data['assignment_start_date'],
                            ]);

                            $record->update(['status' => $data['status']]);
                        });

                        Notification::make()->title('Usulan SK berhasil diproses')->success()->send();
                    }),
                ViewAction::make('detail')
                    ->label('')
                    ->tooltip('Lihat detail pengajuan')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Detail Usulan SK'),
                DeleteAction::make()
                    ->label('Delete'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada usulan SK baru')
            ->emptyStateDescription('Klik Ajukan untuk menambahkan usulan guru atau pegawai baru.');
    }
}
