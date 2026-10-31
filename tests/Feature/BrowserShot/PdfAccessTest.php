<?php

namespace Tests\Feature\BrowserShot;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\IdTypeSeeder::class);
        $this->seed(\Database\Seeders\TypeSeeder::class);
        $this->seed(\Database\Seeders\ParticipantStatusSeeder::class);
    }

    public function test_admin_can_download_users_roster_pdf(): void
    {
        $admin = User::factory()->administrador()->create();

        $response = $this->actingAs($admin)->get('/u/pdf/users');

        $response->assertOk();
    }

    public function test_facilitador_cannot_download_users_roster_pdf(): void
    {
        $facilitador = User::factory()->facilitador()->create();

        $this->actingAs($facilitador)
            ->from('/')
            ->get('/u/pdf/users')
            ->assertRedirect('/');
    }

    public function test_facilitador_cannot_download_facilitators_roster_pdf(): void
    {
        $facilitador = User::factory()->facilitador()->create();

        $this->actingAs($facilitador)
            ->from('/')
            ->get('/u/pdf/facilitators')
            ->assertRedirect('/');
    }

    public function test_participante_cannot_download_participants_roster_pdf(): void
    {
        $participante = User::factory()->participante()->create();

        $this->actingAs($participante)
            ->from('/')
            ->get('/u/pdf/participants')
            ->assertRedirect('/');
    }

    public function test_participante_can_download_own_courses_pdf(): void
    {
        $participante = User::factory()->participante()->create();

        $response = $this->actingAs($participante)->get('/u/pdf/mycourses');

        $response->assertOk();
    }

    public function test_admin_can_download_scheduled_courses_pdf(): void
    {
        $admin = User::factory()->administrador()->create();

        $response = $this->actingAs($admin)->get('/u/pdf/scheduled');

        $response->assertOk();
    }

    public function test_category_list_view_renders_rows_and_shared_layout(): void
    {
        \App\Models\Category::create(['name' => 'Área de Prueba 123']);

        $html = view('pdf.category', ['categories' => \App\Models\Category::all()])->render();

        $this->assertStringContainsString('Lista de Áreas de Conocimiento', $html);
        $this->assertStringContainsString('table class="data"', $html);
        $this->assertStringContainsString('Área de Prueba 123', $html);
        $this->assertStringContainsString('registros', $html);
    }

    public function test_admin_can_download_locations_pdf(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)->get('/u/pdf/locations')->assertOk();
    }

    public function test_facilitador_cannot_download_locations_pdf(): void
    {
        $facilitador = User::factory()->facilitador()->create();

        $this->actingAs($facilitador)
            ->from('/')
            ->get('/u/pdf/locations')
            ->assertRedirect('/');
    }

    public function test_locations_list_view_renders_rows_and_shared_layout(): void
    {
        \App\Models\Location::create(['name' => 'Aula de Prueba 321']);

        $html = view('pdf.locations', [
            'locations' => \App\Models\Location::with('floor.building')->get(),
        ])->render();

        $this->assertStringContainsString('Lista de Aulas', $html);
        $this->assertStringContainsString('table class="data"', $html);
        $this->assertStringContainsString('Aula de Prueba 321', $html);
        $this->assertStringContainsString('registros', $html);
    }

    public function test_admin_can_download_scheduled_course_participants_pdf(): void
    {
        $admin = User::factory()->administrador()->create();
        \App\Models\CourseStatus::create(['name' => 'Por Dictar']);
        $category = \App\Models\Category::factory()->create();
        $modality = \App\Models\Modality::factory()->create();
        $course = \App\Models\Course::factory()->create([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
            'duration' => 8,
        ]);
        \App\Models\Capacity::create(['min' => 1, 'max' => 10, 'course_id' => $course->id]);
        $facilitator = \App\Models\Facilitator::create(['person_id' => \App\Models\Person::factory()->create()->id]);
        $scheduled = \App\Models\Scheduled::create([
            'course_id' => $course->id,
            'facilitator_id' => $facilitator->id,
            'course_status_id' => \App\Models\CourseStatus::POR_DICTAR,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($admin)->get('/u/pdf/participants/' . $scheduled->id);

        $response->assertOk();
    }

    private function makeScheduleWithSession(?int $facilitatorId = null): \App\Models\Scheduled
    {
        \App\Models\CourseStatus::create(['name' => 'Por Dictar']);
        $category = \App\Models\Category::factory()->create();
        $modality = \App\Models\Modality::factory()->create();
        $course = \App\Models\Course::factory()->create([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
            'duration' => 8,
        ]);
        \App\Models\Capacity::create(['min' => 1, 'max' => 10, 'course_id' => $course->id]);

        $facilitatorId = $facilitatorId ?? \App\Models\Facilitator::create([
            'person_id' => \App\Models\Person::factory()->create()->id,
        ])->id;

        $location = \App\Models\Location::factory()->create();

        $scheduled = \App\Models\Scheduled::create([
            'course_id' => $course->id,
            'facilitator_id' => $facilitatorId,
            'course_status_id' => \App\Models\CourseStatus::POR_DICTAR,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        \App\Models\CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $location->id,
            'session_date' => now()->addWeek()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        return $scheduled;
    }

    public function test_admin_can_download_schedule_pdf(): void
    {
        $admin = User::factory()->administrador()->create();
        $scheduled = $this->makeScheduleWithSession();

        $this->actingAs($admin)->get('/u/pdf/schedule/' . $scheduled->id)->assertOk();
    }

    public function test_owning_facilitator_can_download_schedule_pdf(): void
    {
        $facilitator = User::factory()->facilitador()->create();
        $facilitatorRecord = \App\Models\Facilitator::create(['person_id' => $facilitator->person_id]);

        $scheduled = $this->makeScheduleWithSession($facilitatorRecord->id);

        $this->actingAs($facilitator)->get('/u/pdf/schedule/' . $scheduled->id)->assertOk();
    }

    public function test_unrelated_facilitator_cannot_download_schedule_pdf(): void
    {
        $facilitator = User::factory()->facilitador()->create();
        \App\Models\Facilitator::create(['person_id' => $facilitator->person_id]);

        $scheduled = $this->makeScheduleWithSession();

        $this->actingAs($facilitator)
            ->from('/')
            ->get('/u/pdf/schedule/' . $scheduled->id)
            ->assertRedirect('/');
    }

    public function test_assigned_participant_can_download_schedule_pdf(): void
    {
        $scheduled = $this->makeScheduleWithSession();
        $participant = User::factory()->participante()->create();

        \App\Models\Participant::create([
            'person_id' => $participant->person_id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => \App\Models\Participant::PORINICIAR,
        ]);

        $this->actingAs($participant)->get('/u/pdf/schedule/' . $scheduled->id)->assertOk();
    }

    public function test_unassigned_participant_cannot_download_schedule_pdf(): void
    {
        $scheduled = $this->makeScheduleWithSession();
        $participant = User::factory()->participante()->create();

        $this->actingAs($participant)
            ->from('/')
            ->get('/u/pdf/schedule/' . $scheduled->id)
            ->assertRedirect('/');
    }

    public function test_missing_schedule_redirects_back(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->from('/')
            ->get('/u/pdf/schedule/999999')
            ->assertRedirect('/');
    }

    public function test_scheduled_pdf_accepts_month_filter(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->get('/u/pdf/scheduled?hidden_date=10-2026')
            ->assertOk();
    }

    public function test_my_courses_pdf_accepts_month_filter(): void
    {
        $participant = User::factory()->participante()->create();

        $this->actingAs($participant)
            ->get('/u/pdf/mycourses?hidden_date=10-2026')
            ->assertOk();
    }

    public function test_person_courses_pdf_accepts_month_filter(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->get('/u/pdf/person_courses/'.$admin->person_id.'?hidden_date=10-2026')
            ->assertOk();
    }

    public function test_schedule_pdf_view_renders_header_grid_and_merged_dates(): void
    {
        $scheduled = $this->makeScheduleWithSession();

        // Same weekday/time/aula on a later date merges into a single block.
        $session = $scheduled->sessions()->first();
        \App\Models\CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $session->location_id,
            'session_date' => \Carbon\Carbon::parse($session->session_date)->addWeek()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $scheduled->load(['course', 'facilitator.person', 'sessions.location']);
        $grid = $scheduled->weeklyScheduleGrid();

        $html = view('pdf.schedule', [
            'scheduled' => $scheduled,
            'dayNames' => ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
            'rows' => $grid['rows'],
            'columns' => $grid['columns'],
        ])->render();

        $this->assertStringContainsString($scheduled->course->title, $html);
        $this->assertStringContainsString('table class="schedule-grid"', $html);
        $this->assertStringContainsString('8:00 AM', $html);
        $this->assertStringContainsString('08:00', $html);
        $this->assertStringContainsString('.schedule-block .sb-date { display: inline-block; white-space: nowrap; }', $html);
        $this->assertSame(1, substr_count($html, 'class="schedule-block"'));
        $this->assertSame(2, substr_count($html, 'class="sb-date"'));
    }

    public function test_schedule_pdf_splits_overlapping_blocks(): void
    {
        $scheduled = $this->makeScheduleWithSession();
        $session = $scheduled->sessions()->first();

        \App\Models\CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => \App\Models\Location::factory()->create()->id,
            'session_date' => $session->session_date,
            'start_time' => '07:00',
            'end_time' => '09:00',
        ]);

        $scheduled->load(['course', 'facilitator.person', 'sessions.location']);
        $grid = $scheduled->weeklyScheduleGrid();

        $html = view('pdf.schedule', [
            'scheduled' => $scheduled,
            'dayNames' => ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
            'rows' => $grid['rows'],
            'columns' => $grid['columns'],
        ])->render();

        $this->assertSame(2, substr_count($html, 'class="schedule-block"'));
        $this->assertStringContainsString('colspan="2"', $html);
        $this->assertStringContainsString('7:00 AM', $html);
        $this->assertStringContainsString('9:00 AM', $html);
    }

    public function test_schedule_pdf_merges_exact_times_across_locations(): void
    {
        $scheduled = $this->makeScheduleWithSession();
        $session = $scheduled->sessions()->first();
        $secondLocation = \App\Models\Location::factory()->create(['name' => 'Aula Compartida']);

        \App\Models\CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $secondLocation->id,
            'session_date' => $session->session_date,
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
        ]);

        $scheduled->load(['course', 'facilitator.person', 'sessions.location']);
        $grid = $scheduled->weeklyScheduleGrid();

        $html = view('pdf.schedule', [
            'scheduled' => $scheduled,
            'dayNames' => ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
            'rows' => $grid['rows'],
            'columns' => $grid['columns'],
        ])->render();

        $day = (int) \Carbon\Carbon::parse($session->session_date)->dayOfWeekIso;

        $this->assertSame(1, substr_count($html, 'class="schedule-block"'));
        $this->assertSame(1, $grid['columns'][$day]);
        $this->assertStringContainsString('Aula Compartida', $html);
    }
}
