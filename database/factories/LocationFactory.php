<?php

namespace Database\Factories;

use App\Models\Floor;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'floor_id' => Floor::factory(),
            'name' => 'Aula ' . $this->faker->unique()->bothify('###'),
        ];
    }
}
