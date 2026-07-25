<?php

namespace Database\Factories;

use App\Models\Modality;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModalityFactory extends Factory
{
    protected $model = Modality::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
        ];
    }
}
