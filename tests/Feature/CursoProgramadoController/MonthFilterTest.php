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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\IdTypeSeeder::class);
        $this->seed(\Database\Seeders\TypeSeeder::class);
        $this->seed(\Database\Seeders\CourseStatusSeeder::class);
        $this->seed(\Database\Seeders\ParticipantStatusSeeder::class);
    }

    /**
     * @return array{0: User, 1: Facilitator}
     */
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
            'end_date' => Carbon::parse($startDate)->addDays(5)->toDateString(),
        ]);
    }

    public function test_scheduled_list_filters_by_start_date_month_and_honors_boundaries(): void
    {
        $admin = User::factory()->administrador()->create();
        [, $facilitator] = $this->makeFacilitatorUser();

        $this->makeScheduled($facilitator, '2026-09-30');
        $firstDay = $this->makeScheduled($facilitator, '2026-10-01');
        $lastDay = $this->makeScheduled($facilitator, '2026-10-31');
        $this->makeScheduled($facilitator, '2026-11-01');

        $response = $this->actingAs($admin)->get('/u/af_programadas?fechas=10-2026');

        $response->assertOk();
        $response->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(
            [$firstDay->id, $lastDay->id],
            $response->viewData('cursos')->pluck('id')->all(),
        );
        $this->assertSame('10-2026', $response->viewData('fechas'));
    }

    public function test_mis_acciones_filters_a_facilitators_schedule_by_month(): void
    {
        [$facilitatorUser, $facilitator] = $this->makeFacilitatorUser();
        [, $otherFacilitator] = $this->makeFacilitatorUser();

        $october = $this->makeScheduled($facilitator, '2026-10-15');
        $this->makeScheduled($facilitator, '2026-11-15');
        $this->makeScheduled($otherFacilitator, '2026-10-20');

        $response = $this->actingAs($facilitatorUser)->get('/u/mis_acciones?fechas=10-2026');

        $response->assertOk();
        $response->assertSessionHasNoErrors();
        $this->assertSame([$october->id], $response->viewData('cursos')->pluck('id')->all());
    }

    public function test_mis_acciones_filters_a_participants_schedule_by_month(): void
    {
        $participantUser = User::factory()->participante()->create();
        [, $facilitator] = $this->makeFacilitatorUser();

        $october = $this->makeScheduled($facilitator, '2026-10-10');
        $november = $this->makeScheduled($facilitator, '2026-11-10');

        foreach ([$october, $november] as $scheduled) {
            Participant::create([
                'person_id' => $participantUser->person_id,
                'scheduled_id' => $scheduled->id,
                'participant_status_id' => Participant::PORINICIAR,
            ]);
        }

        $response = $this->actingAs($participantUser)->get('/u/mis_acciones?fechas=10-2026');

        $response->assertOk();
        $response->assertSessionHasNoErrors();
        $this->assertSame([$october->id], $response->viewData('cursos')->pluck('id')->all());
    }

    public function test_user_courses_filters_by_month(): void
    {
        $admin = User::factory()->administrador()->create();
        [$facilitatorUser, $facilitator] = $this->makeFacilitatorUser();

        $october = $this->makeScheduled($facilitator, '2026-10-05');
        $this->makeScheduled($facilitator, '2026-12-05');

        $response = $this->actingAs($admin)
            ->get('/u/facilitadores/acciones/'.$facilitatorUser->id.'?fechas=10-2026');

        $response->assertOk();
        $response->assertSessionHasNoErrors();
        $this->assertSame([$october->id], $response->viewData('cursos')->pluck('id')->all());
    }

    public function test_invalid_month_filter_returns_a_validation_error(): void
    {
        $admin = User::factory()->administrador()->create();

        $response = $this->actingAs($admin)
            ->from('/u/af_programadas')
            ->get('/u/af_programadas?fechas=2026-10');

        $response->assertRedirect('/u/af_programadas');
        $response->assertSessionHasErrors('fechas');
    }
}
