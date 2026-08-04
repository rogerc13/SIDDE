<?php

namespace Tests\Feature\CursoProgramadoController;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\CourseStatus;
use App\Models\Facilitator;
use App\Models\Location;
use App\Models\Person;
use App\Models\Scheduled;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScheduleTest extends TestCase
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

    private function makeFacilitator(): Facilitator
    {
        return Facilitator::create(['person_id' => Person::factory()->create()->id]);
    }

    private function makeCourse(array $overrides = []): Course
    {
        $category = \App\Models\Category::factory()->create();
        $modality = \App\Models\Modality::factory()->create();

        return \App\Models\Course::factory()->create(array_merge([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
            'duration' => 8,
        ], $overrides));
    }

    private function makeLocation(): Location
    {
        return Location::create(['name' => 'Aula ' . uniqid()]);
    }

    private function payload(Course $course, Facilitator $facilitator, Location $location, array $sessions = []): array
    {
        if (empty($sessions)) {
            $sessions = [[
                'location_id' => $location->id,
                'session_date' => now()->addDays(10)->toDateString(),
                'start_time' => '08:00',
                'end_time' => '10:00',
                'notes' => '',
            ]];
        }

        return [
            'titulo' => $course->id,
            'facilitador' => $facilitator->id,
            'sessions' => $sessions,
        ];
    }

    public function test_admin_can_schedule_a_course_with_sessions(): void
    {
        $course = $this->makeCourse(['duration' => 8]);
        $facilitator = $this->makeFacilitator();
        $location = $this->makeLocation();
        $user = \App\Models\User::factory()->create(['role_id' => \App\Models\Role::ADMINISTRADOR]);

        $response = $this->actingAs($user)
            ->post('/u/af_programadas/schedule', $this->payload($course, $facilitator, $location));

        $response->assertRedirect();

        $this->assertDatabaseHas('scheduled_course', [
            'course_id' => $course->id,
            'facilitator_id' => $facilitator->id,
            'course_status_id' => CourseStatus::POR_DICTAR,
        ]);

        $scheduled = Scheduled::first();
        $expectedDate = now()->addDays(10)->toDateString();
        $this->assertEquals($expectedDate, $scheduled->start_date);
        $this->assertEquals($expectedDate, $scheduled->end_date);

        $this->assertDatabaseHas('course_sessions', [
            'scheduled_course_id' => $scheduled->id,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);
    }

    public function test_schedule_rejects_12_hour_time_format(): void
    {
        $course = $this->makeCourse();
        $facilitator = $this->makeFacilitator();
        $user = \App\Models\User::factory()->create(['role_id' => \App\Models\Role::ADMINISTRADOR]);

        $location = $this->makeLocation();

        $response = $this->actingAs($user)
            ->from('/u/af_programadas')
            ->post('/u/af_programadas/schedule', [
                'titulo' => $course->id,
                'facilitador' => $facilitator->id,
                'sessions' => [[
                    'location_id' => $location->id,
                    'session_date' => now()->addDays(10)->toDateString(),
                    'start_time' => '1:00 PM',
                    'end_time' => '2:00 PM',
                    'notes' => '',
                ]],
            ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('scheduled_course', 0);
        $this->assertDatabaseCount('course_sessions', 0);
    }

    public function test_schedule_rejects_sessions_exceeding_course_duration(): void
    {
        $course = $this->makeCourse(['duration' => 2]);
        $facilitator = $this->makeFacilitator();
        $user = \App\Models\User::factory()->create(['role_id' => \App\Models\Role::ADMINISTRADOR]);

        $location = $this->makeLocation();

        $response = $this->actingAs($user)
            ->from('/u/af_programadas')
            ->post('/u/af_programadas/schedule', $this->payload($course, $facilitator, $location, [[
                'location_id' => $location->id,
                'session_date' => now()->addDays(10)->toDateString(),
                'start_time' => '08:00',
                'end_time' => '10:00',
                'notes' => '',
            ], [
                'location_id' => $location->id,
                'session_date' => now()->addDays(11)->toDateString(),
                'start_time' => '08:00',
                'end_time' => '10:00',
                'notes' => '',
            ]]));

        $response->assertSessionHasErrors('sessions');
        $this->assertDatabaseCount('scheduled_course', 0);
        $this->assertDatabaseCount('course_sessions', 0);
    }

    public function test_schedule_rejects_overlapping_sessions_at_same_location(): void
    {
        $course = $this->makeCourse(['duration' => 8]);
        $facilitator = $this->makeFacilitator();
        $user = \App\Models\User::factory()->create(['role_id' => \App\Models\Role::ADMINISTRADOR]);

        $location = $this->makeLocation();
        $date = now()->addDays(10)->toDateString();

        $response = $this->actingAs($user)
            ->from('/u/af_programadas')
            ->post('/u/af_programadas/schedule', [
                'titulo' => $course->id,
                'facilitador' => $facilitator->id,
                'sessions' => [[
                    'location_id' => $location->id,
                    'session_date' => $date,
                    'start_time' => '08:00',
                    'end_time' => '10:00',
                    'notes' => '',
                ], [
                    'location_id' => $location->id,
                    'session_date' => $date,
                    'start_time' => '09:00',
                    'end_time' => '11:00',
                    'notes' => '',
                ]],
            ]);

        $response->assertSessionHasErrors('sessions');
        $this->assertDatabaseCount('scheduled_course', 0);
        $this->assertDatabaseCount('course_sessions', 0);
    }

    public function test_schedule_rejects_conflict_with_existing_session(): void
    {
        $course = $this->makeCourse(['duration' => 8]);
        $facilitator = $this->makeFacilitator();
        $user = \App\Models\User::factory()->create(['role_id' => \App\Models\Role::ADMINISTRADOR]);

        $location = $this->makeLocation();
        $date = now()->addDays(10)->toDateString();

        $existing = Scheduled::create([
            'course_id' => $course->id,
            'facilitator_id' => $facilitator->id,
            'course_status_id' => CourseStatus::POR_DICTAR,
            'start_date' => $date,
            'end_date' => $date,
        ]);

        CourseSession::create([
            'scheduled_course_id' => $existing->id,
            'location_id' => $location->id,
            'session_date' => $date,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($user)
            ->from('/u/af_programadas')
            ->post('/u/af_programadas/schedule', $this->payload($course, $facilitator, $location, [[
                'location_id' => $location->id,
                'session_date' => $date,
                'start_time' => '09:00',
                'end_time' => '11:00',
                'notes' => '',
            ]]));

        $response->assertSessionHasErrors('sessions');
        $this->assertDatabaseCount('scheduled_course', 1);
    }

    public function test_unauthorized_role_cannot_schedule(): void
    {
        $course = $this->makeCourse();
        $facilitator = $this->makeFacilitator();
        $user = \App\Models\User::factory()->create(['role_id' => \App\Models\Role::PARTICIPANTE]);

        $response = $this->actingAs($user)
            ->post('/u/af_programadas/schedule', $this->payload($course, $facilitator, $this->makeLocation()));

        $response->assertRedirect();
        $this->assertDatabaseCount('scheduled_course', 0);
        $this->assertDatabaseCount('course_sessions', 0);
    }
}
