<?php

namespace Tests\Feature\CursoController;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DownloadTest extends CursoControllerTestCase
{
    public function test_downloadAllFiles_returns_zip(): void
    {
        $category = \App\Models\Category::factory()->create();
        $modality = \App\Models\Modality::factory()->create();

        $course = \App\Models\Course::factory()->create([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
            'code' => 'ZIP-001',
        ]);

        $dir = storage_path('app/ZIP-001');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir . '/manual_f.pdf', 'content1');
        file_put_contents($dir . '/manual_p.pdf', 'content2');

        File::create(['path' => 'ZIP-001/manual_f.pdf', 'type_id' => 1, 'course_id' => $course->id]);
        File::create(['path' => 'ZIP-001/manual_p.pdf', 'type_id' => 2, 'course_id' => $course->id]);

        $response = $this->actingAs($this->facilitadorUser())
            ->get("/acciones_formacion/{$course->id}/documents");

        $response->assertHeader('Content-Disposition');

        unlink($dir . '/manual_f.pdf');
        unlink($dir . '/manual_p.pdf');
        rmdir($dir);
    }

    public function test_downloadAllFiles_empty_returns_back(): void
    {
        $course = $this->createCourse();

        $response = $this->actingAs($this->facilitadorUser())
            ->get("/acciones_formacion/{$course->id}/documents");

        $response->assertRedirect();
    }

    public function test_download_individual_file_by_type(): void
    {
        $category = \App\Models\Category::factory()->create();
        $modality = \App\Models\Modality::factory()->create();

        $course = \App\Models\Course::factory()->create([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
            'code' => 'DL-001',
        ]);

        $dir = storage_path('app/DL-001');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir . '/manual_f.pdf', 'file content');

        File::create(['path' => 'DL-001/manual_f.pdf', 'type_id' => 1, 'course_id' => $course->id]);

        $response = $this->get("/download/{$course->id}/1");

        $response->assertHeader('Content-Disposition');

        unlink($dir . '/manual_f.pdf');
        rmdir($dir);
    }

    public function test_download_list_files(): void
    {
        $category = \App\Models\Category::factory()->create();
        $modality = \App\Models\Modality::factory()->create();

        $course = \App\Models\Course::factory()->create([
            'category_id' => $category->id,
            'modality_id' => $modality->id,
            'code' => 'LIST-001',
        ]);

        $file = File::create(['path' => 'test.pdf', 'type_id' => 1, 'course_id' => $course->id]);

        $response = $this->get("/download/{$course->id}/0");

        $response->assertJsonFragment(['id' => $file->id]);
    }
}
