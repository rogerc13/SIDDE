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
}
