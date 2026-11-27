<?php

namespace App\Services;

use App\Models\DecreeProposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecreeProposalRejectionService
{
    public function reject(DecreeProposal $proposal, User $actor, string $reason): DecreeProposal
    {
        $this->assertSharedDatabaseConnection();
        $reason = trim($reason);
        $this->validateReason($reason);
        $connection = config('database.default');

        return DB::connection($connection)->transaction(function () use ($proposal, $actor, $reason, $connection): DecreeProposal {
            $locked = DecreeProposal::on($connection)->whereKey($proposal->getKey())->lockForUpdate()->firstOrFail();

            if (! $actor->isAdminInduk() || ! $actor->can('update', $locked)) abort(403);
            $payload = [
                'initial' => $locked->initialNotes(),
                'rejection_reason' => $reason,
            ];
            $encodedNotes = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            if (strlen($encodedNotes) > 65535) {
                throw ValidationException::withMessages(['reason' => 'Gabungan catatan dan alasan penolakan melebihi kapasitas kolom notes.']);
            }

            $locked->notes = $encodedNotes;
            $locked->status = DecreeProposal::STATUS_REJECTED;
            $locked->save();
            return $locked;
        });
    }

    private function validateReason(string $reason): void
    {
        if ($reason === '') throw ValidationException::withMessages(['reason' => 'Keterangan penolakan wajib diisi.']);
        if (strlen($reason) > 65535) throw ValidationException::withMessages(['reason' => 'Keterangan penolakan terlalu panjang.']);
        if (strip_tags($reason) !== $reason) throw ValidationException::withMessages(['reason' => 'Keterangan penolakan tidak boleh berisi HTML atau script.']);
    }

    private function assertSharedDatabaseConnection(): void
    {
        $defaultName = (string) config('database.default');
        $default = config('database.connections.'.$defaultName, []);
        $sidikma = config('database.connections.sidikma_induk', []);

        $driver = (string) ($default['driver'] ?? '');
        $keys = in_array($driver, ['mysql', 'mariadb', 'pgsql', 'sqlsrv'], true)
            ? ['driver', 'host', 'port', 'database', 'username', 'unix_socket']
            : ['driver', 'database'];

        foreach ($keys as $key) {
            if (($default[$key] ?? null) !== ($sidikma[$key] ?? null)) {
                throw ValidationException::withMessages([
                    'database' => 'Koneksi autentikasi dan SIDIKMA berbeda; penolakan dihentikan untuk mencegah transaksi parsial.',
                ]);
            }
        }
    }
}
