<?php

namespace Tests\Feature\CursoProgramadoController;

use App\Models\Capacity;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\CourseStatus;
use App\Models\Facilitator;
use App\Models\Participant;
use App\Models\ParticipantStatus;
use App\Models\Person;
use App\Models\Prerequisite;
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
        $this->seed(\Database\Seeders\ParticipantStatusSeeder::class);

        CourseStatus::create(['name' => 'Por Dictar']);
        CourseStatus::create(['name' => 'En Curso']);
        CourseStatus::create(['name' => 'Culminado']);
        CourseStatus::create(['name' => 'Cancelado']);
    }

    private function makeScheduledPorDictar(int $maxCapacity = 100): Scheduled
    {
        $category = \App\Models\Category::factory()->create();
        $modality = \App\Models\Modality::factory()->create();
        $course = Course::factory()->create([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
            'duration' => 8,
        ]);
        Capacity::create(['min' => 1, 'max' => $maxCapacity, 'course_id' => $course->id]);

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
            ->getJson('/u/af_programadas/assignList?scheduled_id=' . $scheduled->id);

        $response->assertOk();
        $response->assertJsonStructure(['results', 'more']);
        $response->assertJsonPath('results', []);
        $response->assertJsonPath('more', false);
    }

    public function test_assign_list_is_paginated_and_search_finds_participants_beyond_first_page(): void
    {
        $scheduled = $this->makeScheduledPorDictar();
        $admin = User::factory()->administrador()->create();
        $target = null;

        foreach (range(1, 21) as $number) {
            $participant = User::factory()->participante()->create();
            $participant->person->update([
                'name' => 'Roster',
                'last_name' => sprintf('Participant %02d', $number),
            ]);

            if ($number === 21) {
                $target = $participant;
                $target->person->update([
                    'name' => 'Remote',
                    'last_name' => 'Searchable',
                    'id_number' => '987654321',
                ]);
            }
        }

        $firstPage = $this->actingAs($admin)
            ->getJson('/u/af_programadas/assignList?scheduled_id=' . $scheduled->id . '&page=1');

        $firstPage->assertOk();
        $firstPage->assertJsonCount(20, 'results');
        $firstPage->assertJsonPath('more', true);
        $this->assertNotContains($target->person_id, collect($firstPage->json('results'))->pluck('id')->all());

        $search = $this->actingAs($admin)
            ->getJson('/u/af_programadas/assignList?scheduled_id=' . $scheduled->id . '&q=Remote%20Searchable&page=1');

        $search->assertOk();
        $search->assertJsonCount(1, 'results');
        $search->assertJsonPath('results.0.id', $target->person_id);
        $search->assertJsonPath('results.0.text', 'Remote Searchable C.I: V-987.654.321');
        $search->assertJsonPath('more', false);

        $idSearch = $this->actingAs($admin)
            ->getJson('/u/af_programadas/assignList?scheduled_id=' . $scheduled->id . '&q=987654321');

        $idSearch->assertOk();
        $idSearch->assertJsonCount(1, 'results');
        $idSearch->assertJsonPath('results.0.id', $target->person_id);

        $secondPage = $this->actingAs($admin)
            ->getJson('/u/af_programadas/assignList?scheduled_id=' . $scheduled->id . '&page=2');

        $secondPage->assertOk();
        $secondPage->assertJsonCount(1, 'results');
        $secondPage->assertJsonPath('results.0.id', $target->person_id);
        $secondPage->assertJsonPath('more', false);
    }

    public function test_assign_list_only_excludes_participants_with_overlapping_non_cancelled_sessions(): void
    {
        $scheduled = $this->makeScheduledPorDictar();
        $admin = User::factory()->administrador()->create();
        $firstSessionDate = now()->addDays(8)->toDateString();
        $secondSessionDate = now()->addDays(9)->toDateString();
        $outsideSessionDate = now()->addDays(10)->toDateString();

        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => \App\Models\Location::factory()->create()->id,
            'session_date' => $firstSessionDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);
        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => \App\Models\Location::factory()->create()->id,
            'session_date' => $secondSessionDate,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $makeEnrolledParticipant = function (
            string $sessionDate,
            string $startTime,
            string $endTime,
            int $participantStatus,
            string $sessionStatus = 'scheduled',
            int $scheduledStatus = CourseStatus::EN_CURSO
        ) use ($scheduled): User {
            $participantUser = User::factory()->participante()->create();
            $overlappingScheduled = Scheduled::create([
                'course_id' => $scheduled->course_id,
                'facilitator_id' => $scheduled->facilitator_id,
                'course_status_id' => $scheduledStatus,
                'start_date' => now()->addDays(7)->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
            ]);

            CourseSession::create([
                'scheduled_course_id' => $overlappingScheduled->id,
                'location_id' => \App\Models\Location::factory()->create()->id,
                'session_date' => $sessionDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => $sessionStatus,
            ]);

            Participant::create([
                'person_id' => $participantUser->person_id,
                'scheduled_id' => $overlappingScheduled->id,
                'participant_status_id' => $participantStatus,
            ]);

            return $participantUser;
        };

        $conflictingParticipant = $makeEnrolledParticipant($firstSessionDate, '09:30', '10:30', Participant::ENCURSO);
        $backToBackParticipant = $makeEnrolledParticipant($firstSessionDate, '10:00', '11:00', Participant::ENCURSO);
        $differentDayParticipant = $makeEnrolledParticipant($outsideSessionDate, '08:30', '09:30', Participant::ENCURSO);
        $cancelledEnrollmentParticipant = $makeEnrolledParticipant($firstSessionDate, '09:30', '10:30', Participant::CANCELADO);
        $cancelledSessionParticipant = $makeEnrolledParticipant($firstSessionDate, '09:30', '10:30', Participant::ENCURSO, 'cancelled');
        $cancelledCourseParticipant = $makeEnrolledParticipant($firstSessionDate, '09:30', '10:30', Participant::ENCURSO, 'scheduled', CourseStatus::CANCELADO);
        $legacyParticipant = User::factory()->participante()->create();
        $legacyScheduled = Scheduled::create([
            'course_id' => $scheduled->course_id,
            'facilitator_id' => $scheduled->facilitator_id,
            'course_status_id' => CourseStatus::EN_CURSO,
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        Participant::create([
            'person_id' => $legacyParticipant->person_id,
            'scheduled_id' => $legacyScheduled->id,
            'participant_status_id' => Participant::ENCURSO,
        ]);

        $unassignedParticipant = User::factory()->participante()->create();

        $response = $this->actingAs($admin)
            ->getJson('/u/af_programadas/assignList?scheduled_id=' . $scheduled->id);

        $response->assertOk();
        $ids = collect($response->json('results'))->pluck('id')->all();
        $this->assertContains($backToBackParticipant->person_id, $ids);
        $this->assertContains($differentDayParticipant->person_id, $ids);
        $this->assertContains($cancelledEnrollmentParticipant->person_id, $ids);
        $this->assertContains($cancelledSessionParticipant->person_id, $ids);
        $this->assertContains($cancelledCourseParticipant->person_id, $ids);
        $this->assertContains($legacyParticipant->person_id, $ids);
        $this->assertContains($unassignedParticipant->person_id, $ids);
        $this->assertNotContains($conflictingParticipant->person_id, $ids);
    }

    public function test_assign_list_requires_a_completed_prerequisite_with_approved_status(): void
    {
        $scheduled = $this->makeScheduledPorDictar();
        $admin = User::factory()->administrador()->create();
        $eligibleParticipant = User::factory()->participante()->create();
        $ineligibleParticipant = User::factory()->participante()->create();
        $prerequisiteCourse = Course::factory()->create([
            'category_id' => $scheduled->course->category_id,
            'modality_id' => $scheduled->course->modality_id,
            'duration' => 4,
        ]);

        Prerequisite::create([
            'course_id' => $scheduled->course_id,
            'prerequisite' => $prerequisiteCourse->code,
            'course_code' => $scheduled->course->code,
        ]);

        $completedScheduled = Scheduled::create([
            'course_id' => $prerequisiteCourse->id,
            'facilitator_id' => $scheduled->facilitator_id,
            'course_status_id' => CourseStatus::CULMINADO,
            'start_date' => now()->subDays(20)->toDateString(),
            'end_date' => now()->subDays(15)->toDateString(),
        ]);

        Participant::create([
            'person_id' => $eligibleParticipant->person_id,
            'scheduled_id' => $completedScheduled->id,
            'participant_status_id' => Participant::APROBADO,
        ]);

        Participant::create([
            'person_id' => $ineligibleParticipant->person_id,
            'scheduled_id' => $completedScheduled->id,
            'participant_status_id' => Participant::REPROBADO,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/u/af_programadas/assignList?scheduled_id=' . $scheduled->id);

        $response->assertOk();
        $ids = collect($response->json('results'))->pluck('id')->all();
        $this->assertContains($eligibleParticipant->person_id, $ids);
        $this->assertNotContains($ineligibleParticipant->person_id, $ids);
    }

    public function test_programador_can_fetch_assign_list(): void
    {
        $scheduled = $this->makeScheduledPorDictar();
        $programador = User::factory()->programador()->create();

        $response = $this->actingAs($programador)
            ->getJson('/u/af_programadas/assignList?scheduled_id=' . $scheduled->id);

        $response->assertOk();
        $response->assertJsonStructure(['results', 'more']);
    }

    public function test_facilitador_cannot_fetch_assign_list(): void
    {
        $scheduled = $this->makeScheduledPorDictar();
        $facilitador = User::factory()->facilitador()->create();

        $response = $this->actingAs($facilitador)
            ->get('/u/af_programadas/assignList?scheduled_id=' . $scheduled->id);

        $response->assertForbidden();
    }

    public function test_participante_cannot_fetch_assign_list(): void
    {
        $scheduled = $this->makeScheduledPorDictar();
        $participante = User::factory()->participante()->create();

        $response = $this->actingAs($participante)
            ->get('/u/af_programadas/assignList?scheduled_id=' . $scheduled->id);

        $response->assertForbidden();
    }
}
