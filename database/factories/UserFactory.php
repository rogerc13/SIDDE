<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $person = Person::factory()->create();

        return [
            'role_id' => 1,
            'person_id' => $person->id,
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => 1,
        ]);
    }

    public function tecEducativa(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => 2,
        ]);
    }

    public function programador(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => 3,
        ]);
    }

    public function facilitador(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => 4,
        ]);
    }

    public function participante(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => 5,
        ]);
    }
}
