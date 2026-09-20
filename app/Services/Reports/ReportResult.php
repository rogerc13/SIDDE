<?php

namespace App\Services\Reports;

use App\Enums\ReportType;
use Illuminate\Support\Carbon;

/**
 * Normalized, presentation-agnostic report payload.
 *
 * One instance drives every output: JSON for the screen, the PDF page builder,
 * the export writer and the deterministic narrative. Chart-agnostic consumers
 * read {@see $charts} / {@see $tables}; the export layer reads {@see $raw}.
 */
final class ReportResult
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<int, array<string, mixed>>  $summary
     * @param  array<int, string>  $insight
     * @param  array<int, array<string, mixed>>  $charts
     * @param  array<int, array<string, mixed>>  $tables
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public ReportType $type,
        public array $meta = [],
        public array $summary = [],
        public array $insight = [],
        public array $charts = [],
        public array $tables = [],
        public array $raw = [],
    ) {
    }

    public function withMeta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    public function addSummary(array $kpi): self
    {
        $this->summary[] = $kpi;

        return $this;
    }

    public function addInsight(string $insight): self
    {
        $this->insight[] = $insight;

        return $this;
    }

    public function addChart(array $chart): self
    {
        $this->charts[] = $chart;

        return $this;
    }

    public function addTable(array $table): self
    {
        $this->tables[] = $table;

        return $this;
    }

    public function withRaw(array $raw): self
    {
        $this->raw = $raw;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'meta' => [
                'title' => $this->type->label(),
                'generatedAt' => Carbon::now()->toIso8601String(),
                ...$this->meta,
            ],
            'summary' => $this->summary,
            'insight' => $this->insight,
            'charts' => $this->charts,
            'tables' => $this->tables,
            'raw' => $this->raw,
        ];
    }
}
