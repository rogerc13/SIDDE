<?php

namespace Tests\Feature\CursoController;

use App\Models\Course;
use App\Models\File;
use App\Models\Scheduled;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CrudTest extends CursoControllerTestCase
{
    public function test_admin_can_list_courses(): void
    {
        $course = $this->createCourse();

        $response = $this->actingAs($this->adminUser())
            ->get('/u/acciones_formacion');

        $response->assertStatus(200);
        $response->assertSee($course->title);
    }

    public function test_unauthorized_role_cannot_list(): void
    {
        $this->createCourse();

        $response = $this->actingAs($this->programadorUser())
            ->get('/u/acciones_formacion');

        $response->assertRedirect();
    }

    public function test_admin_can_create_course_full(): void
    {
        Storage::fake();

        $category = \App\Models\Category::factory()->create();
        $modality = \App\Models\Modality::factory()->create();
        $type = \App\Models\Type::first();

        $data = [
            'codigo' => 'CRS-001',
            'titulo' => 'Curso de Prueba',
            'categoria_id' => $category->id,
            'modalidad_id' => $modality->id,
            'objetivo' => 'Aprender cosas nuevas',
            'duracion' => 40,
            'dirigido' => 'A todos',
            'min' => 5,
            'max' => 30,
            'content_data' => 'Introduccion, Desarrollo,Conclusion',
            'prerequisite' => '',
            'manual_f' => UploadedFile::fake()->create('manual_f.pdf', 100),
        ];

        $response = $this->actingAs($this->adminUser())
            ->post('/u/acciones_formacion', $data);

        $response->assertJsonFragment(['code' => 'CRS-001']);

        $this->assertDatabaseHas('courses', ['code' => 'CRS-001']);
        $this->assertDatabaseHas('contents', ['text' => 'Desarrollo.']);
        $this->assertDatabaseHas('capacities', ['min' => 5, 'max' => 30]);
    }

    public function test_create_denied_for_unauthorized_role(): void
    {
        $category = \App\Models\Category::factory()->create();
        $modality = \App\Models\Modality::factory()->create();

        $data = [
            'codigo' => 'CRS-002',
            'titulo' => 'Curso Restringido',
            'categoria_id' => $category->id,
            'modalidad_id' => $modality->id,
            'objetivo' => 'Test',
            'duracion' => 20,
            'dirigido' => 'Nadie',
            'min' => 1,
            'max' => 10,
            'content_data' => 'test',
        ];

        $this->actingAs($this->programadorUser())
            ->post('/u/acciones_formacion', $data);

        $this->assertDatabaseMissing('courses', ['code' => 'CRS-002']);
    }

    public function test_admin_can_update_course(): void
    {
        $category = \App\Models\Category::factory()->create();
        $modality = \App\Models\Modality::factory()->create();

        $course = $this->createCourse([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
        ]);

        \App\Models\Capacity::create([
            'min' => 1,
            'max' => 10,
            'course_id' => $course->id,
        ]);

        Storage::fake();

        $data = [
            'codigo' => $course->code,
            'titulo' => 'Titulo Actualizado',
            'categoria_id' => $course->category_id,
            'modalidad_id' => $course->modality_id,
            'objetivo' => 'Nuevo objetivo',
            'duracion' => 60,
            'dirigido' => 'Actualizado',
            'min' => 2,
            'max' => 20,
            'content_data' => 'Nuevo contenido',
        ];

        $response = $this->actingAs($this->adminUser())
            ->putJson("/u/acciones_formacion/{$course->id}", $data);

        $response->assertStatus(200);
        $this->assertTrue(json_decode($response->getContent()));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Titulo Actualizado',
            'objective' => 'Nuevo objetivo',
        ]);
        $this->assertDatabaseHas('contents', ['text' => 'Nuevo contenido']);
        $this->assertDatabaseHas('capacities', ['min' => 2, 'max' => 20]);
    }

    public function test_update_without_delete_flag_does_not_crash(): void
    {
        Storage::fake();

        $category = \App\Models\Category::factory()->create();
        $modality = \App\Models\Modality::factory()->create();

        $course = $this->createCourse([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
        ]);

        \App\Models\Capacity::create([
            'min' => 1,
            'max' => 10,
            'course_id' => $course->id,
        ]);

        $filePath = 'manual_f.pdf';
        Storage::put($filePath, 'dummy');

        File::create([
            'path' => $filePath,
            'type_id' => 1,
            'course_id' => $course->id,
        ]);

        $data = [
            'codigo' => $course->code,
            'titulo' => 'Sin Delete Flag',
            'categoria_id' => $category->id,
            'modalidad_id' => $modality->id,
            'objetivo' => 'Test sin delete_flag',
            'duracion' => 30,
            'dirigido' => 'Test',
            'min' => 1,
            'max' => 15,
            'content_data' => 'Contenido',
        ];

        $response = $this->actingAs($this->adminUser())
            ->putJson("/u/acciones_formacion/{$course->id}", $data);

        $response->assertStatus(200);
        $this->assertTrue(json_decode($response->getContent()));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Sin Delete Flag',
        ]);
    }

    public function test_update_with_delete_flag_removes_files(): void
    {
        Storage::fake();

        $category = \App\Models\Category::factory()->create();
        $modality = \App\Models\Modality::factory()->create();

        $course = $this->createCourse([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
        ]);

        \App\Models\Capacity::create([
            'min' => 1,
            'max' => 10,
            'course_id' => $course->id,
        ]);

        $filePath = 'test/manual_f.pdf';
        Storage::put($filePath, 'dummy');

        $file = File::create([
            'path' => $filePath,
            'type_id' => 1,
            'course_id' => $course->id,
        ]);

        $deleteFlag = json_encode(['facilitator' => true]);

        $data = [
            'codigo' => $course->code,
            'titulo' => 'Con Delete Flag',
            'categoria_id' => $category->id,
            'modalidad_id' => $modality->id,
            'objetivo' => 'Test',
            'duracion' => 30,
            'dirigido' => 'Test',
            'min' => 1,
            'max' => 15,
            'content_data' => 'test',
            'delete_flag' => $deleteFlag,
        ];

        $response = $this->actingAs($this->adminUser())
            ->putJson("/u/acciones_formacion/{$course->id}", $data);

        $response->assertStatus(200);
        $this->assertTrue(json_decode($response->getContent()));

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        Storage::assertMissing($filePath);
    }

    public function test_update_denied_for_unauthorized_role(): void
    {
        $course = $this->createCourse();

        $data = [
            'codigo' => $course->code,
            'titulo' => 'Hack Attempt',
            'categoria_id' => $course->category_id,
            'modalidad_id' => $course->modality_id,
            'objetivo' => 'Hack',
            'duracion' => 10,
            'dirigido' => 'Hack',
            'min' => 1,
            'max' => 5,
            'content_data' => 'hack',
        ];

        $response = $this->actingAs($this->programadorUser())
            ->putJson("/u/acciones_formacion/{$course->id}", $data);

        $response->assertStatus(200);
        $this->assertFalse(json_decode($response->getContent()));

        $this->assertDatabaseMissing('courses', [
            'id' => $course->id,
            'title' => 'Hack Attempt',
        ]);
    }

    public function test_admin_can_delete_course(): void
    {
        Storage::fake();

        $course = $this->createCourse();

        $filePath = 'test/doc.pdf';
        Storage::put($filePath, 'dummy');

        File::create([
            'path' => $filePath,
            'type_id' => 1,
            'course_id' => $course->id,
        ]);

        $response = $this->actingAs($this->adminUser())
            ->delete("/u/acciones_formacion/{$course->id}");

        $response->assertRedirect();

        $this->assertSoftDeleted('courses', ['id' => $course->id]);
        $this->assertDatabaseMissing('files', ['course_id' => $course->id]);
        Storage::assertMissing($filePath);
    }

    public function test_delete_blocked_if_scheduled(): void
    {
        $course = $this->createCourse();

        $person = \App\Models\Person::factory()->create();
        $facilitator = \App\Models\Facilitator::create(['person_id' => $person->id]);
        $courseStatus = \App\Models\CourseStatus::create(['name' => 'Por Dictar']);

        Scheduled::create([
            'course_id' => $course->id,
            'facilitator_id' => $facilitator->id,
            'course_status_id' => $courseStatus->id,
            'start_date' => now(),
            'end_date' => now()->addDays(1),
        ]);

        $response = $this->actingAs($this->adminUser())
            ->delete("/u/acciones_formacion/{$course->id}");

        $response->assertRedirect();
        $this->assertNotSoftDeleted('courses', ['id' => $course->id]);
    }

    public function test_delete_denied_for_unauthorized_role(): void
    {
        $course = $this->createCourse();

        $this->actingAs($this->programadorUser())
            ->delete("/u/acciones_formacion/{$course->id}");

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'deleted_at' => null]);
    }
}
