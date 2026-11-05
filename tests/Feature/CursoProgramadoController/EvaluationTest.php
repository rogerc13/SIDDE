<?php

namespace Tests\Feature\CursoProgramadoController;

use App\Models\Capacity;
use App\Models\Course;
use App\Models\CourseSession;
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

    public function test_programador_cannot_update_participant_status(): void
    {
        $programador = User::factory()->programador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $participant = $this->makeParticipant($scheduled);

        $response = $this->actingAs($programador)
            ->withHeaders(['Accept' => 'application/json'])
            ->put($this->statusUrl($participant), [
                'estado' => Participant::APROBADO,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);
        $this->assertFalse($programador->can('evaluate', $scheduled));
        $this->assertFalse($programador->can('updateStatus', $participant->fresh()));
    }

    public function test_programador_can_open_course_roster(): void
    {
        $programador = User::factory()->programador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $this->makeParticipant($scheduled);

        $response = $this->actingAs($programador)
            ->get("/u/af_programadas/{$scheduled->id}/participantes");

        $response->assertOk();
        $response->assertViewHas('participantes');
        $response->assertViewHas('statuses');
        $response->assertViewHas('canEvaluate', false);
        $this->assertTrue($programador->can('getAllPorCurso', Participant::class));
    }

    public function test_programador_can_assign_participant(): void
    {
        $programador = User::factory()->programador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, now()->addWeek()->toDateString());
        $participantUser = User::factory()->participante()->create();

        $this->assertTrue($programador->can('store', Participant::class));

        $this->actingAs($programador)
            ->getJson('/u/af_programadas/assignList?scheduled_id=' . $scheduled->id)
            ->assertOk();

        $this->actingAs($programador)
            ->from('/u/af_programadas/' . $scheduled->id . '/participantes')
            ->post('/u/af_programadas/' . $scheduled->id . '/asignarParticipante', [
                'participante' => $participantUser->person_id,
                'curso_p_id' => $scheduled->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('participants', [
            'person_id' => $participantUser->person_id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => Participant::PORINICIAR,
        ]);
    }

    public function test_programador_can_register_new_participant(): void
    {
        $programador = User::factory()->programador()->create();
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

        $this->actingAs($programador)
            ->from('/u/af_programadas/' . $scheduled->id . '/participantes')
            ->post("/u/af_programadas/{$scheduled->id}/participantes", $payload)
            ->assertRedirect();

        $person = Person::where('id_number', '12345678')->first();
        $this->assertNotNull($person);

        $this->assertDatabaseHas('participants', [
            'person_id' => $person->id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => Participant::PORINICIAR,
        ]);
        $this->assertDatabaseHas('users', ['email' => 'ana.perez@example.com', 'role_id' => Role::PARTICIPANTE]);
    }

    public function test_programador_can_remove_participant(): void
    {
        $programador = User::factory()->programador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, now()->addWeek()->toDateString());
        $participant = $this->makeParticipant($scheduled, Participant::PORINICIAR);

        $this->assertTrue($programador->can('delete', Participant::class));

        $this->actingAs($programador)
            ->from('/u/af_programadas/' . $scheduled->id . '/participantes')
            ->delete("/u/af_programadas/participantes/{$participant->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('participants', ['id' => $participant->id]);
    }

    public function test_programador_does_not_get_evaluation_controls(): void
    {
        $programador = User::factory()->programador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $this->makeParticipant($scheduled);

        $response = $this->actingAs($programador)->get("/u/af_programadas/{$scheduled->id}/ver");

        $response->assertOk();
        $response->assertViewHas('canEvaluate', false);
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

    public function test_assignment_rejects_participant_with_a_colliding_course_session(): void
    {
        $admin = User::factory()->administrador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $targetDate = now()->addDays(8)->toDateString();
        $targetScheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, $targetDate);
        $otherScheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, $targetDate);
        $participantUser = User::factory()->participante()->create();
        $location = \App\Models\Location::factory()->create();

        CourseSession::create([
            'scheduled_course_id' => $targetScheduled->id,
            'location_id' => $location->id,
            'session_date' => $targetDate,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);
        CourseSession::create([
            'scheduled_course_id' => $otherScheduled->id,
            'location_id' => $location->id,
            'session_date' => $targetDate,
            'start_time' => '10:30',
            'end_time' => '12:00',
            'status' => 'scheduled',
        ]);
        Participant::create([
            'person_id' => $participantUser->person_id,
            'scheduled_id' => $otherScheduled->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);

        $this->actingAs($admin)
            ->from('/u/af_programadas/' . $targetScheduled->id . '/participantes')
            ->post('/u/af_programadas/' . $targetScheduled->id . '/asignarParticipante', [
                'participante' => $participantUser->person_id,
                'curso_p_id' => $targetScheduled->id,
            ])
            ->assertSessionHas('alert');

        $this->assertDatabaseMissing('participants', [
            'person_id' => $participantUser->person_id,
            'scheduled_id' => $targetScheduled->id,
        ]);
    }

    public function test_assignment_allows_overlapping_course_dates_when_sessions_do_not_collide(): void
    {
        $admin = User::factory()->administrador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $targetDate = now()->addDays(8)->toDateString();
        $otherDate = now()->addDays(9)->toDateString();
        $targetScheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, $targetDate);
        $otherScheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, $targetDate);
        $participantUser = User::factory()->participante()->create();
        $location = \App\Models\Location::factory()->create();

        CourseSession::create([
            'scheduled_course_id' => $targetScheduled->id,
            'location_id' => $location->id,
            'session_date' => $targetDate,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);
        CourseSession::create([
            'scheduled_course_id' => $otherScheduled->id,
            'location_id' => $location->id,
            'session_date' => $otherDate,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);
        Participant::create([
            'person_id' => $participantUser->person_id,
            'scheduled_id' => $otherScheduled->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);

        $this->actingAs($admin)
            ->from('/u/af_programadas/' . $targetScheduled->id . '/participantes')
            ->post('/u/af_programadas/' . $targetScheduled->id . '/asignarParticipante', [
                'participante' => $participantUser->person_id,
                'curso_p_id' => $targetScheduled->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('participants', [
            'person_id' => $participantUser->person_id,
            'scheduled_id' => $targetScheduled->id,
        ]);
    }

    public function test_assignment_does_not_apply_date_range_fallback_to_legacy_schedules_without_sessions(): void
    {
        $admin = User::factory()->administrador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $targetDate = now()->addDays(8)->toDateString();
        $targetScheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, $targetDate);
        $legacyScheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, $targetDate);
        $participantUser = User::factory()->participante()->create();

        Participant::create([
            'person_id' => $participantUser->person_id,
            'scheduled_id' => $legacyScheduled->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);

        $this->actingAs($admin)
            ->from('/u/af_programadas/' . $targetScheduled->id . '/participantes')
            ->post('/u/af_programadas/' . $targetScheduled->id . '/asignarParticipante', [
                'participante' => $participantUser->person_id,
                'curso_p_id' => $targetScheduled->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('participants', [
            'person_id' => $participantUser->person_id,
            'scheduled_id' => $targetScheduled->id,
        ]);
    }

    public function test_assignment_allows_participant_when_overlapping_session_is_cancelled(): void
    {
        $admin = User::factory()->administrador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $sessionDate = now()->addDays(8)->toDateString();
        $targetScheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, $sessionDate);
        $otherScheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, $sessionDate);
        $participantUser = User::factory()->participante()->create();
        $location = \App\Models\Location::factory()->create();

        foreach ([$targetScheduled, $otherScheduled] as $scheduled) {
            CourseSession::create([
                'scheduled_course_id' => $scheduled->id,
                'location_id' => $location->id,
                'session_date' => $sessionDate,
                'start_time' => '09:00',
                'end_time' => '11:00',
                'status' => $scheduled->id === $otherScheduled->id ? 'cancelled' : 'scheduled',
            ]);
        }

        Participant::create([
            'person_id' => $participantUser->person_id,
            'scheduled_id' => $otherScheduled->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);

        $this->actingAs($admin)
            ->from('/u/af_programadas/' . $targetScheduled->id . '/participantes')
            ->post('/u/af_programadas/' . $targetScheduled->id . '/asignarParticipante', [
                'participante' => $participantUser->person_id,
                'curso_p_id' => $targetScheduled->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('participants', [
            'person_id' => $participantUser->person_id,
            'scheduled_id' => $targetScheduled->id,
        ]);
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

    public function test_participant_status_update_blocked_when_scheduled_cancelled(): void
    {
        $admin = User::factory()->administrador()->create();
        [$facilitadorUser, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::CANCELADO, now()->subDay()->toDateString());
        $participant = $this->makeParticipant($scheduled, Participant::CANCELADO);

        $response = $this->actingAs($admin)->put($this->statusUrl($participant), [
            'estado' => Participant::APROBADO,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('alert');
        $this->assertSame(Participant::CANCELADO, $participant->fresh()->participant_status_id);
    }

    public function test_participant_status_update_allowed_when_scheduled_culminado(): void
    {
        $admin = User::factory()->administrador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::CULMINADO, now()->subWeek()->toDateString());
        $participant = $this->makeParticipant($scheduled, Participant::ENCURSO);

        $response = $this->actingAs($admin)->put($this->statusUrl($participant), [
            'estado' => Participant::APROBADO,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('alert');
        $this->assertSame(Participant::APROBADO, $participant->fresh()->participant_status_id);
    }

    public function test_participant_delete_blocked_for_cancelled_and_culminado_actions(): void
    {
        $admin = User::factory()->administrador()->create();

        foreach ([CourseStatus::CANCELADO, CourseStatus::CULMINADO] as $status) {
            [, $facilitator] = $this->makeOwningFacilitador();
            $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, $status, now()->subWeek()->toDateString());
            $participant = $this->makeParticipant($scheduled);

            $response = $this->actingAs($admin)->delete('/u/af_programadas/participantes/' . $participant->id);

            $response->assertRedirect();
            $response->assertSessionHas('alert');
            $this->assertNotSoftDeleted('participants', ['id' => $participant->id]);
        }
    }

    public function test_participants_page_uses_fixed_assign_list_endpoint(): void
    {
        $admin = User::factory()->administrador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, now()->addWeek()->toDateString());

        $response = $this->actingAs($admin)->get("/u/af_programadas/{$scheduled->id}/participantes");

        $response->assertOk();
        $response->assertSee('/u/af_programadas/assignList', false);
        $response->assertSee('modal-dialog sidde-modal', false);
        $response->assertSee('fa-user-plus', false);
        $response->assertSee('id="participante"', false);
        $response->assertSee('placeholder: \'Buscar por nombre, apellido o cédula\'', false);
        $response->assertSee('af_programadas/assignList', false);
        $response->assertSee('type: \'GET\'', false);
        $response->assertSee('ajaxSearchRequest.abort()', false);
        $response->assertSee('page: page || 1', false);
        $response->assertDontSee('response.list.forEach', false);
        $response->assertDontSee('af_programadas/participantes/assignList', false);
    }

    public function test_participants_page_hides_management_actions_for_cancelled_action(): void
    {
        $admin = User::factory()->administrador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::CANCELADO, now()->subWeek()->toDateString());
        $this->makeParticipant($scheduled);

        $response = $this->actingAs($admin)->get("/u/af_programadas/{$scheduled->id}/participantes");

        $response->assertOk();
        $response->assertDontSee('Editar Estado del Participante');
        $response->assertDontSee('Eliminar Participante');
    }

    public function test_participants_page_hides_remove_but_allows_status_for_culminado_action(): void
    {
        $admin = User::factory()->administrador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::CULMINADO, now()->subWeek()->toDateString());
        $this->makeParticipant($scheduled);

        $response = $this->actingAs($admin)->get("/u/af_programadas/{$scheduled->id}/participantes");

        $response->assertOk();
        $response->assertSee('Editar Estado del Participante');
        $response->assertDontSee('Eliminar Participante');
    }

    public function test_owning_facilitador_can_view_scheduled_details(): void
    {
        [$facilitadorUser, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());

        $response = $this->actingAs($facilitadorUser)->get("/u/af_programadas/{$scheduled->id}/ver");

        $response->assertOk();
        $response->assertSee($scheduled->course->title);
        $response->assertSee('Creada:');
        $response->assertSee(url('u/mis_acciones'));
        $response->assertDontSee('Más funciones');
    }

    public function test_other_facilitador_cannot_view_scheduled_details(): void
    {
        [, $ownerFacilitator] = $this->makeOwningFacilitador();
        [$otherUser] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $ownerFacilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());

        $response = $this->actingAs($otherUser)
            ->from('/u/mis_acciones')
            ->get("/u/af_programadas/{$scheduled->id}/ver");

        $response->assertRedirect();
        $response->assertSessionHas('alert');
    }

    public function test_mis_acciones_facilitador_view_icon_opens_details(): void
    {
        [$facilitadorUser, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, now()->addWeek()->toDateString());

        $response = $this->actingAs($facilitadorUser)->get('/u/mis_acciones');

        $response->assertOk();
        $response->assertSee(url('u/af_programadas/' . $scheduled->id . '/ver'), false);
    }

    public function test_facilitador_viewing_missing_details_redirects_to_mis_acciones(): void
    {
        [$facilitadorUser] = $this->makeOwningFacilitador();

        $response = $this->actingAs($facilitadorUser)->get('/u/af_programadas/999999/ver');

        $response->assertRedirect('/u/mis_acciones');
        $response->assertSessionHas('alert');
    }

    public function test_details_page_renders_participants_tab(): void
    {
        $admin = User::factory()->administrador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $participant = $this->makeParticipant($scheduled);

        $response = $this->actingAs($admin)->get("/u/af_programadas/{$scheduled->id}/ver");

        $response->assertOk();
        $response->assertSee('participant_status[' . $participant->id . ']', false);
        $response->assertSee($participant->person->name);
        $response->assertSee('Asignar participante');
        $response->assertSee('Registrar nuevo participante');
    }

    public function test_details_page_enables_evaluation_for_owning_facilitador(): void
    {
        [$facilitadorUser, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $this->makeParticipant($scheduled);

        $response = $this->actingAs($facilitadorUser)->get("/u/af_programadas/{$scheduled->id}/ver");

        $response->assertOk();
        $response->assertViewHas('canEvaluate', true);
    }

    public function test_details_page_shows_available_evaluate_button_for_owning_facilitador(): void
    {
        [$facilitadorUser, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $this->makeParticipant($scheduled);

        $response = $this->actingAs($facilitadorUser)->get("/u/af_programadas/{$scheduled->id}/ver");

        $response->assertOk();
        $response->assertSee('Evaluar Participantes');
        $response->assertDontSee('evaluate-button is-blocked', false);
    }

    public function test_details_page_shows_blocked_evaluate_button_for_owning_facilitador_when_action_por_dictar(): void
    {
        [$facilitadorUser, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, now()->addWeek()->toDateString());

        $response = $this->actingAs($facilitadorUser)->get("/u/af_programadas/{$scheduled->id}/ver");

        $response->assertOk();
        $response->assertSee('Evaluar Participantes');
        $response->assertSee('evaluate-button is-blocked', false);
        $response->assertSee('action-block-reasons hidden', false);
        $response->assertSee('evaluation-blocked-reason', false);
        $response->assertSee('No se puede evaluar: la acción de formación está «Por Dictar»', false);
        $response->assertDontSee('data-blocked-reason=', false);
    }

    public function test_details_page_shows_blocked_evaluate_button_for_admin_when_action_por_dictar(): void
    {
        $admin = User::factory()->administrador()->create();
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, now()->addWeek()->toDateString());

        $response = $this->actingAs($admin)->get("/u/af_programadas/{$scheduled->id}/ver");

        $response->assertOk();
        $response->assertSee('Evaluar Participantes');
        $response->assertSee('action-block-reasons hidden', false);
        $response->assertSee('evaluation-blocked-reason', false);
        $response->assertSee('No se puede evaluar: la acción de formación está «Por Dictar»', false);
        $response->assertDontSee('data-blocked-reason=', false);
    }

    public function test_participants_page_does_not_show_evaluate_button_for_other_facilitador(): void
    {
        [, $ownerFacilitator] = $this->makeOwningFacilitador();
        [$otherUser] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $ownerFacilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());

        $response = $this->actingAs($otherUser)->get("/u/af_programadas/{$scheduled->id}/participantes");

        $response->assertOk();
        $response->assertDontSee('Evaluar Participantes');
    }

    private function makeEnrolledParticipantUser(Scheduled $scheduled): User
    {
        $user = User::factory()->participante()->create();

        Participant::create([
            'person_id' => $user->person_id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);

        return $user;
    }

    public function test_enrolled_participant_can_view_scheduled_details(): void
    {
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $participantUser = $this->makeEnrolledParticipantUser($scheduled);

        $response = $this->actingAs($participantUser)->get("/u/af_programadas/{$scheduled->id}/ver");

        $response->assertOk();
        $response->assertSee($scheduled->course->title);
        $response->assertSee('Programación');
        $response->assertSee('Ficha Técnica');
        $response->assertSee('Descargar horario');
        $response->assertSee(url('u/pdf/schedule/' . $scheduled->id), false);
        $response->assertDontSee('href="#tab-participantes"', false);
        $response->assertDontSee('Creada:');
        $response->assertDontSee('Registrados');
    }

    public function test_unenrolled_participant_cannot_view_scheduled_details(): void
    {
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $participantUser = User::factory()->participante()->create();

        $response = $this->actingAs($participantUser)
            ->from('/u/mis_acciones')
            ->get("/u/af_programadas/{$scheduled->id}/ver");

        $response->assertRedirect('/u/mis_acciones');
        $response->assertSessionHas('alert');
    }

    public function test_participant_details_show_only_manual_participante(): void
    {
        $course = $this->makeCourse();
        \App\Models\File::create(['course_id' => $course->id, 'type_id' => 1, 'path' => $course->code . '/Manual Facilitador.pdf']);
        \App\Models\File::create(['course_id' => $course->id, 'type_id' => 2, 'path' => $course->code . '/Manual Participante.pdf']);

        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($course, $facilitator, CourseStatus::EN_CURSO, now()->subDay()->toDateString());
        $participantUser = $this->makeEnrolledParticipantUser($scheduled);

        $response = $this->actingAs($participantUser)->get("/u/af_programadas/{$scheduled->id}/ver");

        $response->assertOk();
        $response->assertSee('Manual del Participante');
        $response->assertDontSee('Manual del Facilitador');
    }

    public function test_mis_acciones_participant_view_icon_opens_details(): void
    {
        [, $facilitator] = $this->makeOwningFacilitador();
        $scheduled = $this->makeScheduled($this->makeCourse(), $facilitator, CourseStatus::POR_DICTAR, now()->addWeek()->toDateString());
        $participantUser = $this->makeEnrolledParticipantUser($scheduled);

        $response = $this->actingAs($participantUser)->get('/u/mis_acciones');

        $response->assertOk();
        $response->assertSee(url('u/af_programadas/' . $scheduled->id . '/ver'), false);
    }
}
