<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

#[Fillable([
    'owner_name',
    'phone',
    'short_title',
    'application_name',
    'logo_path',
    'copyright_text',
    'version',
    'whatsapp_token',
    'server_key',
    'client_key',
    'address',
])]
class ApplicationSetting extends Model
{
    /** @return array<string, string|null> */
    public static function defaults(): array
    {
        return [
            'owner_name' => "L.P. Ma'arif NU PCNU Gunungkidul",
            'phone' => '085122016369',
            'short_title' => 'SiDIKMa-GK',
            'application_name' => "Sistem Data dan Informasi Kelembagaan Ma'arif NU Gunungkidul",
            'logo_path' => null,
            'copyright_text' => 'Copy Right © SiDIKMa-GK',
            'version' => '1.0.0',
            'whatsapp_token' => null,
            'server_key' => null,
            'client_key' => null,
            'address' => 'Jl. Tentara Pelajar, Trimulyo I, Kepek, Wonosari, Gunungkidul, Yogyakarta 55813',
        ];
    }

    public static function current(): self
    {
        if (! Schema::hasTable('application_settings')) {
            return new self(self::defaults());
        }

        return self::query()->firstOrCreate([], self::defaults());
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'whatsapp_token' => 'encrypted',
            'server_key' => 'encrypted',
            'client_key' => 'encrypted',
        ];
    }
}
