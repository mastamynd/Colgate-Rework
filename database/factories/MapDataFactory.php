<?php

namespace Database\Factories;

use App\Models\MapData;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MapDataFactory extends Factory
{
    protected $model = MapData::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['point', 'line', 'polygon', 'multipoint', 'multiline', 'multipolygon']),
            'user_id' => User::factory(),
            'data' => null,
            'data_table' => null,
            'data_columns' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}