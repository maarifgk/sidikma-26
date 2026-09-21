<?php

namespace App\Models;

use App\Models\Concerns\AddsAuditContext;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['school_id','submitted_by','processed_by','approved_by','request_number','request_date','decree_number','decree_date','subject_name','correction_part','old_data','new_data','reason','old_decree_path','supporting_document_path','corrected_decree_path','status','admin_notes','submitted_at','processed_at','approved_at'])]
class DecreeCorrectionRequest extends Model
{
    use AddsAuditContext, LogsActivity, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'diajukan';
    public const STATUS_PROCESSING = 'diproses';
    public const STATUS_REVISION = 'revisi';
    public const STATUS_APPROVED = 'disetujui';
    public const STATUS_REJECTED = 'ditolak';

    public static function statusOptions(): array { return ['draft'=>'Draft','diajukan'=>'Diajukan','diproses'=>'Sedang Diproses','revisi'=>'Revisi','disetujui'=>'Proses Selesai','ditolak'=>'Ditolak']; }
    public static function correctionPartOptions(): array { return ['nama'=>'Nama','nip'=>'NIP','tempat_lahir'=>'Tempat Lahir','tanggal_lahir'=>'Tanggal Lahir','jabatan'=>'Jabatan','tmt'=>'TMT','nama_madrasah'=>'Sekolah/Madrasah','alamat'=>'Alamat','lainnya'=>'Lainnya']; }
    public static function statusColor(string $status): string { return match ($status) { 'diajukan','revisi'=>'warning','diproses'=>'info','disetujui'=>'success','ditolak'=>'danger',default=>'gray' }; }
    public static function generateRequestNumber(): string
    {
        $prefix = 'PSK/'.now()->format('Y/m').'/';
        $lastNumber = static::withTrashed()->where('request_number', 'like', $prefix.'%')->orderByDesc('request_number')->value('request_number');
        $last = filled($lastNumber) ? (int) str($lastNumber)->afterLast('/')->toString() : 0;
        return $prefix.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }
    public function scopeAccessibleTo(Builder $query, User $user): Builder { return $user->isAdminInduk() ? $query : $query->whereIn('school_id', $user->accessibleSchoolIds()); }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function processor(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    protected function casts(): array { return ['request_date'=>'date','decree_date'=>'date','submitted_at'=>'datetime','processed_at'=>'datetime','approved_at'=>'datetime']; }
    public function getActivitylogOptions(): LogOptions { return LogOptions::defaults()->useLogName('decree-correction')->logFillable()->logOnlyDirty(); }
}
