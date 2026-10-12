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
}
