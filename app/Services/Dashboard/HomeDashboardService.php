<?php

namespace App\Services\Dashboard;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\CourseStatus;
use App\Models\Facilitator;
use App\Models\Participant;
use App\Models\Person;
use App\Models\Scheduled;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds the payload for the authenticated landing page (/home).
 *
 * "Active" is calendar based: a facilitator or participant counts as active
 * when they are tied to a scheduled course that has at least one session
 * between today and {@see self::ACTIVITY_WINDOW_DAYS} days from now. All the
 * aggregations run in SQL and every list eager-loads its relations to avoid
 * N+1 queries.
 */
final class HomeDashboardService
{
    public const ACTIVITY_WINDOW_DAYS = 30;
    public const SESSION_FOCUS_DAYS = 7;
    public const UPCOMING_LIMIT = 6;
    public const UPCOMING_SESSIONS_LIMIT = 7;
    public const ACTIVE_PREVIEW_LIMIT = 4;

    /**
     * Full organisation dashboard for Administrador / Tec. Educativa / Programador.
     *
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $byStatus = Scheduled::query()
            ->whereYear('start_date', CarbonImmutable::today()->year)
            ->selectRaw('course_status_id, COUNT(*) as amount')
            ->groupBy('course_status_id')
            ->pluck('amount', 'course_status_id');

        $scheduledTotal = (int) $byStatus->sum();
        $activeScheduledCount = (int) ($byStatus[CourseStatus::POR_DICTAR] ?? 0)
            + (int) ($byStatus[CourseStatus::EN_CURSO] ?? 0);

        return [
            'mode' => 'overview',
            'windowDays' => self::ACTIVITY_WINDOW_DAYS,
            'focusWindowDays' => self::SESSION_FOCUS_DAYS,
            'kpis' => [
                'courses' => Course::query()->count(),
                'scheduled' => $scheduledTotal,
                'activeScheduled' => $activeScheduledCount,
                'activeFacilitators' => $this->activeFacilitatorsCount(),
                'byStatus' => $this->statusBreakdown($byStatus, $scheduledTotal),
            ],
            'upcomingScheduled' => $this->upcomingScheduled(),
            'upcomingSessions' => $this->upcomingSessions(),
            'todaySessionsCount' => $this->todaySessionsCount(),
            'activeFacilitators' => $this->activeFacilitators(),
        ];
    }

    /**
     * Personalised dashboard for facilitadores: their own programmes for the
     * current year, summarised by status and rendered in the home calendar.
     *
     * @return array<string, mixed>
     */
    public function forFacilitator(User $user): array
    {
        $year = $this->currentYear();
        $facilitator = $user->person?->facilitator;

        $scheduled = $facilitator === null
            ? collect()
            : Scheduled::query()
                ->where('facilitator_id', $facilitator->id)
                ->whereYear('start_date', $year)
                ->where('course_status_id', '!=', CourseStatus::CANCELADO)
                ->with($this->calendarRelations($year))
                ->orderBy('start_date')
                ->get();

        return [
            'mode' => 'facilitator',
            'year' => $year,
            'facilitator' => $facilitator,
            'kpis' => $this->statusKpis($scheduled),
            'calendarEvents' => $this->calendarEvents($scheduled),
        ];
    }

    /**
     * Personalised dashboard for participantes: their own enrolments for the
     * current year, summarised by status and rendered in the home calendar.
     *
     * @return array<string, mixed>
     */
    public function forParticipant(User $user): array
    {
        $year = $this->currentYear();

        $scheduled = Scheduled::query()
            ->whereYear('start_date', $year)
            ->where('course_status_id', '!=', CourseStatus::CANCELADO)
            ->whereHas('participants', function (Builder $query) use ($user) {
                $query->where('person_id', $user->person_id)
                    ->where('participant_status_id', '!=', Participant::CANCELADO);
            })
            ->with($this->calendarRelations($year))
            ->orderBy('start_date')
            ->get();

        return [
            'mode' => 'participant',
            'year' => $year,
            'kpis' => $this->statusKpis($scheduled),
            'calendarEvents' => $this->calendarEvents($scheduled),
        ];
    }

    private function currentYear(): int
    {
        return CarbonImmutable::today()->year;
    }

    /**
     * Relations needed to count current-year programmes and render their
     * sessions in the home calendar.
     *
     * @return array<string, mixed>
     */
    private function calendarRelations(int $year): array
    {
        return [
            'course',
            'facilitator.person',
            'courseStatus',
            'sessions' => fn (Builder $query) => $query
                ->whereYear('session_date', $year)
                ->where('status', '!=', 'cancelled')
                ->with(['location' => fn ($location) => $location->withTrashed()])
                ->orderBy('session_date')
                ->orderBy('start_time'),
        ];
    }

    /**
     * @param  Collection<int, Scheduled>  $scheduled
     * @return array{programadas: int, porDictar: int, culminadas: int}
     */
    private function statusKpis(Collection $scheduled): array
    {
        return [
            'programadas' => $scheduled->count(),
            'porDictar' => $scheduled->where('course_status_id', CourseStatus::POR_DICTAR)->count(),
            'culminadas' => $scheduled->where('course_status_id', CourseStatus::CULMINADO)->count(),
        ];
    }

    /**
     * FullCalendar events for every session of the given programmes. Each
     * event links back to the scheduled course detail view.
     *
     * @param  Collection<int, Scheduled>  $scheduled
     * @return array<int, array<string, mixed>>
     */
    private function calendarEvents(Collection $scheduled): array
    {
        $events = [];

        foreach ($scheduled as $item) {
            $courseTitle = $item->course?->title ?? '';
            $facilitatorName = $this->personName($item->facilitator?->person);
            $url = url('u/af_programadas/'.$item->id.'/ver');
            $palette = $this->calendarPalette($item->id);

            foreach ($item->sessions as $session) {
                $locationName = $session->location?->name ?? 'Sin aula';
                $schedule = substr((string) $session->start_time, 0, 5).'–'.substr((string) $session->end_time, 0, 5);
                $dateLabel = CarbonImmutable::parse($session->session_date)
                    ->locale('es')
                    ->translatedFormat('d \d\e F');

                $events[] = [
                    'id' => 'session-'.$session->id,
                    'scheduledId' => $item->id,
                    'title' => $courseTitle,
                    'start' => $session->session_date.'T'.$session->start_time,
                    'end' => $session->session_date.'T'.$session->end_time,
                    'url' => $url,
                    'backgroundColor' => $palette['backgroundColor'],
                    'borderColor' => $palette['borderColor'],
                    'textColor' => $palette['textColor'],
                    'locationName' => $locationName,
                    'facilitatorName' => $facilitatorName,
                    'tooltip' => implode(' · ', array_filter([
                        $courseTitle,
                        $facilitatorName,
                        $locationName,
                        $dateLabel.' '.$schedule,
                    ])),
                ];
            }
        }

        return $events;
    }

    private function personName(?Person $person): string
    {
        if ($person === null) {
            return '';
        }

        return trim(($person->name ?? '').' '.($person->last_name ?? ''));
    }

    /**
     * Sessions of one scheduled action share a single light card colour, so the
     * calendar only changes hue when the action itself changes, matching the
     * weekly schedule blocks on the scheduled course detail view.
     *
     * @return array{backgroundColor: string, borderColor: string, textColor: string}
     */
    private function calendarPalette(int $scheduledId): array
    {
        $hue = (int) round(fmod($scheduledId * 137.508, 360));

        return [
            'backgroundColor' => "hsl({$hue}, 60%, 95%)",
            'borderColor' => "hsl({$hue}, 60%, 40%)",
            'textColor' => '#2c3e50',
        ];
    }

    private function activeFacilitatorsCount(): int
    {
        return Scheduled::query()
            ->where('course_status_id', '!=', CourseStatus::CANCELADO)
            ->whereHas('sessions', fn (Builder $query) => $this->withinWindow($query))
            ->distinct()
            ->count('facilitator_id');
    }

    /**
     * Programmes that have not finished yet, nearest start first. Cancelled
     * programmes are excluded so the panel never advertises a dead course.
     *
     * @return Collection<int, Scheduled>
     */
    private function upcomingScheduled(): Collection
    {
        return Scheduled::query()
            ->with([
                'course.capacity',
                'facilitator.person',
                'courseStatus',
            ])
            ->withCount('participants')
            ->whereDate('end_date', '>=', $this->windowStart())
            ->where('course_status_id', '!=', CourseStatus::CANCELADO)
            ->orderBy('start_date')
            ->limit(self::UPCOMING_LIMIT)
            ->get();
    }

    /**
     * Concrete class sessions happening today or within the immediate focus
     * window (next 7 days), ordered chronologically.
     *
     * @return Collection<int, CourseSession>
     */
    private function upcomingSessions(): Collection
    {
        return CourseSession::query()
            ->with(['scheduled.course', 'scheduled.facilitator.person', 'location' => fn ($query) => $query->withTrashed()])
            ->whereBetween('session_date', [$this->windowStart(), $this->focusWindowEnd()])
            ->where('status', '!=', 'cancelled')
            ->whereHas('scheduled', fn (Builder $query) => $this->activeProgram($query))
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->limit(self::UPCOMING_SESSIONS_LIMIT)
            ->get();
    }

    private function todaySessionsCount(): int
    {
        return CourseSession::query()
            ->whereDate('session_date', $this->windowStart())
            ->where('status', '!=', 'cancelled')
            ->whereHas('scheduled', fn (Builder $query) => $this->activeProgram($query))
            ->count();
    }

    /**
     * @return Collection<int, Facilitator>
     */
    private function activeFacilitators(): Collection
    {
        return Facilitator::query()
            ->with('person')
            ->whereHas('sessions', fn (Builder $query) => $this->withinActiveProgram($query))
            ->withCount([
                'sessions as upcoming_sessions_count' => fn (Builder $query) => $this->withinActiveProgram($query),
            ])
            ->orderByDesc('upcoming_sessions_count')
            ->limit(self::ACTIVE_PREVIEW_LIMIT)
            ->get();
    }

    /**
     * @param  Collection<int|string, int|string>  $byStatus
     * @return array<int, array{id: int, label: string, amount: int, badge: string, percent: int}>
     */
    private function statusBreakdown(Collection $byStatus, int $total): array
    {
        $breakdown = [];

        foreach (CourseStatus::$cpstatus as $id => $label) {
            $amount = (int) ($byStatus[$id] ?? 0);
            $breakdown[] = [
                'id' => $id,
                'label' => $label,
                'amount' => $amount,
                'badge' => $this->statusBadge($id),
                'percent' => $total > 0 ? (int) round(($amount / $total) * 100) : 0,
            ];
        }

        return $breakdown;
    }

    private function statusBadge(int $statusId): string
    {
        return match ($statusId) {
            CourseStatus::POR_DICTAR => 'warning',
            CourseStatus::EN_CURSO => 'info',
            CourseStatus::CULMINADO => 'success',
            CourseStatus::CANCELADO => 'danger',
            default => 'default',
        };
    }

    private function withinWindow(Builder $query): Builder
    {
        return $query
            ->whereBetween('session_date', [$this->windowStart(), $this->windowEnd()])
            ->where('status', '!=', 'cancelled');
    }

    /**
     * A course session that falls inside the activity window and belongs to a
     * scheduled course that has not been cancelled.
     */
    private function withinActiveProgram(Builder $query): Builder
    {
        return $this->withinWindow($query)
            ->whereHas('scheduled', fn (Builder $scheduled) => $this->activeProgram($scheduled));
    }

    private function activeProgram(Builder $query): Builder
    {
        return $query->where('course_status_id', '!=', CourseStatus::CANCELADO);
    }

    private function windowStart(): string
    {
        return CarbonImmutable::today()->toDateString();
    }

    private function windowEnd(): string
    {
        return CarbonImmutable::today()->addDays(self::ACTIVITY_WINDOW_DAYS)->toDateString();
    }

    private function focusWindowEnd(): string
    {
        return CarbonImmutable::today()->addDays(self::SESSION_FOCUS_DAYS)->toDateString();
    }
}
