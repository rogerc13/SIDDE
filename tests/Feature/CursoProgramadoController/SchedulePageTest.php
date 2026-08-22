<?php

namespace Tests\Feature\CursoProgramadoController;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\CourseStatus;
use App\Models\Facilitator;
use App\Models\Location;
use App\Models\Modality;
use App\Models\Person;
use App\Models\Role;
use App\Models\Scheduled;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SchedulePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\IdTypeSeeder::class);
        $this->seed(\Database\Seeders\TypeSeeder::class);

        CourseStatus::create(['name' => 'Por Dictar']);
        CourseStatus::create(['name' => 'En Curso']);
        CourseStatus::create(['name' => 'Culminado']);
        CourseStatus::create(['name' => 'Cancelado']);
    }

    private function makeCourse(array $overrides = []): Course
    {
        $category = Category::factory()->create();
        $modality = Modality::factory()->create();

        return Course::factory()->create(array_merge([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
            'duration' => 8,
        ], $overrides));
    }

    private function makeFacilitatorUser(): User
    {
        $person = Person::factory()->create();
        Facilitator::create(['person_id' => $person->id]);

        return User::factory()->create([
            'role_id' => Role::FACILITADOR,
            'person_id' => $person->id,
        ]);
    }

    private function makeLocation(): Location
    {
        return Location::factory()->create();
    }

    private function makeScheduledCourse(Course $course, Facilitator $facilitator, array $sessions): Scheduled
    {
        $scheduled = Scheduled::create([
            'course_id' => $course->id,
            'facilitator_id' => $facilitator->id,
            'course_status_id' => CourseStatus::POR_DICTAR,
            'start_date' => collect($sessions)->min('session_date'),
            'end_date' => collect($sessions)->max('session_date'),
        ]);

        foreach ($sessions as $session) {
            CourseSession::create(array_merge(['scheduled_course_id' => $scheduled->id], $session));
        }

        return $scheduled;
    }

    public function test_create_page_loads_for_authorized_user(): void
    {
        $user = User::factory()->create(['role_id' => Role::ADMINISTRADOR]);
        $this->makeFacilitatorUser();
        $this->makeCourse();
        $this->makeLocation();

        $response = $this->actingAs($user)->get('/u/af_programadas/crear');

        $response->assertStatus(200);
        $response->assertSee('wizard-form');
        $response->assertSee('Programar Acción de Formación');
    }

    public function test_create_page_forbidden_for_participant(): void
    {
        $user = User::factory()->create(['role_id' => Role::PARTICIPANTE]);

        $response = $this->actingAs($user)->get('/u/af_programadas/crear');

        $response->assertRedirect('/u/af_programadas');
    }

    public function test_edit_page_loads_with_existing_sessions(): void
    {
        $user = User::factory()->create(['role_id' => Role::ADMINISTRADOR]);
        $course = $this->makeCourse(['duration' => 8]);
        $facilitator = $this->makeFacilitatorUser();
        $location = $this->makeLocation();
        $date = now()->addDays(10)->toDateString();

        $scheduled = $this->makeScheduledCourse($course, $facilitator->person->facilitator, [
            ['location_id' => $location->id, 'session_date' => $date, 'start_time' => '08:00', 'end_time' => '10:00', 'notes' => ''],
            ['location_id' => $location->id, 'session_date' => $date, 'start_time' => '10:00', 'end_time' => '12:00', 'notes' => ''],
        ]);

        $response = $this->actingAs($user)->get('/u/af_programadas/' . $scheduled->id . '/editar');

        $response->assertStatus(200);
        $response->assertSee($course->title);
        $response->assertSee($date);
        $response->assertSee('Editar Acción de Formación Programada');
    }

    public function test_view_page_loads_for_authorized_user(): void
    {
        $user = User::factory()->create(['role_id' => Role::ADMINISTRADOR]);
        $course = $this->makeCourse(['duration' => 8]);
        $facilitator = $this->makeFacilitatorUser();
        $location = $this->makeLocation();
        $date = now()->addDays(10)->toDateString();

        $scheduled = $this->makeScheduledCourse($course, $facilitator->person->facilitator, [
            ['location_id' => $location->id, 'session_date' => $date, 'start_time' => '08:00', 'end_time' => '10:00', 'notes' => ''],
        ]);

        $response = $this->actingAs($user)->get('/u/af_programadas/' . $scheduled->id . '/ver');

        $response->assertStatus(200);
        $response->assertSee($date);
        $response->assertSee('Detalles de la Acción de Formación Programada');
        $response->assertSee('Registrados');
        $response->assertSee('Editar');
    }

    public function test_create_page_renders_new_ui_elements(): void
    {
        $user = User::factory()->create(['role_id' => Role::ADMINISTRADOR]);
        $this->makeFacilitatorUser();
        $this->makeCourse();
        $this->makeLocation();

        $response = $this->actingAs($user)->get('/u/af_programadas/crear');

        $response->assertStatus(200);
        $response->assertSee('schedule-summary');
        $response->assertSee('wizard-volver');
        $response->assertSee('location-color-legend');
        $response->assertSee('location-filter-label');
        $response->assertSee('location-key-pinned');
        $response->assertSee('repeat-group');
    }

    public function test_create_page_hides_location_search_for_small_lists(): void
    {
        $user = User::factory()->create(['role_id' => Role::ADMINISTRADOR]);
        $this->makeFacilitatorUser();
        $this->makeCourse();
        $this->makeLocation();

        $response = $this->actingAs($user)->get('/u/af_programadas/crear');

        $response->assertStatus(200);
        $response->assertDontSee('id="location-chip-search"', false);
    }

    public function test_create_page_shows_location_search_for_large_lists(): void
    {
        $user = User::factory()->create(['role_id' => Role::ADMINISTRADOR]);
        $this->makeFacilitatorUser();
        $this->makeCourse();

        for ($i = 0; $i < 11; $i++) {
            $this->makeLocation();
        }

        $response = $this->actingAs($user)->get('/u/af_programadas/crear');

        $response->assertStatus(200);
        $response->assertSee('id="location-chip-search"', false);
    }

    public function test_locations_are_ordered_by_sessions_in_usage_window(): void
    {
        $user = User::factory()->create(['role_id' => Role::ADMINISTRADOR]);
        $course = $this->makeCourse(['duration' => 2]);
        $facilitator = $this->makeFacilitatorUser();
        $busy = $this->makeLocation();
        $quiet = $this->makeLocation();
        $date = now()->toDateString();

        $this->makeScheduledCourse($course, $facilitator->person->facilitator, [
            ['location_id' => $busy->id, 'session_date' => $date, 'start_time' => '08:00', 'end_time' => '10:00', 'notes' => ''],
        ]);

        $response = $this->actingAs($user)->get('/u/af_programadas/crear');

        $content = $response->getContent();
        $busyPos = strpos($content, 'value="' . $busy->id . '" data-building-floor');
        $quietPos = strpos($content, 'value="' . $quiet->id . '" data-building-floor');

        $this->assertNotFalse($busyPos);
        $this->assertNotFalse($quietPos);
        $this->assertLessThan($quietPos, $busyPos);
    }

    public function test_edit_page_shows_status_summary(): void
    {
        $user = User::factory()->create(['role_id' => Role::ADMINISTRADOR]);
        $course = $this->makeCourse(['duration' => 8]);
        $facilitator = $this->makeFacilitatorUser();
        $location = $this->makeLocation();
        $date = now()->addDays(10)->toDateString();

        $scheduled = $this->makeScheduledCourse($course, $facilitator->person->facilitator, [
            ['location_id' => $location->id, 'session_date' => $date, 'start_time' => '08:00', 'end_time' => '10:00', 'notes' => ''],
        ]);

        $response = $this->actingAs($user)->get('/u/af_programadas/' . $scheduled->id . '/editar');

        $response->assertStatus(200);
        $response->assertSee('summary-status');
        $response->assertSee($scheduled->courseStatus->name);
    }

    public function test_update_schedule_syncs_sessions(): void
    {
        $user = User::factory()->create(['role_id' => Role::ADMINISTRADOR]);
        $course = $this->makeCourse(['duration' => 3]);
        $facilitator = $this->makeFacilitatorUser();
        $location = $this->makeLocation();
        $date = now()->addDays(10)->toDateString();

        $scheduled = $this->makeScheduledCourse($course, $facilitator->person->facilitator, [
            ['location_id' => $location->id, 'session_date' => $date, 'start_time' => '08:00', 'end_time' => '10:00', 'notes' => ''],
            ['location_id' => $location->id, 'session_date' => $date, 'start_time' => '10:00', 'end_time' => '12:00', 'notes' => ''],
        ]);

        $existing = $scheduled->sessions()->orderBy('id')->get();
        $keep = $existing->first();
        $remove = $existing->last();

        $payload = [
            'titulo' => $course->id,
            'facilitador' => $facilitator->person->facilitator->id,
            'scheduled_id' => $scheduled->id,
            'sessions' => [
                [
                    'id' => $keep->id,
                    'location_id' => $location->id,
                    'session_date' => $date,
                    'start_time' => '08:00',
                    'end_time' => '09:00',
                    'notes' => 'modificada',
                ],
                [
                    'id' => null,
                    'location_id' => $location->id,
                    'session_date' => $date,
                    'start_time' => '13:00',
                    'end_time' => '15:00',
                    'notes' => 'nueva',
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withHeader('Accept', 'application/json')
            ->putJson('/u/af_programadas/' . $scheduled->id, $payload);

        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('course_sessions', [
            'id' => $keep->id,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'notes' => 'modificada',
        ]);
        $this->assertDatabaseHas('course_sessions', [
            'scheduled_course_id' => $scheduled->id,
            'start_time' => '13:00',
            'end_time' => '15:00',
            'notes' => 'nueva',
        ]);
        $this->assertSoftDeleted('course_sessions', ['id' => $remove->id]);
        $this->assertCount(2, CourseSession::all());
        $this->assertDatabaseCount('course_sessions', 3);
    }

    public function test_update_schedule_rejects_undersized_session_total(): void
    {
        $user = User::factory()->create(['role_id' => Role::ADMINISTRADOR]);
        $course = $this->makeCourse(['duration' => 8]);
        $facilitator = $this->makeFacilitatorUser();
        $location = $this->makeLocation();
        $date = now()->addDays(10)->toDateString();

        $scheduled = $this->makeScheduledCourse($course, $facilitator->person->facilitator, [
            ['location_id' => $location->id, 'session_date' => $date, 'start_time' => '08:00', 'end_time' => '10:00', 'notes' => ''],
            ['location_id' => $location->id, 'session_date' => $date, 'start_time' => '10:00', 'end_time' => '12:00', 'notes' => ''],
        ]);

        $payload = [
            'titulo' => $course->id,
            'facilitador' => $facilitator->person->facilitator->id,
            'scheduled_id' => $scheduled->id,
            'sessions' => [
                ['id' => null, 'location_id' => $location->id, 'session_date' => $date, 'start_time' => '08:00', 'end_time' => '10:00', 'notes' => ''],
            ],
        ];

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withHeader('Accept', 'application/json')
            ->putJson('/u/af_programadas/' . $scheduled->id, $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('sessions');
    }

    public function test_update_schedule_rejects_overlapping_sessions(): void
    {
        $user = User::factory()->create(['role_id' => Role::ADMINISTRADOR]);
        $course = $this->makeCourse(['duration' => 4]);
        $facilitator = $this->makeFacilitatorUser();
        $location = $this->makeLocation();
        $date = now()->addDays(10)->toDateString();

        $scheduled = $this->makeScheduledCourse($course, $facilitator->person->facilitator, [
            ['location_id' => $location->id, 'session_date' => $date, 'start_time' => '08:00', 'end_time' => '10:00', 'notes' => ''],
        ]);

        $payload = [
            'titulo' => $course->id,
            'facilitador' => $facilitator->person->facilitator->id,
            'scheduled_id' => $scheduled->id,
            'sessions' => [
                ['id' => null, 'location_id' => $location->id, 'session_date' => $date, 'start_time' => '08:00', 'end_time' => '10:00', 'notes' => ''],
                ['id' => null, 'location_id' => $location->id, 'session_date' => $date, 'start_time' => '09:00', 'end_time' => '11:00', 'notes' => ''],
            ],
        ];

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withHeader('Accept', 'application/json')
            ->putJson('/u/af_programadas/' . $scheduled->id, $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('sessions');
    }

    public function test_update_schedule_unauthorized_role_redirects(): void
    {
        $user = User::factory()->create(['role_id' => Role::PARTICIPANTE]);
        $course = $this->makeCourse(['duration' => 2]);
        $facilitator = $this->makeFacilitatorUser();
        $location = $this->makeLocation();
        $date = now()->addDays(10)->toDateString();

        $scheduled = $this->makeScheduledCourse($course, $facilitator->person->facilitator, [
            ['location_id' => $location->id, 'session_date' => $date, 'start_time' => '08:00', 'end_time' => '10:00', 'notes' => ''],
        ]);

        $response = $this->actingAs($user)
            ->put('/u/af_programadas/' . $scheduled->id, [
                'titulo' => $course->id,
                'facilitador' => $facilitator->person->facilitator->id,
                'scheduled_id' => $scheduled->id,
                'sessions' => [
                    ['id' => null, 'location_id' => $location->id, 'session_date' => $date, 'start_time' => '08:00', 'end_time' => '10:00', 'notes' => ''],
                ],
            ]);

        $response->assertRedirect('/u/af_programadas');
        $this->assertDatabaseCount('course_sessions', 1);
    }
}
