<?php

namespace App\Models;

use Database\Factories\EmployeePositionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code',
    'name',
    'category',
    'description',
    'is_active',
])]
class EmployeePosition extends Model
{
    public const CATEGORY_TEACHING = 'pengajar';

    public const CATEGORY_STRUCTURAL = 'struktural';

    public const CATEGORY_ADMINISTRATIVE = 'administrasi';

    public const CATEGORY_SUPPORT = 'pendukung';

    /** @use HasFactory<EmployeePositionFactory> */
    use HasFactory, SoftDeletes;

    /** @return array<int, array{code: string, name: string, category: string}> */
    public static function defaultPositions(): array
    {
        $teaching = [
            'GURU-KELAS' => 'Mengajar Guru Kelas',
            'MAPEL-FIKIH' => 'Mengajar Mapel Fikih',
            'PAI' => 'Mengajar PAI',
            'MAPEL-BAHASA-ARAB' => 'Mengajar Mapel Bahasa Arab',
            'MAPEL-AKIDAH-AKHLAK' => 'Mengajar Mapel Akidah Akhlak',
            'MAPEL-QURAN-HADIS' => "Mengajar Mapel Qur'an Hadis",
            'MAPEL-MATEMATIKA' => 'Mengajar Mapel Matematika',
            'MAPEL-BAHASA-INDONESIA' => 'Mengajar Mapel Bahasa Indonesia',
            'MAPEL-SKI' => 'Mengajar Mapel SKI',
            'PJOK' => 'Mengajar PJOK',
            'BAHASA-JAWA' => 'Mengajar Bahasa Jawa',
            'MAPEL-BAHASA-INGGRIS' => 'Mengajar Mapel Bahasa Inggris',
            'MAPEL-IPA' => 'Mengajar Mapel IPA',
            'MAPEL-IPS' => 'Mengajar Mapel IPS',
            'MAPEL-PKN' => 'Mengajar Mapel PKN',
            'MAPEL-SBK' => 'Mengajar Mapel SBK',
            'TIK-PRAKARYA' => 'Mengajar TIK/Prakarya',
            'GURU-BK' => 'Mengajar Guru BK',
            'KE-NU-AN' => 'Mengajar Ke NU an',
        ];

        $positions = collect($teaching)
            ->map(fn (string $name, string $code): array => compact('code', 'name') + [
                'category' => self::CATEGORY_TEACHING,
            ]);

        return $positions->concat([
            ['code' => 'TENAGA-ADMINISTRASI', 'name' => 'Tenaga Administrasi', 'category' => self::CATEGORY_ADMINISTRATIVE],
            ['code' => 'KEPALA-SEKOLAH', 'name' => 'Kepala Madrasah/Sekolah', 'category' => self::CATEGORY_STRUCTURAL],
            ['code' => 'PENJAGA-SEKOLAH', 'name' => 'Penjaga Sekolah/Madrasah', 'category' => self::CATEGORY_SUPPORT],
        ])->values()->all();
    }

    /**
     * Get all assignments that use this position.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
