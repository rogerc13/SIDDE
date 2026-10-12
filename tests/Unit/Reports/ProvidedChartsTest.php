<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\ProvidedCharts;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class ProvidedChartsTest extends TestCase
{
    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function test_it_returns_the_image_for_a_known_key(): void
    {
        $charts = ProvidedCharts::fromRequest($this->request([
            'category_bar' => ['image' => self::PNG],
        ]));

        $this->assertSame(self::PNG, $charts->image('category_bar'));
    }

    public function test_it_returns_null_for_unknown_keys(): void
    {
        $charts = ProvidedCharts::fromRequest($this->request([
            'category_bar' => ['image' => self::PNG],
        ]));

        $this->assertNull($charts->image('category_doughnut'));
    }

    public function test_it_ignores_non_image_payloads(): void
    {
        $charts = ProvidedCharts::fromRequest($this->request([
            'category_bar' => ['image' => 'not-a-data-uri'],
        ]));

        $this->assertNull($charts->image('category_bar'));
    }

    public function test_it_handles_a_missing_charts_field(): void
    {
        $charts = ProvidedCharts::fromRequest(Request::create('/reports/pdf', 'POST'));

        $this->assertNull($charts->image('date_line'));
    }

    /**
     * @param  array<string, array<string, string>>  $charts
     */
    private function request(array $charts): Request
    {
        return Request::create('/reports/pdf', 'POST', ['charts' => $charts]);
    }
}
