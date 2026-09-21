<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    /** @return array<class-string, string> */
    public static function subjectTypeOptions(): array
    {
        return [
            User::class => 'User',
            Role::class => 'Role',
            Permission::class => 'Permission',
            School::class => 'Sekolah/Madrasah',
            Employee::class => 'Guru/Pegawai',
            ApprovalRequest::class => 'Approval',
        ];
    }

    public function subjectTypeLabel(): string
    {
        return self::subjectTypeOptions()[$this->subject_type]
            ?? class_basename((string) $this->subject_type);
    }

    public function subjectLabel(): string
    {
        return match (true) {
            $this->subject instanceof User => $this->subject->name,
            $this->subject instanceof Role => $this->subject->name,
            $this->subject instanceof Permission => $this->subject->name,
            $this->subject instanceof School => $this->subject->name,
            $this->subject instanceof Employee => $this->subject->name,
            $this->subject instanceof ApprovalRequest => "Approval #{$this->subject->getKey()}",
            $this->subject !== null => class_basename($this->subject).' #'.$this->subject->getKey(),
            default => $this->subject_type
                ? class_basename($this->subject_type)." #{$this->subject_id} (dihapus)"
                : 'Aktivitas sistem',
        };
    }

    public function causerLabel(): string
    {
        return match (true) {
            $this->causer instanceof User => $this->causer->name,
            $this->causer !== null => class_basename($this->causer).' #'.$this->causer->getKey(),
            default => 'Sistem',
        };
    }

    /** @return array<string, mixed> */
    public function oldValues(): array
    {
        return $this->properties?->get('old', []) ?? [];
    }

    /** @return array<string, mixed> */
    public function newValues(): array
    {
        return $this->properties?->get('attributes', []) ?? [];
    }
}
