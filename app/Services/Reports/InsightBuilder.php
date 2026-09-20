<?php

namespace App\Services\Reports;

use App\Enums\ReportType;
use Illuminate\Support\Arr;

/**
 * Turns a {@see ReportResult} into a short, deterministic Spanish narrative.
 *
 * No external models are called: the same data always yields the same text, so
 * output is stable and testable. Structure follows the data-storytelling
 * "headline + context + what stands out" pattern.
 */
final class InsightBuilder
{
    /**
     * @return array<int, string>
     */
    public function build(ReportResult $result): array
    {
        $raw = $result->raw;
        $range = $this->rangeLabel($raw);

        $lines = match ($result->type) {
            ReportType::Date => $this->forDate($raw, $range),
            ReportType::Category => $this->forCategory($raw, $range),
            ReportType::Status => $this->forStatus($raw, $range),
            ReportType::Duration => $this->forDuration($raw, $range),
            ReportType::ParticipantByStatus => $this->forParticipantStatus($raw, $range),
            ReportType::Gender => $this->forGender($raw, $range),
            ReportType::ParticipantByQuantity => $this->forParticipantQuantity($raw, $range),
            ReportType::ParticipantAverage => $this->forParticipantAverage($raw, $range),
            ReportType::Canceled => $this->forCanceled($raw, $range),
            ReportType::MostScheduled => $this->forMostScheduled($raw),
            ReportType::LeastScheduled => $this->forLeastScheduled($raw),
            ReportType::NotScheduled => $this->forNotScheduled($raw),
        };

        return array_values(array_filter($lines, fn ($line) => $line !== null && $line !== ''));
    }

    private function rangeLabel(array $raw): ?string
    {
        $start = Arr::get($raw, 'dateRange.startDate') ?? Arr::get($raw, 'start_date');
        $end = Arr::get($raw, 'dateRange.endDate') ?? Arr::get($raw, 'end_date');

        if (! $start || ! $end) {
            return null;
        }

        return 'entre el ' . $this->displayDate((string) $start) . ' y el ' . $this->displayDate((string) $end);
    }

    private function displayDate(string $date): string
    {
        try {
            return \Illuminate\Support\Carbon::parse($date)->format('d-m-Y');
        } catch (\Throwable) {
            return $date;
        }
    }

    /**
     * @return array<int, string>
     */
    private function forDate(array $raw, ?string $range): array
    {
        $total = (int) Arr::get($raw, 'total', 0);
        if ($total === 0) {
            return ['No se registraron acciones de formación' . ($range ? " {$range}" : '') . '.'];
        }

        $y = (array) Arr::get($raw, 'y', []);
        $x = (array) Arr::get($raw, 'x', []);
        $peak = 0;
        $peakLabel = null;
        foreach ($y as $i => $value) {
            if ($value > $peak) {
                $peak = (int) $value;
                $peakLabel = $x[$i] ?? null;
            }
        }

        $lines = ['Se registraron ' . $total . ' acciones de formación' . ($range ? " {$range}" : '') . '.'];
        if ($peakLabel) {
            $lines[] = 'El mayor volumen ocurrió el ' . $this->displayDate((string) $peakLabel) . ', con ' . $peak . ' acciones.';
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function forCategory(array $raw, ?string $range): array
    {
        $courseData = (array) Arr::get($raw, 'courseData', []);
        if (empty($courseData)) {
            return ['No hay acciones de formación por área de conocimiento' . ($range ? " {$range}" : '') . '.'];
        }

        $sorted = collect($courseData)->sortByDesc('amount')->values();
        $top = $sorted->first();
        $total = collect($courseData)->sum('amount');

        $lines = ['Se registraron ' . $total . ' acciones de formación' . ($range ? " {$range}" : '') . '.'];
        if (($top['amount'] ?? 0) > 0) {
            $share = $total > 0 ? (int) round(($top['amount'] / $total) * 100) : 0;
            $lines[] = 'El área de "' . ($top['categoryName'] ?? '') . '" concentró la mayor actividad con ' . $top['amount'] . ' acciones (' . $share . '%).';
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function forStatus(array $raw, ?string $range): array
    {
        $total = (int) Arr::get($raw, 'rates.total', 0);
        if ($total === 0) {
            return ['No hay acciones de formación con estatus' . ($range ? " {$range}" : '') . '.'];
        }

        $completion = Arr::get($raw, 'rates.completionRate', 0);
        $cancellation = Arr::get($raw, 'rates.cancellationRate', 0);

        return [
            'Se analizaron ' . $total . ' acciones de formación' . ($range ? " {$range}" : '') . '.',
            'La tasa de culminación fue del ' . $completion . '% y la de cancelación del ' . $cancellation . '%.',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function forDuration(array $raw, ?string $range): array
    {
        $hours = (int) Arr::get($raw, 'finishedByDateRange', 0);
        if ($hours === 0) {
            return ['No se impartieron horas' . ($range ? " {$range}" : '') . '.'];
        }

        $lines = ['Se impartieron ' . $hours . ' horas de formación culminada' . ($range ? " {$range}" : '') . '.'];
        if (($delta = Arr::get($raw, 'hoursDelta')) !== null) {
            $direction = $delta >= 0 ? 'aumentó' : 'disminuyó';
            $lines[] = 'Respecto al período anterior, la carga horaria ' . $direction . ' un ' . abs((int) $delta) . '%.';
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function forParticipantStatus(array $raw, ?string $range): array
    {
        $participants = $raw['byAllTime'] ?? [];
        $total = is_countable($participants) ? count($participants) : 0;
        if ($total === 0) {
            return ['No se registraron participantes' . ($range ? " {$range}" : '') . '.'];
        }

        $lines = ['Participaron ' . $total . ' personas' . ($range ? " {$range}" : '') . '.'];
        if (($rate = Arr::get($raw, 'approvalRate')) !== null) {
            $lines[] = 'La tasa de aprobación fue del ' . $rate . '%.';
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function forGender(array $raw, ?string $range): array
    {
        $total = (int) Arr::get($raw, 'total', 0);
        if ($total === 0) {
            return ['No se registraron participantes' . ($range ? " {$range}" : '') . '.'];
        }

        $rows = $raw['rows'] ?? [];
        if ($rows instanceof \Illuminate\Support\Collection) {
            $rows = $rows->all();
        }

        $rows = collect((array) $rows);
        $lines = ['Se registraron ' . $total . ' participantes' . ($range ? " {$range}" : '') . '.'];
        foreach ($rows as $row) {
            if (($row['share'] ?? null) !== null) {
                $lines[] = 'El ' . $row['share'] . '% correspondió a ' . $row['label'] . '.';
            }
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function forParticipantQuantity(array $raw, ?string $range): array
    {
        $rows = $raw['dateRangeAmountPerCourse'] ?? [];
        if ($rows instanceof \Illuminate\Support\Collection) {
            $rows = $rows->all();
        }
        if (! is_array($rows) || count($rows) === 0) {
            return ['No hay acciones de formación con participantes asignados' . ($range ? " {$range}" : '') . '.'];
        }

        $top = collect($rows)->sortByDesc('count')->first();
        $total = collect($rows)->sum('count');

        return [
            'Se registraron ' . $total . ' asignaciones de participantes' . ($range ? " {$range}" : '') . '.',
            'La acción con más participantes fue "' . ($top['course'] ?? '') . '" con ' . ($top['count'] ?? 0) . '.',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function forParticipantAverage(array $raw, ?string $range): array
    {
        $rows = (array) Arr::get($raw, 'data', []);
        if (empty($rows)) {
            return ['No hay acciones de formación' . ($range ? " {$range}" : '') . '.'];
        }

        $average = round(collect($rows)->avg('total'), 2);

        return ['El promedio de participantes por acción de formación fue de ' . $average . '.'];
    }

    /**
     * @return array<int, string>
     */
    private function forCanceled(array $raw, ?string $range): array
    {
        $total = (int) Arr::get($raw, 'total', 0);
        if ($total === 0) {
            return ['No se registraron acciones de formación canceladas' . ($range ? " {$range}" : '') . '.'];
        }

        return ['Se cancelaron ' . $total . ' acciones de formación' . ($range ? " {$range}" : '') . '.'];
    }

    /**
     * @return array<int, string>
     */
    private function forMostScheduled(array $raw): array
    {
        $rows = $raw['courseData'] ?? [];
        if ($rows instanceof \Illuminate\Support\Collection) {
            $rows = $rows->all();
        }
        $amounts = $raw['amountData'] ?? [];
        if ($amounts instanceof \Illuminate\Support\Collection) {
            $amounts = $amounts->all();
        }

        if (empty($rows)) {
            return ['No hay acciones de formación programadas.'];
        }

        $top = collect($rows)->sortByDesc(fn ($course) => (int) data_get($amounts, data_get($course, 'id'), 0))->first();

        return ['La acción de formación más programada es "' . data_get($top, 'title', '') . '" con ' . (int) data_get($amounts, data_get($top, 'id'), 0) . ' programaciones.'];
    }

    /**
     * @return array<int, string>
     */
    private function forLeastScheduled(array $raw): array
    {
        $rows = $raw['courseData'] ?? [];
        if ($rows instanceof \Illuminate\Support\Collection) {
            $rows = $rows->all();
        }
        $amounts = $raw['amountData'] ?? [];
        if ($amounts instanceof \Illuminate\Support\Collection) {
            $amounts = $amounts->all();
        }

        if (empty($rows)) {
            return ['No hay acciones de formación programadas.'];
        }

        $least = collect($rows)
            ->sortBy(fn ($course) => (int) data_get($amounts, data_get($course, 'id'), 0))
            ->first();

        return ['La acción de formación menos programada es "' . data_get($least, 'title', '') . '" con ' . (int) data_get($amounts, data_get($least, 'id'), 0) . ' programaciones.'];
    }

    /**
     * @return array<int, string>
     */
    private function forNotScheduled(array $raw): array
    {
        $items = $raw['data'] ?? [];
        $total = is_countable($items) ? count($items) : 0;

        return [$total === 0
            ? 'Todas las acciones de formación han sido programadas al menos una vez.'
            : 'Existen ' . $total . ' acciones de formación que nunca se han programado.'];
    }
}
