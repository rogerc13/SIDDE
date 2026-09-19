<?php

namespace Tests\Feature\CategoryController;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\IdTypeSeeder::class);
    }

    protected function createUser(int $roleId): User
    {
        return User::factory()->create(['role_id' => $roleId]);
    }

    protected function adminUser(): User
    {
        return $this->createUser(Role::ADMINISTRADOR);
    }

    public function test_admin_can_list_categories(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get('/u/areas');

        $response->assertStatus(200);
    }

    public function test_categories_index_filter_input_renders_placeholder(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get('/u/areas');

        $response->assertOk();
        $response->assertSee('placeholder="Buscar por nombre de área"', false);
    }
}
