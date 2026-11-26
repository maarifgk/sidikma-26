<?php

namespace App\Filament\Resources\DecreeProposals\Pages;

use App\Filament\Concerns\HasAdministrationFormFeedback;
use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\DecreeProposals\DecreeProposalResource;
use App\Models\DecreeProposal;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDecreeProposal extends CreateRecord
{
    use HasAdministrationFormFeedback;

    protected static string $resource = DecreeProposalResource::class;

    protected static ?string $title = 'Input Data Usulan Guru & Pegawai Baru';

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User, 403);

        abort_unless($actor->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH), 403);

        return DB::connection('sidikma_induk')->transaction(function () use ($data, $actor): DecreeProposal {
            $schoolIds = $actor->memberships()
                ->active()
                ->whereNotNull('school_id')
                ->pluck('school_id')
                ->unique()
                ->values();

            if ($schoolIds->count() !== 1) {
                throw ValidationException::withMessages([
                    'form' => 'Akun Admin Sekolah/Madrasah harus memiliki tepat satu sekolah aktif sebelum mengajukan usulan.',
                ]);
            }

            $school = School::query()
                ->whereKey($schoolIds->first())
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $school instanceof School) {
                throw ValidationException::withMessages([
                    'form' => 'Sekolah/madrasah aktif untuk akun ini tidak ditemukan.',
                ]);
            }

            $email = strtolower(trim($data['email']));
            $employeeCode = strtoupper(trim($data['employee_code']));
            $nuptk = filled($data['nuptk'] ?? null) ? strtoupper(trim($data['nuptk'])) : null;

            $duplicate = DecreeProposal::query()
                ->where('status', DecreeProposal::STATUS_SUBMITTED)
                ->where(function ($query) use ($email, $employeeCode, $nuptk): void {
                    $query->where('candidate_email', $email)
                        ->orWhereJsonContains('legacy_snapshot->employee_code', $employeeCode);

                    if ($nuptk !== null) {
                        $query->orWhereJsonContains('legacy_snapshot->nuptk', $nuptk);
                    }
                })
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'form' => 'Pengajuan aktif dengan email, EWANUGK, atau NUPTK yang sama sudah tersedia.',
                ]);
            }

            if (User::query()->where('email', $email)->exists()
                || Employee::query()->where(function ($query) use ($email, $employeeCode, $nuptk): void {
                    $query->where('email', $email)->orWhere('employee_code', $employeeCode);

                    if ($nuptk !== null) {
                        $query->orWhere('nuptk', $nuptk);
                    }
                })->exists()) {
                throw ValidationException::withMessages([
                    'form' => 'Email, EWANUGK, atau NUPTK sudah terdaftar pada data yang ada.',
                ]);
            }

            $position = EmployeePosition::query()
                ->whereKey($data['position_id'])
                ->where('is_active', true)
                ->first();

            if (! $position instanceof EmployeePosition) {
                throw ValidationException::withMessages([
                    'position_id' => 'Ketugasan yang dipilih tidak aktif atau tidak ditemukan.',
                ]);
            }

            return DecreeProposal::query()->create([
                'employee_id' => null,
                'candidate_name' => $data['name'],
                'candidate_email' => $email,
                'candidate_phone' => $data['phone'],
                'candidate_school' => $school->name,
                'partner_admin_number' => $data['partner_admin_number'] ?? null,
                'status' => DecreeProposal::STATUS_SUBMITTED,
                'legacy_snapshot' => [
                    'employee_code' => $employeeCode,
                    'name' => $data['name'],
                    'email' => $email,
                    'phone' => $data['phone'],
                    'school_id' => $school->getKey(),
                    'school_name' => $school->name,
                    'school_npsn' => $school->npsn,
                    'foundation_id' => $school->foundation_id,
                    'employment_status' => $data['employment_status'],
                    'nip' => Employee::isPnsStatus($data['employment_status']) ? ($data['nip'] ?? null) : null,
                    'rank' => Employee::isPnsStatus($data['employment_status']) ? ($data['rank'] ?? null) : null,
                    'grade' => Employee::isPnsStatus($data['employment_status']) ? ($data['grade'] ?? null) : null,
                    'birth_date' => $data['birth_date'],
                    'birth_place' => $data['birth_place'],
                    'position_id' => $position->getKey(),
                    'position_name' => $position->name,
                    'assignment_start_date' => $data['assignment_start_date'],
                    'last_education' => $data['last_education'],
                    'nuptk' => $nuptk,
                    'program_study' => $data['program_study'],
                ],
                'photo_path' => $data['photo_path'],
                'diploma_path' => $data['diploma_path'],
                'application_letter_path' => $data['application_letter_path'],
                'service_statement_path' => $data['service_statement_path'],
                'teaching_certificate_path' => $data['teaching_certificate_path'] ?? null,
                'educator_certificate_path' => $data['educator_certificate_path'] ?? null,
                'task_assignment_certificate_path' => $data['task_assignment_certificate_path'],
                'submitted_by' => $actor->getKey(),
            ]);
        });
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Simpan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Kembali');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Usulan SK baru berhasil diajukan';
    }

}
