<?php

namespace Tests\Feature\UserController;

use App\Models\IdType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
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

    protected function nonAdminUser(): User
    {
        return $this->createUser(Role::PROGRAMADOR);
    }

    protected function userData(): array
    {
        $idType = IdType::first();
        return [
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'ci' => '12345678',
            'id_type' => $idType->id,
            'rol' => Role::PARTICIPANTE,
            'email' => fake()->unique()->safeEmail(),
            'phone' => '5551234567',
            'sex' => 'Masculino',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }

    // ==================== getAll ====================

    public function test_admin_can_list_users(): void
    {
        $this->createUser(Role::PARTICIPANTE);

        $response = $this->actingAs($this->adminUser())
            ->get('/u/usuarios');

        $response->assertStatus(200);
    }

    public function test_usuarios_index_filter_inputs_render_placeholders(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get('/u/usuarios');

        $response->assertOk();
        $response->assertSee('placeholder="Buscar por nombre"', false);
        $response->assertSee('placeholder="Buscar por apellido"', false);
        $response->assertSee('placeholder="Buscar por cédula"', false);
    }

    public function test_usuario_modals_render_refreshed_layout(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get('/u/usuarios');

        $response->assertOk();
        $response->assertSee('id="usuario-modal"', false);
        $response->assertSee('sidde-modal', false);
        $response->assertSee('fa fa-users', false);
        $response->assertSee('placeholder="Ej: Juan"', false);
        $response->assertSee('placeholder="Ej: juan.perez@pdvsa.com"', false);
        $response->assertSee('sidde-modal-danger', false);
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $response = $this->actingAs($this->nonAdminUser())
            ->get('/u/usuarios');

        $response->assertRedirect();
    }

    // ==================== get ====================

    public function test_admin_can_get_user(): void
    {
        $user = $this->createUser(Role::PARTICIPANTE);

        $response = $this->actingAs($this->adminUser())
            ->get("/u/usuarios/{$user->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $user->id]);
    }

    public function test_non_admin_cannot_get_user(): void
    {
        $user = $this->createUser(Role::PARTICIPANTE);

        $response = $this->actingAs($this->nonAdminUser())
            ->get("/u/usuarios/{$user->id}");

        $response->assertStatus(404);
    }

    public function test_get_nonexistent_user_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get('/u/usuarios/9999');

        $response->assertStatus(404);
    }

    // ==================== store ====================

    public function test_admin_can_store_user(): void
    {
        $data = $this->userData();

        $response = $this->actingAs($this->adminUser())
            ->post('/u/usuarios', $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => $data['email']]);
        $this->assertDatabaseHas('people', ['name' => $data['nombre'], 'last_name' => $data['apellido']]);
    }

    public function test_non_admin_cannot_store_user(): void
    {
        $data = $this->userData();

        $response = $this->actingAs($this->nonAdminUser())
            ->post('/u/usuarios', $data);

        $response->assertRedirect();

        $this->assertDatabaseMissing('users', ['email' => $data['email']]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post('/u/usuarios', []);

        $response->assertSessionHasErrors(['nombre', 'apellido', 'ci', 'id_type', 'email', 'sex', 'password']);
    }

    // ==================== update ====================

    public function test_admin_can_update_user(): void
    {
        $user = $this->createUser(Role::PARTICIPANTE);

        $data = $this->userData();
        $data['email'] = 'updated@example.com';
        $data['nombre'] = 'Updated';

        $response = $this->actingAs($this->adminUser())
            ->put("/u/usuarios/{$user->id}", $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'updated@example.com']);
        $this->assertDatabaseHas('people', ['id' => $user->person_id, 'name' => 'Updated']);
    }

    public function test_non_admin_cannot_update_user(): void
    {
        $user = $this->createUser(Role::PARTICIPANTE);
        $originalEmail = $user->email;

        $data = $this->userData();
        $data['email'] = 'hacked@example.com';

        $response = $this->actingAs($this->nonAdminUser())
            ->put("/u/usuarios/{$user->id}", $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $originalEmail]);
    }

    public function test_update_nonexistent_user_returns_error(): void
    {
        $data = $this->userData();

        $response = $this->actingAs($this->adminUser())
            ->put('/u/usuarios/9999', $data);

        $response->assertRedirect();
    }

    // ==================== delete ====================

    public function test_admin_can_delete_participante_user(): void
    {
        $user = $this->createUser(Role::PARTICIPANTE);

        $response = $this->actingAs($this->adminUser())
            ->delete("/u/usuarios/{$user->id}");

        $response->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_non_admin_cannot_delete_user(): void
    {
        $user = $this->createUser(Role::PARTICIPANTE);

        $response = $this->actingAs($this->nonAdminUser())
            ->delete("/u/usuarios/{$user->id}");

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_cannot_delete_admin_user(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($this->adminUser())
            ->delete("/u/usuarios/{$admin->id}");

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_delete_nonexistent_user_returns_error(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->delete('/u/usuarios/9999');

        $response->assertRedirect();
    }
}
