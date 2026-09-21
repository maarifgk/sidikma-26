<?php

namespace App\Models;

use App\Models\Concerns\AddsAuditContext;
use App\Services\ApplicationNotificationService;
use Database\Factories\ApprovalRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable([
    'approvable_type',
    'approvable_id',
    'status',
    'submitted_by',
    'verified_by',
    'decided_by',
    'submitted_at',
    'verified_at',
    'decided_at',
    'submission_notes',
    'verification_notes',
    'decision_notes',
])]
class ApprovalRequest extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /** @use HasFactory<ApprovalRequestFactory> */
    use AddsAuditContext, HasFactory, LogsActivity, SoftDeletes;

    /** @var array<int, string> */
    protected static $recordEvents = ['created', 'deleted', 'restored'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    private bool $stateTransitionInProgress = false;

    /**
     * Get the model submitted for approval.
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who submitted the request.
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Get the user who verified the request.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the user who approved or rejected the request.
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function submit(User $actor, ?string $notes = null): self
    {
        return $this->transitionTo(self::STATUS_SUBMITTED, $actor, $notes);
    }

    public function verify(User $actor, ?string $notes = null): self
    {
        return $this->transitionTo(self::STATUS_VERIFIED, $actor, $notes);
    }

    public function approve(User $actor, ?string $notes = null): self
    {
        return $this->transitionTo(self::STATUS_APPROVED, $actor, $notes);
    }

    public function reject(User $actor, string $notes): self
    {
        if (blank($notes)) {
            throw ValidationException::withMessages([
                'decision_notes' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        return $this->transitionTo(self::STATUS_REJECTED, $actor, $notes);
    }

    public function canTransitionTo(string $nextStatus): bool
    {
        return in_array($nextStatus, self::allowedTransitions()[$this->status] ?? [], true);
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Diajukan',
            self::STATUS_VERIFIED => 'Terverifikasi',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
        ];
    }

    /** @return array<class-string<Model>, string> */
    public static function approvableTypeOptions(): array
    {
        return [
            Document::class => 'Dokumen',
            Employee::class => 'Guru/Pegawai',
            School::class => 'Sekolah/Madrasah',
            Foundation::class => 'Yayasan',
        ];
    }

    /** @return array<int, string> */
    public static function openStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_VERIFIED,
        ];
    }

    public function approvableLabel(): string
    {
        return match (true) {
            $this->approvable instanceof Document => $this->approvable->original_name,
            $this->approvable instanceof Employee => $this->approvable->name,
            $this->approvable instanceof School => $this->approvable->name,
            $this->approvable instanceof Foundation => $this->approvable->name,
            default => "Data #{$this->approvable_id}",
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('approval')
            ->logOnly([
                'approvable_type',
                'approvable_id',
                'status',
                'submitted_by',
                'verified_by',
                'decided_by',
                'submitted_at',
                'verified_at',
                'decided_at',
                'submission_notes',
                'verification_notes',
                'decision_notes',
            ])
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn (string $event): string => "Approval {$event}",
            );
    }

    /** @return array<string, array<int, string>> */
    public static function allowedTransitions(): array
    {
        return [
            self::STATUS_DRAFT => [self::STATUS_SUBMITTED],
            self::STATUS_SUBMITTED => [self::STATUS_VERIFIED, self::STATUS_REJECTED],
            self::STATUS_VERIFIED => [self::STATUS_APPROVED, self::STATUS_REJECTED],
            self::STATUS_APPROVED => [],
            self::STATUS_REJECTED => [],
        ];
    }

    /**
     * Register safeguards that force status changes through the state machine.
     */
    protected static function booted(): void
    {
        static::creating(function (ApprovalRequest $request): void {
            if ($request->status !== self::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Permohonan approval baru wajib berstatus draft.',
                ]);
            }
        });

        static::updating(function (ApprovalRequest $request): void {
            if ($request->isDirty('status') && ! $request->stateTransitionInProgress) {
                throw ValidationException::withMessages([
                    'status' => 'Perubahan status wajib melalui alur approval.',
                ]);
            }
        });
    }

    private function transitionTo(string $nextStatus, User $actor, ?string $notes): self
    {
        if (! $this->exists || ! $actor->exists) {
            throw ValidationException::withMessages([
                'status' => 'Permohonan dan pelaku approval harus sudah tersimpan.',
            ]);
        }

        $request = DB::transaction(function () use ($nextStatus, $actor, $notes): self {
            $request = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if (! $request->canTransitionTo($nextStatus)) {
                throw ValidationException::withMessages([
                    'status' => "Status {$request->status} tidak dapat diubah menjadi {$nextStatus}.",
                ]);
            }

            $previousStatus = $request->status;
            $request->stateTransitionInProgress = true;
            $request->status = $nextStatus;

            match ($nextStatus) {
                self::STATUS_SUBMITTED => $request->forceFill([
                    'submitted_by' => $actor->getKey(),
                    'submitted_at' => now(),
                    'submission_notes' => $notes,
                ]),
                self::STATUS_VERIFIED => $request->forceFill([
                    'verified_by' => $actor->getKey(),
                    'verified_at' => now(),
                    'verification_notes' => $notes,
                ]),
                self::STATUS_APPROVED, self::STATUS_REJECTED => $request->forceFill([
                    'decided_by' => $actor->getKey(),
                    'decided_at' => now(),
                    'decision_notes' => $notes,
                ]),
                default => null,
            };

            try {
                $request->save();
            } finally {
                $request->stateTransitionInProgress = false;
            }

            activity('approval')
                ->event($nextStatus)
                ->performedOn($request)
                ->causedBy($actor)
                ->withProperties([
                    'old' => ['status' => $previousStatus],
                    'attributes' => [
                        'status' => $nextStatus,
                        'actor_id' => $actor->getKey(),
                        'notes' => $notes,
                    ],
                ])
                ->log(match ($nextStatus) {
                    self::STATUS_SUBMITTED => 'Approval diajukan',
                    self::STATUS_VERIFIED => 'Approval diverifikasi',
                    self::STATUS_APPROVED => 'Approval disetujui',
                    self::STATUS_REJECTED => 'Approval ditolak',
                    default => 'Status approval berubah',
                });

            $this->setRawAttributes($request->getAttributes(), true);

            return $this;
        });

        app(ApplicationNotificationService::class)->notifyApprovalStatus($request, $actor);

        return $request;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }
}
