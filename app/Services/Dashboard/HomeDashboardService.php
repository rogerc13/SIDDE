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
    public const UPCOMING_SESSIONS_LIMIT = 6;
    public const ACTIVE_LIMIT = 6;

    /**
     * Rows a personalised panel renders before the "Ver todas" link takes
     * over; keeps the landing page scannable when a user has many rows.
     */
    public const LIST_LIMIT = 10;

    /**
     * Full organisation dashboard for Administrador / Tec. Educativa / Programador.
     *
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $byStatus = Scheduled::query()
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
                'activeParticipants' => $this->activeParticipantsCount(),
                'byStatus' => $this->statusBreakdown($byStatus, $scheduledTotal),
            ],
            'upcomingScheduled' => $this->upcomingScheduled(),
            'upcomingSessions' => $this->upcomingSessions(),
            'todaySessionsCount' => $this->todaySessionsCount(),
            'activeFacilitators' => $this->activeFacilitators(),
            'activeParticipants' => $this->activeParticipants(),
        ];
    }

    /**
     * Personalised dashboard for facilitadores: their own upcoming programmes.
     *
     * The window-bounded query is cheap enough to aggregate over the whole
     * set; only the rendered list is capped at {@see self::LIST_LIMIT}.
     *
     * @return array<string, mixed>
     */
    public function forFacilitator(User $user): array
    {
        $facilitator = $user->person?->facilitator;

        $scheduled = $facilitator === null
            ? collect()
            : Scheduled::query()
                ->where('facilitator_id', $facilitator->id)
                ->with([
                    'course.capacity',
                    'course.modality',
                    'courseStatus',
                    'sessions' => fn (Builder $query) => $this->withinWindow($query)
                        ->with(['location' => fn ($location) => $location->withTrashed()])
                        ->orderBy('session_date')
                        ->orderBy('start_time'),
                ])
                ->withCount('participants')
                ->whereHas('sessions', fn (Builder $query) => $this->withinWindow($query))
                ->where('course_status_id', '!=', CourseStatus::CANCELADO)
                ->orderBy('start_date')
                ->get();

        $seats = (int) $scheduled->sum(fn (Scheduled $item) => (int) ($item->course->capacity->first()->max ?? 0));
        $registered = (int) $scheduled->sum('participants_count');
        $upcomingSessionsCount = (int) $scheduled->sum(fn (Scheduled $item) => $item->sessions->count());

        return [
            'mode' => 'facilitator',
            'windowDays' => self::ACTIVITY_WINDOW_DAYS,
            'facilitator' => $facilitator,
            'scheduled' => $scheduled->take(self::LIST_LIMIT)->values(),
            'scheduledTotal' => $scheduled->count(),
            'participantsTotal' => $registered,
            'seatsAvailable' => max(0, $seats - $registered),
            'upcomingSessionsCount' => $upcomingSessionsCount,
        ];
    }

    /**
     * Personalised dashboard for participantes: their own enrolments.
     *
     * @return array<string, mixed>
     */
    public function forParticipant(User $user): array
    {
        $participants = Participant::query()
            ->where('person_id', $user->person_id)
            ->where('participant_status_id', '!=', Participant::CANCELADO)
            ->with([
                'participantStatus',
                'scheduled.course.modality',
                'scheduled.courseStatus',
                'scheduled.facilitator.person',
                'scheduled.sessions' => fn (Builder $query) => $this->withinWindow($query)
                    ->with(['location' => fn ($location) => $location->withTrashed()])
                    ->orderBy('session_date')
                    ->orderBy('start_time'),
            ])
            ->orderByDesc('id')
            ->get();

        $activeEnrolmentsCount = $participants
            ->filter(fn (Participant $item) => $item->isEnCurso() || $item->isPorIniciar())
            ->count();

        $approvedCount = $participants
            ->filter(fn (Participant $item) => $item->isAprobado())
            ->count();

        $upcomingSessionsCount = (int) $participants->sum(function (Participant $item): int {
            if ($item->scheduled === null || $item->scheduled->isCancelado()) {
                return 0;
            }

            return $item->scheduled->sessions->count();
        });

        return [
            'mode' => 'participant',
            'windowDays' => self::ACTIVITY_WINDOW_DAYS,
            'participants' => $participants->take(self::LIST_LIMIT)->values(),
            'participantsTotal' => $participants->count(),
            'activeEnrolmentsCount' => $activeEnrolmentsCount,
            'approvedCount' => $approvedCount,
            'upcomingSessionsCount' => $upcomingSessionsCount,
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

    private function activeParticipantsCount(): int
    {
        return Participant::query()
            ->where('participant_status_id', '!=', Participant::CANCELADO)
            ->whereHas('scheduled', fn (Builder $query) => $this->activeProgram($query))
            ->whereHas('scheduled.sessions', fn (Builder $query) => $this->withinWindow($query))
            ->distinct()
            ->count('person_id');
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
                'course.modality',
                'facilitator.person',
                'courseStatus',
                'sessions' => fn (Builder $query) => $query
                    ->whereDate('session_date', '>=', $this->windowStart())
                    ->where('status', '!=', 'cancelled')
                    ->with(['location' => fn ($location) => $location->withTrashed()])
                    ->orderBy('session_date')
                    ->orderBy('start_time'),
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
            ->limit(self::ACTIVE_LIMIT)
            ->get();
    }

    /**
     * @return Collection<int, Person>
     */
    private function activeParticipants(): Collection
    {
        $personIds = Participant::query()
            ->where('participant_status_id', '!=', Participant::CANCELADO)
            ->whereHas('scheduled', fn (Builder $query) => $this->activeProgram($query))
            ->whereHas('scheduled.sessions', fn (Builder $query) => $this->withinWindow($query))
            ->select('person_id')
            ->distinct()
            ->orderBy('person_id')
            ->limit(self::ACTIVE_LIMIT)
            ->pluck('person_id');

        if ($personIds->isEmpty()) {
            return collect();
        }

        return Person::query()
            ->whereIn('id', $personIds)
            ->with([
                'participant' => function ($query) {
                    $query->where('participant_status_id', '!=', Participant::CANCELADO)
                        ->whereHas('scheduled', fn (Builder $outer) => $this->activeProgram($outer))
                        ->whereHas('scheduled.sessions', fn (Builder $inner) => $this->withinWindow($inner))
                        ->with('scheduled.course', 'participantStatus')
                        ->orderByDesc('id');
                },
            ])
            ->orderBy('name')
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
