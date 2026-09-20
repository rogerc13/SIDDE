<?php

namespace App\Services\Reports;

/**
 * Converts a normalized chart spec (the shape carried inside a
 * {@see ReportResult}) into a Chart.js configuration used by the PDF renderer.
 *
 * The frontend renders the same spec with its own Chart.js setup, so the two
 * stay in sync from a single data contract.
 */
final class ChartConfigFactory
{
    public function __construct(
        private readonly ChartPalette $palette = new ChartPalette(),
    ) {
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<string, mixed>
     */
    public function toChartJs(array $spec): array
    {
        $type = (string) ($spec['type'] ?? 'bar');
        $datasets = [];

        foreach ((array) ($spec['datasets'] ?? []) as $index => $dataset) {
            $color = $dataset['color'] ?? $this->palette->forIndex($index);
            $datasets[] = [
                'label' => (string) ($dataset['label'] ?? ''),
                'data' => array_values((array) ($dataset['data'] ?? [])),
                'backgroundColor' => $dataset['backgroundColor'] ?? $color,
                'borderColor' => $dataset['borderColor'] ?? $color,
                'borderWidth' => $dataset['borderWidth'] ?? 2,
                'tension' => $dataset['tension'] ?? 0.25,
                'fill' => $dataset['fill'] ?? false,
                'spanGaps' => true,
                'pointRadius' => 0,
            ];
        }

        $config = [
            'type' => $type,
            'data' => [
                'labels' => array_values((array) ($spec['labels'] ?? [])),
                'datasets' => $datasets,
            ],
            'options' => [
                'responsive' => false,
                'indexAxis' => $spec['indexAxis'] ?? 'x',
                'plugins' => [
                    'legend' => ['display' => (bool) ($spec['showLegend'] ?? true)],
                    'title' => ['display' => true, 'text' => (string) ($spec['title'] ?? '')],
                ],
            ],
        ];

        if (in_array($type, ['line', 'bar'], true)) {
            $config['options']['scales'] = [
                'x' => [
                    'type' => 'category',
                    'stacked' => (bool) ($spec['stacked'] ?? false),
                    'title' => ['display' => isset($spec['xTitle']), 'text' => (string) ($spec['xTitle'] ?? '')],
                ],
                'y' => [
                    'stacked' => (bool) ($spec['stacked'] ?? false),
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                    'title' => ['display' => isset($spec['yTitle']), 'text' => (string) ($spec['yTitle'] ?? '')],
                ],
            ];
        }

        return $config;
    }
}
