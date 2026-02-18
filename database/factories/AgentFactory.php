<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Travel',
            'phone' => $this->faker->unique()->numerify('08##-####-####'), 
            'address' => $this->faker->address(),
            'commission_amount' => $this->faker->randomElement([1000000, 1500000, 2000000]),
        ];
    }
}