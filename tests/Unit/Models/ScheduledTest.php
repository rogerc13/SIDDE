<?php

namespace Tests\Unit\Models;

use App\Models\Capacity;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\CourseStatus;
use App\Models\Facilitator;
use App\Models\Location;
use App\Models\Participant;
use App\Models\ParticipantStatus;
use App\Models\Person;
use App\Models\Scheduled;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledTest extends TestCase
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

    protected function createScheduled(int $courseStatusId = CourseStatus::POR_DICTAR): Scheduled
    {
        $course = Course::factory()->create();
        $person = Person::factory()->create();
        $facilitator = Facilitator::create(['person_id' => $person->id]);

        return Scheduled::create([
            'course_id' => $course->id,
            'course_status_id' => $courseStatusId,
            'facilitator_id' => $facilitator->id,
            'start_date' => now()->addWeek(),
            'end_date' => now()->addWeeks(4),
        ]);
    }

    public function test_is_por_dictar(): void
    {
        $scheduled = $this->createScheduled(CourseStatus::POR_DICTAR);

        $this->assertTrue($scheduled->isPorDictar());
        $this->assertFalse($scheduled->isEnCurso());
        $this->assertFalse($scheduled->isCulminado());
        $this->assertFalse($scheduled->isCancelado());
    }

    public function test_is_en_curso(): void
    {
        $scheduled = $this->createScheduled(CourseStatus::EN_CURSO);

        $this->assertTrue($scheduled->isEnCurso());
        $this->assertFalse($scheduled->isPorDictar());
        $this->assertFalse($scheduled->isCulminado());
        $this->assertFalse($scheduled->isCancelado());
    }

    public function test_is_culminado(): void
    {
        $scheduled = $this->createScheduled(CourseStatus::CULMINADO);

        $this->assertTrue($scheduled->isCulminado());
        $this->assertFalse($scheduled->isPorDictar());
        $this->assertFalse($scheduled->isEnCurso());
        $this->assertFalse($scheduled->isCancelado());
    }

    public function test_is_cancelado(): void
    {
        $scheduled = $this->createScheduled(CourseStatus::CANCELADO);

        $this->assertTrue($scheduled->isCancelado());
        $this->assertFalse($scheduled->isPorDictar());
        $this->assertFalse($scheduled->isEnCurso());
        $this->assertFalse($scheduled->isCulminado());
    }

    public function test_badge_status_returns_warning_for_por_dictar(): void
    {
        $scheduled = $this->createScheduled(CourseStatus::POR_DICTAR);

        $this->assertEquals('warning', $scheduled->badgeStatus());
    }

    public function test_badge_status_returns_info_for_en_curso(): void
    {
        $scheduled = $this->createScheduled(CourseStatus::EN_CURSO);

        $this->assertEquals('info', $scheduled->badgeStatus());
    }

    public function test_badge_status_returns_success_for_culminado(): void
    {
        $scheduled = $this->createScheduled(CourseStatus::CULMINADO);

        $this->assertEquals('success', $scheduled->badgeStatus());
    }

    public function test_badge_status_returns_danger_for_cancelado(): void
    {
        $scheduled = $this->createScheduled(CourseStatus::CANCELADO);

        $this->assertEquals('danger', $scheduled->badgeStatus());
    }

    public function test_at_max_capacity_returns_true_when_full(): void
    {
        $course = Course::factory()->create(['duration' => 20]);
        Capacity::create(['min' => 1, 'max' => 2, 'course_id' => $course->id]);

        $person = Person::factory()->create();
        $facilitator = Facilitator::create(['person_id' => $person->id]);

        $scheduled = Scheduled::create([
            'course_id' => $course->id,
            'course_status_id' => CourseStatus::POR_DICTAR,
            'facilitator_id' => $facilitator->id,
            'start_date' => now(),
            'end_date' => now()->addWeek(),
        ]);

        ParticipantStatus::create(['name' => 'En Curso']);
        ParticipantStatus::create(['name' => 'Reprobado']);
        ParticipantStatus::create(['name' => 'Aprobado']);
        ParticipantStatus::create(['name' => 'Cancelado']);
        ParticipantStatus::create(['name' => 'Por Iniciar']);

        $p1 = Person::factory()->create();
        $p2 = Person::factory()->create();

        Participant::create(['person_id' => $p1->id, 'scheduled_id' => $scheduled->id, 'participant_status_id' => 1]);
        Participant::create(['person_id' => $p2->id, 'scheduled_id' => $scheduled->id, 'participant_status_id' => 1]);

        $this->assertTrue($scheduled->atMaxCapacity());
    }

    public function test_at_max_capacity_returns_false_when_not_full(): void
    {
        $course = Course::factory()->create(['duration' => 20]);
        Capacity::create(['min' => 1, 'max' => 5, 'course_id' => $course->id]);

        $person = Person::factory()->create();
        $facilitator = Facilitator::create(['person_id' => $person->id]);

        $scheduled = Scheduled::create([
            'course_id' => $course->id,
            'course_status_id' => CourseStatus::POR_DICTAR,
            'facilitator_id' => $facilitator->id,
            'start_date' => now(),
            'end_date' => now()->addWeek(),
        ]);

        $this->assertFalse($scheduled->atMaxCapacity());
    }

    public function test_participant_assignment_block_reason_returns_null_when_available(): void
    {
        $scheduled = $this->createScheduled(CourseStatus::POR_DICTAR);
        Capacity::create(['min' => 1, 'max' => 5, 'course_id' => $scheduled->course_id]);

        $this->assertNull($scheduled->participantAssignmentBlockReason());
    }

    public function test_participant_assignment_block_reason_reports_full_capacity(): void
    {
        $scheduled = $this->createScheduled(CourseStatus::POR_DICTAR);
        Capacity::create(['min' => 1, 'max' => 2, 'course_id' => $scheduled->course_id]);

        $first = Person::factory()->create();
        $second = Person::factory()->create();

        Participant::create([
            'person_id' => $first->id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => Participant::PORINICIAR,
        ]);
        Participant::create([
            'person_id' => $second->id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => Participant::PORINICIAR,
        ]);

        $reason = $scheduled->participantAssignmentBlockReason();

        $this->assertNotNull($reason);
        $this->assertStringContainsString('capacidad máxima', $reason);
        $this->assertStringContainsString('(2/2)', $reason);
    }

    public function test_participant_assignment_block_reason_reports_missing_capacity(): void
    {
        $scheduled = $this->createScheduled(CourseStatus::POR_DICTAR);

        $reason = $scheduled->participantAssignmentBlockReason();

        $this->assertNotNull($reason);
        $this->assertStringContainsString('no tiene una capacidad máxima configurada', $reason);
    }

    public function test_participant_assignment_block_reason_reports_non_por_dictar_status(): void
    {
        $scheduled = $this->createScheduled(CourseStatus::EN_CURSO);
        Capacity::create(['min' => 1, 'max' => 5, 'course_id' => $scheduled->course_id]);

        $reason = $scheduled->participantAssignmentBlockReason();

        $this->assertNotNull($reason);
        $this->assertStringContainsString('está «En Curso»', $reason);
        $this->assertStringContainsString('«Por Dictar»', $reason);
    }

    public function test_total_session_hours(): void
    {
        $course = Course::factory()->create(['duration' => 20]);
        $person = Person::factory()->create();
        $facilitator = Facilitator::create(['person_id' => $person->id]);
        $location = Location::factory()->create();

        $scheduled = Scheduled::create([
            'course_id' => $course->id,
            'course_status_id' => CourseStatus::POR_DICTAR,
            'facilitator_id' => $facilitator->id,
            'start_date' => now(),
            'end_date' => now()->addWeek(),
        ]);

        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $location->id,
            'session_date' => now(),
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'completed',
        ]);

        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $location->id,
            'session_date' => now()->addDay(),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'completed',
        ]);

        $this->assertEquals(6.0, $scheduled->totalSessionHours());
    }

    public function test_total_session_hours_returns_zero_with_no_sessions(): void
    {
        $course = Course::factory()->create(['duration' => 20]);
        $person = Person::factory()->create();
        $facilitator = Facilitator::create(['person_id' => $person->id]);

        $scheduled = Scheduled::create([
            'course_id' => $course->id,
            'course_status_id' => CourseStatus::POR_DICTAR,
            'facilitator_id' => $facilitator->id,
            'start_date' => now(),
            'end_date' => now()->addWeek(),
        ]);

        $this->assertEquals(0.0, $scheduled->totalSessionHours());
    }

    public function test_remaining_hours(): void
    {
        $course = Course::factory()->create(['duration' => 20]);
        $person = Person::factory()->create();
        $facilitator = Facilitator::create(['person_id' => $person->id]);
        $location = Location::factory()->create();

        $scheduled = Scheduled::create([
            'course_id' => $course->id,
            'course_status_id' => CourseStatus::POR_DICTAR,
            'facilitator_id' => $facilitator->id,
            'start_date' => now(),
            'end_date' => now()->addWeek(),
        ]);

        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $location->id,
            'session_date' => now(),
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'completed',
        ]);

        $this->assertEquals(16.0, $scheduled->remainingHours());
    }

    public function test_soft_deletes(): void
    {
        $scheduled = $this->createScheduled();

        $id = $scheduled->id;
        $scheduled->delete();

        $this->assertSoftDeleted('scheduled_course', ['id' => $id]);
    }

    public function test_can_restore_after_soft_delete(): void
    {
        $scheduled = $this->createScheduled();

        $id = $scheduled->id;
        $scheduled->delete();
        $scheduled->restore();

        $this->assertDatabaseHas('scheduled_course', ['id' => $id, 'deleted_at' => null]);
    }

    public function test_course_relationship(): void
    {
        $course = Course::factory()->create();
        $person = Person::factory()->create();
        $facilitator = Facilitator::create(['person_id' => $person->id]);

        $scheduled = Scheduled::create([
            'course_id' => $course->id,
            'course_status_id' => CourseStatus::POR_DICTAR,
            'facilitator_id' => $facilitator->id,
            'start_date' => now(),
            'end_date' => now()->addWeek(),
        ]);

        $this->assertInstanceOf(Course::class, $scheduled->course);
        $this->assertEquals($course->id, $scheduled->course->id);
    }

    public function test_facilitator_relationship(): void
    {
        $person = Person::factory()->create();
        $facilitator = Facilitator::create(['person_id' => $person->id]);
        $course = Course::factory()->create();

        $scheduled = Scheduled::create([
            'course_id' => $course->id,
            'course_status_id' => CourseStatus::POR_DICTAR,
            'facilitator_id' => $facilitator->id,
            'start_date' => now(),
            'end_date' => now()->addWeek(),
        ]);

        $this->assertInstanceOf(Facilitator::class, $scheduled->facilitator);
        $this->assertEquals($facilitator->id, $scheduled->facilitator->id);
    }

    public function test_course_status_relationship(): void
    {
        $scheduled = $this->createScheduled(CourseStatus::POR_DICTAR);

        $this->assertInstanceOf(CourseStatus::class, $scheduled->courseStatus);
        $this->assertEquals(CourseStatus::POR_DICTAR, $scheduled->courseStatus->id);
    }

    public function test_participants_relationship(): void
    {
        $scheduled = $this->createScheduled();

        $person = Person::factory()->create();
        Participant::create([
            'person_id' => $person->id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => 1,
        ]);

        $this->assertCount(1, $scheduled->participants);
    }

    public function test_sessions_relationship(): void
    {
        $scheduled = $this->createScheduled();
        $location = Location::factory()->create();

        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $location->id,
            'session_date' => now(),
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'completed',
        ]);

        $this->assertCount(1, $scheduled->sessions);
    }

    public function test_weekly_session_groups_merges_exact_times_across_locations(): void
    {
        $scheduled = $this->createScheduled();
        $locationA = Location::factory()->create(['name' => 'Aula A']);
        $locationB = Location::factory()->create(['name' => 'Aula B']);

        foreach ([$locationA, $locationB] as $location) {
            CourseSession::create([
                'scheduled_course_id' => $scheduled->id,
                'location_id' => $location->id,
                'session_date' => '2026-09-17',
                'start_time' => '07:00:00',
                'end_time' => '09:00:00',
            ]);
        }

        $groups = $scheduled->fresh()->weeklySessionGroups();

        $this->assertCount(1, $groups);
        $this->assertStringContainsString('Aula A', $groups[0]['location_name']);
        $this->assertStringContainsString('Aula B', $groups[0]['location_name']);
        $this->assertSame(['17-09-2026'], $groups[0]['dates']);
    }

    public function test_weekly_session_layout_splits_overlapping_groups(): void
    {
        $scheduled = $this->createScheduled();
        $locationA = Location::factory()->create();
        $locationB = Location::factory()->create();

        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $locationA->id,
            'session_date' => '2026-09-21',
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
        ]);

        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $locationB->id,
            'session_date' => '2026-09-21',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
        ]);

        $layout = $scheduled->fresh()->weeklySessionLayout();

        $this->assertCount(2, $layout);
        $this->assertEqualsCanonicalizing([0, 1], array_column($layout, 'column'));
        $this->assertSame([2, 2], array_column($layout, 'columns'));
    }

    public function test_weekly_session_layout_keeps_non_overlapping_groups_full_width(): void
    {
        $scheduled = $this->createScheduled();
        $location = Location::factory()->create();

        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $location->id,
            'session_date' => '2026-09-21',
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
        ]);

        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $location->id,
            'session_date' => '2026-09-21',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $layout = $scheduled->fresh()->weeklySessionLayout();

        $this->assertCount(2, $layout);
        $this->assertSame([0, 0], array_column($layout, 'column'));
        $this->assertSame([1, 1], array_column($layout, 'columns'));
    }

    public function test_weekly_session_layout_assigns_three_columns_to_staggered_overlaps(): void
    {
        $scheduled = $this->createScheduled();
        $locations = Location::factory()->count(3)->create();

        $sessions = [
            ['07:00:00', '09:00:00'],
            ['07:30:00', '09:30:00'],
            ['08:00:00', '10:00:00'],
        ];

        foreach ($locations as $index => $location) {
            CourseSession::create([
                'scheduled_course_id' => $scheduled->id,
                'location_id' => $location->id,
                'session_date' => '2026-09-21',
                'start_time' => $sessions[$index][0],
                'end_time' => $sessions[$index][1],
            ]);
        }

        $layout = $scheduled->fresh()->weeklySessionLayout();

        $this->assertCount(3, $layout);
        $this->assertEqualsCanonicalizing([0, 1, 2], array_column($layout, 'column'));
        $this->assertSame([3, 3, 3], array_column($layout, 'columns'));
    }

    public function test_weekly_schedule_grid_exposes_lanes_for_overlapping_groups(): void
    {
        $scheduled = $this->createScheduled();
        $locationA = Location::factory()->create();
        $locationB = Location::factory()->create();

        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $locationA->id,
            'session_date' => '2026-09-21',
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
        ]);

        CourseSession::create([
            'scheduled_course_id' => $scheduled->id,
            'location_id' => $locationB->id,
            'session_date' => '2026-09-21',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
        ]);

        $grid = $scheduled->fresh()->weeklyScheduleGrid();
        $day = \Carbon\Carbon::parse('2026-09-21')->dayOfWeekIso;
        $blocks = array_values(array_filter(
            $grid['rows'][2]['cells'][$day],
            fn (array $cell): bool => $cell['block'] !== null
        ));

        $this->assertSame(2, $grid['columns'][$day]);
        $this->assertCount(2, $blocks);
        $this->assertSame([1, 1], array_column($blocks, 'colspan'));
    }
}
