<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApprovalWorkflow
{
    public function createAndSubmit(Model $approvable, User $actor, ?string $notes = null): ApprovalRequest
    {
        Gate::forUser($actor)->authorize('create', ApprovalRequest::class);

        if (! array_key_exists($approvable::class, ApprovalRequest::approvableTypeOptions())) {
            throw ValidationException::withMessages([
                'approvable' => 'Jenis data ini belum mendukung approval.',
            ]);
        }

        if (! $approvable->exists || ! method_exists($approvable, 'approvalRequests')) {
            throw ValidationException::withMessages([
                'approvable' => 'Data yang akan diajukan belum tersimpan.',
            ]);
        }

        return DB::transaction(function () use ($approvable, $actor, $notes): ApprovalRequest {
            $hasOpenRequest = $approvable->approvalRequests()
                ->whereIn('status', ApprovalRequest::openStatuses())
                ->lockForUpdate()
                ->exists();

            if ($hasOpenRequest) {
                throw ValidationException::withMessages([
                    'approvable' => 'Data ini masih memiliki proses approval yang aktif.',
                ]);
            }

            $request = $approvable->approvalRequests()->create();

            Gate::forUser($actor)->authorize('submit', $request);

            return $request->submit($actor, $notes);
        });
    }
}
