<?php

namespace App\Services\Reports;

use App\Enums\ReportType;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Turns a report's raw payload into the flat list of PDF pages (charts,
 * tables, summaries) consumed by resources/views/pdf/reports/report.blade.php.
 *
 * Raw values are a mix of arrays and Eloquent collections, so everything is
 * normalized with collect() before iteration.
 */
final class ReportPdfPageBuilder
{
    private const DISPLAY_DATE_FORMAT = 'd-m-Y';

    public function __construct(
        private readonly ProvidedCharts $charts = new ProvidedCharts(),
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    public function build(ReportType $type, array $data): array
    {
        return match ($type) {
            ReportType::Date => $this->pagesByDate($data),
            ReportType::Category => $this->pagesByCategory($data),
            ReportType::Status => $this->pagesByStatus($data),
            ReportType::Duration => $this->pagesByDuration($data),
            ReportType::ParticipantByStatus => $this->pagesByParticipantStatus($data),
            ReportType::ParticipantByQuantity => $this->pagesByParticipantQuantity($data),
            ReportType::Gender => $this->pagesByGender($data),
            ReportType::MostScheduled => $this->pagesByMostScheduled($data),
            ReportType::LeastScheduled => $this->pagesByLeastScheduled($data),
            ReportType::Canceled => $this->pagesByCanceled($data),
            ReportType::ParticipantAverage => $this->pagesByParticipantAverage($data),
            ReportType::NotScheduled => $this->pagesByNotScheduled($data),
        };
    }

    /**
     * Format a stored date (Y-m-d) for display (d-m-Y).
     */
    public static function displayDate(string $date): string
    {
        $date = trim($date);

        if ($date === '') {
            return '';
        }

        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date) === 1) {
            return $date;
        }

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
                return Carbon::createFromFormat('Y-m-d', $date)->format(self::DISPLAY_DATE_FORMAT);
            }

            return Carbon::parse($date)->format(self::DISPLAY_DATE_FORMAT);
        } catch (\Throwable) {
            return $date;
        }
    }

    /**
     * A chart page for the given key, or null when the browser didn't supply
     * an image. Reports omit the chart instead of showing a failure message.
     *
     * @return array<string, mixed>|null
     */
    private function chartPage(string $key, string $title): ?array
    {
        $image = $this->charts->image($key);

        if ($image === null) {
            return null;
        }

        return [
            'kind' => 'chart',
            'title' => $title,
            'image' => $image,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByDate(array $data): array
    {
        $startDisplay = self::displayDate((string) Arr::get($data, 'start_date', ''));
        $endDisplay = self::displayDate((string) Arr::get($data, 'end_date', ''));
        $total = (int) Arr::get($data, 'total', 0);

        $title = "Cantidad de Acciones de Formación durante el Período {$startDisplay} - {$endDisplay} : {$total}";

        $page = $this->chartPage('date_line', $title);

        return $page !== null ? [$page] : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByCategory(array $data): array
    {
        $courseData = collect(Arr::get($data, 'courseData', []));
        $range = Arr::get($data, 'dateRange', ['startDate' => '', 'endDate' => '']);
        $startDisplay = self::displayDate((string) Arr::get($range, 'startDate', ''));
        $endDisplay = self::displayDate((string) Arr::get($range, 'endDate', ''));

        $lineTitle = "Cantidad de Acciones de Formación por Áreas de Conocimiento durante el período {$startDisplay} - {$endDisplay}";
        $doughnutTitle = "Distribución de Acciones de Formación por Áreas de Conocimiento durante el período {$startDisplay} - {$endDisplay}";

        $pages = [];

        if ($page = $this->chartPage('category_bar', $lineTitle)) {
            $pages[] = $page;
        }
        if ($page = $this->chartPage('category_doughnut', $doughnutTitle)) {
            $pages[] = $page;
        }

        $pages[] = [
            'kind' => 'table',
            'title' => $lineTitle,
            'columns' => ['Área de Conocimiento', 'Cantidad de Acciones de Formación'],
            'rows' => $courseData->map(fn ($row) => [
                (string) ($row['categoryName'] ?? ''),
                (string) ($row['amount'] ?? 0),
            ])->all(),
        ];

        $rowsCourses = [];
        foreach ($courseData as $row) {
            foreach (collect($row['courseData'] ?? []) as $scheduled) {
                $course = $scheduled['course'] ?? [];
                $category = $course['category'] ?? [];
                $rowsCourses[] = [
                    (string) Arr::get($course, 'title', ''),
                    (string) Arr::get($scheduled, 'start_date', ''),
                    (string) Arr::get($scheduled, 'end_date', ''),
                    (string) Arr::get($category, 'name', ''),
                ];
            }
        }

        $pages[] = [
            'kind' => 'table',
            'title' => "Acciones de Formación por Áreas de Conocimiento durante el período {$startDisplay} - {$endDisplay}",
            'columns' => ['Título', 'Fecha de Inicio', 'Fecha de Culminación', 'Área de Conocimiento'],
            'rows' => $rowsCourses,
        ];

        return $pages;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByStatus(array $data): array
    {
        $courseData = collect(Arr::get($data, 'courseData', []));
        $range = Arr::get($data, 'dateRange', ['startDate' => '', 'endDate' => '']);
        $startDisplay = self::displayDate((string) Arr::get($range, 'startDate', ''));
        $endDisplay = self::displayDate((string) Arr::get($range, 'endDate', ''));

        $chartTitle = "Distribución de Acciones de Formación por Estatus durante el período {$startDisplay} - {$endDisplay}";

        $doughnutLabels = $courseData->map(fn ($row) => (string) ($row['statusName'] ?? ''))->all();
        $doughnutData = $courseData->map(fn ($row) => (int) ($row['amount'] ?? 0))->all();

        $pages = [];

        if ($page = $this->chartPage('status_bar', $chartTitle)) {
            $pages[] = $page;
        }
        if ($page = $this->chartPage('status_doughnut', $chartTitle)) {
            $pages[] = $page;
        }

        $pages[] = [
            'kind' => 'table',
            'title' => "Cantidad de Acciones de Formación por Estatus durante el período {$startDisplay} - {$endDisplay}",
            'columns' => $doughnutLabels,
            'rows' => [array_map(fn ($v) => (string) $v, $doughnutData)],
        ];

        $courseRows = [];
        foreach ($courseData as $row) {
            foreach (collect($row['courseData'] ?? []) as $scheduled) {
                $course = $scheduled['course'] ?? [];
                $status = $scheduled['course_status'] ?? ($scheduled['courseStatus'] ?? []);
                $courseRows[] = [
                    (string) Arr::get($course, 'title', ''),
                    (string) Arr::get($scheduled, 'start_date', ''),
                    (string) Arr::get($scheduled, 'end_date', ''),
                    (string) Arr::get($status, 'name', ''),
                ];
            }
        }

        $pages[] = [
            'kind' => 'table',
            'title' => "Acciones de Formación Durante el período {$startDisplay} - {$endDisplay}",
            'columns' => ['Título', 'Fecha de Inicio', 'Fecha de Culminación', 'Estatus'],
            'rows' => $courseRows,
        ];

        return $pages;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByDuration(array $data): array
    {
        $range = Arr::get($data, 'dateRange', ['startDate' => '', 'endDate' => '']);
        $startDisplay = self::displayDate((string) Arr::get($range, 'startDate', ''));
        $endDisplay = self::displayDate((string) Arr::get($range, 'endDate', ''));
        $total = (string) Arr::get($data, 'finishedByDateRange', '0');

        $firstTableRows = collect(Arr::get($data, 'byDateRange', []))->map(fn ($row) => [
            (string) ($row['course_title'] ?? ''),
            (string) ($row['start_date'] ?? ''),
            (string) ($row['end_date'] ?? ''),
            (string) ($row['duration'] ?? ''),
            (string) ($row['duration_days'] ?? ''),
        ])->all();

        if (count($firstTableRows) === 0) {
            $firstTableRows[] = [[
                'text' => 'No hay Acciones de Formación que mostrar para este período',
                'colspan' => 5,
            ]];
        }

        $mostDuration = collect(Arr::get($data, 'mostDuration', []))->map(fn ($row) => [
            (string) ($row['code'] ?? ''),
            (string) ($row['title'] ?? ''),
            (string) ($row['duration'] ?? ''),
        ])->all();

        return [
            [
                'kind' => 'table',
                'title' => "Total de horas impartidas durante el período {$startDisplay} - {$endDisplay} : {$total} Horas",
                'columns' => ['Título', 'Fecha de Inicio', 'Fecha Fin', 'Duración Horas', 'Duración Días'],
                'rows' => $firstTableRows,
            ],
            [
                'kind' => 'table',
                'title' => 'Acciones de Formación según cantidad de horas',
                'columns' => ['Código', 'Título', 'Duración en Horas'],
                'rows' => $mostDuration,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByParticipantStatus(array $data): array
    {
        $range = Arr::get($data, 'dateRange', ['startDate' => '', 'endDate' => '']);
        $startDisplay = self::displayDate((string) Arr::get($range, 'startDate', ''));
        $endDisplay = self::displayDate((string) Arr::get($range, 'endDate', ''));

        $rows = (array) Arr::get($data, 'allStatusbyDateRange', []);
        $labels = (array) Arr::get($data, 'labels', []);

        $statusTotals = [];
        foreach ($labels as $labelRow) {
            $name = (string) ($labelRow['label'] ?? '');
            if ($name === '') {
                continue;
            }
            $statusTotals[$name] = 0;
            foreach ($rows as $r) {
                if (($r['status'] ?? null) === $name) {
                    $statusTotals[$name] += (int) ($r['countByStatus'] ?? 0);
                }
            }
        }

        $pages = [];

        $barPageTitle = 'Distribución de Participantes por Estatus (Barras)';
        $doughnutPageTitle = 'Distribución de Participantes por Estatus (Dona)';

        if ($page = $this->chartPage('participant_status_bar', $barPageTitle)) {
            $pages[] = $page;
        }
        if ($page = $this->chartPage('participant_status_doughnut', $doughnutPageTitle)) {
            $pages[] = $page;
        }

        $statusOrder = ['En Curso', 'Reprobado', 'Aprobado', 'Cancelado', 'Por Iniciar'];
        $pages[] = [
            'kind' => 'table',
            'title' => "Cantidad de Participantes Durante el Período {$startDisplay} - {$endDisplay}",
            'columns' => $statusOrder,
            'rows' => [[
                (string) ($statusTotals[$statusOrder[0]] ?? 0),
                (string) ($statusTotals[$statusOrder[1]] ?? 0),
                (string) ($statusTotals[$statusOrder[2]] ?? 0),
                (string) ($statusTotals[$statusOrder[3]] ?? 0),
                (string) ($statusTotals[$statusOrder[4]] ?? 0),
            ]],
        ];

        $participantRows = [];
        foreach (collect(Arr::get($data, 'byAllTime', [])) as $p) {
            $person = $p['person'] ?? [];
            $scheduled = $p['scheduled'] ?? [];
            $course = $scheduled['course'] ?? [];
            $status = $p['participant_status'] ?? ($p['participantStatus'] ?? []);
            $participantRows[] = [
                (string) Arr::get($person, 'name', ''),
                (string) Arr::get($person, 'last_name', ''),
                (string) Arr::get($person, 'id_number', ''),
                (string) Arr::get($status, 'name', ''),
                (string) Arr::get($course, 'title', ''),
                (string) Arr::get($scheduled, 'start_date', ''),
            ];
        }

        $pages[] = [
            'kind' => 'table',
            'title' => "Lista de Participantes Durante el Período {$startDisplay} - {$endDisplay}",
            'columns' => ['Nombres', 'Apellidos', 'Cédula', 'Estatus', 'Acción de Formación', 'Fecha de Inicio'],
            'rows' => $participantRows,
        ];

        return $pages;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByParticipantQuantity(array $data): array
    {
        $rows = Arr::get($data, 'dateRangeAmountPerCourse', 0);

        if ($rows === 0 || (is_countable($rows) && count($rows) === 0)) {
            return [[
                'kind' => 'table',
                'title' => 'Acciones de Formación por Cantidad de Participantes',
                'columns' => ['Mensaje'],
                'rows' => [['No existen Acciones de Formación con participantes asignados en este período de tiempo']],
            ]];
        }

        $rows = collect($rows)->map(fn ($row) => [
            (string) ($row['course'] ?? ''),
            (string) ($row['start_date'] ?? ''),
            (string) ($row['end_date'] ?? ''),
            (string) ($row['count'] ?? 0),
        ])->all();

        return [[
            'kind' => 'table',
            'title' => 'Acciones de Formación por Cantidad de Participantes',
            'columns' => ['Título', 'Fecha de Inicio', 'Fecha de Culminación', 'Cantidad de Participantes'],
            'rows' => $rows,
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByGender(array $data): array
    {
        $range = Arr::get($data, 'dateRange', ['startDate' => '', 'endDate' => '']);
        $startDisplay = self::displayDate((string) Arr::get($range, 'startDate', ''));
        $endDisplay = self::displayDate((string) Arr::get($range, 'endDate', ''));
        $total = (int) Arr::get($data, 'total', 0);

        $rows = collect(Arr::get($data, 'rows', []));
        $maleCount = (int) (($rows->firstWhere('label', 'Masculino')['amount'] ?? 0));
        $femaleCount = (int) (($rows->firstWhere('label', 'Femenino')['amount'] ?? 0));

        $pages = [];

        $barPageTitle = 'Distribución de Participantes por Género (Barras)';
        $doughnutPageTitle = 'Distribución de Participantes por Género (Dona)';

        if ($page = $this->chartPage('gender_bar', $barPageTitle)) {
            $pages[] = $page;
        }
        if ($page = $this->chartPage('gender_doughnut', $doughnutPageTitle)) {
            $pages[] = $page;
        }

        $pages[] = [
            'kind' => 'table',
            'title' => "Cantidad de Participantes por Género durante el período {$startDisplay} - {$endDisplay} : {$total}",
            'columns' => ['Masculino', 'Femenino', 'Total'],
            'rows' => [[(string) $maleCount, (string) $femaleCount, (string) $total]],
        ];

        return $pages;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByMostScheduled(array $data): array
    {
        $amountData = collect(Arr::get($data, 'amountData', []));
        $courseData = collect(Arr::get($data, 'courseData', []));

        $rows = [];
        $total = 0;
        foreach ($courseData as $course) {
            $amount = (int) ($amountData[(string) ($course['id'] ?? '')] ?? 0);
            $total += $amount;
            $rows[] = [(string) ($course['code'] ?? ''), (string) ($course['title'] ?? ''), (string) $amount];
        }

        return [[
            'kind' => 'table',
            'title' => "Acciones de Formación más programadas (Histórico) (Total: {$total})",
            'columns' => ['Código', 'Título', 'Cantidad'],
            'rows' => $rows,
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByLeastScheduled(array $data): array
    {
        $amountData = collect(Arr::get($data, 'amountData', []));
        $courseData = collect(Arr::get($data, 'courseData', []))
            ->sortBy(fn ($course) => (int) ($amountData[(string) ($course['id'] ?? '')] ?? 0))
            ->values();

        $rows = [];
        $total = 0;
        foreach ($courseData as $course) {
            $amount = (int) ($amountData[(string) ($course['id'] ?? '')] ?? 0);
            $total += $amount;
            $rows[] = [(string) ($course['code'] ?? ''), (string) ($course['title'] ?? ''), (string) $amount];
        }

        return [[
            'kind' => 'table',
            'title' => "Acciones de Formación menos programadas (Histórico) (Total: {$total})",
            'columns' => ['Código', 'Título', 'Cantidad'],
            'rows' => $rows,
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByNotScheduled(array $data): array
    {
        $items = collect(Arr::get($data, 'data', []));

        if ($items->isEmpty()) {
            return [[
                'kind' => 'table',
                'title' => 'Acciones de Formación No Programadas (Histórico)',
                'columns' => ['Mensaje'],
                'rows' => [['No existen Acciones de Formación sin programar']],
            ]];
        }

        $rows = $items->map(function ($course) {
            return [
                (string) ($course['code'] ?? ''),
                (string) ($course['title'] ?? ''),
                (string) Arr::get($course['category'] ?? [], 'name', ''),
            ];
        })->all();

        return [[
            'kind' => 'table',
            'title' => 'Acciones de Formación No Programadas (Histórico)',
            'columns' => ['Código', 'Título', 'Área de Conocimiento'],
            'rows' => $rows,
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByCanceled(array $data): array
    {
        $items = collect(Arr::get($data, 'data', []));

        if ($items->isEmpty()) {
            return [[
                'kind' => 'table',
                'title' => 'Acciones de Formación Canceladas',
                'columns' => ['Mensaje'],
                'rows' => [['No existen Acciones de Formación Canceladas en el período']],
            ]];
        }

        $rows = $items->map(fn ($scheduled) => [
            (string) ($scheduled['code'] ?? ''),
            (string) ($scheduled['title'] ?? ''),
            (string) ($scheduled['category'] ?? ''),
            self::displayDate((string) ($scheduled['start_date'] ?? '')),
            self::displayDate((string) ($scheduled['end_date'] ?? '')),
        ])->all();

        return [[
            'kind' => 'table',
            'title' => 'Acciones de Formación Canceladas (Total: '.$items->count().')',
            'columns' => ['Código', 'Título', 'Área de Conocimiento', 'Inicio', 'Fin'],
            'rows' => $rows,
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByParticipantAverage(array $data): array
    {
        $rows = collect(Arr::get($data, 'data', []));

        if ($rows->isEmpty()) {
            return [[
                'kind' => 'table',
                'title' => 'Promedio de Participantes por Acción de Formación',
                'columns' => ['Mensaje'],
                'rows' => [['No existen Acciones de Formación en el período']],
            ]];
        }

        // group per-course rows into their date buckets and average them
        $buckets = [];
        foreach ($rows as $row) {
            $buckets[(string) ($row['date'] ?? '')][] = $row;
        }
        ksort($buckets);

        $round2 = fn ($v) => round((float) $v, 2);
        $labels = array_keys($buckets);
        $avgOf = fn (array $group, string $field) => $round2(
            array_sum(array_map(fn ($r) => (float) Arr::get($r, $field, 0), $group)) / count($group)
        );

        $avgTotal = array_map(fn ($d) => $avgOf($buckets[$d], 'total'), $labels);
        $avgApproved = array_map(fn ($d) => $avgOf($buckets[$d], 'approved'), $labels);
        $avgFailed = array_map(fn ($d) => $avgOf($buckets[$d], 'failed'), $labels);

        $overallAvgTotal = $round2($rows->avg('total'));
        $overallAvgApproved = $round2($rows->avg('approved'));
        $overallAvgFailed = $round2($rows->avg('failed'));

        $title = 'Promedio de Participantes por Acción de Formación';

        $pages = [];

        if ($page = $this->chartPage('participant_average_line', $title)) {
            $pages[] = $page;
        }

        $tableRows = [];
        foreach ($labels as $i => $date) {
            $tableRows[] = [self::displayDate($date), (string) $avgTotal[$i], (string) $avgApproved[$i], (string) $avgFailed[$i]];
        }

        $pages[] = [
            'kind' => 'table',
            'title' => "Promedios por Período (General: {$overallAvgTotal} participantes, {$overallAvgApproved} aprobados, {$overallAvgFailed} reprobados)",
            'columns' => ['Período', 'Prom. Participantes', 'Prom. Aprobados', 'Prom. Reprobados'],
            'rows' => $tableRows,
        ];

        return $pages;
    }
}
