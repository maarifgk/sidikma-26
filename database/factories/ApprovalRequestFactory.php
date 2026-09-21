<?php

namespace Database\Factories;

use App\Models\ApprovalRequest;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalRequest>
 */
class ApprovalRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approvable_type' => Document::class,
            'approvable_id' => Document::factory(),
            'status' => ApprovalRequest::STATUS_DRAFT,
            'submitted_by' => null,
            'verified_by' => null,
            'decided_by' => null,
            'submitted_at' => null,
            'verified_at' => null,
            'decided_at' => null,
            'submission_notes' => null,
            'verification_notes' => null,
            'decision_notes' => null,
        ];
    }
}
