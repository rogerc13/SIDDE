<?php

namespace Tests\Feature\CursoController;

class UtilityTest extends CursoControllerTestCase
{
    public function test_codeCheck_returns_true_if_code_taken(): void
    {
        $course = $this->createCourse(['code' => 'EXIST-01']);

        $response = $this->actingAs($this->adminUser())
            ->post('/u/codes', ['codeValue' => 'EXIST-01']);

        $this->assertEquals('true', $response->content());
    }

    public function test_codeCheck_returns_false_if_code_available(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post('/u/codes', ['codeValue' => 'AVAIL-99']);

        $this->assertEquals('false', $response->content());
    }

    public function test_codeCheck_includes_soft_deleted(): void
    {
        $course = $this->createCourse(['code' => 'DEL-001']);
        $course->delete();

        $response = $this->actingAs($this->adminUser())
            ->post('/u/codes', ['codeValue' => 'DEL-001']);

        $this->assertEquals('true', $response->content());
    }

    public function test_codeCheck_respects_ignore_id(): void
    {
        $course = $this->createCourse(['code' => 'SAME-01']);
        $this->createCourse(['code' => 'OTHER-01']);

        $response = $this->actingAs($this->adminUser())
            ->post('/u/codes', [
                'codeValue' => 'SAME-01',
                'ignore_id' => $course->id,
            ]);

        $this->assertEquals('false', $response->content());
    }

    public function test_prerequisiteList_returns_courses(): void
    {
        $this->createCourse(['code' => 'PRE-001']);
        $this->createCourse(['code' => 'PRE-002']);

        $response = $this->actingAs($this->adminUser())
            ->post('/u/prerequisite');

        $response->assertJsonStructure(['courses' => [['id', 'code', 'title']]]);
    }

    public function test_courseDetails_returns_relations(): void
    {
        $course = $this->createCourse(['code' => 'DETAIL-01']);

        $response = $this->actingAs($this->adminUser())
            ->get("/u/acciones_formacion/details/{$course->id}");

        $response->assertJsonFragment(['code' => 'DETAIL-01']);
    }

    public function test_get_returns_json(): void
    {
        $course = $this->createCourse(['code' => 'GET-001']);

        $response = $this->actingAs($this->adminUser())
            ->get("/u/acciones_formacion/{$course->id}");

        $response->assertJsonFragment(['code' => 'GET-001']);
    }
}
