<?php

namespace App\Http\Controllers;

use App\Enums\ReportType;
use App\Services\Reports\InsightBuilder;
use App\Services\Reports\ProvidedCharts;
use App\Services\Reports\ReportQueryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class ReportPdfController extends Controller
{
    private const DISPLAY_DATE_FORMAT = 'd-m-Y';

    private ?ProvidedCharts $providedCharts = null;

    public function __construct(
        private readonly ReportQueryService $queries,
        private readonly InsightBuilder $insights,
    ) {
    }

    public function download(Request $request)
    {
        $type = (string) $request->input('report-type', '');

        $this->providedCharts = ProvidedCharts::fromRequest($request);

        $allowed = array_map(fn (ReportType $type) => $type->value, ReportType::cases());

        abort_unless(in_array($type, $allowed, true), 422, 'Tipo de reporte inválido.');

        $reportType = ReportType::from($type);
        $result = $this->queries->run($reportType, $request);
        $data = $result->raw;

        $pages = $this->buildPages($type, $data);

        if ($reportType->hasInsight() && ($insights = $this->insights->build($result)) !== []) {
            array_unshift($pages, [
                'kind' => 'summary',
                'title' => 'Resumen Ejecutivo',
                'lines' => $insights,
            ]);
        }

        $reportTitle = $reportType->label();

        [$startDate, $endDate] = $this->resolveDateRange($type, $data, $request);

        $startDateDisplay = $this->formatDateForDisplay($startDate);
        $endDateDisplay = $this->formatDateForDisplay($endDate);

        $pdf = Pdf::loadView('pdf.reports.report', [
            'type' => $type,
            'pages' => $pages,
            'reportTitle' => $reportTitle,
        ])->setPaper('a4', 'portrait');

        $filename = $reportTitle;
        if ($startDateDisplay !== '' && $endDateDisplay !== '') {
            $filename .= " - {$startDateDisplay} al {$endDateDisplay}";
        }
        $filename = $this->sanitizeFilename($filename).'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveDateRange(string $type, array $data, Request $request): array
    {
        $start = (string) $request->input('start_date', '');
        $end = (string) $request->input('end_date', '');

        if ($start !== '' && $end !== '') {
            return [$start, $end];
        }

        $range = Arr::get($data, 'dateRange');
        if (is_array($range)) {
            $start = $start ?: (string) Arr::get($range, 'startDate', '');
            $end = $end ?: (string) Arr::get($range, 'endDate', '');

            if ($start !== '' && $end !== '') {
                return [$start, $end];
            }
        }

        if ($type === 'date') {
            $start = $start ?: (string) Arr::get($data, 'start_date', '');
            $end = $end ?: (string) Arr::get($data, 'end_date', '');
        }

        return [$start, $end];
    }

    private function sanitizeFilename(string $name): string
    {
        $name = trim($name);

        // Windows reserved characters: \ / : * ? " < > |
        $name = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '-', $name);
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        $name = trim($name, " .-\t\n\r\0\x0B");

        return $name !== '' ? $name : 'Reporte';
    }

    private function formatDateForDisplay(string $date): string
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
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function buildPages(string $type, array $data): array
    {
        return match ($type) {
            'date' => $this->pagesByDate($data),
            'category' => $this->pagesByCategory($data),
            'status' => $this->pagesByStatus($data),
            'duration' => $this->pagesByDuration($data),
            'participant-by-status' => $this->pagesByParticipantStatus($data),
            'participant-by-quantity' => $this->pagesByParticipantQuantity($data),
            'gender' => $this->pagesByGender($data),
            'most-scheduled' => $this->pagesByMostScheduled($data),
            'least-scheduled' => $this->pagesByLeastScheduled($data),
            'canceled' => $this->pagesByCanceled($data),
            'participant-average' => $this->pagesByParticipantAverage($data),
            'not-scheduled' => $this->pagesByNotScheduled($data),
            default => [],
        };
    }

    /**
     * A chart page for the given key, or null when the browser didn't supply
     * an image. Reports omit the chart instead of showing a failure message.
     *
     * @return array<string, mixed>|null
     */
    private function chartPage(string $key, string $title): ?array
    {
        $image = $this->providedCharts?->image($key);

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
        $start = (string) Arr::get($data, 'start_date', '');
        $end = (string) Arr::get($data, 'end_date', '');
        $startDisplay = $this->formatDateForDisplay($start);
        $endDisplay = $this->formatDateForDisplay($end);
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
        $courseData = (array) Arr::get($data, 'courseData', []);
        $range = Arr::get($data, 'dateRange', ['startDate' => '', 'endDate' => '']);
        $start = (string) Arr::get($range, 'startDate', '');
        $end = (string) Arr::get($range, 'endDate', '');
        $startDisplay = $this->formatDateForDisplay($start);
        $endDisplay = $this->formatDateForDisplay($end);

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
            'rows' => array_map(function ($row) {
                return [(string) ($row['categoryName'] ?? ''), (string) ($row['amount'] ?? 0)];
            }, $courseData),
        ];

        $rowsCourses = [];
        foreach ($courseData as $row) {
            foreach ((array) ($row['courseData'] ?? []) as $scheduled) {
                $course = $scheduled['course'] ?? [];
                $cat = $course['category'] ?? [];
                $rowsCourses[] = [
                    (string) Arr::get($course, 'title', ''),
                    (string) Arr::get($scheduled, 'start_date', ''),
                    (string) Arr::get($scheduled, 'end_date', ''),
                    (string) Arr::get($cat, 'name', ''),
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
        $courseData = (array) Arr::get($data, 'courseData', []);
        $range = Arr::get($data, 'dateRange', ['startDate' => '', 'endDate' => '']);
        $start = (string) Arr::get($range, 'startDate', '');
        $end = (string) Arr::get($range, 'endDate', '');
        $startDisplay = $this->formatDateForDisplay($start);
        $endDisplay = $this->formatDateForDisplay($end);

        $chartTitle = "Distribución de Acciones de Formación por Estatus durante el período {$startDisplay} - {$endDisplay}";

        $doughnutLabels = [];
        $doughnutData = [];
        foreach ($courseData as $row) {
            $doughnutLabels[] = (string) ($row['statusName'] ?? '');
            $doughnutData[] = (int) ($row['amount'] ?? 0);
        }

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
            'columns' => array_map(fn ($s) => (string) $s, $doughnutLabels),
            'rows' => [array_map(fn ($v) => (string) $v, $doughnutData)],
        ];

        $courseRows = [];
        foreach ($courseData as $row) {
            foreach ((array) ($row['courseData'] ?? []) as $scheduled) {
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
        $start = (string) Arr::get($range, 'startDate', '');
        $end = (string) Arr::get($range, 'endDate', '');
        $startDisplay = $this->formatDateForDisplay($start);
        $endDisplay = $this->formatDateForDisplay($end);
        $total = (string) Arr::get($data, 'finishedByDateRange', '0');

        $pages = [];

        $byDateRange = (array) Arr::get($data, 'byDateRange', []);
        $firstTableRows = array_map(function ($row) {
            return [
                (string) ($row['course_title'] ?? ''),
                (string) ($row['start_date'] ?? ''),
                (string) ($row['end_date'] ?? ''),
                (string) ($row['duration'] ?? ''),
                (string) ($row['duration_days'] ?? ''),
            ];
        }, $byDateRange);

        if (count($firstTableRows) === 0) {
            $firstTableRows[] = [[
                'text' => 'No hay Acciones de Formación que mostrar para este período',
                'colspan' => 5,
            ]];
        }

        $pages[] = [
            'kind' => 'table',
            'title' => "Total de horas impartidas durante el período {$startDisplay} - {$endDisplay} : {$total} Horas",
            'columns' => ['Título', 'Fecha de Inicio', 'Fecha Fin', 'Duración Horas', 'Duración Días'],
            'rows' => $firstTableRows,
        ];

        $mostDuration = (array) Arr::get($data, 'mostDuration', []);
        $pages[] = [
            'kind' => 'table',
            'title' => 'Acciones de Formación según cantidad de horas',
            'columns' => ['Código', 'Título', 'Duración en Horas'],
            'rows' => array_map(function ($row) {
                return [
                    (string) ($row['code'] ?? ''),
                    (string) ($row['title'] ?? ''),
                    (string) ($row['duration'] ?? ''),
                ];
            }, $mostDuration),
        ];

        return $pages;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByParticipantStatus(array $data): array
    {
        $range = Arr::get($data, 'dateRange', ['startDate' => '', 'endDate' => '']);
        $start = (string) Arr::get($range, 'startDate', '');
        $end = (string) Arr::get($range, 'endDate', '');
        $startDisplay = $this->formatDateForDisplay($start);
        $endDisplay = $this->formatDateForDisplay($end);

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

        // Fixed order (as used in frontend)
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

        $participants = (array) Arr::get($data, 'byAllTime', []);
        $participantRows = [];
        foreach ($participants as $p) {
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

        if ($rows === 0) {
            return [[
                'kind' => 'table',
                'title' => 'Acciones de Formación por Cantidad de Participantes',
                'columns' => ['Mensaje'],
                'rows' => [['No existen Acciones de Formación con participantes asignados en este período de tiempo']],
            ]];
        }

        $rows = (array) $rows;

        return [[
            'kind' => 'table',
            'title' => 'Acciones de Formación por Cantidad de Participantes',
            'columns' => ['Título', 'Fecha de Inicio', 'Fecha de Culminación', 'Cantidad de Participantes'],
            'rows' => array_map(function ($row) {
                return [
                    (string) ($row['course'] ?? ''),
                    (string) ($row['start_date'] ?? ''),
                    (string) ($row['end_date'] ?? ''),
                    (string) ($row['count'] ?? 0),
                ];
            }, $rows),
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByGender(array $data): array
    {
        $range = Arr::get($data, 'dateRange', ['startDate' => '', 'endDate' => '']);
        $start = (string) Arr::get($range, 'startDate', '');
        $end = (string) Arr::get($range, 'endDate', '');
        $startDisplay = $this->formatDateForDisplay($start);
        $endDisplay = $this->formatDateForDisplay($end);

        $rows = (array) Arr::get($data, 'rows', []);
        $total = (int) Arr::get($data, 'total', 0);

        $male = collect($rows)->firstWhere('label', 'Masculino');
        $female = collect($rows)->firstWhere('label', 'Femenino');
        $maleCount = (int) ($male['amount'] ?? 0);
        $femaleCount = (int) ($female['amount'] ?? 0);

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
        $amountData = (array) Arr::get($data, 'amountData', []);
        $courseData = (array) Arr::get($data, 'courseData', []);

        $rows = [];
        $total = 0;
        foreach ($courseData as $c) {
            $id = (string) ($c['id'] ?? '');
            $amount = (int) ($amountData[$id] ?? 0);
            $total += $amount;
            $rows[] = [(string) ($c['code'] ?? ''), (string) ($c['title'] ?? ''), (string) $amount];
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
        $amountData = (array) Arr::get($data, 'amountData', []);
        $courseData = collect((array) Arr::get($data, 'courseData', []))
            ->sortBy(fn ($course) => (int) ($amountData[(string) ($course['id'] ?? '')] ?? 0))
            ->values();

        $rows = [];
        $total = 0;
        foreach ($courseData as $c) {
            $id = (string) ($c['id'] ?? '');
            $amount = (int) ($amountData[$id] ?? 0);
            $total += $amount;
            $rows[] = [(string) ($c['code'] ?? ''), (string) ($c['title'] ?? ''), (string) $amount];
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
        $items = (array) Arr::get($data, 'data', []);

        if (count($items) === 0) {
            return [[
                'kind' => 'table',
                'title' => 'Acciones de Formación No Programadas (Histórico)',
                'columns' => ['Mensaje'],
                'rows' => [['No existen Acciones de Formación sin programar']],
            ]];
        }

        $rows = [];
        foreach ($items as $c) {
            $category = $c['category'] ?? [];
            $rows[] = [(string) ($c['code'] ?? ''), (string) ($c['title'] ?? ''), (string) Arr::get($category, 'name', '')];
        }

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
        $items = (array) Arr::get($data, 'data', []);

        if (count($items) === 0) {
            return [[
                'kind' => 'table',
                'title' => 'Acciones de Formación Canceladas',
                'columns' => ['Mensaje'],
                'rows' => [['No existen Acciones de Formación Canceladas en el período']],
            ]];
        }

        $rows = [];
        foreach ($items as $s) {
            $rows[] = [
                (string) ($s['code'] ?? ''),
                (string) ($s['title'] ?? ''),
                (string) ($s['category'] ?? ''),
                $this->formatDateForDisplay((string) ($s['start_date'] ?? '')),
                $this->formatDateForDisplay((string) ($s['end_date'] ?? '')),
            ];
        }

        return [[
            'kind' => 'table',
            'title' => 'Acciones de Formación Canceladas (Total: '.count($items).')',
            'columns' => ['Código', 'Título', 'Área de Conocimiento', 'Inicio', 'Fin'],
            'rows' => $rows,
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByParticipantAverage(array $data): array
    {
        $rows = (array) Arr::get($data, 'data', []);

        if (count($rows) === 0) {
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

        $overallAvgTotal = $round2(
            array_sum(array_map(fn ($r) => (float) ($r['total'] ?? 0), $rows)) / count($rows)
        );
        $overallAvgApproved = $round2(
            array_sum(array_map(fn ($r) => (float) ($r['approved'] ?? 0), $rows)) / count($rows)
        );
        $overallAvgFailed = $round2(
            array_sum(array_map(fn ($r) => (float) ($r['failed'] ?? 0), $rows)) / count($rows)
        );

        $title = 'Promedio de Participantes por Acción de Formación';

        $pages = [];

        if ($page = $this->chartPage('participant_average_line', $title)) {
            $pages[] = $page;
        }

        $tableRows = [];
        foreach ($labels as $i => $d) {
            $tableRows[] = [$this->formatDateForDisplay($d), (string) $avgTotal[$i], (string) $avgApproved[$i], (string) $avgFailed[$i]];
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
