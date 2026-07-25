<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'id_number' => fake()->unique()->numerify('########'),
            'id_type_id' => 1,
            'phone' => fake()->phoneNumber(),
            'sex' => fake()->randomElement(['M', 'F']),
        ];
    }
}
