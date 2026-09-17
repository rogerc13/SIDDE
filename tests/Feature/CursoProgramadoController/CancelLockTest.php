<?php

namespace Tests\Feature\CursoProgramadoController;

use App\Models\Capacity;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelLockTest extends TestCase
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

    private function makeCourse(int $duration = 2): Course
    {
        $course = Course::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'modality_id' => Modality::factory()->create()->id,
            'duration' => $duration,
        ]);

        Capacity::create(['min' => 1, 'max' => 10, 'course_id' => $course->id]);

        return $course;
    }

    private function makeFacilitator(): Facilitator
    {
        return Facilitator::create(['person_id' => Person::factory()->create()->id]);
    }

    private function makeScheduled(Course $course, Facilitator $facilitator, int $status, string $startDate): Scheduled
    {
        return Scheduled::create([
            'course_id' => $course->id,
            'facilitator_id' => $facilitator->id,
            'course_status_id' => $status,
            'start_date' => $startDate,
            'end_date' => \Carbon\Carbon::parse($startDate)->addDays(5)->toDateString(),
        ]);
    }

    private function validPayload(Course $course, Facilitator $facilitator, Location $location, Scheduled $scheduled): array
    {
        return [
            'titulo' => $course->id,
            'facilitador' => $facilitator->id,
            'scheduled_id' => $scheduled->id,
            'sessions' => [[
                'id' => null,
                'location_id' => $location->id,
                'session_date' => now()->addDays(20)->toDateString(),
                'start_time' => '08:00',
                'end_time' => '10:00',
            ]],
        ];
    }

    public function test_cancel_rejects_an_already_cancelled_action(): void
    {
        $admin = User::factory()->administrador()->create();
        $scheduled = $this->makeScheduled($this->makeCourse(), $this->makeFacilitator(), CourseStatus::CANCELADO, now()->addWeek()->toDateString());

        $response = $this->actingAs($admin)
            ->from('/u/af_programadas')
            ->put('/u/af_programadas/cancel/' . $scheduled->id);

        $response->assertRedirect('/u/af_programadas');
        $response->assertSessionHas('alert');
        $this->assertSame(CourseStatus::CANCELADO, $scheduled->fresh()->course_status_id);
    }

    public function test_update_is_blocked_for_cancelled_and_culminado_actions(): void
    {
        $admin = User::factory()->administrador()->create();
        $location = Location::factory()->create();

        foreach ([CourseStatus::CANCELADO, CourseStatus::CULMINADO] as $status) {
            $course = $this->makeCourse();
            $facilitator = $this->makeFacilitator();
            $scheduled = $this->makeScheduled($course, $facilitator, $status, now()->subWeek()->toDateString());

            $response = $this->actingAs($admin)
                ->withHeaders(['Accept' => 'application/json'])
                ->putJson('/u/af_programadas/' . $scheduled->id, $this->validPayload($course, $facilitator, $location, $scheduled));

            $response->assertStatus(422);
            $response->assertJson(['success' => false]);
            $this->assertDatabaseCount('course_sessions', 0);
        }
    }

    public function test_admin_can_delete_a_future_por_dictar_action_and_its_sessions(): void
    {
        $admin = User::factory()->administrador()->create();
        $scheduled = $this->makeScheduled($this->makeCourse(), $this->makeFacilitator(), CourseStatus::POR_DICTAR, now()->addDays(10)->toDateString());

        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => Location::factory()->create()->id,
            'session_date' => now()->addDays(10)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($admin)->delete('/u/af_programadas/' . $scheduled->id);

        $response->assertRedirect();
        $this->assertSoftDeleted('scheduled_course', ['id' => $scheduled->id]);
        $this->assertSoftDeleted('course_sessions', ['scheduled_course_id' => $scheduled->id]);
    }

    public function test_non_admin_cannot_delete_an_action(): void
    {
        $programador = User::factory()->create(['role_id' => Role::PROGRAMADOR]);
        $scheduled = $this->makeScheduled($this->makeCourse(), $this->makeFacilitator(), CourseStatus::POR_DICTAR, now()->addDays(10)->toDateString());

        $response = $this->actingAs($programador)->delete('/u/af_programadas/' . $scheduled->id);

        $response->assertRedirect();
        $response->assertSessionHas('alert');
        $this->assertNotSoftDeleted('scheduled_course', ['id' => $scheduled->id]);
    }

    public function test_admin_cannot_delete_an_action_that_already_started(): void
    {
        $admin = User::factory()->administrador()->create();
        $scheduled = $this->makeScheduled($this->makeCourse(), $this->makeFacilitator(), CourseStatus::CULMINADO, now()->subWeek()->toDateString());

        $response = $this->actingAs($admin)->delete('/u/af_programadas/' . $scheduled->id);

        $response->assertRedirect();
        $response->assertSessionHas('alert');
        $this->assertNotSoftDeleted('scheduled_course', ['id' => $scheduled->id]);
    }
}
