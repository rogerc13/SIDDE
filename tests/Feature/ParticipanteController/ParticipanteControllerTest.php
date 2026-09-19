<?php

namespace Tests\Feature\ParticipanteController;

use App\Models\IdType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipanteControllerTest extends TestCase
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

    protected function programadorUser(): User
    {
        return $this->createUser(Role::PROGRAMADOR);
    }

    protected function tecEducativaUser(): User
    {
        return $this->createUser(Role::TECNOLOGIA_EDUCATIVA);
    }

    protected function createParticipanteUser(): User
    {
        return $this->createUser(Role::PARTICIPANTE);
    }

    protected function userData(): array
    {
        $idType = IdType::first();
        return [
            'nombre' => 'Carlos',
            'apellido' => 'Lopez',
            'ci' => '11223344',
            'id_type' => $idType->id,
            'email' => fake()->unique()->safeEmail(),
            'phone' => '5554443333',
            'sex' => 'Masculino',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }

    // ==================== getAll ====================

    public function test_admin_can_list_participantes(): void
    {
        $this->createParticipanteUser();

        $response = $this->actingAs($this->adminUser())
            ->get('/u/participantes');

        $response->assertStatus(200);
    }

    public function test_participantes_index_filter_inputs_render_placeholders(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get('/u/participantes');

        $response->assertOk();
        $response->assertSee('placeholder="Buscar por nombre"', false);
        $response->assertSee('placeholder="Buscar por apellido"', false);
        $response->assertSee('placeholder="Buscar por cédula"', false);
    }

    public function test_participante_modals_render_refreshed_layout(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get('/u/participantes');

        $response->assertOk();
        $response->assertSee('id="participante-modal"', false);
        $response->assertSee('sidde-modal', false);
        $response->assertSee('placeholder="Ej: Juan"', false);
        $response->assertSee('placeholder="Ej: juan.perez@pdvsa.com"', false);
        $response->assertSee('sidde-modal-danger', false);
    }

    public function test_programador_can_list_participantes(): void
    {
        $this->createParticipanteUser();

        $response = $this->actingAs($this->programadorUser())
            ->get('/u/participantes');

        $response->assertStatus(200);
    }

    public function test_tec_educativa_cannot_list_participantes(): void
    {
        $response = $this->actingAs($this->tecEducativaUser())
            ->get('/u/participantes');

        $response->assertRedirect();
    }

    // ==================== get ====================

    public function test_admin_can_get_participante(): void
    {
        $user = $this->createParticipanteUser();

        $response = $this->actingAs($this->adminUser())
            ->get("/u/participantes/{$user->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $user->id]);
    }

    public function test_programador_can_get_participante(): void
    {
        $user = $this->createParticipanteUser();

        $response = $this->actingAs($this->programadorUser())
            ->get("/u/participantes/{$user->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $user->id]);
    }

    public function test_tec_educativa_cannot_get_participante(): void
    {
        $user = $this->createParticipanteUser();

        $response = $this->actingAs($this->tecEducativaUser())
            ->get("/u/participantes/{$user->id}");

        $response->assertStatus(404);
    }

    public function test_get_nonexistent_participante_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get('/u/participantes/9999');

        $response->assertStatus(404);
    }

    // ==================== store ====================

    public function test_admin_can_store_participante(): void
    {
        $data = $this->userData();

        $response = $this->actingAs($this->adminUser())
            ->post('/u/participantes', $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => $data['email'], 'role_id' => Role::PARTICIPANTE]);
        $this->assertDatabaseHas('people', ['name' => $data['nombre'], 'last_name' => $data['apellido']]);
    }

    public function test_programador_can_store_participante(): void
    {
        $data = $this->userData();

        $response = $this->actingAs($this->programadorUser())
            ->post('/u/participantes', $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => $data['email'], 'role_id' => Role::PARTICIPANTE]);
    }

    public function test_tec_educativa_cannot_store_participante(): void
    {
        $data = $this->userData();

        $response = $this->actingAs($this->tecEducativaUser())
            ->post('/u/participantes', $data);

        $response->assertRedirect();

        $this->assertDatabaseMissing('users', ['email' => $data['email']]);
    }

    // ==================== update ====================

    public function test_admin_can_update_participante(): void
    {
        $user = $this->createParticipanteUser();

        $data = $this->userData();
        $data['email'] = 'updated.participante@example.com';
        $data['nombre'] = 'Updated';

        $response = $this->actingAs($this->adminUser())
            ->put("/u/participantes/{$user->id}", $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'updated.participante@example.com']);
        $this->assertDatabaseHas('people', ['id' => $user->person_id, 'name' => 'Updated']);
    }

    public function test_programador_can_update_participante(): void
    {
        $user = $this->createParticipanteUser();

        $data = $this->userData();
        $data['email'] = 'prog.updated@example.com';

        $response = $this->actingAs($this->programadorUser())
            ->put("/u/participantes/{$user->id}", $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'prog.updated@example.com']);
    }

    public function test_tec_educativa_cannot_update_participante(): void
    {
        $user = $this->createParticipanteUser();
        $originalEmail = $user->email;

        $data = $this->userData();
        $data['email'] = 'hacked@example.com';

        $response = $this->actingAs($this->tecEducativaUser())
            ->put("/u/participantes/{$user->id}", $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $originalEmail]);
    }

    public function test_update_nonexistent_participante_returns_error(): void
    {
        $data = $this->userData();

        $response = $this->actingAs($this->adminUser())
            ->put('/u/participantes/9999', $data);

        $response->assertRedirect();
    }

    // ==================== delete ====================

    public function test_admin_can_delete_participante(): void
    {
        $user = $this->createParticipanteUser();

        $response = $this->actingAs($this->adminUser())
            ->delete("/u/participantes/{$user->id}");

        $response->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_programador_can_delete_participante(): void
    {
        $user = $this->createParticipanteUser();

        $response = $this->actingAs($this->programadorUser())
            ->delete("/u/participantes/{$user->id}");

        $response->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_tec_educativa_cannot_delete_participante(): void
    {
        $user = $this->createParticipanteUser();

        $response = $this->actingAs($this->tecEducativaUser())
            ->delete("/u/participantes/{$user->id}");

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_delete_nonexistent_participante_returns_error(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->delete('/u/participantes/9999');

        $response->assertRedirect();
    }
}
