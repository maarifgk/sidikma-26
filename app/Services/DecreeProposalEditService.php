<?php

namespace App\Services;

use App\Models\DecreeProposal;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class DecreeProposalEditService
{
    /** @param array<string, mixed> $data */
    public function save(DecreeProposal $proposal, User $actor, array $data): void
    {
        DB::connection('sidikma_induk')->transaction(function () use ($proposal, $actor, $data): void {
            $locked = DecreeProposal::on('sidikma_induk')
                ->whereKey($proposal->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked instanceof DecreeProposal) {
                throw ValidationException::withMessages(['proposal' => 'Pengajuan tidak ditemukan.']);
            }

            abort_unless($actor->can('edit', $locked), 403);

            if (blank($data['edit_version'] ?? null)
                || (string) $locked->getRawOriginal('updated_at') !== (string) $data['edit_version']) {
                throw ValidationException::withMessages([
                    'proposal' => 'Data pengajuan telah berubah. Tutup form, muat ulang data, lalu coba lagi.',
                ]);
            }

            $this->validateReplacementDocuments($data);
            $normalized = $this->normalize($data);
            $this->validateConflicts($locked, $normalized);

            $snapshot = is_array($locked->legacy_snapshot) ? $locked->legacy_snapshot : [];
            $snapshot = array_merge($snapshot, [
                'employee_code' => $normalized['employee_code'],
                'name' => $normalized['name'],
                'email' => $normalized['email'],
                'phone' => $normalized['phone'],
                'employment_status' => $normalized['employment_status'],
                'nip' => $normalized['nip'],
                'rank' => $normalized['rank'],
                'grade' => $normalized['grade'],
                'birth_date' => $normalized['birth_date'],
                'birth_place' => $normalized['birth_place'],
                'position_id' => $normalized['position_id'],
                'position_name' => $normalized['position_name'],
                'assignment_start_date' => $normalized['assignment_start_date'],
                'last_education' => $normalized['last_education'],
                'nuptk' => $normalized['nuptk'],
                'program_study' => $normalized['program_study'],
            ]);

            $notes = json_decode((string) $locked->notes, true);
            $notes = is_array($notes) ? $notes : [];
            $notes['initial'] = $normalized['notes'];

            $locked->fill([
                'candidate_name' => $normalized['name'],
                'candidate_email' => $normalized['email'],
                'candidate_phone' => $normalized['phone'],
                'partner_admin_number' => $normalized['partner_admin_number'],
                'legacy_snapshot' => $snapshot,
                'notes' => json_encode($notes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ]);
            $documentFields = ['photo_path', 'diploma_path', 'application_letter_path', 'service_statement_path', 'task_assignment_certificate_path'];
            $oldPaths = collect($documentFields)
                ->map(fn (string $field): mixed => $locked->getAttribute($field))
                ->filter(fn (mixed $path): bool => is_string($path) && $path !== '')
                ->values()
                ->all();
            $storedPaths = [];

            try {
                foreach ($documentFields as $document) {
                    if (($data[$document] ?? null) instanceof UploadedFile) {
                        $newPath = $data[$document]->store('edits', 'decree-proposals');
                        if (! is_string($newPath) || blank($newPath)) {
                            throw ValidationException::withMessages([
                                $document => 'File pengganti gagal disimpan.',
                            ]);
                        }
                        $storedPaths[] = $newPath;
                        $locked->setAttribute($document, $newPath);
                    }
                }
                $locked->save();
            } catch (\Throwable $exception) {
                foreach ($storedPaths as $storedPath) {
                    if (in_array($storedPath, $oldPaths, true)) {
                        continue;
                    }

                    $referenced = DecreeProposal::on('sidikma_induk')
                        ->where(function ($query) use ($documentFields, $storedPath): void {
                            foreach ($documentFields as $field) {
                                $query->orWhere($field, $storedPath);
                            }
                        })
                        ->exists();

                    if (! $referenced) {
                        Storage::disk('decree-proposals')->delete($storedPath);
                    }
                }

                throw $exception;
            }
        });
    }

    /** @param array<string, mixed> $data */
    private function validateReplacementDocuments(array $data): void
    {
        $rules = [];
        foreach ([
            'photo_path' => ['file', 'mimetypes:image/jpeg,image/png,image/webp', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'diploma_path' => ['file', 'mimetypes:application/pdf', 'mimes:pdf', 'max:10240'],
            'application_letter_path' => ['file', 'mimetypes:application/pdf', 'mimes:pdf', 'max:10240'],
            'service_statement_path' => ['file', 'mimetypes:application/pdf', 'mimes:pdf', 'max:10240'],
            'task_assignment_certificate_path' => ['file', 'mimetypes:application/pdf', 'mimes:pdf', 'max:10240'],
        ] as $field => $fieldRules) {
            if (array_key_exists($field, $data) && filled($data[$field])) {
                $rules[$field] = $fieldRules;
            }
        }

        if ($rules !== []) {
            Validator::make($data, $rules)->validate();
        }
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function normalize(array $data): array
    {
        $status = (string) ($data['employment_status'] ?? '');
        $pns = Employee::isPnsStatus($status);
        $position = EmployeePosition::on('sidikma_induk')->whereKey($data['position_id'] ?? null)
            ->where('is_active', true)->first();

        if (! $position instanceof EmployeePosition) {
            throw ValidationException::withMessages(['position_id' => 'Ketugasan tidak aktif atau tidak ditemukan.']);
        }

        return [
            'name' => trim((string) ($data['name'] ?? '')),
            'email' => strtolower(trim((string) ($data['email'] ?? ''))),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'employee_code' => strtoupper(trim((string) ($data['employee_code'] ?? ''))),
            'partner_admin_number' => filled($data['partner_admin_number'] ?? null) ? trim((string) $data['partner_admin_number']) : null,
            'employment_status' => $status,
            'nip' => $pns ? (filled($data['nip'] ?? null) ? trim((string) $data['nip']) : null) : null,
            'rank' => $pns ? ($data['rank'] ?? null) : null,
            'grade' => $pns ? ($data['grade'] ?? null) : null,
            'birth_place' => trim((string) ($data['birth_place'] ?? '')),
            'birth_date' => $data['birth_date'] ?? null,
            'position_id' => $position->getKey(),
            'position_name' => $position->name,
            'assignment_start_date' => $data['assignment_start_date'] ?? null,
            'last_education' => trim((string) ($data['last_education'] ?? '')),
            'nuptk' => filled($data['nuptk'] ?? null) ? strtoupper(trim((string) $data['nuptk'])) : null,
            'program_study' => trim((string) ($data['program_study'] ?? '')),
            'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
        ];
    }

    /** @param array<string, mixed> $data */
    private function validateConflicts(DecreeProposal $proposal, array $data): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'employee_code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9._\/-]+$/i'],
            'employment_status' => ['required', 'string'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'position_id' => ['required'],
            'assignment_start_date' => ['required', 'date'],
            'last_education' => ['required', 'string', 'max:100'],
            'program_study' => ['required', 'string', 'max:150'],
            'nuptk' => ['nullable', 'string', 'max:30', 'regex:/^[A-Z0-9._\/-]+$/i'],
            'nip' => ['nullable', 'digits:18'],
            'rank' => ['nullable', 'string', 'max:100'],
            'grade' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ];

        if (Employee::isPnsStatus($data['employment_status'])) {
            $rules['nip'] = ['required', 'digits:18'];
            $rules['rank'] = ['required', 'string', 'max:100'];
            $rules['grade'] = ['required', 'string', 'max:50'];
        }

        Validator::make($data, $rules)->validate();

        $errors = [];

        $connection = 'sidikma_induk';
        $currentEmployee = Employee::on($connection)->withTrashed()->find($proposal->employee_id);
        $currentUserId = $currentEmployee?->user_id;

        if (User::on($connection)
            ->when($currentUserId !== null, fn ($query) => $query->where('id', '!=', $currentUserId))
            ->where('email', $data['email'])
            ->exists()) $errors['email'] = 'Email sudah digunakan oleh akun lain.';
        if (Employee::on($connection)->withTrashed()->when($proposal->employee_id !== null, fn ($query) => $query->where('id', '!=', $proposal->employee_id))->where(function ($query) use ($data): void {
            $query->where('email', $data['email'])->orWhere('employee_code', $data['employee_code']);
            if (filled($data['nuptk'])) $query->orWhere('nuptk', $data['nuptk']);
            if (filled($data['nip'])) $query->orWhere('nip', $data['nip']);
        })->exists()) $errors['identity'] = 'Email, EWANUGK, NUPTK, atau NIP sudah digunakan data pegawai lain.';

        $duplicate = DecreeProposal::on($connection)->where('id', '!=', $proposal->getKey())
            ->whereIn('status', [
                DecreeProposal::STATUS_SUBMITTED,
                DecreeProposal::STATUS_APPROVED,
                DecreeProposal::STATUS_COMPLETED,
                DecreeProposal::STATUS_REVISION,
            ])
            ->where(function ($query) use ($data): void {
                $query->where('candidate_email', $data['email'])
                    ->orWhereJsonContains('legacy_snapshot->employee_code', $data['employee_code']);
                if (filled($data['nuptk'])) $query->orWhereJsonContains('legacy_snapshot->nuptk', $data['nuptk']);
            })->exists();
        if ($duplicate) $errors['proposal_conflict'] = 'Pengajuan lain dengan identitas yang sama sudah tersedia.';

        if ($errors !== []) throw ValidationException::withMessages($errors);
    }
}
