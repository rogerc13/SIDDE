<?php

namespace Tests\Feature\CursoProgramadoController;

use App\Models\Capacity;
use App\Models\Course;
use App\Models\CourseStatus;
use App\Models\Facilitator;
use App\Models\Participant;
use App\Models\Person;
use App\Models\Role;
use App\Models\Scheduled;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationTest extends TestCase
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

    private function makeCourse(int $maxCapacity = 10): Course
    {
        $course = \App\Models\Course::factory()->create([
            'category_id' => \App\Models\Category::factory()->create()->id,
            'modality_id' => \App\Models\Modality::factory()->create()->id,
            'duration' => 8,
        ]);

        Capacity::create(['min' => 1, 'max' => $maxCapacity, 'course_id' => $course->id]);

        return $course;
    }

    private function makeScheduled(Course $course, Facilitator $facilitator, int $statusId, string $startDate): Scheduled
    {
        return Scheduled::create([
            'course_id' => $course->id,
            'facilitator_id' => $facilitator->id,
            'course_status_id' => $statusId,
            'start_date' => $startDate,
            'end_date' => \Carbon\Carbon::parse($startDate)->addDays(5)->toDateString(),
        ]);
    }

    private function makeOwningFacilitador(): array
    {
        $user = User::factory()->facilitador()->create();
        $facilitator = Facilitator::create(['person_id' => $user->person_id]);

        return [$user, $facilitator];
    }

    private function makeParticipant(Scheduled $scheduled, ?int $statusId = null): Participant
    {
        return Participant::create([
            'person_id' => Person::factory()->create()->id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => $statusId ?? Participant::ENCURSO,
        ]);
    }

    private function statusUrl(Participant $participant): string
    {
        return "/u/af_programadas/estado-participantes/{$participant->id}";
    }

    public function test_seeder_creates_five_statuses_matching_database_labels(): void
    {
        $this->assertDatabaseCount('participant_status', 5);

        foreach (['En Curso', 'Reprobado', 'Aprobado', 'Cancelado', 'Por Iniciar'] as $name) {
            $this->assertDatabaseHas('participant_status', ['name' => $name]);
        }
    }

    public function test_admin_can_update_participant_status(): void
    {
        $admin = User::factory()->administrador()->create();
        [$facilitadorUser, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $participant = $this->makeParticipant($scheduled);

        $response = $this->actingAs($admin)->put($this->statusUrl($participant), [
            'estado' => Participant::APROBADO,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('alert');
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'participant_status_id' => Participant::APROBADO,
        ]);
    }

    public function test_facilitador_can_evaluate_own_course_in_progress(): void
    {
        [$facilitadorUser, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $participant = $this->makeParticipant($scheduled);

        $response = $this->actingAs($facilitadorUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->put($this->statusUrl($participant), [
                'estado' => Participant::APROBADO,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'participant_status_id' => Participant::APROBADO,
        ]);
    }

    public function test_facilitador_cannot_evaluate_another_facilitators_course(): void
    {
        [, $ownerFacilitator] = $this->makeOwningFacilitador();
        [$otherUser] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $ownerFacilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $participant = $this->makeParticipant($scheduled);

        $response = $this->actingAs($otherUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->put($this->statusUrl($participant), [
                'estado' => Participant::APROBADO,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);
    }

    public function test_facilitador_cannot_evaluate_course_that_has_not_started(): void
    {
        [$facilitadorUser, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, now()->addWeek()->toDateString());
        $participant = $this->makeParticipant($scheduled);

        $response = $this->actingAs($facilitadorUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->put($this->statusUrl($participant), [
                'estado' => Participant::APROBADO,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);
    }

    public function test_participant_role_cannot_change_evaluation_statuses(): void
    {
        $admin = User::factory()->administrador()->create();
        [$facilitadorUser, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $participant = $this->makeParticipant($scheduled);
        $participante = User::factory()->participante()->create();

        $response = $this->actingAs($participante)->put($this->statusUrl($participant), [
            'estado' => Participant::APROBADO,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('alert');
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);

        $this->assertTrue($admin->can('updateStatus', $participant->fresh()));
    }

    public function test_update_rejects_invalid_status_values(): void
    {
        $admin = User::factory()->administrador()->create();
        [$facilitadorUser, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $participant = $this->makeParticipant($scheduled);

        foreach ([999, 'aprobado', -1] as $invalid) {
            $this->actingAs($admin)
                ->from('/u/mis_acciones')
                ->put($this->statusUrl($participant), ['estado' => $invalid])
                ->assertSessionHasErrors('estado');
        }

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);
    }

    public function test_asignar_participante_rejects_duplicates(): void
    {
        $admin = User::factory()->administrador()->create();
        [$facilitadorUser, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, now()->addWeek()->toDateString());

        $payload = ['participante' => Person::factory()->create()->id, 'curso_p_id' => $scheduled->id];

        $this->actingAs($admin)
            ->from('/u/mis_acciones')
            ->post("/u/af_programadas/{$scheduled->id}/asignarParticipante", $payload)
            ->assertRedirect();

        $this->actingAs($admin)
            ->from('/u/mis_acciones')
            ->post("/u/af_programadas/{$scheduled->id}/asignarParticipante", $payload)
            ->assertSessionHas('alert');

        $this->assertDatabaseCount('participants', 1);
    }

    public function test_asignar_participante_respects_max_capacity(): void
    {
        $admin = User::factory()->administrador()->create();
        [$facilitadorUser, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(maxCapacity: 1), $facilitator, CourseStatus::POR_DICTAR, now()->addWeek()->toDateString());
        $this->makeParticipant($scheduled, Participant::PORINICIAR);

        $this->actingAs($admin)
            ->from('/u/mis_acciones')
            ->post("/u/af_programadas/{$scheduled->id}/asignarParticipante", [
                'participante' => Person::factory()->create()->id,
                'curso_p_id' => $scheduled->id,
            ])
            ->assertSessionHas('alert');

        $this->assertDatabaseCount('participants', 1);
    }

    public function test_assignment_initial_status_depends_on_start_date(): void
    {
        $admin = User::factory()->administrador()->create();
        [$facilitadorUser, $facilitator] = $this->makeOwningFacilitador();

        $future = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, now()->addWeek()->toDateString());
        $past = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, now()->subDay()->toDateString());

        $this->actingAs($admin)->post("/u/af_programadas/{$future->id}/asignarParticipante", [
            'participante' => Person::factory()->create()->id,
            'curso_p_id' => $future->id,
        ]);

        $this->actingAs($admin)->post("/u/af_programadas/{$past->id}/asignarParticipante", [
            'participante' => Person::factory()->create()->id,
            'curso_p_id' => $past->id,
        ]);

        $this->assertSame(
            Participant::PORINICIAR,
            Participant::where('scheduled_id', $future->id)->first()->participant_status_id
        );
        $this->assertSame(
            Participant::ENCURSO,
            Participant::where('scheduled_id', $past->id)->first()->participant_status_id
        );
    }

    public function test_store_registers_new_participant_with_full_person_data(): void
    {
        $admin = User::factory()->administrador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(maxCapacity: 5), $facilitator, CourseStatus::POR_DICTAR, now()->addWeek()->toDateString());

        $payload = [
            'nombre' => 'Ana',
            'apellido' => 'Pérez',
            'ci' => '12345678',
            'id_type' => 1,
            'sex' => 'Femenino',
            'phone' => '04141234567',
            'email' => 'ana.perez@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $this->actingAs($admin)
            ->from('/u/mis_acciones')
            ->post("/u/af_programadas/{$scheduled->id}/participantes", $payload)
            ->assertRedirect();

        $person = Person::where('id_number', '12345678')->first();
        $this->assertNotNull($person);
        $this->assertSame('Femenino', $person->sex);
        $this->assertSame('04141234567', $person->phone);
        $this->assertSame(1, $person->id_type_id);

        $participant = Participant::where('person_id', $person->id)->where('scheduled_id', $scheduled->id)->first();
        $this->assertNotNull($participant);
        $this->assertSame(Participant::PORINICIAR, $participant->participant_status_id);

        $this->assertDatabaseHas('users', ['email' => 'ana.perez@example.com', 'role_id' => Role::PARTICIPANTE]);
    }

    public function test_admin_sees_evaluation_page_for_scheduled_course(): void
    {
        $admin = User::factory()->administrador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $participant = $this->makeParticipant($scheduled);

        $response = $this->actingAs($admin)->get("/u/af_programadas/{$scheduled->id}/participantes");

        $response->assertOk();
        $response->assertViewHas('canEvaluate', true);
        $response->assertViewHas('statuses');
        $response->assertViewHas('participantes');
    }

    public function test_facilitador_cannot_open_participants_page_of_other_facilitators_course_is_not_restricted_by_view(): void
    {
        // View access remains role-based (any facilitador can list participants),
        // but evaluation controls are hidden and the server blocks writes.
        [, $ownerFacilitator] = $this->makeOwningFacilitador();
        [$otherUser] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $ownerFacilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());

        $response = $this->actingAs($otherUser)->get("/u/af_programadas/{$scheduled->id}/participantes");

        $response->assertOk();
        $response->assertViewHas('canEvaluate', false);
    }

    public function test_participant_role_cannot_open_participants_page(): void
    {
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $participante = User::factory()->participante()->create();

        $this->actingAs($participante)
            ->from('/')
            ->get("/u/af_programadas/{$scheduled->id}/participantes")
            ->assertRedirect('/');
    }
}
