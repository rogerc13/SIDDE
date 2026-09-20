<?php

namespace App\Http\Controllers;

use App\Enums\ReportType;
use App\Models\ParticipantStatus;
use App\Services\Reports\ChartPalette;
use App\Services\Reports\InsightBuilder;
use App\Services\Reports\ReportExportService;
use App\Services\Reports\ReportQueryService;
use App\Services\Reports\ReportResult;
use App\Services\Reports\ReportSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportQueryService $queries,
        private readonly InsightBuilder $insights,
        private readonly ReportSummaryService $summaries,
        private readonly ReportExportService $exports,
        private readonly ChartPalette $palette,
    ) {
    }

    /**
     * Registry-driven dispatch: every report type shares one endpoint shape.
     */
    public function show(Request $request): JsonResponse
    {
        $type = ReportType::tryFrom((string) $request->route('reportType'));

        abort_if($type === null, 404);

        return response()->json($this->build($type, $request));
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json($this->summaries->build($request));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $type = ReportType::tryFrom((string) $request->input('report-type', ''));

        abort_if($type === null, 422, 'Tipo de reporte inválido.');

        $format = (string) $request->input('format', 'csv');

        return $this->exports->download($this->resolve($type, $request), $format);
    }

    /**
     * @return array<string, mixed>
     */
    private function build(ReportType $type, Request $request): array
    {
        return $this->resolve($type, $request)->toArray();
    }

    private function resolve(ReportType $type, Request $request): ReportResult
    {
        $result = $this->queries->run($type, $request);

        foreach ($this->insights->build($result) as $line) {
            $result->addInsight($line);
        }

        return $result;
    }

    /**
     * Metadata for the frontend: report catalog + shared palette.
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'types' => ReportType::options(),
            'palette' => $this->palette->toArray(),
        ]);
    }

    public function participantStatus(): JsonResponse
    {
        return response()->json(['statuses' => ParticipantStatus::all()]);
    }
}
