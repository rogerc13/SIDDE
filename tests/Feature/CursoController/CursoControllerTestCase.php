<?php

namespace Tests\Feature\CursoController;

use App\Models\Category;
use App\Models\Course;
use App\Models\Modality;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class CursoControllerTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\IdTypeSeeder::class);
        $this->seed(\Database\Seeders\TypeSeeder::class);
    }

    protected function createUser(int $roleId): User
    {
        return User::factory()->create(['role_id' => $roleId]);
    }

    protected function adminUser(): User
    {
        return $this->createUser(Role::ADMINISTRADOR);
    }

    protected function tecEducativaUser(): User
    {
        return $this->createUser(Role::TECNOLOGIA_EDUCATIVA);
    }

    protected function programadorUser(): User
    {
        return $this->createUser(Role::PROGRAMADOR);
    }

    protected function facilitadorUser(): User
    {
        return $this->createUser(Role::FACILITADOR);
    }

    protected function participanteUser(): User
    {
        return $this->createUser(Role::PARTICIPANTE);
    }

    protected function createCourse(array $overrides = []): Course
    {
        $category = Category::factory()->create();
        $modality = Modality::factory()->create();

        return Course::factory()->create(array_merge([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
        ], $overrides));
    }
}
