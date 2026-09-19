<?php

namespace Tests\Feature\FacilitadorController;

use App\Models\Facilitator;
use App\Models\IdType;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilitadorControllerTest extends TestCase
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

    protected function tecEducativaUser(): User
    {
        return $this->createUser(Role::TECNOLOGIA_EDUCATIVA);
    }

    protected function programadorUser(): User
    {
        return $this->createUser(Role::PROGRAMADOR);
    }

    protected function createFacilitadorUser(): User
    {
        $user = $this->createUser(Role::FACILITADOR);
        Facilitator::create(['person_id' => $user->person_id]);
        return $user;
    }

    protected function userData(): array
    {
        $idType = IdType::first();
        return [
            'nombre' => 'Maria',
            'apellido' => 'Garcia',
            'ci' => '87654321',
            'id_type' => $idType->id,
            'email' => fake()->unique()->safeEmail(),
            'phone' => '5559876543',
            'sex' => 'Femenino',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }

    // ==================== getAll ====================

    public function test_admin_can_list_facilitadores(): void
    {
        $this->createFacilitadorUser();

        $response = $this->actingAs($this->adminUser())
            ->get('/u/facilitadores');

        $response->assertStatus(200);
    }

    public function test_facilitadores_index_filter_inputs_render_placeholders(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get('/u/facilitadores');

        $response->assertOk();
        $response->assertSee('placeholder="Buscar por nombre"', false);
        $response->assertSee('placeholder="Buscar por apellido"', false);
        $response->assertSee('placeholder="Buscar por cédula"', false);
    }

    public function test_tec_educativa_can_list_facilitadores(): void
    {
        $this->createFacilitadorUser();

        $response = $this->actingAs($this->tecEducativaUser())
            ->get('/u/facilitadores');

        $response->assertStatus(200);
    }

    public function test_programador_cannot_list_facilitadores(): void
    {
        $response = $this->actingAs($this->programadorUser())
            ->get('/u/facilitadores');

        $response->assertRedirect();
    }

    // ==================== get ====================

    public function test_admin_can_get_facilitador(): void
    {
        $user = $this->createFacilitadorUser();

        $response = $this->actingAs($this->adminUser())
            ->get("/u/facilitadores/{$user->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $user->id]);
    }

    public function test_tec_educativa_can_get_facilitador(): void
    {
        $user = $this->createFacilitadorUser();

        $response = $this->actingAs($this->tecEducativaUser())
            ->get("/u/facilitadores/{$user->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $user->id]);
    }

    public function test_programador_cannot_get_facilitador(): void
    {
        $user = $this->createFacilitadorUser();

        $response = $this->actingAs($this->programadorUser())
            ->get("/u/facilitadores/{$user->id}");

        $response->assertStatus(404);
    }

    public function test_get_nonexistent_facilitador_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get('/u/facilitadores/9999');

        $response->assertStatus(404);
    }

    // ==================== store ====================

    public function test_admin_can_store_facilitador(): void
    {
        $data = $this->userData();

        $response = $this->actingAs($this->adminUser())
            ->post('/u/facilitadores', $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => $data['email'], 'role_id' => Role::FACILITADOR]);
        $this->assertDatabaseHas('facilitators', [
            'person_id' => User::where('email', $data['email'])->first()->person_id,
        ]);
    }

    public function test_tec_educativa_can_store_facilitador(): void
    {
        $data = $this->userData();

        $response = $this->actingAs($this->tecEducativaUser())
            ->post('/u/facilitadores', $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => $data['email'], 'role_id' => Role::FACILITADOR]);
    }

    public function test_programador_cannot_store_facilitador(): void
    {
        $data = $this->userData();

        $response = $this->actingAs($this->programadorUser())
            ->post('/u/facilitadores', $data);

        $response->assertRedirect();

        $this->assertDatabaseMissing('users', ['email' => $data['email']]);
    }

    // ==================== update ====================

    public function test_admin_can_update_facilitador(): void
    {
        $user = $this->createFacilitadorUser();

        $data = $this->userData();
        $data['email'] = 'updated.facilitador@example.com';
        $data['nombre'] = 'Updated';

        $response = $this->actingAs($this->adminUser())
            ->put("/u/facilitadores/{$user->id}", $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'updated.facilitador@example.com']);
        $this->assertDatabaseHas('people', ['id' => $user->person_id, 'name' => 'Updated']);
    }

    public function test_tec_educativa_can_update_facilitador(): void
    {
        $user = $this->createFacilitadorUser();

        $data = $this->userData();
        $data['email'] = 'tec.updated@example.com';

        $response = $this->actingAs($this->tecEducativaUser())
            ->put("/u/facilitadores/{$user->id}", $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'tec.updated@example.com']);
    }

    public function test_programador_cannot_update_facilitador(): void
    {
        $user = $this->createFacilitadorUser();
        $originalEmail = $user->email;

        $data = $this->userData();
        $data['email'] = 'hacked@example.com';

        $response = $this->actingAs($this->programadorUser())
            ->put("/u/facilitadores/{$user->id}", $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $originalEmail]);
    }

    public function test_update_nonexistent_facilitador_returns_error(): void
    {
        $data = $this->userData();

        $response = $this->actingAs($this->adminUser())
            ->put('/u/facilitadores/9999', $data);

        $response->assertRedirect();
    }

    // ==================== delete ====================

    public function test_admin_can_delete_facilitador(): void
    {
        $user = $this->createFacilitadorUser();

        $response = $this->actingAs($this->adminUser())
            ->delete("/u/facilitadores/{$user->id}");

        $response->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_tec_educativa_can_delete_facilitador(): void
    {
        $user = $this->createFacilitadorUser();

        $response = $this->actingAs($this->tecEducativaUser())
            ->delete("/u/facilitadores/{$user->id}");

        $response->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_programador_cannot_delete_facilitador(): void
    {
        $user = $this->createFacilitadorUser();

        $response = $this->actingAs($this->programadorUser())
            ->delete("/u/facilitadores/{$user->id}");

        $response->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_delete_nonexistent_facilitador_returns_error(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->delete('/u/facilitadores/9999');

        $response->assertRedirect();
    }
}
