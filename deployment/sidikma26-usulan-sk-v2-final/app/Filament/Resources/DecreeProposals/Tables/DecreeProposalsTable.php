<?php

namespace App\Filament\Resources\DecreeProposals\Tables;

use App\Models\DecreeProposal;
use App\Models\User;
use App\Services\DecreeProposalApprovalService;
use App\Services\DecreeProposalEditService;
use App\Services\DecreeProposalRejectionService;
use App\Filament\Resources\DecreeProposals\Schemas\DecreeProposalEditForm;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
                self::editAction(),
                self::approveAction(),
                self::rejectAction(),
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
                    ->modalFooterActions(fn (): array => [
                        Action::make('closeDetail')
                            ->label('Tutup')
                            ->icon('heroicon-o-x-mark')
                            ->color('gray')
                            ->button()
                            ->close(),
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

    private static function editAction(): Action
    {
        return Action::make('edit')
            ->label('Edit Data')
            ->icon('heroicon-o-pencil-square')
            ->color('info')
            ->button()
            ->tooltip('Perbaiki Data Pengajuan')
            ->visible(fn (DecreeProposal $record): bool => (auth()->user()?->isAdminInduk()
                || auth()->user()?->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH))
                && (auth()->user()?->can('view', $record) ?? false))
            ->disabled(fn (DecreeProposal $record): bool => $record->status !== DecreeProposal::STATUS_SUBMITTED
                || $record->employee_id !== null
                || ! (auth()->user()?->can('edit', $record) ?? false))
            ->authorize(fn (DecreeProposal $record): bool => auth()->user()?->can('edit', $record) ?? false)
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
            });
    }

    private static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Simpan')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->button()
            ->tooltip('Simpan dan Setujui Pengajuan')
            ->visible(fn (DecreeProposal $record): bool => (auth()->user()?->isAdminInduk() ?? false)
                && (auth()->user()?->can('view', $record) ?? false))
            ->disabled(fn (DecreeProposal $record): bool => $record->status !== DecreeProposal::STATUS_SUBMITTED
                || $record->employee_id !== null
                || ! (auth()->user()?->can('update', $record) ?? false))
            ->tooltip(fn (DecreeProposal $record): string => ! (auth()->user()?->can('update', $record) ?? false)
                ? 'Anda tidak memiliki izin untuk menyetujui pengajuan ini.'
                : ($record->employee_id !== null
                    ? 'Proposal lama sudah terhubung dengan data pegawai.'
                    : ($record->status === DecreeProposal::STATUS_SUBMITTED
                        ? 'Simpan dan Setujui Pengajuan'
                        : 'Pengajuan sudah diproses.')))
            ->authorize(fn (DecreeProposal $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->requiresConfirmation()
            ->modalHeading('Setujui Usulan SK Baru')
            ->modalDescription('Persetujuan akan membuat akun guru/pegawai dan data kepegawaian secara atomik.')
            ->modalSubmitActionLabel('Ya, Simpan dan Setujui')
            ->action(function (DecreeProposal $record): void {
                $actor = auth()->user();
                abort_unless($actor instanceof User && $actor->can('update', $record), 403);
                $result = app(DecreeProposalApprovalService::class)->approve($record, $actor);
                $notification = Notification::make()->title('Pengajuan SK baru berhasil disetujui.');
                if ($result['reset_status'] !== \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
                    $notification->warning()->body('Akun berhasil dibuat. Link password belum terkirim; guru dapat menggunakan Lupa Password.');
                } else {
                    $notification->success()->body('Akun guru dan data pegawai telah berhasil dibuat.');
                }
                $notification->send();
            });
    }

    private static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Tolak')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->button()
            ->visible(fn (DecreeProposal $record): bool => (auth()->user()?->isAdminInduk() ?? false)
                && (auth()->user()?->can('view', $record) ?? false))
            ->disabled(fn (DecreeProposal $record): bool => $record->status !== DecreeProposal::STATUS_SUBMITTED
                || $record->employee_id !== null
                || ! (auth()->user()?->can('update', $record) ?? false))
            ->tooltip(fn (DecreeProposal $record): string => ! (auth()->user()?->can('update', $record) ?? false)
                ? 'Anda tidak memiliki izin untuk menolak pengajuan ini.'
                : ($record->employee_id !== null
                    ? 'Proposal lama memerlukan penanganan khusus.'
                    : ($record->status === DecreeProposal::STATUS_SUBMITTED
                        ? 'Tolak Pengajuan'
                        : 'Pengajuan sudah diproses.')))
            ->authorize(fn (DecreeProposal $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->requiresConfirmation()
            ->modalHeading('Tolak Usulan SK Baru')
            ->modalDescription('Alasan penolakan wajib diisi dan akan dapat dibaca Admin Sekolah/Madrasah.')
            ->schema([
                Textarea::make('reason')
                    ->label('Keterangan Penolakan')
                    ->required()
                    ->maxLength(65535)
                    ->rows(5),
            ])
            ->modalSubmitActionLabel('Konfirmasi Penolakan')
            ->action(function (DecreeProposal $record, array $data): void {
                $actor = auth()->user();
                abort_unless($actor instanceof User && $actor->can('update', $record), 403);
                app(DecreeProposalRejectionService::class)->reject($record, $actor, (string) ($data['reason'] ?? ''));
                Notification::make()->title('Pengajuan SK baru berhasil ditolak.')->body('Keterangan penolakan telah disimpan.')->success()->send();
            });
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
