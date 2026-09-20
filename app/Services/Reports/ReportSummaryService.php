<?php

namespace App\Services\Reports;

use App\Models\CourseStatus;
use App\Models\Participant;
use App\Models\Scheduled;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Headline KPIs for the executive strip.
 *
 * Each metric documents its own formula so the numbers shown on screen can be
 * traced back to a definition (metric governance).
 */
final class ReportSummaryService
{
    public function __construct(
        private readonly DateRangeFactory $ranges = new DateRangeFactory(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $range = $this->ranges->build($request);
        $start = $range->start();
        $end = $range->end();
        $singleDay = $start === $end;

        $applyRange = fn ($query) => $singleDay
            ? $query->whereDate('start_date', $start)
            : $query->whereBetween('start_date', [$start, $end]);

        $byStatus = Scheduled::query()
            ->tap($applyRange)
            ->selectRaw('course_status_id, COUNT(*) as amount')
            ->groupBy('course_status_id')
            ->pluck('amount', 'course_status_id');

        $total = (int) $byStatus->sum();
        $completed = (int) ($byStatus[CourseStatus::CULMINADO] ?? 0);
        $canceled = (int) ($byStatus[CourseStatus::CANCELADO] ?? 0);

        $hours = (int) DB::table('scheduled_course')
            ->join('courses', 'courses.id', '=', 'scheduled_course.course_id')
            ->where('scheduled_course.course_status_id', CourseStatus::CULMINADO)
            ->whereBetween('scheduled_course.start_date', [$start, $end])
            ->sum('courses.duration');

        $participants = Participant::query()
            ->whereHas('scheduled', function ($query) use ($start, $end, $singleDay) {
                $singleDay
                    ? $query->whereDate('start_date', $start)
                    : $query->whereBetween('start_date', [$start, $end]);
            })
            ->count();

        $approved = Participant::query()
            ->where('participant_status_id', Participant::APROBADO)
            ->whereHas('scheduled', function ($query) use ($start, $end, $singleDay) {
                $singleDay
                    ? $query->whereDate('start_date', $start)
                    : $query->whereBetween('start_date', [$start, $end]);
            })
            ->count();

        $previous = $this->previousPeriodTotal($start, $end);
        $delta = $previous > 0 ? (int) round(($total - $previous) / $previous * 100) : null;

        return [
            'dateRange' => ['startDate' => $start, 'endDate' => $end],
            'kpis' => [
                $this->kpi('actions', 'Acciones de formación', $total, 'acciones', 'Conteo de acciones con fecha de inicio en el período.', $delta),
                $this->kpi('completion', 'Tasa de culminación', $total > 0 ? round(100 * $completed / $total, 1) : 0, '%', 'Acciones culminadas / total de acciones del período.'),
                $this->kpi('cancellation', 'Tasa de cancelación', $total > 0 ? round(100 * $canceled / $total, 1) : 0, '%', 'Acciones canceladas / total de acciones del período.'),
                $this->kpi('hours', 'Horas impartidas', $hours, 'horas', 'Suma de duración de acciones culminadas en el período.'),
                $this->kpi('participants', 'Participantes', $participants, 'personas', 'Participantes asignados a acciones iniciadas en el período.'),
                $this->kpi('approval', 'Tasa de aprobación', $participants > 0 ? round(100 * $approved / $participants, 1) : 0, '%', 'Participantes aprobados / participantes del período.'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function kpi(string $key, string $label, int|float $value, string $unit, string $formula, ?int $delta = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'unit' => $unit,
            'formula' => $formula,
            'delta' => $delta,
            'display' => $unit === '%' ? $value . '%' : number_format((float) $value, 0, ',', '.'),
        ];
    }

    private function previousPeriodTotal(string $start, string $end): int
    {
        $currentStart = Carbon::parse($start);
        $currentEnd = Carbon::parse($end);
        $periodDays = $currentStart->diffInDays($currentEnd) + 1;
        $prevEnd = $currentStart->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($periodDays - 1);

        return Scheduled::whereBetween('start_date', [$prevStart->toDateString(), $prevEnd->toDateString()])->count();
    }
}
