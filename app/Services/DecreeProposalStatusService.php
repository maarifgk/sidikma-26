<?php

namespace App\Services;

use App\Models\DecreeProposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecreeProposalStatusService
{
    public function update(DecreeProposal $proposal, User $actor, string $status, ?string $reason = null): DecreeProposal
    {
        $status = trim($status);
        $reason = $reason !== null ? trim($reason) : null;
        if (! array_key_exists($status, DecreeProposal::statusOptions())) {
            throw ValidationException::withMessages(['status' => 'Status pengajuan tidak valid.']);
        }
        if ($status === DecreeProposal::STATUS_REJECTED && blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'Keterangan penolakan wajib diisi.']);
        }
        if ($reason !== null && strip_tags($reason) !== $reason) {
            throw ValidationException::withMessages(['reason' => 'Keterangan tidak boleh berisi HTML atau script.']);
        }

        $connection = (string) config('database.default');
        return DB::connection($connection)->transaction(function () use ($proposal, $actor, $status, $reason, $connection): DecreeProposal {
            $locked = DecreeProposal::on($connection)->whereKey($proposal->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($actor->can('updateStatus', $locked), 403);

            $payload = json_decode((string) $locked->notes, true);
            $payload = is_array($payload) ? $payload : [];
            if (array_key_exists('initial', $payload) === false) {
                $payload['initial'] = $locked->initialNotes();
            }
            if ($reason !== null) {
                $payload['decision_reason'] = $reason;
                if ($status === DecreeProposal::STATUS_REJECTED) {
                    $payload['rejection_reason'] = $reason;
                }
            }
            $payload['decision_status'] = $status;
            $payload['decision_updated_at'] = now()->toISOString();
            $payload['decision_updated_by'] = $actor->getKey();

            $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            if (strlen($encoded) > 65535) {
                throw ValidationException::withMessages(['reason' => 'Keterangan melebihi kapasitas kolom notes.']);
            }

            $locked->notes = $encoded;
            $locked->status = $status;
            $locked->save();
            return $locked;
        });
    }
}
