<?php

namespace Tests\Unit\Models;

use App\Models\Course;
use App\Models\CourseStatus;
use App\Models\Facilitator;
use App\Models\Participant;
use App\Models\ParticipantStatus;
use App\Models\Person;
use App\Models\Scheduled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantTest extends TestCase
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
        $this->seed(\Database\Seeders\ModalitySeeder::class);
    }

    protected function createParticipant(int $participantStatusId = Participant::ENCURSO): Participant
    {
        $person = Person::factory()->create();
        $course = Course::factory()->create();
        $facilitatorPerson = Person::factory()->create();
        $facilitator = Facilitator::create(['person_id' => $facilitatorPerson->id]);

        $scheduled = Scheduled::create([
            'course_id' => $course->id,
            'course_status_id' => CourseStatus::POR_DICTAR,
            'facilitator_id' => $facilitator->id,
            'start_date' => now(),
            'end_date' => now()->addWeek(),
        ]);

        return Participant::create([
            'person_id' => $person->id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => $participantStatusId,
        ]);
    }

    public function test_is_en_curso(): void
    {
        $participant = $this->createParticipant(Participant::ENCURSO);

        $this->assertTrue($participant->isEnCurso());
        $this->assertFalse($participant->isReprobado());
        $this->assertFalse($participant->isAprobado());
        $this->assertFalse($participant->isCancelado());
        $this->assertFalse($participant->isPorIniciar());
    }

    public function test_is_reprobado(): void
    {
        $participant = $this->createParticipant(Participant::REPROBADO);

        $this->assertTrue($participant->isReprobado());
        $this->assertFalse($participant->isEnCurso());
        $this->assertFalse($participant->isAprobado());
        $this->assertFalse($participant->isCancelado());
        $this->assertFalse($participant->isPorIniciar());
    }

    public function test_is_aprobado(): void
    {
        $participant = $this->createParticipant(Participant::APROBADO);

        $this->assertTrue($participant->isAprobado());
        $this->assertFalse($participant->isEnCurso());
        $this->assertFalse($participant->isReprobado());
        $this->assertFalse($participant->isCancelado());
        $this->assertFalse($participant->isPorIniciar());
    }

    public function test_is_cancelado(): void
    {
        $participant = $this->createParticipant(Participant::CANCELADO);

        $this->assertTrue($participant->isCancelado());
        $this->assertFalse($participant->isEnCurso());
        $this->assertFalse($participant->isReprobado());
        $this->assertFalse($participant->isAprobado());
        $this->assertFalse($participant->isPorIniciar());
    }

    public function test_is_por_iniciar(): void
    {
        $participant = $this->createParticipant(Participant::PORINICIAR);

        $this->assertTrue($participant->isPorIniciar());
        $this->assertFalse($participant->isEnCurso());
        $this->assertFalse($participant->isReprobado());
        $this->assertFalse($participant->isAprobado());
        $this->assertFalse($participant->isCancelado());
    }

    public function test_badge_status_returns_success_for_en_curso(): void
    {
        $participant = $this->createParticipant(Participant::ENCURSO);

        $this->assertEquals('success', $participant->badgeStatus());
    }

    public function test_badge_status_returns_danger_for_reprobado(): void
    {
        $participant = $this->createParticipant(Participant::REPROBADO);

        $this->assertEquals('danger', $participant->badgeStatus());
    }

    public function test_badge_status_returns_info_for_aprobado(): void
    {
        $participant = $this->createParticipant(Participant::APROBADO);

        $this->assertEquals('info', $participant->badgeStatus());
    }

    public function test_badge_status_returns_default_for_cancelado(): void
    {
        $participant = $this->createParticipant(Participant::CANCELADO);

        $this->assertEquals('default', $participant->badgeStatus());
    }

    public function test_badge_status_returns_warning_for_por_iniciar(): void
    {
        $participant = $this->createParticipant(Participant::PORINICIAR);

        $this->assertEquals('warning', $participant->badgeStatus());
    }

    public function test_person_relationship(): void
    {
        $participant = $this->createParticipant();

        $this->assertInstanceOf(Person::class, $participant->person);
    }

    public function test_scheduled_relationship(): void
    {
        $participant = $this->createParticipant();

        $this->assertInstanceOf(Scheduled::class, $participant->scheduled);
    }

    public function test_participant_status_relationship(): void
    {
        $participant = $this->createParticipant(Participant::ENCURSO);

        $this->assertInstanceOf(ParticipantStatus::class, $participant->participantStatus);
        $this->assertEquals(Participant::ENCURSO, $participant->participantStatus->id);
    }

    public function test_soft_deletes(): void
    {
        $participant = $this->createParticipant();

        $id = $participant->id;
        $participant->delete();

        $this->assertSoftDeleted('participants', ['id' => $id]);
    }

    public function test_can_restore_after_soft_delete(): void
    {
        $participant = $this->createParticipant();

        $id = $participant->id;
        $participant->delete();
        $participant->restore();

        $this->assertDatabaseHas('participants', ['id' => $id, 'deleted_at' => null]);
    }
}
