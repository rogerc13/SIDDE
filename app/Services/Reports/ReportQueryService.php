<?php

namespace App\Services\Reports;

use App\Enums\ReportType;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseStatus;
use App\Models\Participant;
use App\Models\ParticipantStatus;
use App\Models\Scheduled;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Produces the normalized {@see ReportResult} for every report type.
 *
 * Aggregation happens in SQL (grouped by the date column) and bucket
 * attribution is delegated to {@see DateBuckets}, replacing the per-report PHP
 * filtering the old controller relied on.
 */
final class ReportQueryService
{
    public function __construct(
        private readonly DateRangeFactory $ranges = new DateRangeFactory(),
        private readonly ChartPalette $palette = new ChartPalette(),
    ) {
    }

    public function run(ReportType $type, Request $request): ReportResult
    {
        $range = $type->isDateRanged() ? $this->ranges->build($request) : null;

        $raw = match ($type) {
            ReportType::Date => $this->byDate($range),
            ReportType::Category => $this->byCategory($range),
            ReportType::Status => $this->byStatus($range),
            ReportType::Duration => $this->byDuration($range),
            ReportType::Canceled => $this->byCanceled($range),
            ReportType::ParticipantByQuantity => $this->participantByQuantity($range),
            ReportType::ParticipantByStatus => $this->participantByStatus($request, $range),
            ReportType::ParticipantAverage => $this->participantAverage($request, $range),
            ReportType::Gender => $this->participantByGender($range),
            ReportType::MostScheduled => $this->mostScheduled(),
            ReportType::LeastScheduled => $this->leastScheduled(),
            ReportType::NotScheduled => $this->notScheduled(),
        };

        $result = (new ReportResult($type))->withRaw($raw);

        if ($range !== null) {
            $result->withMeta([
                'startDate' => Carbon::parse($range->start())->format('Y-m-d'),
                'endDate' => Carbon::parse($range->end())->format('Y-m-d'),
                'step' => $range->isDaily() ? '1 day' : null,
            ]);
        }

        return $result;
    }

    // ---------------------------------------------------------------- date

    /**
     * @return array<string, mixed>
     */
    private function byDate(?DateBuckets $range): array
    {
        $labels = $range->labels();
        $counts = Scheduled::query()
            ->whereBetween('start_date', [$range->start(), $range->end()])
            ->selectRaw('start_date, COUNT(*) as amount')
            ->groupBy('start_date')
            ->pluck('amount', 'start_date');

        $y = array_fill(0, count($labels), 0);
        foreach ($counts as $date => $amount) {
            $index = $range->bucketIndexFor((string) $date);
            if ($index !== null) {
                $y[$index] += (int) $amount;
            }
        }

        return [
            'x' => $labels,
            'y' => $y,
            'total' => array_sum($y),
            'yData' => [],
            'start_date' => $range->start(),
            'end_date' => $range->end(),
        ];
    }

    // ------------------------------------------------------------ category

    /**
     * @return array<string, mixed>
     */
    private function byCategory(?DateBuckets $range): array
    {
        $labels = $range->labels();
        $buckets = $range->buckets();

        $allScheduled = Scheduled::with('course', 'course.category')
            ->whereBetween('start_date', [$range->start(), $range->end()])
            ->get();

        $categoryCounts = Scheduled::query()
            ->join('courses', 'courses.id', '=', 'scheduled_course.course_id')
            ->whereBetween('scheduled_course.start_date', [$range->start(), $range->end()])
            ->selectRaw('courses.category_id as category_id, scheduled_course.start_date as start_date, COUNT(*) as amount')
            ->groupBy('courses.category_id', 'scheduled_course.start_date')
            ->get();

        $matrix = [];
        foreach ($categoryCounts as $row) {
            $index = $range->bucketIndexFor((string) $row->start_date);
            if ($index === null) {
                continue;
            }
            $matrix[(int) $row->category_id][$index] = ($matrix[(int) $row->category_id][$index] ?? 0) + (int) $row->amount;
        }

        $byCategory = $allScheduled->groupBy(fn ($s) => $s->course->category_id);

        $categories = [];
        $yAxis = [];
        $graphData = [];
        $courseData = [];

        foreach (Category::all() as $category) {
            $catScheduled = $byCategory->get($category->id, collect());
            $categories[] = $category->name;

            foreach ($buckets as $k => $bucket) {
                $yAxis[] = [
                    'category' => $category->name,
                    'y' => $matrix[$category->id][$k] ?? 0,
                    'x' => Carbon::parse($bucket['start'])->format('Y-m-d'),
                ];
            }

            $scheduledDates = $catScheduled->pluck('start_date');
            $graphData[] = [
                'category' => $category->name,
                'y' => $scheduledDates,
                'x' => $scheduledDates,
            ];

            $courseData[] = [
                'categoryName' => $category->name,
                'courseData' => $catScheduled->values(),
                'amount' => $catScheduled->count(),
            ];
        }

        return [
            'x' => $labels,
            'graphData' => $graphData,
            'categories' => $categories,
            'courseData' => $courseData,
            'dateRange' => ['startDate' => $range->start(), 'endDate' => $range->end()],
            'y' => $yAxis,
        ];
    }

    // -------------------------------------------------------------- status

    /**
     * @return array<string, mixed>
     */
    private function byStatus(?DateBuckets $range): array
    {
        $byAllTime = Scheduled::with('courseStatus')
            ->select('course_status_id')
            ->selectRaw('COUNT(*) as amount')
            ->groupBy('course_status_id')
            ->orderByDesc('amount')
            ->get();

        $labels = $range->labels();
        $buckets = $range->buckets();

        $allScheduled = Scheduled::with('course', 'courseStatus')
            ->whereBetween('start_date', [$range->start(), $range->end()])
            ->get();

        $statusCounts = Scheduled::query()
            ->whereBetween('start_date', [$range->start(), $range->end()])
            ->selectRaw('course_status_id, start_date, COUNT(*) as amount')
            ->groupBy('course_status_id', 'start_date')
            ->get();

        $matrix = [];
        foreach ($statusCounts as $row) {
            $index = $range->bucketIndexFor((string) $row->start_date);
            if ($index === null) {
                continue;
            }
            $matrix[(int) $row->course_status_id][$index] = ($matrix[(int) $row->course_status_id][$index] ?? 0) + (int) $row->amount;
        }

        $byStatus = $allScheduled->groupBy('course_status_id');

        $statusNames = [];
        $rows = [];
        $courseData = [];

        foreach (CourseStatus::all() as $status) {
            $statusScheduled = $byStatus->get($status->id, collect());
            $statusNames[] = $status->name;

            foreach ($buckets as $k => $bucket) {
                $rows[] = [
                    'status' => $status->name,
                    'x' => Carbon::parse($bucket['start'])->format('Y-m-d'),
                    'y' => $matrix[$status->id][$k] ?? 0,
                ];
            }

            $courseData[] = [
                'statusName' => $status->name,
                'courseData' => $statusScheduled->values(),
                'amount' => $statusScheduled->count(),
            ];
        }

        $totalInRange = collect($courseData)->sum('amount');
        $completed = collect($courseData)->where('statusName', 'Culminado')->sum('amount');
        $canceled = collect($courseData)->where('statusName', 'Cancelado')->sum('amount');

        return [
            'statuses' => $statusNames,
            'x' => $labels,
            'y' => $rows,
            'byAllTime' => $byAllTime,
            'courseData' => $courseData,
            'dateRange' => ['startDate' => $range->start(), 'endDate' => $range->end()],
            'rates' => [
                'total' => $totalInRange,
                'completionRate' => $totalInRange > 0 ? round(100 * $completed / $totalInRange, 1) : 0,
                'cancellationRate' => $totalInRange > 0 ? round(100 * $canceled / $totalInRange, 1) : 0,
            ],
        ];
    }

    // ------------------------------------------------------------ duration

    /**
     * @return array<string, mixed>
     */
    private function byDuration(?DateBuckets $range): array
    {
        $byAllTime = DB::table('scheduled_course')
            ->join('courses', 'courses.id', '=', 'scheduled_course.course_id')
            ->sum('courses.duration');

        $finishedTotal = DB::table('scheduled_course')
            ->join('courses', 'courses.id', '=', 'scheduled_course.course_id')
            ->where('scheduled_course.course_status_id', CourseStatus::CULMINADO)
            ->sum('courses.duration');

        $mostDuration = Course::with('scheduled')->whereHas('scheduled')->orderBy('duration', 'desc')->get();

        $scheduledCourses = Scheduled::where('course_status_id', CourseStatus::CULMINADO)
            ->whereBetween('start_date', [$range->start(), $range->end()])
            ->with('course')
            ->get();

        $currentStart = Carbon::parse($range->start());
        $currentEnd = Carbon::parse($range->end());
        $periodDays = $currentStart->diffInDays($currentEnd) + 1;
        $prevEnd = $currentStart->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($periodDays - 1);

        $previousCount = Scheduled::where('course_status_id', CourseStatus::CULMINADO)
            ->whereBetween('start_date', [$prevStart->toDateString(), $prevEnd->toDateString()])
            ->count();

        $previousHours = (int) DB::table('scheduled_course')
            ->join('courses', 'courses.id', '=', 'scheduled_course.course_id')
            ->where('scheduled_course.course_status_id', CourseStatus::CULMINADO)
            ->whereBetween('scheduled_course.start_date', [$prevStart->toDateString(), $prevEnd->toDateString()])
            ->sum('courses.duration');

        $currentCount = $scheduledCourses->count();
        $currentHours = (int) $scheduledCourses->pluck('course.duration')->sum();

        $list = $scheduledCourses->map(function ($scheduled) {
            $durationDays = null;
            if ($scheduled->start_date && $scheduled->end_date) {
                $durationDays = Carbon::parse($scheduled->start_date)->diffInDays(Carbon::parse($scheduled->end_date)) + 1;
            }
            $course = $scheduled->course;

            return [
                'scheduled_id' => $scheduled->id,
                'course_id' => $course ? $course->id : '',
                'course_title' => $course ? $course->title : 'Sin título',
                'duration' => $course ? $course->duration : 0,
                'start_date' => $scheduled->start_date ?? '',
                'end_date' => $scheduled->end_date ?? '',
                'duration_days' => $durationDays ?? '',
            ];
        })->values();

        return [
            'byAllTime' => $byAllTime,
            'finishedTotal' => $finishedTotal,
            'mostDuration' => $mostDuration,
            'byDateRange' => $list,
            'finishedByDateRange' => $currentHours,
            'dateRange' => ['startDate' => $range->start(), 'endDate' => $range->end()],
            'previousCount' => $previousCount,
            'previousHours' => $previousHours,
            'countDelta' => $previousCount > 0 ? (int) round(($currentCount - $previousCount) / $previousCount * 100) : null,
            'hoursDelta' => $previousHours > 0 ? (int) round(($currentHours - $previousHours) / $previousHours * 100) : null,
        ];
    }

    // ------------------------------------------------------------ canceled

    /**
     * @return array<string, mixed>
     */
    private function byCanceled(?DateBuckets $range): array
    {
        $data = Scheduled::withTrashed()
            ->where('course_status_id', CourseStatus::CANCELADO)
            ->whereBetween('start_date', [$range->start(), $range->end()])
            ->with('course.category')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn ($scheduled) => [
                'id' => $scheduled->id,
                'code' => $scheduled->course?->code ?? '',
                'title' => $scheduled->course?->title ?? 'Sin título',
                'category' => $scheduled->course?->category?->name ?? '',
                'start_date' => $scheduled->start_date,
                'end_date' => $scheduled->end_date,
                'deleted_at' => $scheduled->deleted_at,
            ])
            ->values();

        return [
            'dateRange' => ['startDate' => $range->start(), 'endDate' => $range->end()],
            'total' => count($data),
            'data' => $data,
        ];
    }

    // ------------------------------------------- participant by quantity

    /**
     * @return array<string, mixed>
     */
    private function participantByQuantity(?DateBuckets $range): array
    {
        $amountAllTime = Participant::selectRaw('COUNT(*) as amount')->get();
        $amountAllTimePerCourse = Participant::select('scheduled_id')
            ->selectRaw('COUNT(*) AS count')
            ->groupBy('scheduled_id')
            ->orderByDesc('count')
            ->get();

        $rows = Scheduled::query()
            ->leftJoin('participants', 'participants.scheduled_id', '=', 'scheduled_course.id')
            ->leftJoin('capacities', 'capacities.course_id', '=', 'scheduled_course.course_id')
            ->whereBetween('start_date', [$range->start(), $range->end()])
            ->whereNull('participants.deleted_at')
            ->groupBy('scheduled_course.id')
            ->select(
                'scheduled_course.id as scheduled_id',
                'scheduled_course.course_id',
                'scheduled_course.start_date',
                'scheduled_course.end_date',
            )
            ->selectRaw('COUNT(participants.id) AS participant_count')
            ->selectRaw('MAX(capacities.max) AS capacity_max')
            ->get();

        $courseTitles = Course::whereIn('id', $rows->pluck('course_id')->unique())->pluck('title', 'id');

        $buckets = $range->buckets();
        $amountPerCourse = [];

        foreach ($buckets as $k => $bucket) {
            $bucketRows = $rows->filter(function ($row) use ($range, $k, $bucket) {
                $index = $range->bucketIndexFor(Carbon::parse($row->start_date)->toDateString());

                return $index === $k;
            });

            foreach ($bucketRows as $row) {
                $amountPerCourse[] = [
                    'date' => Carbon::parse($bucket['start'])->format('Y-m-d'),
                    'scheduled_id' => $row->scheduled_id,
                    'course' => $courseTitles[$row->course_id] ?? 'Sin título',
                    'start_date' => $row->start_date,
                    'end_date' => $row->end_date,
                    'count' => (int) $row->participant_count,
                    'capacity' => $row->capacity_max ? (int) $row->capacity_max : null,
                    'fillRate' => $row->capacity_max ? (int) round(100 * (int) $row->participant_count / $row->capacity_max) : null,
                ];
            }
        }

        $sorted = count($amountPerCourse) > 0
            ? collect($amountPerCourse)->sortByDesc('count')->values()
            : 0;

        return [
            'amountAllTime' => $amountAllTime,
            'amountAllTimePerCourse' => $amountAllTimePerCourse,
            'dateRangeAmountPerCourse' => $sorted,
        ];
    }

    // ------------------------------------------- participant by status

    /**
     * @return array<string, mixed>
     */
    private function participantByStatus(Request $request, ?DateBuckets $range): array
    {
        $request->validate([
            'status_id' => 'nullable|integer|exists:participant_status,id',
        ], [
            'status_id.exists' => 'El estatus de participante seleccionado no existe.',
        ]);

        $statusFilter = $request->input('status_id');
        $dateRange = ['startDate' => $range->start(), 'endDate' => $range->end()];

        $byAllTime = Participant::with([
            'person',
            'participantStatus',
            'scheduled' => fn ($query) => $query->withTrashed()->with('course'),
        ])
            ->when($statusFilter, fn ($query) => $query->where('participant_status_id', (int) $statusFilter))
            ->whereHas('scheduled', function ($query) use ($dateRange) {
                $query->when($dateRange['startDate'] === $dateRange['endDate'], function ($q) use ($dateRange) {
                    return $q->whereDate('start_date', $dateRange['startDate']);
                }, function ($q) use ($dateRange) {
                    return $q->whereBetween('start_date', [$dateRange['startDate'], $dateRange['endDate']]);
                });
            })
            ->get();

        $buckets = $range->buckets();
        $byStatusGrouped = $byAllTime->groupBy('participant_status_id');
        $allStatus = ParticipantStatus::all()->keyBy('id');

        $allStatusByDateRange = [];
        $labels = [];

        foreach ($allStatus as $statusId => $status) {
            $statusParticipants = $byStatusGrouped->get($statusId, collect());

            foreach ($buckets as $k => $bucket) {
                $count = $statusParticipants->filter(function ($p) use ($range, $k) {
                    if (! $p->scheduled) {
                        return false;
                    }

                    return $range->bucketIndexFor(Carbon::parse($p->scheduled->start_date)->toDateString()) === $k;
                })->count();

                $allStatusByDateRange[] = [
                    'date' => Carbon::parse($bucket['start'])->format('Y-m-d'),
                    'countByStatus' => $count,
                    'status' => $status->name,
                ];
            }

            $labels[] = ['label' => $status->name];
        }

        $total = $byAllTime->count();
        $approved = $byAllTime->where('participantStatus.name', 'Aprobado')->count();

        return [
            'dateRange' => $dateRange,
            'byAllTime' => $byAllTime,
            'byStatusByDateRange' => [],
            'allStatusbyDateRange' => $allStatusByDateRange,
            'labels' => $labels,
            'approvalRate' => $total > 0 ? (int) round($approved / $total * 100) : null,
        ];
    }

    // ------------------------------------------- participant average

    /**
     * @return array<string, mixed>
     */
    private function participantAverage(Request $request, ?DateBuckets $range): array
    {
        $validStatusIds = ParticipantStatus::pluck('id')->all();
        $statusInput = $request->input('status_id');
        $statusId = ($statusInput !== null && $statusInput !== '' && in_array((int) $statusInput, $validStatusIds, true))
            ? (int) $statusInput
            : null;

        $data = [];

        foreach ($range->buckets() as $bucket) {
            $query = Scheduled::query()
                ->leftJoin('participants', 'participants.scheduled_id', '=', 'scheduled_course.id')
                ->whereNull('participants.deleted_at');

            if ($statusId !== null) {
                $query->where('participants.participant_status_id', $statusId);
            }

            $query->where('start_date', '>=', $bucket['start']);
            if ($bucket['isLast']) {
                $query->where('start_date', '<=', Carbon::parse($range->currentDate)->endOfDay());
            } else {
                $query->where('start_date', '<', $bucket['end']);
            }

            $rows = $query
                ->groupBy('scheduled_course.id')
                ->select('scheduled_course.id as scheduled_id')
                ->selectRaw('SUM(CASE WHEN participants.participant_status_id = ? THEN 1 ELSE 0 END) AS approved', [Participant::APROBADO])
                ->selectRaw('SUM(CASE WHEN participants.participant_status_id = ? THEN 1 ELSE 0 END) AS failed', [Participant::REPROBADO])
                ->selectRaw('COUNT(participants.id) AS total')
                ->get();

            foreach ($rows as $row) {
                $data[] = [
                    'date' => Carbon::parse($bucket['start'])->format('Y-m-d'),
                    'scheduled_id' => $row->scheduled_id,
                    'approved' => (int) $row->approved,
                    'failed' => (int) $row->failed,
                    'total' => (int) $row->total,
                ];
            }
        }

        return [
            'dateRange' => ['startDate' => $range->start(), 'endDate' => $range->end()],
            'data' => $data,
        ];
    }

    // -------------------------------------------------------------- gender

    /**
     * @return array<string, mixed>
     */
    private function participantByGender(?DateBuckets $range): array
    {
        $startDate = $range->start();
        $endDate = $range->end();

        $counts = Participant::query()
            ->selectRaw('people.sex as sex, COUNT(participants.id) as amount')
            ->join('people', 'participants.person_id', '=', 'people.id')
            ->whereHas('scheduled', function ($query) use ($startDate, $endDate) {
                $query->when($startDate === $endDate, function ($q) use ($startDate) {
                    return $q->whereDate('start_date', $startDate);
                }, function ($q) use ($startDate, $endDate) {
                    return $q->whereBetween('start_date', [$startDate, $endDate]);
                });
            })
            ->groupBy('people.sex')
            ->orderBy('people.sex')
            ->get();

        $rows = $counts->map(function ($row) {
            $raw = is_string($row->sex) ? trim($row->sex) : null;
            $label = 'No especificado';

            if (! empty($raw)) {
                $upper = mb_strtoupper($raw);
                if (in_array($upper, ['M', 'MALE', 'MASCULINO', 'H', 'HOMBRE'], true)) {
                    $label = 'Masculino';
                } elseif (in_array($upper, ['F', 'FEMALE', 'FEMENINO', 'MUJER'], true)) {
                    $label = 'Femenino';
                } else {
                    $label = $raw;
                }
            }

            return ['sex' => $raw, 'label' => $label, 'amount' => (int) $row->amount];
        })->values();

        $byLabel = $rows->groupBy('label')->map(fn ($group, $label) => [
            'label' => $label,
            'amount' => $group->sum('amount'),
        ])->values();

        $order = ['Femenino' => 1, 'Masculino' => 2, 'No especificado' => 99];
        $byLabel = $byLabel->sortBy(fn ($row) => $order[$row['label']] ?? 50)->values();
        $total = $byLabel->sum('amount');

        $byLabel = $byLabel->map(function ($row) use ($total) {
            $row['share'] = $total > 0 ? (int) round($row['amount'] / $total * 100) : null;

            return $row;
        })->values();

        return [
            'dateRange' => ['startDate' => $startDate, 'endDate' => $endDate],
            'total' => $total,
            'rows' => $byLabel,
        ];
    }

    // ------------------------------------------------------- most scheduled

    /**
     * @return array<string, mixed>
     */
    private function mostScheduled(): array
    {
        return $this->schedulingCounts();
    }

    /**
     * Courses scheduled the fewest times (never-scheduled courses are covered
     * by the dedicated not-scheduled report, so they are excluded here).
     *
     * @return array<string, mixed>
     */
    private function leastScheduled(): array
    {
        return $this->schedulingCounts();
    }

    /**
     * Shared historical scheduling counts for every course scheduled at least
     * once. Ordering is applied per report.
     *
     * @return array<string, mixed>
     */
    private function schedulingCounts(): array
    {
        $amountData = Scheduled::query()
            ->select('course_id', DB::raw('COUNT(*) AS total'))
            ->groupBy('course_id')
            ->pluck('total', 'course_id');

        $courseData = Course::whereIn('id', $amountData->keys())
            ->select('title', 'id', 'code')
            ->get();

        return ['amountData' => $amountData, 'courseData' => $courseData];
    }

    // -------------------------------------------------------- not scheduled

    /**
     * @return array<string, mixed>
     */
    private function notScheduled(): array
    {
        $data = Course::with('scheduled', 'capacity', 'category')->whereDoesntHave('scheduled')->get();

        return ['data' => $data];
    }
}
