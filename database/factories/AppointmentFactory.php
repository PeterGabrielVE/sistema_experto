<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'scheduled_at' => now()->addWeek()->setTime(13, 0),
            'status' => Appointment::SCHEDULED,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(['status' => Appointment::CANCELLED, 'cancelled_at' => now()]);
    }
}
