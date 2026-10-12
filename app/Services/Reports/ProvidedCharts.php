<?php

namespace App\Services\Reports;

use Illuminate\Http\Request;

/**
 * Chart images captured in the browser and posted with the PDF request.
 *
 * Charts are keyed by a stable id set on the canvas via data-chart-key (e.g.
 * "category_bar"), so the PDF maps each image to its page without depending on
 * DOM order or on the browser matching the server's exact wording.
 */
final class ProvidedCharts
{
    /**
     * @param  array<string, mixed>  $charts
     */
    public function __construct(private readonly array $charts = [])
    {
    }

    public static function fromRequest(Request $request): self
    {
        $charts = $request->input('charts', []);

        return new self(is_array($charts) ? $charts : []);
    }

    /**
     * PNG (or other image) data URI for the given chart key, or null when the
     * chart is absent or the payload is not a usable data URI.
     */
    public function image(string $key): ?string
    {
        $chart = $this->charts[$key] ?? null;

        if (! is_array($chart)) {
            return null;
        }

        $image = $chart['image'] ?? null;

        if (! is_string($image) || ! str_starts_with($image, 'data:image/')) {
            return null;
        }

        return $image;
    }
}
