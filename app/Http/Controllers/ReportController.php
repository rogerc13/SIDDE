<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Scheduled;
use App\Models\Course;
use App\Models\Participant;
use App\Models\ParticipantStatus;
use App\Models\Category;
use App\Models\CourseStatus;
use App\Models\Person;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;


class ReportController extends Controller
{
    /** Hard cap on generated buckets so a huge range × small step cannot storm the DB. */
    protected const MAX_RANGE_BUCKETS = 400;

    /** Interval steps offered by the reports UI. */
    protected const ALLOWED_STEPS = ['1 day', '1 week', '15 days', '1 month', '4 months', '6 months', '1 year'];

    protected function range($request)
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

        $startDateInput = $request->start_date;
        $endDateInput = $request->end_date;

        $rangeStart = ! empty($startDateInput)
            ? Carbon::parse($startDateInput)->startOfDay()
            : (! empty($endDateInput)
                ? Carbon::parse($endDateInput)->subMonth()->startOfDay()
                : Carbon::now()->subMonth()->startOfDay());

        $currentDate = ! empty($endDateInput)
            ? Carbon::parse($endDateInput)->endOfDay()
            : Carbon::now()->endOfDay();

        if ($rangeStart->greaterThan($currentDate)) {
            [$rangeStart, $currentDate] = [$currentDate->copy()->startOfDay(), $rangeStart->copy()->endOfDay()];
        }

        // Ensure step is a valid period string (examples: '1 day', '7 days', '1 month')
        try {
            $period = CarbonPeriod::create($rangeStart, $step, $currentDate);
        } catch (\Throwable $e) {
            // fallback to daily
            $period = CarbonPeriod::create($rangeStart, '1 day', $currentDate);
        }

        $date = [];
        foreach ($period as $d) {
            $date[] = $d->format('Y-m-d');
        }

        // Ensure the final bucket always ends at the chosen end date
        $endDateString = $currentDate->format('Y-m-d');
        if (empty($date)) {
            $date[] = $rangeStart->format('Y-m-d');
        }
        if (end($date) !== $endDateString) {
            $date[] = $endDateString;
        }

        if (count($date) > self::MAX_RANGE_BUCKETS + 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'start_date' => 'El rango seleccionado es demasiado amplio para el intervalo indicado. Reduzca el rango de fechas o aumente el intervalo.',
            ]);
        }


        $day = (stripos($step, 'day') !== false && intval($step) === 1);
        $numberOfSteps = max(0, count($date) - 1);

        return (object)[
            'currentDate' => $currentDate->format('Y-m-d'),
            'interval' => $period,
            'date' => $date,
            'numberOfSteps' => $numberOfSteps,
            'day' => $day
        ];
    }

    public function participantStatus()
    { //dynamic participant status selector

        $statuses = ParticipantStatus::all();
        return response()->json(['statuses' => $statuses]);
    }

    public function byDate(Request $request)
    {
        $datesObj = $this->range($request);
        $date = $datesObj->date;
        $numberOfSteps = $datesObj->numberOfSteps;
        $day = $datesObj->day;
        $xAxis = [];
        $y = [];

        $steps = max(1, $numberOfSteps);
        for ($k = 0; $k <= $steps; $k++) {
            $start_date = $date[$k];
            // if not daily, end is next date or same for last
            $end_index = ($day ? $k : min($k + 1, count($date) - 1));
            $end_date = $date[$end_index];

            $xAxis[] = Carbon::parse($start_date)->format('Y-m-d');
            if ($day) {
                $y[] = Scheduled::whereDate('start_date', $start_date)->count();
            } else {
                $y[] = Scheduled::whereBetween('start_date', [$start_date, $end_date])->count();
            }
        }
        return response()->json([
            'x' => $xAxis,
            'y' => $y,
            'total' => array_sum($y),
            'yData' => [], // keep shape stable; populate if you need raw items
            'start_date' => $xAxis[0] ?? null,
            'end_date' => end($xAxis) ?: null
        ]);
    } //end course by date range

    public function byCategory(Request $request)
    {
        //Course amount By Category

        $dates =  $this->range($request);
        $date = $dates->date;
        $day = $dates->day;

        $xAxis = [];
        $yAxis = [];
        $graphData = [];
        $categories = [];
        $courseData = [];

        $steps = max(1, count($date) - 1);
        for ($k = 0; $k <= $steps; $k++) {
            $xAxis[] = Carbon::parse($date[$k])->format('Y-m-d');
        }

        foreach (Category::all() as $category) {
            for ($k = 0; $k <= $steps; $k++) {
                $start_date = $date[$k];
                $end_date = $day ? $start_date : $date[min($k + 1, count($date) - 1)];

                $yAxis[] = [
                    'category' => $category->name,
                    'y' => Scheduled::with('course')
                        ->when($day, function ($query) use ($start_date) {
                            return $query->whereDate('start_date', $start_date);
                        }, function ($query) use ($start_date, $end_date) {
                            return $query->whereBetween('start_date', [$start_date, $end_date]);
                        })->whereHas(
                            'course',
                            function ($query) use ($category) {
                                $query->where('category_id', $category->id);
                            }
                        )->count(),
                    'x' => Carbon::parse($start_date)->format('Y-m-d'),
                ];
            }
            $categories[] = $category->name;

            $scheduledDatesForCategory = Scheduled::whereBetween('start_date', [$date[0], end($date)])
                ->whereHas('course', function ($query) use ($category) {
                    $query->where('category_id', $category->id);
                })
                ->pluck('start_date');

            $graphData[] = [
                'category' => $category->name,
                'y' => $scheduledDatesForCategory,
                'x' => $scheduledDatesForCategory,
            ];

            $courseData[] = [
                'categoryName' => $category->name,
                'courseData' => Scheduled::with('course', 'course.category')->whereHas('course', function ($query) use ($category) {
                    $query->where('category_id', $category->id);
                })->whereBetween('start_date', [$date[0], end($date)])->get(),
                'amount' => Scheduled::with('course')->whereHas('course', function ($query) use ($category) {
                    $query->where('category_id', $category->id);
                })->whereBetween('start_date', [$date[0], end($date)])->count()
            ];
        }

        $dateRangeHelper = ['startDate' => Carbon::parse($date[0])->format('Y-m-d'), 'endDate' => Carbon::parse(end($date))->format('Y-m-d')];

        return response()->json([
            'x' => $xAxis,
            'graphData' => $graphData,
            'categories' => $categories,
            'courseData' => $courseData,
            'dateRange' => $dateRangeHelper,
            'y' => $yAxis
        ]);
    } //end course by category

    public function byStatus(Request $request)
    {
        //Courses By Status

        /* $statusCourse = Scheduled::with('courseStatus')
            ->where('course_status_id', $request->course_status_id)
            ->get(); */

        //by all time all course status no conditions, no time range
        $byAllTime = Scheduled::with('courseStatus')->select('course_status_id')->selectRaw('COUNT(*) as amount')
            ->groupBy('course_status_id')->orderByDesc('amount')->get();

        //by date range

        $dates =  $this->range($request);
        $date = $dates->date;
        $day = $dates->day;

        $xAxis = [];
        $byDateRange = [];
        $statusNames = [];
        $courseData = [];

        $steps = max(1, count($date) - 1);
        for ($k = 0; $k <= $steps; $k++) {
            $xAxis[] = Carbon::parse($date[$k])->format('Y-m-d');
        }

        foreach (CourseStatus::all() as $status) {
            for ($k = 0; $k <= $steps; $k++) {
                $start_date = $date[$k];
                $end_date = $day ? $start_date : $date[min($k + 1, count($date) - 1)];

                //Data to show graphs
                $byDateRange[] = [
                    'status' => $status->name,
                    'x' => Carbon::parse($start_date)->format('Y-m-d'),
                    'y' => Scheduled::where('course_status_id', $status->id)
                        ->when($day, function ($query) use ($start_date) {
                            return $query->whereDate('start_date', $start_date);
                        }, function ($query) use ($start_date, $end_date) {
                            return $query->whereBetween('start_date', [$start_date, $end_date]);
                        })->count()
                ];
            }
            $statusNames[] = $status->name;

            //Data to show course list by date range
            $courseData[] = [
                'statusName' => $status->name,
                'courseData' => Scheduled::with('course', 'courseStatus')
                    ->where('course_status_id', $status->id)
                    ->whereBetween('start_date', [$date[0], end($date)])->get(),
                'amount' => Scheduled::with('course', 'courseStatus')
                    ->where('course_status_id', $status->id)
                    ->whereBetween('start_date', [$date[0], end($date)])->count(),
            ];
        }

        //by given status
        //$response = Scheduled::where('course_status_id',$request->status_id)->with('courseStatus')->count();

        $dateRangeHelper = ['startDate' => Carbon::parse($date[0])->format('Y-m-d'), 'endDate' => Carbon::parse(end($date))->format('Y-m-d')];

        return response()->json([
            'statuses' => $statusNames,
            'x' => $xAxis,
            'y' => $byDateRange,
            'byAllTime' => $byAllTime,
            'courseData' => $courseData,
            'dateRange' => $dateRangeHelper
        ]);
    } //end course by status

    public function byCourseDuration(Request $request)
    {

        // ALL TIME
        $byAllTime = Scheduled::with('course')->get()->pluck('course.duration')->sum();
        $finishedTotal = Scheduled::where('course_status_id', CourseStatus::CULMINADO)->with('course')->get()->pluck('course.duration')->sum();
        $mostDuration = Course::with('scheduled')->whereHas('scheduled')->orderBy('duration', 'desc')->get();
        // Span per run computed app-side: DATEDIFF() is not portable across DB engines.
        $spansMostDays = Scheduled::with('course')->get()
            ->each(function ($scheduled) {
                $scheduled->max_difference = Carbon::parse($scheduled->start_date)
                    ->startOfDay()
                    ->diffInDays(Carbon::parse($scheduled->end_date)->startOfDay());
            })
            ->sortByDesc('max_difference')
            ->values();

        // DATE RANGE (query only once for the full range)
        $dates = $this->range($request);
        $date = $dates->date;
        $start_date = $date[0];
        $end_date = end($date);
        // Only include finished scheduled courses (status 3) in both total and list
        // Use the exact same logic for the list as for the total
        $scheduledCourses = Scheduled::where('course_status_id', CourseStatus::CULMINADO)
            ->whereBetween('start_date', [$start_date, $end_date])
            ->with('course')
            ->get();
        // Calculate total hours for the range (sum durations of finished scheduled courses in range)
        $dateHelper = $scheduledCourses->pluck('course.duration')->sum();

        $dateRangeHelper = [
            'startDate' => Carbon::parse($start_date)->format('Y-m-d'),
            'endDate' => Carbon::parse($end_date)->format('Y-m-d')
        ];
        // Return one row per finished scheduled course in the date range
        $scheduledList = $scheduledCourses
            ->map(function ($scheduled) {
                $durationDays = null;
                if ($scheduled->start_date && $scheduled->end_date) {
                    $start = \Carbon\Carbon::parse($scheduled->start_date);
                    $end = \Carbon\Carbon::parse($scheduled->end_date);
                    $durationDays = $start->diffInDays($end) + 1;
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
            })
            ->values();

        return response()->json([
            'byAllTime' => $byAllTime,
            'finishedTotal' => $finishedTotal,
            'mostDuration' => $mostDuration,
            'byDateRange' => $scheduledList,
            'finishedByDateRange' => $dateHelper,
            'spansMostDays' => $spansMostDays,
            'dateRange' => $dateRangeHelper
        ]);
    } //end course duration

    public function byCanceled()
    {
        //Canceled Courses
        $canceledCourse = Scheduled::withTrashed()->where('course_status_id', CourseStatus::CANCELADO)->with('course')->get();
        return $canceledCourse;
    }

    public function participantsByStatus(Request $request)
    {

        // date buckets + defaults shared with every other report
        $dates =  $this->range($request);
        $date = $dates->date;
        $day = $dates->day;

        // date range helper (used by frontend for titles); missing dates fall
        // back to the same last-month default as the other reports
        $dateRange = [
            'startDate' => Carbon::parse($date[0])->format('Y-m-d'),
            'endDate' => Carbon::parse(end($date))->format('Y-m-d'),
        ];

        $byAllTime = [];

        // participants during selected range (all statuses)
        $byAllTime = Participant::with([
            'person',
            'participantStatus',
            'scheduled' => function ($query) {
                $query->withTrashed()->with('course');
            },
        ])
            ->whereHas('scheduled', function ($query) use ($dateRange) {
                $query->when($dateRange['startDate'] === $dateRange['endDate'], function ($q) use ($dateRange) {
                    return $q->whereDate('start_date', $dateRange['startDate']);
                }, function ($q) use ($dateRange) {
                    return $q->whereBetween('start_date', [$dateRange['startDate'], $dateRange['endDate']]);
                });
            })
            ->get();


        // participants not in a course: no longer included in this report

        //by date range by status
        $steps = max(1, count($date) - 1);

        $allStatusbyDateRange = [];
        $labels = [];

        $byStatusByDateRange = [];
        foreach ($date as $bucketDate) {
            $byStatusByDateRange[] = [
                'date' => Carbon::parse($bucketDate)->format('Y-m-d'),
                // kept for backward compatibility (frontend uses this array to derive first/last date if needed)
                'countByStatus' => 0,
                'status' => null,
            ];
        }

        foreach (ParticipantStatus::all() as $status) {
            for ($k = 0; $k <= $steps; $k++) {
                $start_date = $date[$k];
                $end_date = $day ? $start_date : $date[min($k + 1, count($date) - 1)];

                $allStatusbyDateRange[] = [
                    'date' => Carbon::parse($start_date)->format('Y-m-d'),
                    'countByStatus' => Participant::where('participant_status_id', $status->id)->whereHas(
                        'scheduled',
                        function ($query) use ($start_date, $end_date) {
                            $query->when($start_date === $end_date, function ($q) use ($start_date) {
                                return $q->whereDate('start_date', $start_date);
                            }, function ($q) use ($start_date, $end_date) {
                                return $q->whereBetween('start_date', [$start_date, $end_date]);
                            });
                        }
                    )->count(),
                    'status' => $status->name,
                ];
            }

            $labels[] = ['label' => $status->name,];
        }

        return response()->json([
            'dateRange' => $dateRange,
            'byAllTime' => $byAllTime,
            'byStatusByDateRange' => $byStatusByDateRange,
            'allStatusbyDateRange' => $allStatusbyDateRange,
            'labels' => $labels,
        ]);
    } //end by participant status

    public function participantsByGender(Request $request)
    {
        $range = $this->range($request);
        $startDate = $range->date[0] ?? null;
        $endDate = ! empty($range->date) ? end($range->date) : null;

        // Fallbacks (shouldn't happen due to range() defaults)
        $startDate = $startDate ?: Carbon::now()->subMonth()->format('Y-m-d');
        $endDate = $endDate ?: Carbon::now()->format('Y-m-d');

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
                $u = mb_strtoupper($raw);
                if (in_array($u, ['M', 'MALE', 'MASCULINO', 'H', 'HOMBRE'], true)) {
                    $label = 'Masculino';
                } elseif (in_array($u, ['F', 'FEMALE', 'FEMENINO', 'MUJER'], true)) {
                    $label = 'Femenino';
                } else {
                    // keep custom values visible (e.g. "Otro")
                    $label = $raw;
                }
            }

            return [
                'sex' => $raw,
                'label' => $label,
                'amount' => (int) $row->amount,
            ];
        })->values();

        // Combine duplicates after normalization (e.g. M + Masculino)
        $byLabel = $rows->groupBy('label')->map(function ($group, $label) {
            return [
                'label' => $label,
                'amount' => $group->sum('amount'),
            ];
        })->values();

        // Stable ordering for common labels
        $order = ['Femenino' => 1, 'Masculino' => 2, 'No especificado' => 99];
        $byLabel = $byLabel->sortBy(function ($row) use ($order) {
            return $order[$row['label']] ?? 50;
        })->values();

        return response()->json([
            'dateRange' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ],
            'total' => $byLabel->sum('amount'),
            'rows' => $byLabel,
        ]);
    }

    public function courseByParticipantQuantity(Request $request)
    {
        //ALL TIME
        //participant amount of participants all time no condition
        $amountAllTime = Participant::selectRaw('COUNT(*) as amount')->get();
        //participant amount by course, all time no condition
        $amountAllTimePerCourse = Participant::select('scheduled_id')->selectRaw('COUNT(*) AS count')
            ->groupBy('scheduled_id')->orderByDesc('count')->get();

        //BY DATE RANGE: one grouped query for the whole range instead of
        //loading every run per bucket plus a COUNT per course
        $range = $this->range($request);
        $startDate = $range->date[0];
        $endDate = end($range->date);

        $rows = Scheduled::query()
            ->leftJoin('participants', 'participants.scheduled_id', '=', 'scheduled_course.id')
            ->whereBetween('start_date', [$startDate, $endDate])
            ->whereNull('participants.deleted_at')
            ->groupBy('scheduled_course.id')
            ->select(
                'scheduled_course.id as scheduled_id',
                'scheduled_course.course_id',
                'scheduled_course.start_date',
                'scheduled_course.end_date'
            )
            ->selectRaw('COUNT(participants.id) AS participant_count')
            ->get();

        $courseTitles = Course::whereIn('id', $rows->pluck('course_id')->unique())
            ->pluck('title', 'id');

        // bucket attribution preserved: a run appears once per bucket that contains its start date
        $dateRangeAmountPerCourse = [];
        foreach ($range->date as $key => $bucketStart) {
            $end_date = $range->day ? $bucketStart : ($range->date[min($key + 1, count($range->date) - 1)] ?? $bucketStart);

            foreach ($rows as $row) {
                $rowStart = Carbon::parse($row->start_date)->toDateString();
                if ($rowStart >= $bucketStart && $rowStart <= $end_date) {
                    $dateRangeAmountPerCourse[] = [
                        'date' => Carbon::parse($bucketStart)->format('Y-m-d'),
                        'scheduled_id' => $row->scheduled_id,
                        'course' => $courseTitles[$row->course_id] ?? 'Sin título',
                        'start_date' => $row->start_date,
                        'end_date' => $row->end_date,
                        'count' => (int) $row->participant_count,
                    ];
                }
            }
        }

        $sorted = count($dateRangeAmountPerCourse) > 0
            ? collect($dateRangeAmountPerCourse)->sortByDesc('count')->values()
            : 0;

        return response()->json([
            'amountAllTime' => $amountAllTime,
            'amountAllTimePerCourse' => $amountAllTimePerCourse,
            'dateRangeAmountPerCourse' => $sorted
        ]);
    } //end participant amount

    public function participantAverage(Request $request)
    {

        $dates =  $this->range($request);
        $date = $dates->date;
        $numberOfSteps = $dates->numberOfSteps;
        $day = $dates->day;
        $j = 0;

        $data = [];

        foreach ($date as $key => $value) {
            $start_date = $date[$key];
            $end_date = $date[$day === true ? $key : ($j < $numberOfSteps ? $j = $j + 1 : $j)];

            // one grouped query per bucket instead of three COUNTs per course
            $rows = Scheduled::query()
                ->leftJoin('participants', 'participants.scheduled_id', '=', 'scheduled_course.id')
                ->whereBetween('start_date', [$start_date, $end_date])
                ->whereNull('participants.deleted_at')
                ->groupBy('scheduled_course.id')
                ->select('scheduled_course.id as scheduled_id')
                ->selectRaw('SUM(CASE WHEN participants.participant_status_id = ? THEN 1 ELSE 0 END) AS approved', [Participant::APROBADO])
                ->selectRaw('SUM(CASE WHEN participants.participant_status_id = ? THEN 1 ELSE 0 END) AS failed', [Participant::REPROBADO])
                ->selectRaw('COUNT(participants.id) AS total')
                ->get();

            foreach ($rows as $row) {
                $data[] = [
                    'date' => Carbon::parse($start_date)->format('Y-m-d'),
                    'scheduled_id' => $row->scheduled_id,
                    'approved' => (int) $row->approved,
                    'failed' => (int) $row->failed,
                    'total' => (int) $row->total,
                ];
            }
        }

        return response()->json(['data' => $data]);
    } //end participant average

    public function notScheduled(Request $request)
    {
        //all courses not present on scheduled table
        $data = Course::with('scheduled', 'capacity', 'category')->whereDoesntHave('scheduled')->get();
        return response()->json(['data' => $data]);
    } //end courses not scheduled

    public function mostScheduled(Request $request)
    {
        //id of course that appears the most on scheduled table
        //get courses ids, count how many times each one repeats, get course data of each

        $amountData = Scheduled::query()
            ->select('course_id', DB::raw('COUNT(*) AS total'))
            ->groupBy('course_id')
            ->pluck('total', 'course_id');

        $courseData = Course::whereIn('id', $amountData->keys())
            ->select('title', 'id', 'code')
            ->get();

        return response()->json(['amountData' => $amountData, 'courseData' => $courseData]);
    } //end course most scheduled
}
