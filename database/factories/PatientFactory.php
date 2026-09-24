<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Rules\Rut;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        $number = (string) fake()->unique()->numberBetween(5_000_000, 25_000_000);

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'rut' => $number.'-'.Rut::checkDigit($number),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->streetAddress(),
            'birthdate' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(array_keys(Patient::GENDERS)),
            'image' => '0.jpg',
        ];
    }

    public function born(string $date): static
    {
        return $this->state(['birthdate' => $date]);
    }
}
