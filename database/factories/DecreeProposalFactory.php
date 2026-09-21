<?php

namespace Database\Factories;

use App\Models\DecreeProposal;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DecreeProposal> */
class DecreeProposalFactory extends Factory
{
    protected $model = DecreeProposal::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'status' => DecreeProposal::STATUS_SUBMITTED,
            'notes' => fake()->optional()->sentence(),
            'submitted_by' => User::factory(),
        ];
    }
}
