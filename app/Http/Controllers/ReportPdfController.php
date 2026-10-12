<?php

namespace App\Http\Controllers;

use App\Enums\ReportType;
use App\Services\Reports\InsightBuilder;
use App\Services\Reports\ProvidedCharts;
use App\Services\Reports\ReportPdfPageBuilder;
use App\Services\Reports\ReportQueryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ReportPdfController extends Controller
{
    public function __construct(
        private readonly ReportQueryService $queries,
        private readonly InsightBuilder $insights,
    ) {
    }

    public function download(Request $request)
    {
        $type = (string) $request->input('report-type', '');

        $allowed = array_map(fn (ReportType $type) => $type->value, ReportType::cases());

        abort_unless(in_array($type, $allowed, true), 422, 'Tipo de reporte inválido.');

        $reportType = ReportType::from($type);
        $result = $this->queries->run($reportType, $request);
        $data = $result->raw;

        $pages = (new ReportPdfPageBuilder(ProvidedCharts::fromRequest($request)))
            ->build($reportType, $data);

        if ($reportType->hasInsight() && ($insights = $this->insights->build($result)) !== []) {
            array_unshift($pages, [
                'kind' => 'summary',
                'title' => 'Resumen Ejecutivo',
                'lines' => $insights,
            ]);
        }

        $reportTitle = $reportType->label();

        [$startDate, $endDate] = $this->resolveDateRange($type, $data, $request);

        $startDateDisplay = ReportPdfPageBuilder::displayDate($startDate);
        $endDateDisplay = ReportPdfPageBuilder::displayDate($endDate);

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
}
