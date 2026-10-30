<?php

namespace Tests\Feature\Home;

use App\Models\Capacity;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\CourseStatus;
use App\Models\Facilitator;
use App\Models\Location;
use App\Models\Participant;
use App\Models\Person;
use App\Models\Role;
use App\Models\Scheduled;
use App\Models\User;
use App\Services\Dashboard\HomeDashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\IdTypeSeeder::class);
        $this->seed(\Database\Seeders\CourseStatusSeeder::class);
        $this->seed(\Database\Seeders\ParticipantStatusSeeder::class);
    }

    private function userWithRole(int $roleId): User
    {
        return User::factory()->create(['role_id' => $roleId]);
    }

    /**
     * @return array{0: Scheduled, 1: Facilitator, 2: Course}
     */
    private function makeScheduled(?string $sessionDate = null, array $scheduledOverrides = [], ?int $locationId = null): array
    {
        $course = Course::factory()->create();
        Capacity::create(['min' => 1, 'max' => 20, 'course_id' => $course->id]);

        $facilitator = Facilitator::create([
            'person_id' => Person::factory()->create()->id,
        ]);

        $scheduled = Scheduled::create(array_merge([
            'course_id' => $course->id,
            'facilitator_id' => $facilitator->id,
            'course_status_id' => CourseStatus::POR_DICTAR,
            'start_date' => CarbonImmutable::today()->addDays(3)->toDateString(),
            'end_date' => CarbonImmutable::today()->addDays(10)->toDateString(),
        ], $scheduledOverrides));

        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $locationId ?? Location::factory()->create()->id,
            'session_date' => $sessionDate ?? CarbonImmutable::today()->addDays(5)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        return [$scheduled, $facilitator, $course];
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/home')->assertRedirect(route('login'));
    }

    public function test_admin_sees_overview_dashboard(): void
    {
        $this->makeScheduled();

        $response = $this->actingAs($this->userWithRole(Role::ADMINISTRADOR))->get('/home');

        $response->assertOk();
        $response->assertSee('Facilitadores activos');
        $response->assertSee('Próximas Acciones de Formación');
    }

    public function test_participant_does_not_see_organisation_wide_widgets(): void
    {
        $this->makeScheduled();

        $response = $this->actingAs($this->userWithRole(Role::PARTICIPANTE))->get('/home');

        $response->assertOk();
        $response->assertSee('Mis acciones de formación');
        $response->assertDontSee('Facilitadores activos');
    }

    public function test_active_facilitators_only_counts_sessions_within_the_window(): void
    {
        $this->makeScheduled();
        $this->makeScheduled(CarbonImmutable::today()->addDays(90)->toDateString());

        $overview = app(HomeDashboardService::class)->overview();

        $this->assertSame(1, $overview['kpis']['activeFacilitators']);
    }

    public function test_upcoming_scheduled_is_ordered_by_start_date(): void
    {
        $this->makeScheduled(null, ['start_date' => CarbonImmutable::today()->addDays(20)->toDateString()]);
        $this->makeScheduled(null, ['start_date' => CarbonImmutable::today()->addDays(2)->toDateString()]);

        $upcoming = app(HomeDashboardService::class)->overview()['upcomingScheduled'];

        $this->assertSame(
            CarbonImmutable::today()->addDays(2)->toDateString(),
            $upcoming->first()->start_date,
        );
    }

    public function test_facilitator_sees_only_own_scheduled_courses(): void
    {
        [$scheduled, , $course] = $this->makeScheduled();

        $user = User::factory()->facilitador()->create(['person_id' => $scheduled->facilitator->person_id]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
        $response->assertSee($course->title);
        $response->assertSee('Mis próximas acciones de formación');
    }

    public function test_participant_sees_own_enrolments(): void
    {
        [$scheduled, , $course] = $this->makeScheduled();

        $user = User::factory()->participante()->create();
        Participant::create([
            'person_id' => $user->person_id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
        $response->assertSee($course->title);
        $response->assertSee('Mis acciones de formación');
    }

    public function test_cancelled_scheduled_courses_are_not_active(): void
    {
        [$scheduled] = $this->makeScheduled(null, ['course_status_id' => CourseStatus::CANCELADO]);

        Participant::create([
            'person_id' => Person::factory()->create()->id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);

        $overview = app(HomeDashboardService::class)->overview();

        $this->assertSame(0, $overview['kpis']['activeFacilitators']);
        $this->assertTrue($overview['activeFacilitators']->isEmpty());
    }

    public function test_in_progress_scheduled_appears_in_upcoming(): void
    {
        $this->makeScheduled(CarbonImmutable::today()->addDays(2)->toDateString(), [
            'start_date' => CarbonImmutable::today()->subDays(3)->toDateString(),
            'end_date' => CarbonImmutable::today()->addDays(10)->toDateString(),
        ]);

        $upcoming = app(HomeDashboardService::class)->overview()['upcomingScheduled'];

        $this->assertCount(1, $upcoming);
        $this->assertSame(
            CarbonImmutable::today()->subDays(3)->toDateString(),
            $upcoming->first()->start_date,
        );
    }

    public function test_upcoming_scheduled_respects_the_limit(): void
    {
        for ($i = 1; $i <= HomeDashboardService::UPCOMING_LIMIT + 3; $i++) {
            $this->makeScheduled(null, [
                'start_date' => CarbonImmutable::today()->addDays($i)->toDateString(),
                'end_date' => CarbonImmutable::today()->addDays($i + 5)->toDateString(),
            ]);
        }

        $upcoming = app(HomeDashboardService::class)->overview()['upcomingScheduled'];

        $this->assertCount(HomeDashboardService::UPCOMING_LIMIT, $upcoming);
    }

    public function test_upcoming_sessions_respects_the_limit(): void
    {
        for ($i = 0; $i < HomeDashboardService::UPCOMING_SESSIONS_LIMIT + 3; $i++) {
            $this->makeScheduled(
                CarbonImmutable::today()->addDays($i % HomeDashboardService::SESSION_FOCUS_DAYS)->toDateString(),
            );
        }

        $upcoming = app(HomeDashboardService::class)->overview()['upcomingSessions'];

        $this->assertCount(HomeDashboardService::UPCOMING_SESSIONS_LIMIT, $upcoming);
    }

    public function test_cancelled_scheduled_courses_are_not_listed_as_upcoming(): void
    {
        [$cancelled] = $this->makeScheduled(null, ['course_status_id' => CourseStatus::CANCELADO]);
        [$active] = $this->makeScheduled(null, ['start_date' => CarbonImmutable::today()->addDays(4)->toDateString()]);

        $upcoming = app(HomeDashboardService::class)->overview()['upcomingScheduled'];

        $this->assertCount(1, $upcoming);
        $this->assertSame($active->id, $upcoming->first()->id);
        $this->assertNotContains($cancelled->id, $upcoming->pluck('id')->all());
    }

    public function test_personalised_dashboards_title_their_panel_with_a_second_level_heading(): void
    {
        [$scheduled] = $this->makeScheduled();

        $facilitator = User::factory()->facilitador()->create(['person_id' => $scheduled->facilitator->person_id]);
        $facilitatorResponse = $this->actingAs($facilitator)->get('/home');

        $facilitatorResponse->assertOk();
        $facilitatorResponse->assertSee('<h2 class="sidde-panel-title">Mis próximas acciones de formación</h2>', false);
        $facilitatorResponse->assertDontSee('<h4 class="sidde-panel-title"', false);

        $participant = User::factory()->participante()->create();
        Participant::create([
            'person_id' => $participant->person_id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);
        $participantResponse = $this->actingAs($participant)->get('/home');

        $participantResponse->assertOk();
        $participantResponse->assertSee('<h2 class="sidde-panel-title">Mis acciones de formación</h2>', false);
        $participantResponse->assertDontSee('<h4 class="sidde-panel-title"', false);
    }

    public function test_overview_renders_the_status_breakdown_widget(): void
    {
        $this->makeScheduled(null, [
            'start_date' => CarbonImmutable::today()->startOfYear()->addMonth()->toDateString(),
            'end_date' => CarbonImmutable::today()->startOfYear()->addMonths(2)->toDateString(),
        ]);

        $response = $this->actingAs($this->userWithRole(Role::ADMINISTRADOR))->get('/home');

        $response->assertOk();
        $response->assertSee('Acciones de Formación Programadas por estado');
        $response->assertSee('Por Dictar');
        $response->assertSee('aria-label="Ver todas las acciones de formación (1)"', false);
    }

    public function test_status_breakdown_only_counts_current_year_scheduled(): void
    {
        $this->makeScheduled(null, [
            'start_date' => CarbonImmutable::today()->startOfYear()->addMonth()->toDateString(),
            'end_date' => CarbonImmutable::today()->startOfYear()->addMonths(2)->toDateString(),
            'course_status_id' => CourseStatus::POR_DICTAR,
        ]);
        $this->makeScheduled(null, [
            'start_date' => CarbonImmutable::today()->subYear()->startOfYear()->addMonth()->toDateString(),
            'end_date' => CarbonImmutable::today()->subYear()->startOfYear()->addMonths(2)->toDateString(),
            'course_status_id' => CourseStatus::CULMINADO,
        ]);

        $overview = app(HomeDashboardService::class)->overview();
        $statuses = collect($overview['kpis']['byStatus'])->keyBy('id');

        $this->assertSame(1, $overview['kpis']['scheduled']);
        $this->assertSame(1, $overview['kpis']['activeScheduled']);
        $this->assertSame(1, $statuses[CourseStatus::POR_DICTAR]['amount']);
        $this->assertSame(0, $statuses[CourseStatus::CULMINADO]['amount']);
    }

    public function test_overview_surfaces_the_activity_window_and_kpi_units(): void
    {
        $window = HomeDashboardService::ACTIVITY_WINDOW_DAYS;

        $response = $this->actingAs($this->userWithRole(Role::ADMINISTRADOR))->get('/home');

        $response->assertOk();
        $response->assertSee('en el catálogo');
        $response->assertSee('próximos '.$window.' días');
        $response->assertSee('activos · próximos '.$window.' días');
    }

    public function test_facilitator_dashboard_caps_the_list_and_reports_totals(): void
    {
        $total = HomeDashboardService::LIST_LIMIT + 2;
        $rows = [];

        for ($i = 1; $i <= $total; $i++) {
            [$scheduled] = $this->makeScheduled(null, [
                'start_date' => CarbonImmutable::today()->addDays($i)->toDateString(),
                'end_date' => CarbonImmutable::today()->addDays($i + 5)->toDateString(),
            ]);
            $rows[] = $scheduled;
        }

        $facilitatorId = $rows[0]->facilitator_id;
        foreach ($rows as $row) {
            $row->update(['facilitator_id' => $facilitatorId]);
        }

        $user = User::factory()->facilitador()->create(['person_id' => $rows[0]->facilitator->person_id]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
        $response->assertSee('aria-label="Ver todas mis acciones de formación ('.$total.')"', false);
        $response->assertSee('Cupos disponibles');
        $response->assertSee($rows[0]->course->title);
        $response->assertDontSee($rows[$total - 1]->course->title);
    }

    public function test_participant_dashboard_caps_the_list_and_reports_totals(): void
    {
        $total = HomeDashboardService::LIST_LIMIT + 2;
        $rows = [];
        $user = User::factory()->participante()->create();

        for ($i = 1; $i <= $total; $i++) {
            [$scheduled] = $this->makeScheduled();
            Participant::create([
                'person_id' => $user->person_id,
                'scheduled_id' => $scheduled->id,
                'participant_status_id' => Participant::ENCURSO,
            ]);
            $rows[] = $scheduled;
        }

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
        $response->assertSee('aria-label="Ver todas mis acciones de formación ('.$total.')"', false);
        $response->assertSee($rows[$total - 1]->course->title);
        $response->assertDontSee($rows[0]->course->title);
    }

    public function test_participant_dashboard_links_to_programme_details(): void
    {
        [$scheduled] = $this->makeScheduled();
        $user = User::factory()->participante()->create();
        Participant::create([
            'person_id' => $user->person_id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
        $response->assertSee('u/af_programadas/'.$scheduled->id.'/ver', false);
    }

    public function test_overview_renders_quick_actions_and_status_filter_links(): void
    {
        $this->makeScheduled();

        $response = $this->actingAs($this->userWithRole(Role::ADMINISTRADOR))->get('/home');

        $response->assertOk();
        $response->assertSee('Programar A.F.');
        $response->assertSee('Catálogo A.F.');
        $response->assertSee('Reportes');
        $response->assertSee('u/af_programadas?id_estado='.CourseStatus::POR_DICTAR, false);
    }

    public function test_overview_lists_sessions_in_the_seven_day_focus_window(): void
    {
        [$soonScheduled, , $soonCourse] = $this->makeScheduled(
            CarbonImmutable::today()->toDateString(),
        );
        [, , $farCourse] = $this->makeScheduled(
            CarbonImmutable::today()->addDays(HomeDashboardService::SESSION_FOCUS_DAYS + 5)->toDateString(),
        );

        $overview = app(HomeDashboardService::class)->overview();

        $this->assertSame(1, $overview['todaySessionsCount']);
        $this->assertCount(1, $overview['upcomingSessions']);
        $this->assertSame($soonScheduled->id, $overview['upcomingSessions']->first()->scheduled_course_id);

        $response = $this->actingAs($this->userWithRole(Role::ADMINISTRADOR))->get('/home');
        $response->assertOk();
        $response->assertSee('Próximas Clases');
        $response->assertSee('1 sesión hoy');
        $response->assertSee($soonCourse->title);
    }

    public function test_participant_dashboard_computes_kpi_summary_counts(): void
    {
        [$activeScheduled] = $this->makeScheduled();
        [$approvedScheduled] = $this->makeScheduled();

        $user = User::factory()->participante()->create();

        Participant::create([
            'person_id' => $user->person_id,
            'scheduled_id' => $activeScheduled->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);
        Participant::create([
            'person_id' => $user->person_id,
            'scheduled_id' => $approvedScheduled->id,
            'participant_status_id' => Participant::APROBADO,
        ]);

        $payload = app(HomeDashboardService::class)->forParticipant($user);

        $this->assertSame(1, $payload['activeEnrolmentsCount']);
        $this->assertSame(1, $payload['approvedCount']);
        $this->assertSame(2, $payload['upcomingSessionsCount']);

        $response = $this->actingAs($user)->get('/home');
        $response->assertOk();
        $response->assertSee('Activas');
        $response->assertSee('Sesiones próximas');
        $response->assertSee('Aprobadas');
    }

    public function test_agenda_keeps_the_aula_name_when_its_location_is_soft_deleted(): void
    {
        $location = Location::factory()->create(['name' => 'Aula Retirada']);
        $location->delete();

        $this->makeScheduled(null, [], $location->id);

        $response = $this->actingAs($this->userWithRole(Role::ADMINISTRADOR))->get('/home');

        $response->assertOk();
        $response->assertSee('Próximas Clases');
        $response->assertSee('Aula Retirada');
        $response->assertDontSee('Sin aula');
    }

    public function test_personalised_views_keep_the_aula_name_when_its_location_is_soft_deleted(): void
    {
        $location = Location::factory()->create(['name' => 'Aula Retirada']);
        $location->delete();

        [$scheduled] = $this->makeScheduled(null, [], $location->id);

        $facilitator = User::factory()->facilitador()->create(['person_id' => $scheduled->facilitator->person_id]);
        $this->actingAs($facilitator)->get('/home')
            ->assertOk()
            ->assertSee('Aula Retirada');

        $participant = User::factory()->participante()->create();
        Participant::create([
            'person_id' => $participant->person_id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);

        $this->actingAs($participant)->get('/home')
            ->assertOk()
            ->assertSee('Aula Retirada');
    }
}
