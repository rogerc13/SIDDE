<?php

namespace App\Services\Reports;

use Carbon\Carbon;

/**
 * Immutable description of the date range and its buckets.
 *
 * Buckets use half-open semantics: a run starting exactly on the next bucket's
 * boundary belongs to that next bucket only, so runs are never double counted
 * across adjacent buckets. The final bucket absorbs everything up to the end of
 * the chosen range.
 */
final class DateBuckets
{
    /**
     * @param  array<int, string>  $dates  Bucket boundary dates (Y-m-d).
     */
    public function __construct(
        public readonly string $currentDate,
        public readonly array $dates,
        public readonly int $numberOfSteps,
        public readonly bool $day,
    ) {
    }

    public function isDaily(): bool
    {
        return $this->day;
    }

    /**
     * @return array<int, array{start: string, end: string, isLast: bool}>
     */
    public function buckets(): array
    {
        $last = count($this->dates) - 1;
        $buckets = [];

        foreach ($this->dates as $k => $start) {
            $isLast = $k === $last;

            $buckets[] = [
                'start' => $start,
                'end' => $this->day ? $start : ($this->dates[min($k + 1, $last)] ?? $start),
                'isLast' => $isLast,
            ];
        }

        return $buckets;
    }

    /**
     * @return array<int, string>
     */
    public function labels(): array
    {
        return $this->dates;
    }

    public function start(): string
    {
        return $this->dates[0] ?? $this->currentDate;
    }

    public function end(): string
    {
        $count = count($this->dates);

        return $count > 0 ? $this->dates[$count - 1] : $this->currentDate;
    }

    /**
     * Map a concrete date to its bucket index, honoring the half-open
     * boundary rules. Returns null when the date falls outside the range.
     */
    public function bucketIndexFor(string $date): ?int
    {
        $date = Carbon::parse($date)->toDateString();

        foreach ($this->buckets() as $index => $bucket) {
            $afterStart = $date >= $bucket['start'];
            $beforeEnd = $bucket['isLast']
                ? $date <= Carbon::parse($this->currentDate)->toDateString()
                : $date < $bucket['end'];

            if ($afterStart && $beforeEnd) {
                return $index;
            }
        }

        return null;
    }
}
