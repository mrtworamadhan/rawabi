<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UmrahPackageFactory extends Factory
{
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('now', '+6 months');
        
        return [
            'name' => 'Paket Umrah ' . $this->faker->monthName(),
            'price' => $this->faker->numberBetween(28, 40) * 1000000,
            'target_jamaah' => 45,
            'departure_date' => $date,
            'return_date' => (clone $date)->modify('+9 days'),
            'status' => 'open',
        ];
    }
}