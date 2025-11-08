<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Course;

class CursoControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function prerequisite_list_returns_courses_json()
    {
        // Arrange: create some courses
        Course::factory()->create(['code' => 'A001', 'title' => 'Course A']);
        Course::factory()->create(['code' => 'B002', 'title' => 'Course B']);

        // Act: call the endpoint
        $response = $this->getJson(route('curso.prerequisiteList'));

        // Assert: check structure and content
        $response->assertStatus(200)
            ->assertJsonStructure([
                'courses' => [
                    '*' => ['id', 'code', 'title']
                ]
            ])
            ->assertJsonFragment(['code' => 'A001', 'title' => 'Course A'])
            ->assertJsonFragment(['code' => 'B002', 'title' => 'Course B']);
    }
}
