<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Builds the {@see DateBuckets} for a report request.
 *
 * Centralizes validation and the half-open bucketing rules that used to be
 * re-implemented per report.
 */
final class DateRangeFactory
{
    /** Hard cap on generated buckets so a huge range × small step cannot storm the DB. */
    public const MAX_RANGE_BUCKETS = 400;

    /** Interval steps offered by the reports UI. */
    public const ALLOWED_STEPS = ['1 day', '1 week', '15 days', '1 month', '4 months', '6 months', '1 year'];

    public function build(Request $request): DateBuckets
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'step' => 'nullable|string|max:20|in:' . implode(',', self::ALLOWED_STEPS),
        ], [
            'start_date.date' => 'La fecha inicial no es válida.',
            'end_date.date' => 'La fecha final no es válida.',
            'step.in' => 'El intervalo seleccionado no es válido.',
        ]);

        $step = $request->step ?? '1 month';

        $rangeStart = ! empty($request->start_date)
            ? Carbon::parse($request->start_date)->startOfDay()
            : (! empty($request->end_date)
                ? Carbon::parse($request->end_date)->subMonth()->startOfDay()
                : Carbon::now()->subMonth()->startOfDay());

        $currentDate = ! empty($request->end_date)
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        if ($rangeStart->greaterThan($currentDate)) {
            [$rangeStart, $currentDate] = [$currentDate->copy()->startOfDay(), $rangeStart->copy()->endOfDay()];
        }

        try {
            $period = CarbonPeriod::create($rangeStart, $step, $currentDate);
        } catch (\Throwable) {
            $period = CarbonPeriod::create($rangeStart, '1 day', $currentDate);
        }

        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        // Ensure the final bucket always ends at the chosen end date.
        $endDateString = $currentDate->format('Y-m-d');
        if (empty($dates)) {
            $dates[] = $rangeStart->format('Y-m-d');
        }
        if (end($dates) !== $endDateString) {
            $dates[] = $endDateString;
        }

        if (count($dates) > self::MAX_RANGE_BUCKETS + 1) {
            throw ValidationException::withMessages([
                'start_date' => 'El rango seleccionado es demasiado amplio para el intervalo indicado. Reduzca el rango de fechas o aumente el intervalo.',
            ]);
        }

        $day = stripos($step, 'day') !== false && intval($step) === 1;

        return new DateBuckets(
            currentDate: $currentDate->format('Y-m-d'),
            dates: array_values($dates),
            numberOfSteps: max(0, count($dates) - 1),
            day: $day,
        );
    }
}
