<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'document_type' => 'dokumen-pegawai',
            'owner_type' => Employee::class,
            'owner_id' => Employee::factory(),
            'disk' => Document::PRIVATE_DISK,
            'path' => "employees/{$uuid}.pdf",
            'original_name' => "dokumen-{$uuid}.pdf",
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1024, 5 * 1024 * 1024),
            'checksum' => hash('sha256', $uuid),
            'status' => Document::STATUS_ACTIVE,
            'uploaded_by' => User::factory(),
        ];
    }
}
