<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JamaahFactory extends Factory
{
    public function definition(): array
    {
        $gender = $this->faker->randomElement(['pria', 'wanita']);
        
        return [
            'user_id' => User::factory(),
            'nik' => $this->faker->unique()->numerify('################'), // 16 digit
            'name' => $gender === 'pria' ? $this->faker->name('male') : $this->faker->name('female'),
            'gender' => $gender,
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'passport_number' => strtoupper($this->faker->bothify('?#######')),
            'passport_expiry' => $this->faker->dateTimeBetween('+1 years', '+5 years'),
            'shirt_size' => $this->faker->randomElement(['S', 'M', 'L', 'XL', 'XXL']),
        ];
    }
}