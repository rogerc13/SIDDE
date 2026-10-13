<?php

namespace App\Services\Database;

use App\Models\Funciones;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Presentation metadata and helpers for the admin database browser.
 *
 * Keeps column labels, hidden/technical columns, foreign-key label maps and
 * the accent-insensitive search in one place so the controller and the views
 * stay generic across the ~20 exposed tables.
 */
class DatabaseTablePresenter
{
    /** Columns hidden by default (technical bookkeeping). */
    public const TECHNICAL_COLUMNS = ['created_at', 'updated_at', 'deleted_at'];

    /** Tables offered in the "Cargos y registros" group for when a table is not domain data. */
    public const DOMAIN_TABLES = [
        'buildings',
        'floors',
        'locations',
        'course_sessions',
        'scheduled_course',
        'courses',
        'contents',
        'capacities',
        'prerequisites',
        'files',
        'people',
        'facilitators',
        'participants',
        'users',
    ];

    /** Allowed page sizes for the browser. */
    public const PAGE_SIZES = [25, 50, 100, 250];

    /** Maximum characters rendered before a text cell is truncated. */
    private const MAX_TEXT_LENGTH = 80;

    /**
     * Human labels shared across tables. Anything missing falls back to
     * Str::headline().
     *
     * @var array<string, string>
     */
    private const LABELS = [
        'id' => 'ID',
        'created_at' => 'Creado',
        'updated_at' => 'Actualizado',
        'deleted_at' => 'Eliminado',
        'name' => 'Nombre',
        'last_name' => 'Apellido',
        'id_number' => 'Cédula',
        'id_type_id' => 'Tipo de cédula',
        'sex' => 'Sexo',
        'avatar_path' => 'Avatar',
        'phone' => 'Teléfono',
        'email' => 'Correo',
        'password' => 'Contraseña',
        'remember_token' => 'Token de sesión',
        'role_id' => 'Rol',
        'person_id' => 'Persona',
        'course_id' => 'Acción de Formación',
        'category_id' => 'Área',
        'modality_id' => 'Modalidad',
        'code' => 'Código',
        'title' => 'Título',
        'objective' => 'Objetivo',
        'duration' => 'Duración',
        'addressed' => 'Dirigido a',
        'text' => 'Contenido',
        'min' => 'Mínimo',
        'max' => 'Máximo',
        'path' => 'Ruta',
        'type_id' => 'Tipo',
        'prerequisite' => 'Prerrequisito',
        'course_code' => 'Código de A.F.',
        'scheduled_id' => 'Programada',
        'scheduled_course_id' => 'Programada',
        'facilitator_id' => 'Facilitador',
        'course_status_id' => 'Estatus',
        'participant_status_id' => 'Estatus',
        'start_date' => 'Fecha de inicio',
        'end_date' => 'Fecha de fin',
        'session_date' => 'Fecha de sesión',
        'start_time' => 'Hora de inicio',
        'end_time' => 'Hora de fin',
        'status' => 'Estatus',
        'location_id' => 'Aula',
        'floor_id' => 'Piso',
        'building_id' => 'Edificio',
    ];

    /**
     * @return array<int, array{name:string,type:string}>
     */
    public function columns(string $table): array
    {
        return collect(Schema::getColumns($table))
            ->map(fn (array $column): array => [
                'name' => $column['name'],
                'type' => strtolower((string) $column['type']),
            ])
            ->all();
    }

    public function columnNames(string $table): array
    {
        return array_column($this->columns($table), 'name');
    }

    public function label(string $column): string
    {
        return self::LABELS[$column] ?? Str::headline($column);
    }

    public function isNumeric(string $type): bool
    {
        return (bool) preg_match('/int|decimal|numeric|float|double|real/', $type);
    }

    public function isDateTime(string $type): bool
    {
        return (bool) preg_match('/date|time|timestamp/', $type);
    }

    public function isText(string $type): bool
    {
        return (bool) preg_match('/char|text|clob|string/', $type);
    }

    public function isTechnical(string $column): bool
    {
        return in_array($column, self::TECHNICAL_COLUMNS, true);
    }

    public function isSensitive(string $column): bool
    {
        return in_array($column, ['password', 'remember_token'], true);
    }

    public function isDomainTable(string $table): bool
    {
        return in_array($table, self::DOMAIN_TABLES, true);
    }

    /**
     * @param  array<int, array{name:string,type:string}>  $columns
     * @return array<int, array{name:string,type:string}>
     */
    public function displayColumns(array $columns, bool $showAll): array
    {
        if ($showAll) {
            return $columns;
        }

        return array_values(array_filter(
            $columns,
            fn (array $column): bool => ! $this->isTechnical($column['name'])
        ));
    }

    public function dateFormat(string $type): string
    {
        if (str_contains($type, 'time') && ! str_contains($type, 'date')) {
            return 'H:i';
        }

        if (str_contains($type, 'datetime') || str_contains($type, 'timestamp')) {
            return 'd-m-Y H:i';
        }

        return 'd-m-Y';
    }

    public function maxTextLength(): int
    {
        return self::MAX_TEXT_LENGTH;
    }

    /**
     * Accent-insensitive LIKE search across the table's text columns.
     *
     * SQLite folds diacritics through the strip_accents() function registered
     * in AppServiceProvider; MySQL/MariaDB use their accent-insensitive
     * collation. Sensitive columns (credentials) are never searched.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<int, array{name:string,type:string}>  $columns
     */
    public function applySearch($query, array $columns, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $textColumns = collect($columns)
            ->filter(fn (array $column): bool => $this->isText($column['type']))
            ->pluck('name')
            ->reject(fn (string $name): bool => in_array($name, ['password', 'remember_token'], true))
            ->values()
            ->all();

        if ($textColumns === []) {
            return;
        }

        $escaped = Funciones::escapeLike($term);
        $isSqlite = $query->getConnection()->getDriverName() === 'sqlite';
        $grammar = $query->getGrammar();

        $query->where(function ($search) use ($textColumns, $escaped, $isSqlite, $grammar): void {
            foreach ($textColumns as $column) {
                if ($isSqlite) {
                    $wrapped = $grammar->wrap($column);
                    $search->orWhereRaw(
                        "lower(strip_accents({$wrapped})) LIKE ?",
                        ['%' . Funciones::normalizeSearchTerm($escaped) . '%']
                    );
                } else {
                    $search->orWhere($column, 'LIKE', '%' . $escaped . '%');
                }
            }
        });
    }

    /**
     * Foreign-key / code label maps for the rows on the current page.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $pageRows
     * @return array<string, array<int|string, string>>
     */
    public function valueMaps(string $table, Collection $pageRows): array
    {
        return match ($table) {
            'people' => ['id_type_id' => $this->pluckMap('id_types', 'name')],
            'participants' => [
                'person_id' => $this->personMap($pageRows->pluck('person_id')),
                'participant_status_id' => $this->inMap('participant_status', 'name', $pageRows->pluck('participant_status_id')),
                'scheduled_id' => $this->scheduledMap($pageRows->pluck('scheduled_id')),
            ],
            'facilitators' => ['person_id' => $this->personMap($pageRows->pluck('person_id'))],
            'scheduled_course' => [
                'course_id' => $this->courseMap($pageRows->pluck('course_id')),
                'facilitator_id' => $this->facilitatorMap($pageRows->pluck('facilitator_id')),
                'course_status_id' => $this->inMap('course_status', 'name', $pageRows->pluck('course_status_id')),
            ],
            'course_sessions' => [
                'scheduled_course_id' => $this->scheduledMap($pageRows->pluck('scheduled_course_id')),
                'location_id' => $this->inMap('locations', 'name', $pageRows->pluck('location_id')),
            ],
            'courses' => [
                'category_id' => $this->pluckMap('categories', 'name'),
                'modality_id' => $this->pluckMap('modalities', 'name'),
            ],
            'floors' => ['building_id' => $this->inMap('buildings', 'name', $pageRows->pluck('building_id'))],
            'locations' => ['floor_id' => $this->inMap('floors', 'name', $pageRows->pluck('floor_id'))],
            'contents', 'capacities' => ['course_id' => $this->courseMap($pageRows->pluck('course_id'))],
            'files' => [
                'course_id' => $this->courseMap($pageRows->pluck('course_id')),
                'type_id' => $this->inMap('types', 'name', $pageRows->pluck('type_id')),
            ],
            'prerequisites' => [
                'course_id' => $this->courseMap($pageRows->pluck('course_id')),
                'course_code' => $this->courseTitleByCodeMap(),
                'prerequisite' => $this->courseTitleByCodeMap(),
            ],
            'users' => [
                'role_id' => $this->pluckMap('roles', 'name'),
                'person_id' => $this->personMap($pageRows->pluck('person_id')),
            ],
            default => [],
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>  $ids
     * @return array<int|string, string>
     */
    private function inMap(string $relatedTable, string $column, Collection $ids): array
    {
        $ids = $ids->filter()->unique()->values()->all();

        if ($ids === []) {
            return [];
        }

        return DB::table($relatedTable)
            ->whereIn('id', $ids)
            ->pluck($column, 'id')
            ->all();
    }

    /**
     * @return array<int|string, string>
     */
    private function pluckMap(string $relatedTable, string $column): array
    {
        return DB::table($relatedTable)->pluck($column, 'id')->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>  $ids
     * @return array<int|string, string>
     */
    private function personMap(Collection $ids): array
    {
        $ids = $ids->filter()->unique()->values()->all();

        if ($ids === []) {
            return [];
        }

        return DB::table('people')
            ->whereIn('id', $ids)
            ->selectRaw("id, CONCAT(name, ' ', last_name) as full_name")
            ->pluck('full_name', 'id')
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>  $ids
     * @return array<int|string, string>
     */
    private function courseMap(Collection $ids): array
    {
        $ids = $ids->filter()->unique()->values()->all();

        if ($ids === []) {
            return [];
        }

        return DB::table('courses')
            ->whereIn('id', $ids)
            ->selectRaw("id, CONCAT(code, ' - ', title) as label")
            ->pluck('label', 'id')
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>  $ids
     * @return array<int|string, string>
     */
    private function facilitatorMap(Collection $ids): array
    {
        $ids = $ids->filter()->unique()->values()->all();

        if ($ids === []) {
            return [];
        }

        return DB::table('facilitators')
            ->leftJoin('people', 'facilitators.person_id', '=', 'people.id')
            ->whereIn('facilitators.id', $ids)
            ->selectRaw("facilitators.id as id, CONCAT(people.name, ' ', people.last_name) as full_name")
            ->pluck('full_name', 'id')
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>  $ids
     * @return array<int|string, string>
     */
    private function scheduledMap(Collection $ids): array
    {
        $ids = $ids->filter()->unique()->values()->all();

        if ($ids === []) {
            return [];
        }

        return DB::table('scheduled_course')
            ->whereIn('scheduled_course.id', $ids)
            ->join('courses', 'scheduled_course.course_id', '=', 'courses.id')
            ->selectRaw("scheduled_course.id as id, CONCAT(scheduled_course.start_date, ' - ', courses.title) as label")
            ->pluck('label', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function courseTitleByCodeMap(): array
    {
        return DB::table('courses')->pluck('title', 'code')->all();
    }
}
