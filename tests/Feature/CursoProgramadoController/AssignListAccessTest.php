<?php

namespace Tests\Feature\CursoProgramadoController;

use App\Models\Capacity;
use App\Models\Course;
use App\Models\CourseStatus;
use App\Models\Facilitator;
use App\Models\Person;
use App\Models\Role;
use App\Models\Scheduled;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignListAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\IdTypeSeeder::class);
        $this->seed(\Database\Seeders\TypeSeeder::class);

        CourseStatus::create(['name' => 'Por Dictar']);
    }

    private function makeScheduledPorDictar(): Scheduled
    {
        $category = \App\Models\Category::factory()->create();
        $modality = \App\Models\Modality::factory()->create();
        $course = Course::factory()->create([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
            'duration' => 8,
        ]);
        Capacity::create(['min' => 1, 'max' => 10, 'course_id' => $course->id]);

        return Scheduled::create([
            'course_id' => $course->id,
            'facilitator_id' => Facilitator::create(['person_id' => Person::factory()->create()->id])->id,
            'course_status_id' => CourseStatus::POR_DICTAR,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);
    }

    public function test_admin_can_fetch_assign_list(): void
    {
        $scheduled = $this->makeScheduledPorDictar();
        $admin = User::factory()->administrador()->create();

        $response = $this->actingAs($admin)
            ->post('/u/af_programadas/assignList', ['scheduled_id' => $scheduled->id]);

        $response->assertOk();
        $response->assertJsonStructure(['success']);
    }

    public function test_facilitador_cannot_fetch_assign_list(): void
    {
        $scheduled = $this->makeScheduledPorDictar();
        $facilitador = User::factory()->facilitador()->create();

        $this->actingAs($facilitador)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/u/af_programadas/assignList', ['scheduled_id' => $scheduled->id])
            ->assertForbidden();
    }

    public function test_participante_cannot_fetch_assign_list(): void
    {
        $scheduled = $this->makeScheduledPorDictar();
        $participante = User::factory()->participante()->create();

        $this->actingAs($participante)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/u/af_programadas/assignList', ['scheduled_id' => $scheduled->id])
            ->assertForbidden();
    }
}
