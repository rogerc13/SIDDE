<?php

namespace Tests\Feature\Admin;

use App\Models\Building;
use App\Models\Capacity;
use App\Models\Category;
use App\Models\Content;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\CourseStatus;
use App\Models\Facilitator;
use App\Models\Floor;
use App\Models\Location;
use App\Models\Modality;
use App\Models\Participant;
use App\Models\ParticipantStatus;
use App\Models\Person;
use App\Models\Prerequisite;
use App\Models\Role;
use App\Models\Scheduled;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DatabaseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\IdTypeSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::ADMINISTRADOR]);
    }

    private function nonAdmin(): User
    {
        return User::factory()->create(['role_id' => Role::PROGRAMADOR]);
    }

    private function makeScheduled(Course $course): Scheduled
    {
        $facilitator = Facilitator::create(['person_id' => Person::factory()->create()->id]);
        $status = CourseStatus::create(['name' => 'Por Dictar']);

        return Scheduled::create([
            'course_id' => $course->id,
            'facilitator_id' => $facilitator->id,
            'course_status_id' => $status->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);
    }

    public function test_database_index_requires_admin(): void
    {
        $this->actingAs($this->nonAdmin())
            ->get(route('database.index'))
            ->assertRedirect(route('home'));
    }

    public function test_database_index_lists_all_tables(): void
    {
        $response = $this->actingAs($this->admin())->get(route('database.index'));

        $response->assertOk();

        foreach (['buildings', 'capacities', 'course_sessions', 'floors', 'locations', 'courses', 'users'] as $table) {
            $response->assertSee($table);
        }
    }

    public function test_every_table_link_on_the_index_resolves(): void
    {
        $index = $this->actingAs($this->admin())->get(route('database.index'));

        $index->assertOk();

        preg_match_all('#/database/([a-zA-Z_]+)"#', $index->getContent(), $matches);

        $tables = array_values(array_unique($matches[1]));

        $this->assertNotEmpty($tables);

        foreach ($tables as $table) {
            $this->actingAs($this->admin())
                ->get('/database/' . $table)
                ->assertOk();
        }
    }

    public function test_unknown_table_returns_not_found(): void
    {
        $this->actingAs($this->admin())
            ->get('/database/not_a_real_table')
            ->assertNotFound();
    }

    public function test_floor_location_and_session_pages_render_related_labels(): void
    {
        $building = Building::factory()->create(['name' => 'Edificio Central']);
        $floor = Floor::factory()->create(['building_id' => $building->id, 'name' => 'Piso 3']);
        $location = Location::factory()->create(['floor_id' => $floor->id, 'name' => 'Aula 301']);

        $course = Course::factory()->create(['title' => 'Curso de Prueba']);
        $scheduled = $this->makeScheduled($course);

        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $location->id,
            'session_date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->get('/database/floors')->assertOk()->assertSee('Edificio Central');
        $this->actingAs($admin)->get('/database/locations')->assertOk()->assertSee('Piso 3');
        $this->actingAs($admin)
            ->get('/database/course_sessions')
            ->assertOk()
            ->assertSee('Aula 301')
            ->assertSee('Curso de Prueba');
    }

    public function test_admin_can_update_a_user(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['role_id' => Role::PROGRAMADOR]);
        $newEmail = 'updated-' . $target->id . '@pdvsa.com';

        $response = $this->actingAs($admin)->patch(route('database.users.update', $target->id), [
            'email' => $newEmail,
            'role_id' => Role::TECNOLOGIA_EDUCATIVA,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'email' => $newEmail,
            'role_id' => Role::TECNOLOGIA_EDUCATIVA,
        ]);
    }

    public function test_admin_can_update_a_course(): void
    {
        $admin = $this->admin();
        $course = Course::factory()->create();
        $category = Category::factory()->create();
        $modality = Modality::factory()->create();

        $response = $this->actingAs($admin)->patch(route('database.courses.update', $course->id), [
            'code' => $course->code,
            'title' => 'Título Actualizado',
            'objective' => 'Objetivo actualizado',
            'duration' => 16,
            'addressed' => 'Dirigido a todos',
            'category_id' => $category->id,
            'modality_id' => $modality->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Título Actualizado',
            'duration' => 16,
            'category_id' => $category->id,
            'modality_id' => $modality->id,
        ]);
    }

    public function test_course_with_scheduled_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $course = Course::factory()->create();
        $this->makeScheduled($course);

        $this->actingAs($admin)
            ->delete(route('database.courses.destroy', $course->id))
            ->assertRedirect();

        $this->assertNotSoftDeleted('courses', ['id' => $course->id]);
    }

    public function test_course_without_scheduled_is_soft_deleted_and_restored(): void
    {
        $admin = $this->admin();
        $course = Course::factory()->create();

        $this->actingAs($admin)
            ->delete(route('database.courses.destroy', $course->id))
            ->assertRedirect();

        $this->assertSoftDeleted('courses', ['id' => $course->id]);

        $this->actingAs($admin)
            ->patch(route('database.courses.restore', $course->id))
            ->assertRedirect();

        $this->assertNotSoftDeleted('courses', ['id' => $course->id]);
    }

    public function test_force_destroy_cascades_related_records(): void
    {
        $admin = $this->admin();
        $course = Course::factory()->create();

        Content::create(['text' => 'Contenido', 'course_id' => $course->id]);
        Capacity::create(['min' => 1, 'max' => 10, 'course_id' => $course->id]);
        Prerequisite::create(['course_id' => $course->id, 'prerequisite' => 'X', 'course_code' => 'X']);

        $scheduled = $this->makeScheduled($course);

        $participantStatus = ParticipantStatus::create(['name' => 'En Curso']);
        Participant::create([
            'person_id' => Person::factory()->create()->id,
            'participant_status_id' => $participantStatus->id,
            'scheduled_id' => $scheduled->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('database.courses.forceDestroy', $course->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseMissing('scheduled_course', ['id' => $scheduled->id]);
        $this->assertDatabaseMissing('participants', ['scheduled_id' => $scheduled->id]);
        $this->assertDatabaseMissing('contents', ['course_id' => $course->id]);
        $this->assertDatabaseMissing('capacities', ['course_id' => $course->id]);
        $this->assertDatabaseMissing('prerequisites', ['course_id' => $course->id]);
    }

    public function test_registration_routes_are_disabled(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->assertTrue(Route::has('login'));

        $this->get('/register')->assertNotFound();
    }
}
