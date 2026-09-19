<?php

namespace Tests\Feature\LocationController;

use App\Models\Building;
use App\Models\Floor;
use App\Models\IdType;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationControllerTest extends TestCase
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

    protected function createLocationWithFloor(): Location
    {
        $building = Building::factory()->create();
        $floor = Floor::factory()->create(['building_id' => $building->id]);
        return Location::factory()->create(['floor_id' => $floor->id]);
    }

    // ==================== Location CRUD ====================

    // --- getAll ---

    public function test_admin_can_list_locations(): void
    {
        $location = $this->createLocationWithFloor();

        $response = $this->actingAs($this->adminUser())
            ->get('/u/ubicaciones');

        $response->assertStatus(200);
    }

    public function test_ubicaciones_index_filter_input_renders_placeholder(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get('/u/ubicaciones');

        $response->assertOk();
        $response->assertSee('placeholder="Buscar por nombre de aula"', false);
    }

    public function test_location_modals_render_refreshed_layout(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get('/u/ubicaciones');

        $response->assertOk();
        $response->assertSee('id="location-modal"', false);
        $response->assertSee('sidde-modal', false);
        $response->assertSee('fa fa-map-marker', false);
        $response->assertSee('placeholder="Ej: Edificio A"', false);
        $response->assertSee('placeholder="Ej: Aula 101"', false);
        $response->assertSee('sidde-modal-danger', false);
    }

    public function test_tec_educativa_can_list_locations(): void
    {
        $response = $this->actingAs($this->tecEducativaUser())
            ->get('/u/ubicaciones');

        $response->assertStatus(200);
    }

    public function test_programador_cannot_list_locations(): void
    {
        $response = $this->actingAs($this->programadorUser())
            ->get('/u/ubicaciones');

        $response->assertRedirect();
    }

    // --- get ---

    public function test_admin_can_get_location(): void
    {
        $location = $this->createLocationWithFloor();

        $response = $this->actingAs($this->adminUser())
            ->get("/u/ubicaciones/{$location->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $location->id]);
    }

    public function test_tec_educativa_can_get_location(): void
    {
        $location = $this->createLocationWithFloor();

        $response = $this->actingAs($this->tecEducativaUser())
            ->get("/u/ubicaciones/{$location->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $location->id]);
    }

    public function test_programador_cannot_get_location(): void
    {
        $location = $this->createLocationWithFloor();

        $response = $this->actingAs($this->programadorUser())
            ->get("/u/ubicaciones/{$location->id}");

        $response->assertStatus(404);
    }

    public function test_get_nonexistent_location_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get('/u/ubicaciones/9999');

        $response->assertStatus(404);
    }

    // --- store ---

    public function test_admin_can_store_location(): void
    {
        $building = Building::factory()->create();
        $floor = Floor::factory()->create(['building_id' => $building->id]);

        $response = $this->actingAs($this->adminUser())
            ->post('/u/ubicaciones', [
                'nombre' => 'Aula 101',
                'floor_id' => $floor->id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('locations', ['name' => 'Aula 101', 'floor_id' => $floor->id]);
    }

    public function test_tec_educativa_can_store_location(): void
    {
        $building = Building::factory()->create();
        $floor = Floor::factory()->create(['building_id' => $building->id]);

        $response = $this->actingAs($this->tecEducativaUser())
            ->post('/u/ubicaciones', [
                'nombre' => 'Aula 202',
                'floor_id' => $floor->id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('locations', ['name' => 'Aula 202']);
    }

    public function test_programador_cannot_store_location(): void
    {
        $building = Building::factory()->create();
        $floor = Floor::factory()->create(['building_id' => $building->id]);

        $response = $this->actingAs($this->programadorUser())
            ->post('/u/ubicaciones', [
                'nombre' => 'Aula 303',
                'floor_id' => $floor->id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseMissing('locations', ['name' => 'Aula 303']);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post('/u/ubicaciones', []);

        $response->assertSessionHasErrors(['nombre', 'floor_id']);
    }

    // --- update ---

    public function test_admin_can_update_location(): void
    {
        $location = $this->createLocationWithFloor();

        $response = $this->actingAs($this->adminUser())
            ->put("/u/ubicaciones/{$location->id}", [
                'nombre' => 'Aula Actualizada',
                'floor_id' => $location->floor_id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('locations', ['id' => $location->id, 'name' => 'Aula Actualizada']);
    }

    public function test_tec_educativa_can_update_location(): void
    {
        $location = $this->createLocationWithFloor();

        $response = $this->actingAs($this->tecEducativaUser())
            ->put("/u/ubicaciones/{$location->id}", [
                'nombre' => 'Aula Tec',
                'floor_id' => $location->floor_id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('locations', ['id' => $location->id, 'name' => 'Aula Tec']);
    }

    public function test_programador_cannot_update_location(): void
    {
        $location = $this->createLocationWithFloor();
        $originalName = $location->name;

        $response = $this->actingAs($this->programadorUser())
            ->put("/u/ubicaciones/{$location->id}", [
                'nombre' => 'Aula Hackeada',
                'floor_id' => $location->floor_id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('locations', ['id' => $location->id, 'name' => $originalName]);
    }

    public function test_update_nonexistent_location_returns_error(): void
    {
        $building = Building::factory()->create();
        $floor = Floor::factory()->create(['building_id' => $building->id]);

        $response = $this->actingAs($this->adminUser())
            ->put('/u/ubicaciones/9999', [
                'nombre' => 'No Existe',
                'floor_id' => $floor->id,
            ]);

        $response->assertRedirect();
    }

    // --- delete ---

    public function test_admin_can_delete_location(): void
    {
        $location = $this->createLocationWithFloor();

        $response = $this->actingAs($this->adminUser())
            ->delete("/u/ubicaciones/{$location->id}");

        $response->assertRedirect();

        $this->assertSoftDeleted('locations', ['id' => $location->id]);
    }

    public function test_tec_educativa_can_delete_location(): void
    {
        $location = $this->createLocationWithFloor();

        $response = $this->actingAs($this->tecEducativaUser())
            ->delete("/u/ubicaciones/{$location->id}");

        $response->assertRedirect();

        $this->assertSoftDeleted('locations', ['id' => $location->id]);
    }

    public function test_programador_cannot_delete_location(): void
    {
        $location = $this->createLocationWithFloor();

        $response = $this->actingAs($this->programadorUser())
            ->delete("/u/ubicaciones/{$location->id}");

        $response->assertRedirect();

        $this->assertDatabaseHas('locations', ['id' => $location->id, 'deleted_at' => null]);
    }

    public function test_delete_nonexistent_location_returns_error(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->delete('/u/ubicaciones/9999');

        $response->assertRedirect();
    }

    // ==================== Building CRUD ====================

    // --- getBuilding ---

    public function test_admin_can_get_building(): void
    {
        $building = Building::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->get("/u/ubicaciones/building/{$building->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $building->id]);
    }

    public function test_programador_cannot_get_building(): void
    {
        $building = Building::factory()->create();

        $response = $this->actingAs($this->programadorUser())
            ->get("/u/ubicaciones/building/{$building->id}");

        $response->assertStatus(404);
    }

    // --- storeBuilding ---

    public function test_admin_can_store_building(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson('/u/ubicaciones/building', [
                'name' => 'Edificio Nuevo',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('buildings', ['name' => 'Edificio Nuevo']);
    }

    public function test_tec_educativa_can_store_building(): void
    {
        $response = $this->actingAs($this->tecEducativaUser())
            ->postJson('/u/ubicaciones/building', [
                'name' => 'Edificio Tec',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('buildings', ['name' => 'Edificio Tec']);
    }

    public function test_programador_cannot_store_building(): void
    {
        $response = $this->actingAs($this->programadorUser())
            ->postJson('/u/ubicaciones/building', [
                'name' => 'Edificio Hack',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('buildings', ['name' => 'Edificio Hack']);
    }

    // --- updateBuilding ---

    public function test_admin_can_update_building(): void
    {
        $building = Building::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->putJson("/u/ubicaciones/building/{$building->id}", [
                'name' => 'Edificio Actualizado',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('buildings', ['id' => $building->id, 'name' => 'Edificio Actualizado']);
    }

    public function test_programador_cannot_update_building(): void
    {
        $building = Building::factory()->create();

        $response = $this->actingAs($this->programadorUser())
            ->putJson("/u/ubicaciones/building/{$building->id}", [
                'name' => 'Hackeado',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('buildings', ['id' => $building->id, 'name' => $building->name]);
    }

    public function test_update_nonexistent_building_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->putJson('/u/ubicaciones/building/9999', [
                'name' => 'No Existe',
            ]);

        $response->assertStatus(404);
    }

    // --- deleteBuilding ---

    public function test_admin_can_delete_building(): void
    {
        $building = Building::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->deleteJson("/u/ubicaciones/building/{$building->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('buildings', ['id' => $building->id]);
    }

    public function test_programador_cannot_delete_building(): void
    {
        $building = Building::factory()->create();

        $response = $this->actingAs($this->programadorUser())
            ->deleteJson("/u/ubicaciones/building/{$building->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('buildings', ['id' => $building->id, 'deleted_at' => null]);
    }

    public function test_delete_building_with_fails_returns_422(): void
    {
        $building = Building::factory()->create();
        Floor::factory()->create(['building_id' => $building->id]);

        $response = $this->actingAs($this->adminUser())
            ->deleteJson("/u/ubicaciones/building/{$building->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('buildings', ['id' => $building->id, 'deleted_at' => null]);
    }

    public function test_delete_nonexistent_building_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->deleteJson('/u/ubicaciones/building/9999');

        $response->assertStatus(404);
    }

    // ==================== Floor CRUD ====================

    // --- getFloor ---

    public function test_admin_can_get_floor(): void
    {
        $floor = Floor::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->get("/u/ubicaciones/floor/{$floor->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $floor->id]);
    }

    public function test_programador_cannot_get_floor(): void
    {
        $floor = Floor::factory()->create();

        $response = $this->actingAs($this->programadorUser())
            ->get("/u/ubicaciones/floor/{$floor->id}");

        $response->assertStatus(404);
    }

    // --- storeFloor ---

    public function test_admin_can_store_floor(): void
    {
        $building = Building::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->postJson('/u/ubicaciones/floor', [
                'name' => 'Piso Nuevo',
                'building_id' => $building->id,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('floors', ['name' => 'Piso Nuevo', 'building_id' => $building->id]);
    }

    public function test_tec_educativa_can_store_floor(): void
    {
        $building = Building::factory()->create();

        $response = $this->actingAs($this->tecEducativaUser())
            ->postJson('/u/ubicaciones/floor', [
                'name' => 'Piso Tec',
                'building_id' => $building->id,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('floors', ['name' => 'Piso Tec']);
    }

    public function test_programador_cannot_store_floor(): void
    {
        $building = Building::factory()->create();

        $response = $this->actingAs($this->programadorUser())
            ->postJson('/u/ubicaciones/floor', [
                'name' => 'Piso Hack',
                'building_id' => $building->id,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('floors', ['name' => 'Piso Hack']);
    }

    // --- updateFloor ---

    public function test_admin_can_update_floor(): void
    {
        $floor = Floor::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->putJson("/u/ubicaciones/floor/{$floor->id}", [
                'name' => 'Piso Actualizado',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('floors', ['id' => $floor->id, 'name' => 'Piso Actualizado']);
    }

    public function test_programador_cannot_update_floor(): void
    {
        $floor = Floor::factory()->create();

        $response = $this->actingAs($this->programadorUser())
            ->putJson("/u/ubicaciones/floor/{$floor->id}", [
                'name' => 'Piso Hack',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('floors', ['id' => $floor->id, 'name' => $floor->name]);
    }

    public function test_update_nonexistent_floor_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->putJson('/u/ubicaciones/floor/9999', [
                'name' => 'No Existe',
            ]);

        $response->assertStatus(404);
    }

    // --- deleteFloor ---

    public function test_admin_can_delete_floor(): void
    {
        $floor = Floor::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->deleteJson("/u/ubicaciones/floor/{$floor->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('floors', ['id' => $floor->id]);
    }

    public function test_programador_cannot_delete_floor(): void
    {
        $floor = Floor::factory()->create();

        $response = $this->actingAs($this->programadorUser())
            ->deleteJson("/u/ubicaciones/floor/{$floor->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('floors', ['id' => $floor->id, 'deleted_at' => null]);
    }

    public function test_delete_floor_with_locations_returns_422(): void
    {
        $building = Building::factory()->create();
        $floor = Floor::factory()->create(['building_id' => $building->id]);
        Location::factory()->create(['floor_id' => $floor->id]);

        $response = $this->actingAs($this->adminUser())
            ->deleteJson("/u/ubicaciones/floor/{$floor->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('floors', ['id' => $floor->id, 'deleted_at' => null]);
    }

    public function test_delete_nonexistent_floor_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->deleteJson('/u/ubicaciones/floor/9999');

        $response->assertStatus(404);
    }
}
