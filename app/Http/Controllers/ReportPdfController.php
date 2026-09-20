<?php

namespace App\Http\Controllers;

use App\Enums\ReportType;
use App\Services\Reports\InsightBuilder;
use App\Services\Reports\PuppeteerChartRenderer;
use App\Services\Reports\ReportQueryService;
use Barryvdh\DomPDF\Facade\Pdf;
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
        private readonly ReportQueryService $queries,
        private readonly InsightBuilder $insights,
    ) {
    }

    public function download(Request $request)
    {
        $type = (string) $request->input('report-type', '');

        $charts = $request->input('charts', []);
        $this->providedCharts = is_array($charts) ? array_values(array_filter($charts, fn ($c) => is_array($c))) : [];

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
        $lineConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Promedio Total',
                        'data' => $avgTotal,
                        'borderColor' => 'rgb(75, 192, 192)',
                        'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                        'fill' => false,
                        'tension' => 0.5,
                    ],
                    [
                        'label' => 'Promedio Aprobados',
                        'data' => $avgApproved,
                        'borderColor' => 'rgb(54, 162, 235)',
                        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                        'fill' => false,
                        'tension' => 0.5,
                    ],
                    [
                        'label' => 'Promedio Reprobados',
                        'data' => $avgFailed,
                        'borderColor' => 'rgb(255, 99, 132)',
                        'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                        'fill' => false,
                        'tension' => 0.5,
                    ],
                ],
            ],
            'options' => [
                'responsive' => false,
                'plugins' => [
                    'legend' => ['display' => true],
                    'title' => ['display' => true, 'text' => $title],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                    ],
                ],
            ],
        ];

        $pages = [];

        $pages[] = $this->consumeProvidedChart($title)
            ?? (($img = $this->safeRenderChart($lineConfig)) ? ['kind' => 'chart', 'title' => $title, 'image' => $img] : $this->chartFailurePage($title));

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
