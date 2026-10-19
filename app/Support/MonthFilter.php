<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class MonthFilter
{
    public const INPUT_FORMAT = 'm-Y';

    public static function fromRequest(Request $request, string $key): ?CarbonImmutable
    {
        $request->validate([
            $key => ['nullable', 'string', 'regex:/^(0[1-9]|1[0-2])-\d{4}$/'],
        ], [
            $key . '.regex' => 'El mes debe tener el formato MM-AAAA.',
        ]);

        return self::parse($request->input($key));
    }

    public static function parse(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^(0[1-9]|1[0-2])-\d{4}$/', $value) !== 1) {
            return null;
        }

        $month = CarbonImmutable::createFromFormat('!' . self::INPUT_FORMAT, $value);

        return $month === false ? null : $month->startOfMonth();
    }

    public static function applyTo(
        Builder $query,
        CarbonImmutable $month,
        string $column = 'start_date',
    ): Builder {
        return $query->whereBetween($column, [
            $month->toDateString(),
            $month->endOfMonth()->toDateString(),
        ]);
    }

    public static function display(?CarbonImmutable $month): ?string
    {
        return $month?->format(self::INPUT_FORMAT);
    }
}
