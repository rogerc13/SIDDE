<?php

namespace Tests\Feature;

use App\Http\Controllers\CursoController;
use App\Models\Category;
use App\Models\Course;
use App\Models\File;
use App\Models\Modality;
use App\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class CourseDeleteCascadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_course_deletes_related_documents(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Test database is not configured/available: ' . $e->getMessage());
        }

        Storage::fake();

        $category = Category::create(['name' => 'Category']);
        $modality = Modality::create(['name' => 'Modality']);
        $type = Type::create(['name' => 'Manual']);

        $course = Course::create([
            'code' => 'COURSE-001',
            'title' => 'Test Course',
            'category_id' => $category->id,
            'modality_id' => $modality->id,
            'objective' => 'Objective',
            'duration' => 60,
            'addressed' => 'Anyone',
        ]);

        $path = $course->code . '/manual.pdf';
        Storage::put($path, 'dummy');

        $file = File::create([
            'path' => $path,
            'type_id' => $type->id,
            'course_id' => $course->id,
        ]);

        $mockUser = Mockery::mock(\App\Models\User::class);
        $mockUser->shouldReceive('can')->with('delete', Course::class)->andReturn(true);
        Auth::shouldReceive('user')->andReturn($mockUser);

        app(CursoController::class)->delete($course->id);

        $this->assertSoftDeleted('courses', ['id' => $course->id]);
        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        Storage::assertMissing($path);
    }
}
