<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePosition;
use App\Models\Foundation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeAssignment>
 */
class EmployeeAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'employee_position_id' => EmployeePosition::factory(),
            'foundation_id' => Foundation::factory(),
            'school_id' => null,
            'start_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'end_date' => null,
            'decree_number' => fake()->optional()->bothify('SK-####/????'),
            'decree_date' => fake()->optional()->dateTimeBetween('-5 years', 'now')?->format('Y-m-d'),
            'decree_period' => fake()->optional()->randomElement(['2025/2026', '2026/2027']),
            'status' => EmployeeAssignment::STATUS_ACTIVE,
            'is_primary' => false,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
