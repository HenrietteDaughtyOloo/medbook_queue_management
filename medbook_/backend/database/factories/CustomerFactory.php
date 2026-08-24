<?php

namespace Database\Factories;

use App\Enums\CustomerStatus;
use App\Enums\Priority;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'service' => fake()->randomElement([
                'Consultation', 
                'Prescription collection', 
                'Lab results'
                ]),
            'arrival_at' => now()->subMinutes(fake()->numberBetween(1, 120)),
            'original_priority' => Priority::NORMAL,
            'status' => CustomerStatus::Waiting,
        ];
    }
}