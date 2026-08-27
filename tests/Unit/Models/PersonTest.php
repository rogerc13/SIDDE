<?php

namespace Tests\Unit\Models;

use App\Models\Facilitator;
use App\Models\Participant;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonTest extends TestCase
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

    public function test_id_format_formats_number_with_thousands_separator(): void
    {
        $person = Person::factory()->create(['id_number' => '12345678']);

        $this->assertEquals('12.345.678', $person->id_format());
    }

    public function test_id_format_handles_small_numbers(): void
    {
        $person = Person::factory()->create(['id_number' => '12345']);

        $this->assertEquals('12.345', $person->id_format());
    }

    public function test_id_format_handles_zero(): void
    {
        $person = Person::factory()->create(['id_number' => '0']);

        $this->assertEquals('0', $person->id_format());
    }

    public function test_user_relationship(): void
    {
        $person = Person::factory()->create();
        User::factory()->create(['person_id' => $person->id]);

        $this->assertInstanceOf(User::class, $person->user);
    }

    public function test_user_relationship_returns_null_when_no_user(): void
    {
        $person = Person::factory()->create();

        $this->assertNull($person->user);
    }

    public function test_facilitator_relationship(): void
    {
        $person = Person::factory()->create();
        Facilitator::create(['person_id' => $person->id]);

        $this->assertInstanceOf(Facilitator::class, $person->facilitator);
    }

    public function test_facilitator_relationship_returns_null_when_not_facilitator(): void
    {
        $person = Person::factory()->create();

        $this->assertNull($person->facilitator);
    }

    public function test_participant_relationship(): void
    {
        $person = Person::factory()->create();

        $course = \App\Models\Course::factory()->create();
        $facilitatorPerson = Person::factory()->create();
        $facilitator = Facilitator::create(['person_id' => $facilitatorPerson->id]);
        $scheduled = \App\Models\Scheduled::create([
            'course_id' => $course->id,
            'course_status_id' => \App\Models\CourseStatus::POR_DICTAR,
            'facilitator_id' => $facilitator->id,
            'start_date' => now(),
            'end_date' => now()->addWeek(),
        ]);

        Participant::create([
            'person_id' => $person->id,
            'scheduled_id' => $scheduled->id,
            'participant_status_id' => 1,
        ]);

        $this->assertCount(1, $person->participant);
    }

    public function test_cascading_soft_delete_facilitator(): void
    {
        $person = Person::factory()->create();
        Facilitator::create(['person_id' => $person->id]);

        $person->delete();

        $this->assertSoftDeleted('facilitators', ['person_id' => $person->id]);
    }

    public function test_cascading_restore_facilitator(): void
    {
        $person = Person::factory()->create();
        $facilitator = Facilitator::create(['person_id' => $person->id]);

        $person->delete();
        $person->restore();

        $this->assertDatabaseHas('facilitators', [
            'person_id' => $person->id,
            'deleted_at' => null,
        ]);
    }

    public function test_force_delete_cascades_facilitator(): void
    {
        $person = Person::factory()->create();
        Facilitator::create(['person_id' => $person->id]);

        $person->forceDelete();

        $this->assertDatabaseMissing('facilitators', ['person_id' => $person->id]);
    }

    public function test_soft_deletes(): void
    {
        $person = Person::factory()->create();

        $id = $person->id;
        $person->delete();

        $this->assertSoftDeleted('people', ['id' => $id]);
    }

    public function test_can_restore_after_soft_delete(): void
    {
        $person = Person::factory()->create();

        $id = $person->id;
        $person->delete();
        $person->restore();

        $this->assertDatabaseHas('people', ['id' => $id, 'deleted_at' => null]);
    }

    public function test_fillable_attributes(): void
    {
        $person = Person::factory()->create();

        $fillable = $person->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('last_name', $fillable);
        $this->assertContains('id_number', $fillable);
        $this->assertContains('id_type_id', $fillable);
        $this->assertContains('phone', $fillable);
        $this->assertContains('sex', $fillable);
        $this->assertContains('avatar_path', $fillable);
    }

    public function test_id_type_relationship(): void
    {
        $person = Person::factory()->create(['id_type_id' => 1]);

        $this->assertEquals(1, $person->idType->id);
    }
}
