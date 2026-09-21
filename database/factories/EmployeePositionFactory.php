<?php

namespace Database\Factories;

use App\Models\EmployeePosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeePosition>
 */
class EmployeePositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $positions = [
            [EmployeePosition::CATEGORY_TEACHING, 'Guru'],
            [EmployeePosition::CATEGORY_STRUCTURAL, 'Kepala Sekolah'],
            [EmployeePosition::CATEGORY_STRUCTURAL, 'Wakil Kepala Sekolah'],
            [EmployeePosition::CATEGORY_ADMINISTRATIVE, 'Bendahara'],
            [EmployeePosition::CATEGORY_ADMINISTRATIVE, 'Staf Administrasi'],
            [EmployeePosition::CATEGORY_SUPPORT, 'Tenaga Pendukung'],
        ];
        [$category, $name] = fake()->randomElement($positions);

        return [
            'code' => fake()->unique()->bothify('JBT-####-????'),
            'name' => $name,
            'category' => $category,
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
