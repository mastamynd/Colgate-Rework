<?php

namespace Database\Factories;

use App\Models\MapData;
use App\Models\MapDataRow;
use Illuminate\Database\Eloquent\Factories\Factory;

class MapDataRowFactory extends Factory
{
    protected $model = MapDataRow::class;

    public function definition(): array
    {
        return [
            'map_data_id' => MapData::factory(),
            'data' => [
                'latitude' => $this->faker->latitude(),
                'longitude' => $this->faker->longitude(),
                'name' => $this->faker->words(2, true),
                'description' => $this->faker->sentence(),
            ],
        ];
    }
}