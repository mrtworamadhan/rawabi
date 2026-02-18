<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->word()),
            'stock_quantity' => $this->faker->numberBetween(0, 500),
            // Asumsi ada kolom type/category, sesuaikan kalau tidak ada
             'type' => $this->faker->randomElement(['equipment', 'clothing']), 
        ];
    }
}