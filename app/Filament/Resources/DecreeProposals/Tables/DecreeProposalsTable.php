<?php

namespace App\Filament\Resources\DecreeProposals\Tables;

use App\Models\DecreeProposal;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\School;
use App\Models\User;
use App\Services\DecreeProposalApprovalService;
use App\Services\DecreeProposalEditService;
use App\Services\DecreeProposalRejectionService;
use App\Filament\Resources\DecreeProposals\Schemas\DecreeProposalEditForm;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
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
                    ->state(fn (DecreeProposal $record): string => $record->displayEmployeeCode())
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.name')
                    ->label('Nama Lengkap')
                    ->state(fn (DecreeProposal $record): string => $record->displayName())
                    ->searchable(['employee.name', 'candidate_name'])
                    ->sortable(),
                TextColumn::make('employee.school.name')
                    ->label('Asal Madrasah/Sekolah')
                    ->state(fn (DecreeProposal $record): string => $record->displaySchoolName())
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('candidate_email')
                    ->label('Email')
                    ->state(fn (DecreeProposal $record): string => $record->displayEmail())
                    ->placeholder('-')
                    ->searchable(['candidate_email', 'employee.email']),
                TextColumn::make('candidate_phone')
                    ->label('Telepon')
                    ->state(fn (DecreeProposal $record): string => $record->displayPhone())
                    ->placeholder('-'),
                TextColumn::make('candidate_position')
                    ->label('Ketugasan')
                    ->state(fn (DecreeProposal $record): string => $record->displayPositionName())
                    ->placeholder('-'),
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
                    ->modalHeading('Proses Usulan SK')
                    ->modalDescription('Periksa data calon guru/pegawai, lengkapi informasi yang diperlukan, lalu simpan status pengajuan.')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->visible(fn (DecreeProposal $record): bool => (auth()->user()?->isAdminInduk() ?? false)
                        && false)
                    ->authorize(fn (DecreeProposal $record): bool => auth()->user()?->can('update', $record) ?? false)
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
                        Section::make('Identitas Pengusul')
                            ->icon('heroicon-o-user-circle')
                            ->description('Data utama guru atau pegawai yang mengajukan SK.')
                            ->extraAttributes(['class' => 'decree-process-section'])
                            ->schema([
                                TextInput::make('employee_code')->label('EWANUGK')->required()->maxLength(50),
                                TextInput::make('name')->label('Nama lengkap')->required()->maxLength(255),
                                Select::make('school_id')
                                    ->label('Asal madrasah/sekolah')
                                    ->options(fn (): array => School::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()->preload()->required()->native(false),
                                Select::make('employment_status')
                                    ->label('Status kepegawaian')
                                    ->options(Employee::employmentStatusOptions())->required()->native(false),
                                TextInput::make('email')->label('Email')->email()->required()->maxLength(255),
                                TextInput::make('phone')->label('Nomor telepon')->tel()->required()->maxLength(30),
                            ])
                            ->columns(2),
                        Section::make('Data Pribadi dan Ketugasan')
                            ->icon('heroicon-o-identification')
                            ->description('Pastikan tanggal, posisi, dan pendidikan sudah sesuai dokumen pendukung.')
                            ->extraAttributes(['class' => 'decree-process-section'])
                            ->schema([
                                TextInput::make('birth_place')->label('Tempat lahir')->required()->maxLength(100),
                                DatePicker::make('birth_date')->label('Tanggal lahir')->required()->maxDate(now()),
                                DatePicker::make('assignment_start_date')->label('TMT')->helperText('Terhitung mulai tanggal')->required(),
                                Select::make('position_id')
                                    ->label('Ketugasan')
                                    ->options(fn (): array => EmployeePosition::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()->preload()->required()->native(false),
                                TextInput::make('nuptk')->label('NUPTK/NPK')->nullable()->maxLength(30),
                                TextInput::make('last_education')->label('Pendidikan terakhir & tahun lulus')->required()->maxLength(100),
                                TextInput::make('program_study')->label('Program studi')->required()->maxLength(150),
                                Select::make('status')
                                    ->label('Status pengajuan')
                                    ->options(DecreeProposal::statusOptions())->required()->native(false),
                            ])
                            ->columns(2),
                    ])
                    ->action(function (DecreeProposal $record, array $data): void {
                        abort_unless(auth()->user()?->can('update', $record), 403);

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
                                'nama_lengkap' => $data['name'],
                                'email' => strtolower(trim($data['email'])),
                                'no_tlp' => $data['phone'],
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
                    ->label('Lihat')
                    ->tooltip('Lihat Detail Pengajuan')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->button()
                    ->modalHeading('Detail Usulan SK Baru')
                    ->modalDescription('SIDIKMA 26 — Administrasi Kepegawaian')
                    ->modalIcon('heroicon-o-clipboard-document-list')
                    ->modalIconColor('success')
                    ->modalCancelAction(false)
                    ->modalWidth(Width::Screen)
                    ->extraModalWindowAttributes(['class' => 'decree-proposal-detail-modal'])
                    ->modalFooterActions(fn (DecreeProposal $record): array => [
                        Action::make('closeDetail')
                            ->label('Tutup')
                            ->icon('heroicon-o-x-mark')
                            ->color('gray')
                            ->button()
                            ->close(),
                        Action::make('editFromDetail')
                            ->label('Edit Data')
                            ->icon('heroicon-o-pencil-square')
                            ->color('info')
                            ->button()
                            ->tooltip('Perbaiki Data Pengajuan')
                            ->visible(fn (): bool => auth()->user()?->isAdminInduk() === true
                                && $record->status === DecreeProposal::STATUS_SUBMITTED
                                && $record->employee_id === null)
                            ->authorize(fn (): bool => auth()->user()?->can('update', $record) ?? false)
                            ->modalHeading('Edit Data Pengajuan')
                            ->modalDescription('Perbaiki data pengajuan tanpa menyetujui atau mengubah statusnya.')
                            ->modalWidth(Width::Screen)
                            ->extraModalWindowAttributes(['class' => 'decree-proposal-detail-modal'])
                            ->schema(DecreeProposalEditForm::schema())
                            ->fillForm(fn (DecreeProposal $record): array => self::editFormData($record))
                            ->modalSubmitActionLabel('Simpan Perubahan')
                            ->modalCancelActionLabel('Batal')
                            ->action(function (DecreeProposal $record, array $data): void {
                                $actor = auth()->user();
                                abort_unless($actor instanceof User, 403);
                                app(DecreeProposalEditService::class)->save($record, $actor, $data);
                                Notification::make()->title('Perubahan data pengajuan berhasil disimpan.')->success()->send();
                            }),
                        Action::make('rejectFromDetail')
                            ->label('Tolak')
                            ->icon('heroicon-o-x-circle')
                            ->color('danger')
                            ->visible(fn (): bool => auth()->user()?->isAdminInduk() === true
                                && $record->status === DecreeProposal::STATUS_SUBMITTED
                                && $record->employee_id === null)
                            ->authorize(fn (): bool => auth()->user()?->can('update', $record) ?? false)
                            ->requiresConfirmation()
                            ->modalHeading('Tolak Usulan SK Baru')
                            ->schema([
                                Textarea::make('reason')
                                    ->label('Keterangan Penolakan')
                                    ->required()
                                    ->maxLength(65535)
                                    ->rows(5),
                            ])
                            ->action(function (DecreeProposal $record, array $data): void {
                                app(DecreeProposalRejectionService::class)->reject(
                                    $record,
                                    auth()->user(),
                                    (string) ($data['reason'] ?? ''),
                                );
                                Notification::make()->title('Pengajuan SK baru berhasil ditolak')->success()->send();
                            }),
                        Action::make('approveFromDetail')
                            ->label('Simpan / Setujui')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->visible(fn (): bool => auth()->user()?->isAdminInduk() === true
                                && $record->status === DecreeProposal::STATUS_SUBMITTED
                                && $record->employee_id === null)
                            ->authorize(fn (): bool => auth()->user()?->can('update', $record) ?? false)
                            ->requiresConfirmation()
                            ->modalHeading('Setujui Usulan SK Baru')
                            ->modalDescription('Sistem akan membuat akun guru/pegawai dan data kepegawaian secara otomatis.')
                            ->action(function (DecreeProposal $record): void {
                                app(DecreeProposalApprovalService::class)->approve($record, auth()->user());
                                Notification::make()->title('Pengajuan SK baru berhasil disetujui')->success()->send();
                            }),
                    ])
                    ->authorize(fn (DecreeProposal $record): bool => auth()->user()?->can('view', $record) ?? false),
                DeleteAction::make()
                    ->label('Delete')
                    ->tooltip('Hapus Pengajuan')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->button()
                    ->outlined()
                    ->disabled(fn (DecreeProposal $record): bool => $record->employee_id !== null)
                    ->tooltip(fn (DecreeProposal $record): string => $record->employee_id !== null
                        ? 'Proposal lama tidak dapat dihapus karena sudah terhubung dengan data pegawai.'
                        : 'Hapus Pengajuan')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Usulan SK Baru')
                    ->modalDescription('Pengajuan akan dipindahkan ke arsip. Data User, Employee, Membership, EmployeeAssignment, dan dokumen tidak akan dihapus.')
                    ->authorize(fn (DecreeProposal $record): bool => auth()->user()?->can('delete', $record) ?? false),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum ada usulan SK baru')
            ->emptyStateDescription('Klik Ajukan untuk menambahkan usulan guru atau pegawai baru.');
    }

    /** @return array<string, mixed> */
    private static function editFormData(DecreeProposal $record): array
    {
        $snapshot = is_array($record->legacy_snapshot) ? $record->legacy_snapshot : [];

        return [
            'edit_version' => $record->getRawOriginal('updated_at'),
            'name' => $record->candidate_name ?: ($snapshot['name'] ?? null),
            'employee_code' => $snapshot['employee_code'] ?? null,
            'email' => $record->candidate_email ?: ($snapshot['email'] ?? null),
            'phone' => $record->candidate_phone ?: ($snapshot['phone'] ?? null),
            'partner_admin_number' => $record->partner_admin_number,
            'employment_status' => $snapshot['employment_status'] ?? null,
            'nip' => $snapshot['nip'] ?? null,
            'rank' => $snapshot['rank'] ?? null,
            'grade' => $snapshot['grade'] ?? null,
            'birth_place' => $snapshot['birth_place'] ?? null,
            'birth_date' => $snapshot['birth_date'] ?? null,
            'position_id' => $snapshot['position_id'] ?? null,
            'assignment_start_date' => $snapshot['assignment_start_date'] ?? null,
            'last_education' => $snapshot['last_education'] ?? null,
            'nuptk' => $snapshot['nuptk'] ?? null,
            'program_study' => $snapshot['program_study'] ?? null,
            'notes' => $record->initialNotes(),
        ];
    }
}
