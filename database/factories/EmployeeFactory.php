<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'foundation_id' => null,
            'school_id' => null,
            'user_id' => null,
            'employee_code' => fake()->unique()->bothify('PEG-####-????'),
            'name' => fake()->name(),
            'avatar_path' => null,
            'nik' => fake()->unique()->numerify('################'),
            'nip' => null,
            'nuptk' => null,
            'employee_type' => fake()->randomElement([
                Employee::TYPE_GURU,
                Employee::TYPE_PEGAWAI,
            ]),
            'employment_status' => fake()->randomElement([
                'GTY',
                'GTT',
                'Pegawai Tetap Yayasan',
                'Pegawai Tidak Tetap',
                'PNS',
                'PNS_SERTIFIKASI',
            ]),
            'last_education' => fake()->randomElement(['SMA/MA', 'D3', 'S1', 'S2']),
            'program_study' => fake()->optional()->randomElement([
                'Pendidikan Agama Islam',
                'Pendidikan Guru Madrasah Ibtidaiyah',
                'Manajemen Pendidikan',
            ]),
            'gender' => fake()->randomElement([
                Employee::GENDER_MALE,
                Employee::GENDER_FEMALE,
            ]),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
