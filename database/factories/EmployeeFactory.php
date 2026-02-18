<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        // Gender random buat nentuin nama
        $gender = $this->faker->randomElement(['pria', 'wanita']);
        $name = $gender === 'pria' ? $this->faker->name('male') : $this->faker->name('female');

        return [
            'user_id' => User::factory(), // Otomatis create user
            'nik_karyawan' => $this->faker->unique()->numerify('RW-####'),
            'full_name' => $name,
            'nickname' => explode(' ', $name)[0],
            'place_of_birth' => $this->faker->city(),
            'date_of_birth' => $this->faker->date('Y-m-d', '2000-01-01'),
            'gender' => $gender,
            'phone_number' => $this->faker->phoneNumber(),
            'address_ktp' => $this->faker->address(),
            'department' => $this->faker->jobTitle(), // Nanti di override seeder
            'position' => 'Staff',
            'join_date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['probation', 'contract', 'permanent']),
        ];
    }
}