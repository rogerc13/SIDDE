<?php

namespace Tests\Feature\CursoController;

use App\Models\Capacity;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class AuthTest extends CursoControllerTestCase
{
    private function createViewableCourse(): \App\Models\Course
    {
        $course = $this->createCourse();
        Capacity::create(['min' => 1, 'max' => 10, 'course_id' => $course->id]);
        Storage::fake();
        File::create(['path' => 'doc.pdf', 'type_id' => 1, 'course_id' => $course->id]);
        return $course;
    }

    public function test_getAccionFormacion_allows_admin(): void
    {
        $course = $this->createViewableCourse();

        $response = $this->actingAs($this->adminUser())
            ->get("/acciones_formacion/{$course->id}");

        $response->assertStatus(200);
    }

    public function test_getAccionFormacion_allows_facilitador(): void
    {
        $course = $this->createViewableCourse();

        $response = $this->actingAs($this->facilitadorUser())
            ->get("/acciones_formacion/{$course->id}");

        $response->assertStatus(200);
    }

    public function test_getAccionFormacion_redirects_unauthorized_role(): void
    {
        $course = $this->createViewableCourse();

        $response = $this->actingAs($this->participanteUser())
            ->get("/acciones_formacion/{$course->id}");

        $response->assertRedirect(route('login'));
    }

    public function test_getAccionFormacion_redirects_unauthenticated(): void
    {
        $course = $this->createViewableCourse();

        $response = $this->get("/acciones_formacion/{$course->id}");

        $response->assertRedirect(route('login'));
    }

    public function test_downloadAllFiles_allows_facilitador(): void
    {
        $course = $this->createCourse();

        $response = $this->actingAs($this->facilitadorUser())
            ->get("/acciones_formacion/{$course->id}/documents");

        $response->assertRedirect();
    }

    public function test_downloadAllFiles_denies_admin(): void
    {
        $course = $this->createCourse();

        $response = $this->actingAs($this->adminUser())
            ->get("/acciones_formacion/{$course->id}/documents");

        $response->assertRedirect();
    }

    public function test_get_policy_before_query(): void
    {
        $course = $this->createCourse();

        $response = $this->actingAs($this->participanteUser())
            ->get("/u/acciones_formacion/{$course->id}");

        $response->assertJson([]);
    }
}
