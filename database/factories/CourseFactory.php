<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Category;
use App\Models\Modality;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('???-####')),
            'title' => fake()->sentence(3),
            'category_id' => Category::factory(),
            'modality_id' => Modality::factory(),
            'objective' => fake()->paragraph(),
            'duration' => fake()->numberBetween(8, 80),
            'addressed' => fake()->sentence(),
        ];
    }
}
