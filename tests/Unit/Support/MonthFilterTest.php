<?php

namespace Tests\Unit\Support;

use App\Support\MonthFilter;
use PHPUnit\Framework\TestCase;

class MonthFilterTest extends TestCase
{
    public function test_parses_valid_month_year_values(): void
    {
        foreach ([
            '01-2026' => '2026-01-01',
            '10-2026' => '2026-10-01',
            '12-2026' => '2026-12-01',
        ] as $value => $expected) {
            $month = MonthFilter::parse($value);

            $this->assertNotNull($month);
            $this->assertSame($expected, $month->toDateString());
        }
    }

    public function test_returns_null_for_empty_values(): void
    {
        $this->assertNull(MonthFilter::parse(null));
        $this->assertNull(MonthFilter::parse(''));
        $this->assertNull(MonthFilter::parse('   '));
    }

    public function test_returns_null_for_invalid_values(): void
    {
        foreach (['2026-10', '13-2026', '1-2026', '00-2026', 'foo'] as $value) {
            $this->assertNull(MonthFilter::parse($value), $value);
        }
    }

    public function test_displays_month_year_values(): void
    {
        $month = MonthFilter::parse('10-2026');

        $this->assertSame('10-2026', MonthFilter::display($month));
        $this->assertNull(MonthFilter::display(null));
    }
}
