<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Floor;
use Illuminate\Database\Eloquent\Factories\Factory;

class FloorFactory extends Factory
{
    protected $model = Floor::class;

    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'name' => 'Piso ' . $this->faker->randomNumber(2),
        ];
    }
}
