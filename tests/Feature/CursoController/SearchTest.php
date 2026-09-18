<?php

namespace Tests\Feature\CursoController;

use App\Models\Category;
use App\Models\Course;
use App\Models\Modality;

class SearchTest extends CursoControllerTestCase
{
    private function makeCourseInCategory(Category $category, array $overrides = []): Course
    {
        $modality = Modality::factory()->create();

        return Course::factory()->create(array_merge([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
            'duration' => 8,
        ], $overrides));
    }

    private function flatChildren(array $response): array
    {
        return collect($response['results'])
            ->flatMap(fn ($group) => $group['children'])
            ->values()
            ->all();
    }

    public function test_search_requires_authentication(): void
    {
        $this->get('/u/acciones_formacion/search')->assertRedirect();
    }

    public function test_search_returns_grouped_select2_payload(): void
    {
        $category = Category::factory()->create(['name' => 'Area Uno']);
        $course = $this->makeCourseInCategory($category, [
            'title' => 'Curso Alpha',
            'duration' => 5,
        ]);

        $response = $this->actingAs($this->adminUser())
            ->getJson('/u/acciones_formacion/search');

        $response->assertOk();
        $response->assertJsonStructure([
            'results' => [
                ['text', 'children' => [['id', 'text', 'duration', 'url']]],
            ],
            'more',
        ]);

        $children = $this->flatChildren($response->json());
        $child = collect($children)->firstWhere('id', $course->id);

        $this->assertNotNull($child);
        $this->assertSame('Curso Alpha', $child['text']);
        $this->assertSame(5, $child['duration']);
        $this->assertSame(url("acciones_formacion/{$course->id}"), $child['url']);
    }

    public function test_search_paginates_twenty_courses_per_page(): void
    {
        $category = Category::factory()->create();

        for ($i = 1; $i <= 25; $i++) {
            $this->makeCourseInCategory($category, ['title' => sprintf('Curso %02d', $i)]);
        }

        $first = $this->actingAs($this->adminUser())
            ->getJson('/u/acciones_formacion/search');

        $first->assertOk();
        $this->assertCount(20, $this->flatChildren($first->json()));
        $this->assertTrue($first->json('more'));

        $second = $this->actingAs($this->adminUser())
            ->getJson('/u/acciones_formacion/search?page=2');

        $second->assertOk();
        $this->assertCount(5, $this->flatChildren($second->json()));
        $this->assertFalse($second->json('more'));
    }

    public function test_search_matches_title_across_all_courses(): void
    {
        $category = Category::factory()->create();

        for ($i = 1; $i <= 25; $i++) {
            $this->makeCourseInCategory($category, ['title' => sprintf('Curso %02d', $i)]);
        }

        $this->makeCourseInCategory($category, ['title' => 'Zzz Acción Meta']);

        $response = $this->actingAs($this->adminUser())
            ->getJson('/u/acciones_formacion/search?q=' . urlencode('Zzz Acción Meta'));

        $response->assertOk();

        $titles = collect($this->flatChildren($response->json()))->pluck('text');
        $this->assertTrue($titles->contains('Zzz Acción Meta'));
    }

    public function test_search_matches_category_name(): void
    {
        $category = Category::factory()->create(['name' => 'Área Muy Particular']);
        $this->makeCourseInCategory($category, ['title' => 'Curso Sin Coincidencia']);

        $response = $this->actingAs($this->adminUser())
            ->getJson('/u/acciones_formacion/search?q=' . urlencode('Área Muy Particular'));

        $response->assertOk();

        $titles = collect($this->flatChildren($response->json()))->pluck('text');
        $this->assertTrue($titles->contains('Curso Sin Coincidencia'));
    }

    public function test_search_ignores_accents_in_title(): void
    {
        $category = Category::factory()->create();
        $this->makeCourseInCategory($category, ['title' => 'Ética, Valores y Compromiso']);
        $this->makeCourseInCategory($category, ['title' => 'VENEZUELA POTENCIA ENERGÉTICA']);

        $response = $this->actingAs($this->adminUser())
            ->getJson('/u/acciones_formacion/search?q=' . urlencode('Etica'));

        $response->assertOk();

        $titles = collect($this->flatChildren($response->json()))->pluck('text');
        $this->assertTrue($titles->contains('Ética, Valores y Compromiso'));

        $response = $this->actingAs($this->adminUser())
            ->getJson('/u/acciones_formacion/search?q=' . urlencode('ENERGETICA'));

        $response->assertOk();

        $titles = collect($this->flatChildren($response->json()))->pluck('text');
        $this->assertTrue($titles->contains('VENEZUELA POTENCIA ENERGÉTICA'));
    }

    public function test_search_ignores_accents_in_category_name(): void
    {
        $category = Category::factory()->create(['name' => 'Área Muy Particular']);
        $this->makeCourseInCategory($category, ['title' => 'Curso Sin Coincidencia']);

        $response = $this->actingAs($this->adminUser())
            ->getJson('/u/acciones_formacion/search?q=' . urlencode('Area Muy Particular'));

        $response->assertOk();

        $titles = collect($this->flatChildren($response->json()))->pluck('text');
        $this->assertTrue($titles->contains('Curso Sin Coincidencia'));
    }

    public function test_search_ignores_accents_in_both_directions(): void
    {
        $category = Category::factory()->create();
        $this->makeCourseInCategory($category, ['title' => 'Curso Sin Acentos']);

        $response = $this->actingAs($this->adminUser())
            ->getJson('/u/acciones_formacion/search?q=' . urlencode('Cúrso Sín Ácentos'));

        $response->assertOk();

        $titles = collect($this->flatChildren($response->json()))->pluck('text');
        $this->assertTrue($titles->contains('Curso Sin Acentos'));
    }

    public function test_search_excludes_soft_deleted_courses(): void
    {
        $category = Category::factory()->create();
        $course = $this->makeCourseInCategory($category, ['title' => 'Curso Borrado']);
        $course->delete();

        $response = $this->actingAs($this->adminUser())
            ->getJson('/u/acciones_formacion/search?q=' . urlencode('Curso Borrado'));

        $response->assertOk();

        $ids = collect($this->flatChildren($response->json()))->pluck('id');
        $this->assertFalse($ids->contains($course->id));
    }
}
