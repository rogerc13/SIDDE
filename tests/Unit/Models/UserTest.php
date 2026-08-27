<?php

namespace Tests\Unit\Models;

use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\IdTypeSeeder::class);
        $this->seed(\Database\Seeders\TypeSeeder::class);
    }

    public function test_is_administrador(): void
    {
        $user = User::factory()->administrador()->create();

        $this->assertTrue($user->isAdministrador());
        $this->assertFalse($user->isTecEducativa());
        $this->assertFalse($user->isProgramador());
        $this->assertFalse($user->isFacilitador());
        $this->assertFalse($user->isParticipante());
    }

    public function test_is_tec_educativa(): void
    {
        $user = User::factory()->tecEducativa()->create();

        $this->assertTrue($user->isTecEducativa());
        $this->assertFalse($user->isAdministrador());
        $this->assertFalse($user->isProgramador());
        $this->assertFalse($user->isFacilitador());
        $this->assertFalse($user->isParticipante());
    }

    public function test_is_programador(): void
    {
        $user = User::factory()->programador()->create();

        $this->assertTrue($user->isProgramador());
        $this->assertFalse($user->isAdministrador());
        $this->assertFalse($user->isTecEducativa());
        $this->assertFalse($user->isFacilitador());
        $this->assertFalse($user->isParticipante());
    }

    public function test_is_facilitador(): void
    {
        $user = User::factory()->facilitador()->create();

        $this->assertTrue($user->isFacilitador());
        $this->assertFalse($user->isAdministrador());
        $this->assertFalse($user->isTecEducativa());
        $this->assertFalse($user->isProgramador());
        $this->assertFalse($user->isParticipante());
    }

    public function test_is_participante(): void
    {
        $user = User::factory()->participante()->create();

        $this->assertTrue($user->isParticipante());
        $this->assertFalse($user->isAdministrador());
        $this->assertFalse($user->isTecEducativa());
        $this->assertFalse($user->isProgramador());
        $this->assertFalse($user->isFacilitador());
    }

    public function test_role_relationship(): void
    {
        $user = User::factory()->administrador()->create();

        $this->assertInstanceOf(Role::class, $user->role);
        $this->assertEquals(Role::ADMINISTRADOR, $user->role->id);
    }

    public function test_person_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(Person::class, $user->person);
        $this->assertEquals($user->person_id, $user->person->id);
    }

    public function test_hidden_attributes(): void
    {
        $user = User::factory()->create();

        $toArray = $user->toArray();

        $this->assertArrayNotHasKey('password', $toArray);
        $this->assertArrayNotHasKey('remember_token', $toArray);
    }

    public function test_soft_deletes(): void
    {
        $user = User::factory()->create();

        $id = $user->id;
        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $id]);
    }

    public function test_can_restore_after_soft_delete(): void
    {
        $user = User::factory()->create();

        $id = $user->id;
        $user->delete();
        $user->restore();

        $this->assertDatabaseHas('users', ['id' => $id, 'deleted_at' => null]);
    }

    public function test_fillable_attributes(): void
    {
        $user = User::factory()->create();

        $this->assertContains('role_id', $user->getFillable());
        $this->assertContains('person_id', $user->getFillable());
        $this->assertContains('email', $user->getFillable());
        $this->assertContains('password', $user->getFillable());
    }
}
