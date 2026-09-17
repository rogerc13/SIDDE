<?php

namespace Tests\Unit;

use App\Models\Funciones;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class FuncionesTest extends TestCase
{
    public function test_format_date_returns_empty_string_for_null(): void
    {
        $this->assertSame('', Funciones::formatDate(null));
    }

    public function test_format_date_returns_empty_string_for_empty_string(): void
    {
        $this->assertSame('', Funciones::formatDate(''));
    }

    public function test_format_date_formats_iso_string_as_day_month_year(): void
    {
        $this->assertSame('16-09-2026', Funciones::formatDate('2026-09-16'));
    }

    public function test_format_date_formats_carbon_instance(): void
    {
        $this->assertSame('16-09-2026', Funciones::formatDate(Carbon::create(2026, 9, 16, 10, 0, 0)));
    }

    public function test_format_date_accepts_custom_format(): void
    {
        $this->assertSame('09-2026', Funciones::formatDate('2026-09-16', 'm-Y'));
    }

    public function test_format_date_returns_original_value_for_invalid_date(): void
    {
        $this->assertSame('not-a-date', Funciones::formatDate('not-a-date'));
    }
}
