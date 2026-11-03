<?php

namespace Tests\Feature\CursoProgramadoController;

use App\Models\Capacity;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseStatus;
use App\Models\Facilitator;
use App\Models\Modality;
use App\Models\Participant;
use App\Models\Scheduled;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyCoursesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\IdTypeSeeder::class);
        $this->seed(\Database\Seeders\TypeSeeder::class);
        $this->seed(\Database\Seeders\ParticipantStatusSeeder::class);

        CourseStatus::create(['name' => 'Por Dictar']);
        CourseStatus::create(['name' => 'En Curso']);
        CourseStatus::create(['name' => 'Culminado']);
        CourseStatus::create(['name' => 'Cancelado']);
    }

    private function makeFacilitatorUser(): array
    {
        $user = User::factory()->facilitador()->create();
        $facilitator = Facilitator::create(['person_id' => $user->person_id]);

        return [$user, $facilitator];
    }

    private function makeScheduled(Facilitator $facilitator, string $startDate): Scheduled
    {
        $course = Course::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'modality_id' => Modality::factory()->create()->id,
            'duration' => 8,
        ]);
        Capacity::create(['min' => 1, 'max' => 10, 'course_id' => $course->id]);

        return Scheduled::create([
            'course_id' => $course->id,
            'facilitator_id' => $facilitator->id,
            'course_status_id' => CourseStatus::POR_DICTAR,
            'start_date' => $startDate,
            'end_date' => \Carbon\Carbon::parse($startDate)->addDays(5)->toDateString(),
        ]);
    }

    public function test_mis_acciones_orders_newest_first(): void
    {
        [$user, $facilitator] = $this->makeFacilitatorUser();
        $this->makeScheduled($facilitator, now()->addDays(5)->toDateString());
        $newer = $this->makeScheduled($facilitator, now()->addDays(20)->toDateString());

        $response = $this->actingAs($user)->get('/u/mis_acciones');

        $response->assertOk();
        $cursos = $response->viewData('cursos');
        $this->assertSame($newer->id, $cursos->first()->id);
    }

    public function test_user_acciones_orders_newest_first(): void
    {
        $admin = User::factory()->administrador()->create();
        [$user, $facilitator] = $this->makeFacilitatorUser();
        $this->makeScheduled($facilitator, now()->addDays(5)->toDateString());
        $newer = $this->makeScheduled($facilitator, now()->addDays(20)->toDateString());

        $response = $this->actingAs($admin)->get('/u/facilitadores/acciones/' . $user->id);

        $response->assertOk();
        $cursos = $response->viewData('cursos');
        $this->assertSame($newer->id, $cursos->first()->id);
    }

    public function test_mis_acciones_shows_course_status(): void
    {
        [$user, $facilitator] = $this->makeFacilitatorUser();
        $this->makeScheduled($facilitator, now()->addDays(5)->toDateString());

        $response = $this->actingAs($user)->get('/u/mis_acciones');

        $response->assertOk();
        $response->assertSee('Estado');
        $response->assertSee('Por Dictar');
    }

    public function test_user_acciones_shows_course_status(): void
    {
        $admin = User::factory()->administrador()->create();
        [$user, $facilitator] = $this->makeFacilitatorUser();
        $this->makeScheduled($facilitator, now()->addDays(5)->toDateString());

        $response = $this->actingAs($admin)->get('/u/facilitadores/acciones/' . $user->id);

        $response->assertOk();
        $response->assertSee('Estado');
        $response->assertSee('Por Dictar');
    }

    public function test_mis_acciones_filter_input_renders_placeholder(): void
    {
        [$user] = $this->makeFacilitatorUser();

        $response = $this->actingAs($user)->get('/u/mis_acciones');

        $response->assertOk();
        $response->assertSee('placeholder="Buscar por título"', false);
    }

    public function test_user_acciones_filter_input_renders_placeholder(): void
    {
        $admin = User::factory()->administrador()->create();
        [$user] = $this->makeFacilitatorUser();

        $response = $this->actingAs($admin)->get('/u/facilitadores/acciones/' . $user->id);

        $response->assertOk();
        $response->assertSee('placeholder="Buscar por título"', false);
    }

    public function test_user_acciones_links_to_scheduled_course_view_for_facilitator(): void
    {
        $admin = User::factory()->administrador()->create();
        [$user, $facilitator] = $this->makeFacilitatorUser();
        $scheduled = $this->makeScheduled($facilitator, now()->addDays(5)->toDateString());

        $response = $this->actingAs($admin)->get('/u/facilitadores/acciones/' . $user->id);

        $response->assertOk();
        $response->assertSee(url('u/af_programadas/'.$scheduled->id.'/ver'), false);
        $response->assertDontSee(url('acciones_formacion/'.$scheduled->course_id), false);
    }

    public function test_user_acciones_links_to_scheduled_course_view_for_participant(): void
    {
        $admin = User::factory()->administrador()->create();
        $participantUser = User::factory()->participante()->create();
        [, $facilitator] = $this->makeFacilitatorUser();
        $scheduled = $this->makeScheduled($facilitator, now()->addDays(5)->toDateString());

        Participant::create([
            'person_id' => $participantUser->person_id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => Participant::PORINICIAR,
        ]);

        $response = $this->actingAs($admin)->get('/u/participantes/acciones/' . $participantUser->id);

        $response->assertOk();
        $response->assertSee(url('u/af_programadas/'.$scheduled->id.'/ver'), false);
        $response->assertDontSee(url('acciones_formacion/'.$scheduled->course_id), false);
    }
}
