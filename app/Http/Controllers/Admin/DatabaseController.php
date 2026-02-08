<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCourseUpdateRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\Capacity;
use App\Models\Content;
use App\Models\File as CourseFile;
use App\Models\Funciones;
use App\Models\Modality;
use App\Models\Participant;
use App\Models\Person;
use App\Models\Prerequisite;
use App\Models\Role;
use App\Models\Scheduled;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\Facades\File as IlluminateFile;

class DatabaseController extends Controller
{
    public function index(): View
    {
        $tables = $this->getTableNames();

        return view('pages.admin.database.index', [
            'tables' => $tables,
        ]);
    }

    public function show(string $table): View
    {
        $tables = $this->getTableNames();

        abort_unless(in_array($table, $tables, true), 404);

        $columns = Schema::getColumnListing($table);
        $query = DB::table($table);

        $orderColumn = null;
        if (in_array('id', $columns, true)) {
            $orderColumn = 'id';
        } elseif (isset($columns[0])) {
            $orderColumn = $columns[0];
        }

        if ($orderColumn) {
            $query->orderBy($orderColumn);
        }

        $rows = $query->paginate(100);

        $tableView = 'pages.admin.database.tables.' . $table;

        if (ViewFacade::exists($tableView)) {
            if ($table === 'courses') {
                return view($tableView, [
                    'table' => $table,
                    'columns' => $columns,
                    'rows' => $rows,
                    'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
                    'modalities' => Modality::query()->orderBy('name')->get(['id', 'name']),
                ]);
            }

            if ($table === 'users') {
                $roleMap = Role::query()->orderBy('name')->pluck('name', 'id')->all();
                $personNameMap = Person::query()
                    ->selectRaw("id, CONCAT(name, ' ', last_name) as full_name")
                    ->pluck('full_name', 'id')
                    ->all();

                return view($tableView, [
                    'table' => $table,
                    'columns' => $columns,
                    'rows' => $rows,
                    'valueMaps' => [
                        'role_id' => $roleMap,
                        'person_id' => $personNameMap,
                    ],
                ]);
            }

            if ($table === 'scheduled_course') {
                $courseLabelMap = DB::table('courses')
                    ->selectRaw("id, CONCAT(code, ' - ', title) as label")
                    ->pluck('label', 'id')
                    ->all();

                $facilitatorNameMap = DB::table('facilitators')
                    ->leftJoin('people', 'facilitators.person_id', '=', 'people.id')
                    ->selectRaw("facilitators.id as id, CONCAT(people.name, ' ', people.last_name) as full_name")
                    ->pluck('full_name', 'id')
                    ->all();

                $statusNameMap = DB::table('course_status')
                    ->pluck('name', 'id')
                    ->all();

                return view($tableView, [
                    'table' => $table,
                    'columns' => $columns,
                    'rows' => $rows,
                    'valueMaps' => [
                        'course_id' => $courseLabelMap,
                        'facilitator_id' => $facilitatorNameMap,
                        'course_status_id' => $statusNameMap,
                    ],
                ]);
            }

            if ($table === 'people') {
                $idTypeNameMap = DB::table('id_types')
                    ->pluck('name', 'id')
                    ->all();

                return view($tableView, [
                    'table' => $table,
                    'columns' => $columns,
                    'rows' => $rows,
                    'valueMaps' => [
                        'id_type_id' => $idTypeNameMap,
                    ],
                ]);
            }

            if ($table === 'participants') {
                $pageRows = collect($rows->items());

                $personIds = $pageRows->pluck('person_id')->filter()->unique()->values()->all();
                $statusIds = $pageRows->pluck('participant_status_id')->filter()->unique()->values()->all();
                $scheduledIds = $pageRows->pluck('scheduled_id')->filter()->unique()->values()->all();

                $personNameMap = empty($personIds)
                    ? []
                    : DB::table('people')
                        ->whereIn('id', $personIds)
                        ->selectRaw("id, CONCAT(name, ' ', last_name) as full_name")
                        ->pluck('full_name', 'id')
                        ->all();

                $participantStatusMap = empty($statusIds)
                    ? []
                    : DB::table('participant_status')
                        ->whereIn('id', $statusIds)
                        ->pluck('name', 'id')
                        ->all();

                $scheduledLabelMap = empty($scheduledIds)
                    ? []
                    : DB::table('scheduled_course')
                        ->whereIn('scheduled_course.id', $scheduledIds)
                        ->join('courses', 'scheduled_course.course_id', '=', 'courses.id')
                        ->selectRaw("scheduled_course.id as id, CONCAT(scheduled_course.start_date, ' - ', courses.title) as label")
                        ->pluck('label', 'id')
                        ->all();

                return view($tableView, [
                    'table' => $table,
                    'columns' => $columns,
                    'rows' => $rows,
                    'valueMaps' => [
                        'person_id' => $personNameMap,
                        'participant_status_id' => $participantStatusMap,
                        'scheduled_id' => $scheduledLabelMap,
                    ],
                ]);
            }

            if ($table === 'facilitators') {
                $pageRows = collect($rows->items());
                $personIds = $pageRows->pluck('person_id')->filter()->unique()->values()->all();

                $personNameMap = empty($personIds)
                    ? []
                    : DB::table('people')
                        ->whereIn('id', $personIds)
                        ->selectRaw("id, CONCAT(name, ' ', last_name) as full_name")
                        ->pluck('full_name', 'id')
                        ->all();

                return view($tableView, [
                    'table' => $table,
                    'columns' => $columns,
                    'rows' => $rows,
                    'valueMaps' => [
                        'person_id' => $personNameMap,
                    ],
                ]);
            }

            if ($table === 'contents') {
                $pageRows = collect($rows->items());
                $courseIds = $pageRows->pluck('course_id')->filter()->unique()->values()->all();

                $courseLabelMap = empty($courseIds)
                    ? []
                    : DB::table('courses')
                        ->whereIn('id', $courseIds)
                        ->selectRaw("id, CONCAT(code, ' - ', title) as label")
                        ->pluck('label', 'id')
                        ->all();

                return view($tableView, [
                    'table' => $table,
                    'columns' => $columns,
                    'rows' => $rows,
                    'valueMaps' => [
                        'course_id' => $courseLabelMap,
                    ],
                ]);
            }

            if ($table === 'capacities') {
                $pageRows = collect($rows->items());
                $courseIds = $pageRows->pluck('course_id')->filter()->unique()->values()->all();

                $courseLabelMap = empty($courseIds)
                    ? []
                    : DB::table('courses')
                        ->whereIn('id', $courseIds)
                        ->selectRaw("id, CONCAT(code, ' - ', title) as label")
                        ->pluck('label', 'id')
                        ->all();

                return view($tableView, [
                    'table' => $table,
                    'columns' => $columns,
                    'rows' => $rows,
                    'valueMaps' => [
                        'course_id' => $courseLabelMap,
                    ],
                ]);
            }

            return view($tableView, [
                'table' => $table,
                'columns' => $columns,
                'rows' => $rows,
            ]);
        }

        return view('pages.admin.database.table', [
            'table' => $table,
            'columns' => $columns,
            'rows' => $rows,
        ]);
    }

    public function updateCourse(AdminCourseUpdateRequest $request, int $id): RedirectResponse
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->can('update', Course::class)) {
            return redirect()->back()->with('alert', Funciones::getAlert('danger', 'Error', 'No tienes permisos para realizar esta accion.'));
        }

        $course = Course::withTrashed()->findOrFail($id);

        $validated = $request->validated();
        $newCode = (string) $validated['code'];
        $restore = (bool) ($validated['restore'] ?? false);

        $oldCode = (string) $course->code;
        $didMoveDirectory = false;

        if ($newCode !== $oldCode) {
            if (Storage::exists($newCode)) {
                return redirect()->back()->withInput()->with('alert', Funciones::getAlert('danger', 'Error', 'Ya existe una carpeta de documentos con el nuevo código.'));
            }

            if (Storage::exists($oldCode)) {
                Storage::makeDirectory($newCode);

                $oldPrefix = rtrim($oldCode, '/') . '/';
                $newPrefix = rtrim($newCode, '/') . '/';

                foreach (Storage::allFiles($oldCode) as $oldPath) {
                    $relative = str_starts_with($oldPath, $oldPrefix)
                        ? substr($oldPath, strlen($oldPrefix))
                        : basename($oldPath);

                    Storage::move($oldPath, $newPrefix . $relative);
                }

                Storage::deleteDirectory($oldCode);
                $didMoveDirectory = true;
            }
        }

        try {
            DB::transaction(function () use ($course, $validated, $oldCode, $newCode, $restore): void {
                $course->fill([
                    'code' => $newCode,
                    'title' => (string) $validated['title'],
                    'objective' => (string) $validated['objective'],
                    'duration' => (int) $validated['duration'],
                    'addressed' => (string) $validated['addressed'],
                    'category_id' => (int) $validated['category_id'],
                    'modality_id' => (int) $validated['modality_id'],
                ]);

                if ($newCode !== $oldCode) {
                    $filesTable = (new CourseFile())->getTable();

                    $files = CourseFile::query()
                        ->where('course_id', $course->id)
                        ->get();

                    foreach ($files as $file) {
                        if (is_string($file->path) && str_starts_with($file->path, $oldCode . '/')) {
                            $file->path = $newCode . '/' . substr($file->path, strlen($oldCode) + 1);
                            $file->save();
                        }
                    }

                    if (Schema::hasColumn($filesTable, 'file_path')) {
                        $filesWithAltPath = DB::table($filesTable)
                            ->where('course_id', $course->id)
                            ->where('file_path', 'like', $oldCode . '/%')
                            ->get(['id', 'file_path']);

                        foreach ($filesWithAltPath as $row) {
                            $filePath = (string) $row->file_path;
                            $newPath = $newCode . '/' . substr($filePath, strlen($oldCode) + 1);
                            DB::table($filesTable)->where('id', $row->id)->update(['file_path' => $newPath]);
                        }
                    }

                    Prerequisite::query()
                        ->where('course_id', $course->id)
                        ->update(['course_code' => $newCode]);

                    Prerequisite::query()
                        ->where('prerequisite', $oldCode)
                        ->update(['prerequisite' => $newCode]);
                }

                if ($restore && $course->trashed()) {
                    $course->restore();
                }

                $course->save();
            });
        } catch (\Throwable $e) {
            if ($didMoveDirectory && Storage::exists($newCode) && ! Storage::exists($oldCode)) {
                Storage::makeDirectory($oldCode);
                $newPrefix = rtrim($newCode, '/') . '/';
                $oldPrefix = rtrim($oldCode, '/') . '/';

                foreach (Storage::allFiles($newCode) as $newPath) {
                    $relative = str_starts_with($newPath, $newPrefix)
                        ? substr($newPath, strlen($newPrefix))
                        : basename($newPath);

                    Storage::move($newPath, $oldPrefix . $relative);
                }

                Storage::deleteDirectory($newCode);
            }

            throw $e;
        }

        return redirect()->back()->with('alert', Funciones::getAlert('success', 'Actualizado', 'Operación exitosa.'));
    }

    public function destroyCourse(int $id): RedirectResponse
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->can('delete', Course::class)) {
            return redirect()->back()->with('alert', Funciones::getAlert('danger', 'Error', 'No tienes permisos para realizar esta accion.'));
        }

        $course = Course::query()->with(['scheduled'])->findOrFail($id);

        if ($course->scheduled->count() > 0) {
            return redirect()->back()->with('alert', Funciones::getAlert('danger', 'Error', 'Esta acción de formación no puede ser eliminada, forma parte de cursos programados.'));
        }

        $course->delete();

        return redirect()->back()->with('alert', Funciones::getAlert('success', 'Eliminado exitosamente', 'Operación exitosa.'));
    }

    public function restoreCourse(int $id): RedirectResponse
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->can('delete', Course::class)) {
            return redirect()->back()->with('alert', Funciones::getAlert('danger', 'Error', 'No tienes permisos para realizar esta accion.'));
        }

        $course = Course::withTrashed()->findOrFail($id);
        $course->restore();

        return redirect()->back()->with('alert', Funciones::getAlert('success', 'Restaurado exitosamente', 'Operación exitosa.'));
    }

    public function forceDestroyCourse(int $id): RedirectResponse
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->can('delete', Course::class)) {
            return redirect()->back()->with('alert', Funciones::getAlert('danger', 'Error', 'No tienes permisos para realizar esta accion.'));
        }

        $course = Course::withTrashed()->findOrFail($id);

        $courseCode = is_string($course->code) ? $course->code : null;

        $storedFilePaths = CourseFile::query()
            ->where('course_id', $course->id)
            ->get()
            ->map(fn (CourseFile $courseFile) => $courseFile->path ?? ($courseFile->file_path ?? null))
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->values()
            ->all();

        $legacyFilenames = [];
        foreach (['ficha_tecnica', 'manual_p', 'manual_f', 'guia', 'presentacion'] as $column) {
            $filename = $course->{$column} ?? null;
            if (is_string($filename) && $filename !== '') {
                $legacyFilenames[] = $filename;
            }
        }

        DB::transaction(function () use ($course, $courseCode): void {
            $scheduledIds = Scheduled::withTrashed()
                ->where('course_id', $course->id)
                ->pluck('id')
                ->all();

            if (! empty($scheduledIds)) {
                Participant::withTrashed()->whereIn('scheduled_id', $scheduledIds)->forceDelete();
                Scheduled::withTrashed()->whereIn('id', $scheduledIds)->forceDelete();
            }

            Content::query()->where('course_id', $course->id)->delete();
            Capacity::query()->where('course_id', $course->id)->delete();
            CourseFile::query()->where('course_id', $course->id)->delete();

            Prerequisite::query()->where('course_id', $course->id)->delete();

            if (is_string($courseCode) && $courseCode !== '') {
                Prerequisite::query()
                    ->where('prerequisite', $courseCode)
                    ->orWhere('course_code', $courseCode)
                    ->delete();
            }

            $course->forceDelete();
        });

        foreach ($storedFilePaths as $storedPath) {
            Storage::delete($storedPath);
        }

        if ($courseCode) {
            Storage::deleteDirectory($courseCode);
        }

        foreach ($legacyFilenames as $filename) {
            $absolutePath = base_path('public/uploads/documentos/' . $filename);
            if (IlluminateFile::exists($absolutePath)) {
                IlluminateFile::delete($absolutePath);
            }
        }

        return redirect()->back()->with('alert', Funciones::getAlert('success', 'Eliminado permanentemente', 'Operación exitosa.'));
    }

    /**
     * @return array<int, string>
     */
    private function getTableNames(): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        $rawRows = match ($driver) {
            'mysql', 'mariadb' => $connection->select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"'),
            'pgsql' => $connection->select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname NOT IN ('pg_catalog', 'information_schema')"),
            'sqlite' => $connection->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"),
            'sqlsrv' => $connection->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'"),
            default => [],
        };

        $excluded = $this->excludedTables();

        return collect($rawRows)
            ->map(fn (object $row) => $this->extractFirstColumnValue($row))
            ->filter(fn (?string $name) => filled($name))
            ->reject(fn (string $name) => in_array($name, $excluded, true))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function excludedTables(): array
    {
        return [
            'migrations',
            'failed_jobs',
            'jobs',
            'job_batches',
            'sessions',
            'cache',
            'cache_locks',
            'password_resets',
            'password_reset_tokens',
            'personal_access_tokens',
        ];
    }

    private function extractFirstColumnValue(object $row): ?string
    {
        $values = array_values((array) $row);

        if (! isset($values[0])) {
            return null;
        }

        return (string) $values[0];
    }
}
