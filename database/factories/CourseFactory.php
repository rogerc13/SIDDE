<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('???-####')),
            'title' => fake()->sentence(3),
            'objective' => fake()->paragraph(),
            'duration' => fake()->numberBetween(8, 80),
            'addressed' => fake()->sentence(),
        ];
    }
}
