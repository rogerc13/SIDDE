<?php

namespace App\Http\Controllers;

use App\Services\Reports\PuppeteerChartRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ReportPdfController extends Controller
{
    private const DISPLAY_DATE_FORMAT = 'd-m-Y';

    /**
     * @var array<int, array{title?: string, image?: string}>
     */
    private array $providedCharts = [];

    public function __construct(
        private readonly PuppeteerChartRenderer $chartRenderer,
    ) {
    }

    public function download(Request $request)
    {
        $type = (string) $request->input('report-type', '');

        $charts = $request->input('charts', []);
        $this->providedCharts = is_array($charts) ? array_values(array_filter($charts, fn ($c) => is_array($c))) : [];

        $allowed = [
            'date',
            'category',
            'status',
            'most-scheduled',
            'not-scheduled',
            'participant-by-quantity',
            'duration',
            'participant-by-status',
            'gender',
        ];

        abort_unless(in_array($type, $allowed, true), 422, 'Tipo de reporte inválido.');

        $data = $this->getReportData($type, $request);

        $pages = $this->buildPages($type, $data);

        $reportTitle = $this->reportDisplayName($type);

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

    private function reportDisplayName(string $type): string
    {
        return match ($type) {
            'date' => 'Cantidad de Acciones de Formación',
            'category' => 'Cantidad de Acciones de Formación Por Areas de Conocimiento',
            'status' => 'Cantidad de Acciones de Formación Por Estatus',
            'most-scheduled' => 'Acciónes de Formación más Programadas',
            'not-scheduled' => 'Acciones de Formación No Programadas',
            'participant-by-quantity' => 'Acciones de Formación por Cantidad de Participantes',
            'duration' => 'Duración de Acciones de Formación',
            'participant-by-status' => 'Estatus de Participantes',
            'gender' => 'Distribución de Participantes por Género',
            default => 'Reportes',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function getReportData(string $type, Request $request): array
    {
        $controller = app(ReportController::class);

        $result = match ($type) {
            'date' => $controller->byDate($request),
            'category' => $controller->byCategory($request),
            'status' => $controller->byStatus($request),
            'duration' => $controller->byCourseDuration($request),
            'participant-by-status' => $controller->participantsByStatus($request),
            'participant-by-quantity' => $controller->courseByParticipantQuantity($request),
            'gender' => $controller->participantsByGender($request),
            'most-scheduled' => $controller->mostScheduled($request),
            'not-scheduled' => $controller->notScheduled($request),
            default => [],
        };

        if ($result instanceof JsonResponse) {
            return (array) $result->getData(true);
        }

        if (is_string($result)) {
            $decoded = json_decode($result, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($result)) {
            return $result;
        }

        return [];
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
            'not-scheduled' => $this->pagesByNotScheduled($data),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $chartConfig
     */
    private function safeRenderChart(array $chartConfig): ?string
    {
        try {
            return $this->chartRenderer->renderChartToDataUri($chartConfig);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function consumeProvidedChart(?string $fallbackTitle = null): ?array
    {
        if (count($this->providedCharts) === 0) {
            return null;
        }

        $chart = array_shift($this->providedCharts);
        if (! is_array($chart)) {
            return null;
        }

        $img = $chart['image'] ?? null;
        if (! is_string($img) || ! str_starts_with($img, 'data:image/')) {
            return null;
        }

        return [
            'kind' => 'chart',
            'title' => (string) ($chart['title'] ?? $fallbackTitle ?? ''),
            'image' => $img,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function chartFailurePage(string $title): array
    {
        return [
            'kind' => 'table',
            'title' => $title,
            'columns' => ['Mensaje'],
            'rows' => [[
                'No se pudo generar el gráfico automáticamente en el servidor. Se incluye la tabla/datos disponibles.',
            ]],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByDate(array $data): array
    {
        $x = Arr::get($data, 'x', []);
        $y = Arr::get($data, 'y', []);
        $start = (string) Arr::get($data, 'start_date', '');
        $end = (string) Arr::get($data, 'end_date', '');
        $startDisplay = $this->formatDateForDisplay($start);
        $endDisplay = $this->formatDateForDisplay($end);
        $total = (int) Arr::get($data, 'total', 0);

        $labels = array_map(fn ($v) => (string) $v, (array) $x);
        $values = [];
        foreach ($labels as $idx => $date) {
            $count = $y[$idx] ?? null;
            $values[] = is_numeric($count) ? (int) $count : 0;
        }

        $title = "Cantidad de Acciones de Formación durante el Período {$startDisplay} - {$endDisplay} : {$total}";

        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Cantidad de Acciones de Formación',
                    'data' => $values,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderWidth' => 3,
                    'tension' => 0.35,
                    'spanGaps' => true,
                    'pointRadius' => 0,
                ]],
            ],
            'options' => [
                'responsive' => false,
                'plugins' => [
                    'legend' => ['display' => true],
                    'title' => ['display' => true, 'text' => $title],
                ],
                'scales' => [
                    'x' => [
                        'type' => 'category',
                        'title' => ['display' => true, 'text' => 'Período de Tiempo'],
                    ],
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['stepSize' => 1, 'precision' => 0],
                    ],
                ],
            ],
        ];

        if ($provided = $this->consumeProvidedChart($title)) {
            return [$provided];
        }

        $img = $this->safeRenderChart($chartConfig);
        if (! $img) {
            return [$this->chartFailurePage($title)];
        }

        return [[
            'kind' => 'chart',
            'title' => $title,
            'image' => $img,
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pagesByCategory(array $data): array
    {
        $palette = [
            '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd',
            '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf',
        ];

        $x = (array) Arr::get($data, 'x', []);
        $rows = (array) Arr::get($data, 'y', []);
        $categories = (array) Arr::get($data, 'categories', []);
        $courseData = (array) Arr::get($data, 'courseData', []);
        $range = Arr::get($data, 'dateRange', ['startDate' => '', 'endDate' => '']);
        $start = (string) Arr::get($range, 'startDate', '');
        $end = (string) Arr::get($range, 'endDate', '');
        $startDisplay = $this->formatDateForDisplay($start);
        $endDisplay = $this->formatDateForDisplay($end);

        $labels = array_map(fn ($v) => (string) $v, (array) $x);
        $datasets = [];
        foreach ($categories as $i => $categoryName) {
            $values = [];
            foreach ($labels as $date) {
                $found = collect($rows)->first(function ($r) use ($categoryName, $date) {
                    return ($r['category'] ?? null) === $categoryName && ($r['x'] ?? null) === $date;
                });
                $value = $found['y'] ?? null;
                $values[] = is_numeric($value) ? (int) $value : 0;
            }

            $datasets[] = [
                'label' => $categoryName,
                'data' => $values,
                'borderColor' => $palette[$i % count($palette)],
                'backgroundColor' => $palette[$i % count($palette)],
                'borderWidth' => 2,
                'tension' => 0.25,
                'spanGaps' => true,
                'pointRadius' => 0,
            ];
        }

        $lineTitle = "Cantidad de Acciones de Formación por Áreas de Conocimiento durante el período {$startDisplay} - {$endDisplay}";

        $lineConfig = [
            'type' => 'line',
            'data' => ['labels' => $labels, 'datasets' => $datasets],
            'options' => [
                'responsive' => false,
                'plugins' => [
                    'legend' => ['display' => true],
                    'title' => ['display' => true, 'text' => $lineTitle],
                ],
                'scales' => [
                    'x' => [
                        'type' => 'category',
                        'title' => ['display' => true, 'text' => 'Período de Tiempo'],
                    ],
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['stepSize' => 1, 'precision' => 0],
                        'title' => ['display' => true, 'text' => 'Cantidad de Acciones de Formación'],
                    ],
                ],
            ],
        ];

        $doughnutTitle = "Distribución de Acciones de Formación por Áreas de Conocimiento durante el período {$startDisplay} - {$endDisplay}";
        $doughnutLabels = [];
        $doughnutData = [];
        $doughnutColors = [];

        foreach ($courseData as $i => $row) {
            $doughnutLabels[] = (string) ($row['categoryName'] ?? '');
            $doughnutData[] = (int) ($row['amount'] ?? 0);
            $doughnutColors[] = $palette[$i % count($palette)];
        }

        $doughnutConfig = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $doughnutLabels,
                'datasets' => [[
                    'data' => $doughnutData,
                    'backgroundColor' => $doughnutColors,
                ]],
            ],
            'options' => [
                'responsive' => false,
                'plugins' => [
                    'legend' => ['display' => true],
                    'title' => ['display' => true, 'text' => $doughnutTitle],
                ],
            ],
        ];

        $pages = [];

        $pages[] = $this->consumeProvidedChart($lineTitle)
            ?? (($img = $this->safeRenderChart($lineConfig)) ? ['kind' => 'chart', 'title' => $lineTitle, 'image' => $img] : $this->chartFailurePage($lineTitle));

        $pages[] = $this->consumeProvidedChart($doughnutTitle)
            ?? (($img = $this->safeRenderChart($doughnutConfig)) ? ['kind' => 'chart', 'title' => $doughnutTitle, 'image' => $img] : $this->chartFailurePage($doughnutTitle));

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
        $palette = [
            '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd',
            '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf',
        ];

        $x = (array) Arr::get($data, 'x', []);
        $rows = (array) Arr::get($data, 'y', []);
        $statuses = (array) Arr::get($data, 'statuses', []);
        $courseData = (array) Arr::get($data, 'courseData', []);
        $range = Arr::get($data, 'dateRange', ['startDate' => '', 'endDate' => '']);
        $start = (string) Arr::get($range, 'startDate', '');
        $end = (string) Arr::get($range, 'endDate', '');
        $startDisplay = $this->formatDateForDisplay($start);
        $endDisplay = $this->formatDateForDisplay($end);

        $labels = array_map(fn ($v) => (string) $v, (array) $x);
        $datasets = [];
        foreach ($statuses as $i => $statusName) {
            $values = [];
            foreach ($labels as $date) {
                $found = collect($rows)->first(function ($r) use ($statusName, $date) {
                    return ($r['status'] ?? null) === $statusName && ($r['x'] ?? null) === $date;
                });
                $value = $found['y'] ?? null;
                $values[] = is_numeric($value) ? (int) $value : 0;
            }

            $datasets[] = [
                'label' => $statusName,
                'data' => $values,
                'borderColor' => $palette[$i % count($palette)],
                'backgroundColor' => $palette[$i % count($palette)],
                'borderWidth' => 2,
                'tension' => 0.25,
                'spanGaps' => true,
                'pointRadius' => 0,
            ];
        }

        $lineTitle = "Distribución de Acciones de Formación por Estatus durante el período {$startDisplay} - {$endDisplay}";

        $lineConfig = [
            'type' => 'line',
            'data' => ['labels' => $labels, 'datasets' => $datasets],
            'options' => [
                'responsive' => false,
                'plugins' => [
                    'legend' => ['display' => true],
                    'title' => ['display' => true, 'text' => $lineTitle],
                ],
                'scales' => [
                    'x' => [
                        'type' => 'category',
                        'title' => ['display' => true, 'text' => 'Período de Tiempo'],
                    ],
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['stepSize' => 1, 'precision' => 0],
                        'title' => ['display' => true, 'text' => 'Cantidad de Acciones de Formación'],
                    ],
                ],
            ],
        ];

        $doughnutLabels = [];
        $doughnutData = [];
        $doughnutColors = [];

        foreach ($courseData as $i => $row) {
            $doughnutLabels[] = (string) ($row['statusName'] ?? '');
            $doughnutData[] = (int) ($row['amount'] ?? 0);
            $doughnutColors[] = $palette[$i % count($palette)];
        }

        $doughnutConfig = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $doughnutLabels,
                'datasets' => [[
                    'data' => $doughnutData,
                    'backgroundColor' => $doughnutColors,
                ]],
            ],
            'options' => [
                'responsive' => false,
                'plugins' => [
                    'legend' => ['display' => true],
                    'title' => ['display' => true, 'text' => $lineTitle],
                ],
            ],
        ];

        $pages = [];

        $pages[] = $this->consumeProvidedChart($lineTitle)
            ?? (($img = $this->safeRenderChart($lineConfig)) ? ['kind' => 'chart', 'title' => $lineTitle, 'image' => $img] : $this->chartFailurePage($lineTitle));

        $pages[] = $this->consumeProvidedChart($lineTitle)
            ?? (($img = $this->safeRenderChart($doughnutConfig)) ? ['kind' => 'chart', 'title' => $lineTitle, 'image' => $img] : $this->chartFailurePage($lineTitle));

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

        $chartLabels = array_keys($statusTotals);
        $chartData = array_values($statusTotals);

        $palette = [
            '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd',
            '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf',
        ];

        $colors = [];
        foreach ($chartLabels as $i => $lbl) {
            $colors[] = $palette[$i % count($palette)];
        }

        $title = "Distribución de Participantes por Estatus en el Período {$startDisplay} - {$endDisplay}";

        $barConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $chartLabels,
                'datasets' => [[
                    'data' => $chartData,
                    'backgroundColor' => $colors,
                ]],
            ],
            'options' => [
                'responsive' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                    'title' => ['display' => true, 'text' => $title],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['stepSize' => 1, 'precision' => 0],
                    ],
                ],
            ],
        ];

        $doughnutConfig = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $chartLabels,
                'datasets' => [[
                    'data' => $chartData,
                    'backgroundColor' => $colors,
                ]],
            ],
            'options' => [
                'responsive' => false,
                'plugins' => [
                    'legend' => ['display' => true],
                    'title' => ['display' => true, 'text' => $title],
                ],
            ],
        ];

        $pages = [];

        $barPageTitle = 'Distribución de Participantes por Estatus (Barras)';
        $doughnutPageTitle = 'Distribución de Participantes por Estatus (Dona)';

        $pages[] = $this->consumeProvidedChart($barPageTitle)
            ?? (($img = $this->safeRenderChart($barConfig)) ? ['kind' => 'chart', 'title' => $barPageTitle, 'image' => $img] : $this->chartFailurePage($barPageTitle));

        $pages[] = $this->consumeProvidedChart($doughnutPageTitle)
            ?? (($img = $this->safeRenderChart($doughnutConfig)) ? ['kind' => 'chart', 'title' => $doughnutPageTitle, 'image' => $img] : $this->chartFailurePage($doughnutPageTitle));

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
        $labels = array_map(fn ($r) => (string) ($r['label'] ?? ''), $rows);
        $values = array_map(fn ($r) => (int) ($r['amount'] ?? 0), $rows);
        $total = (int) Arr::get($data, 'total', array_sum($values));

        $title = "Distribución de Participantes por Género en el Período {$startDisplay} - {$endDisplay}";

        $palette = ['#1f77b4', '#ff7f0e', '#7f7f7f'];
        $colors = [];
        foreach ($labels as $i => $lbl) {
            $colors[] = $palette[$i % count($palette)];
        }

        $barConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $values,
                    'backgroundColor' => $colors,
                ]],
            ],
            'options' => [
                'responsive' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                    'title' => ['display' => true, 'text' => $title],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['stepSize' => 1, 'precision' => 0],
                    ],
                ],
            ],
        ];

        $doughnutConfig = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $values,
                    'backgroundColor' => $colors,
                ]],
            ],
            'options' => [
                'responsive' => false,
                'plugins' => [
                    'legend' => ['display' => true],
                    'title' => ['display' => true, 'text' => $title],
                ],
            ],
        ];

        $male = collect($rows)->firstWhere('label', 'Masculino');
        $female = collect($rows)->firstWhere('label', 'Femenino');
        $maleCount = (int) ($male['amount'] ?? 0);
        $femaleCount = (int) ($female['amount'] ?? 0);

        $pages = [];
        $barPageTitle = $title.' (Barras)';
        $doughnutPageTitle = $title.' (Dona)';

        $pages[] = $this->consumeProvidedChart($barPageTitle)
            ?? (($img = $this->safeRenderChart($barConfig)) ? ['kind' => 'chart', 'title' => $barPageTitle, 'image' => $img] : $this->chartFailurePage($barPageTitle));

        $pages[] = $this->consumeProvidedChart($doughnutPageTitle)
            ?? (($img = $this->safeRenderChart($doughnutConfig)) ? ['kind' => 'chart', 'title' => $doughnutPageTitle, 'image' => $img] : $this->chartFailurePage($doughnutPageTitle));

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
            'title' => "Acciones de Formación más programadas (Total: {$total})",
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
                'title' => 'Acciones de Formación No Programadas',
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
            'title' => 'Acciones de Formación No Programadas',
            'columns' => ['Código', 'Título', 'Área de Conocimiento'],
            'rows' => $rows,
        ]];
    }
}
