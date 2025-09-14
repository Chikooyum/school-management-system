<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // database/factories/StudentFactory.php
public function definition(): array
{
    return [
        'name' => fake()->name(),
        'enrollment_year' => 2025, // Anda bisa ubah sesuai kebutuhan
        'date_of_birth' => fake()->dateTimeBetween('2020-01-01', '2021-12-31')->format('Y-m-d'),
        'mother_name' => fake()->name('female'),
        'mother_date_of_birth' => fake()->dateTimeBetween('1985-01-01', '1995-12-31')->format('Y-m-d'),
        'phone_number' => fake()->phoneNumber(),
        'address' => fake()->address(),
        'registration_wave' => fake()->numberBetween(1, 3),
        'is_alumni_sibling' => fake()->boolean(10), // 10% kemungkinan dapat diskon
        'status' => 'Aktif',
    ];
}
}
