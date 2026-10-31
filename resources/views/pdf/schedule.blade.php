@extends('pdf.list')

@php
    $facilitatorName = trim($scheduled->facilitator->person->name . ' ' . $scheduled->facilitator->person->last_name);
    $rangeLabel = \Carbon\Carbon::parse($scheduled->start_date)->format('d-m-Y') . ' → ' . \Carbon\Carbon::parse($scheduled->end_date)->format('d-m-Y');
@endphp

@section('title', $scheduled->course->title)
@section('subtitle', $facilitatorName . ' · ' . $rangeLabel . ' · Generado el ' . now()->format('d-m-Y H:i'))

@section('content')
    <style>
        table.schedule-grid {
            width: 100%;
            border-collapse: collapse;
        }
        table.schedule-grid th,
        table.schedule-grid td {
            border: 1px solid #d9dde3;
            vertical-align: top;
            padding: 0;
        }
        table.schedule-grid thead th {
            background-color: #bdedbc;
            color: #045702;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            padding: 4px 2px;
        }
        table.schedule-grid th.sg-hour {
            width: 5%;
        }
        table.schedule-grid th.sg-lane {
            height: 0;
            padding: 0;
            border: 0;
            background: transparent;
            font-size: 0;
            line-height: 0;
        }
        table.schedule-grid td.sg-hour {
            background-color: #f6f8f9;
            color: #6b7684;
            font-size: 8px;
            text-align: right;
            padding: 2px 4px;
        }
        table.schedule-grid tbody td { height: 17px; }
        table.schedule-grid td.sg-no-left { border-left: 0; }
        table.schedule-grid td.sg-no-right { border-right: 0; }
        .schedule-block {
            background-color: #eef8f1;
            border-left: 2px solid #27ae60;
            padding: 2px 3px;
            font-size: 7.5px;
            line-height: 1.25;
            color: #1f2733;
            overflow: hidden;
            word-wrap: break-word;
        }
        .schedule-block span { display: block; }
        .schedule-block .sb-time { font-weight: bold; }
        .schedule-block .sb-aula { color: #1f7a44; }
        .schedule-block .sb-dates { color: #444; }
        .schedule-block .sb-date { display: inline-block; white-space: nowrap; }
    </style>

    <table class="schedule-grid">
        <thead>
            <tr>
                <th class="sg-hour" rowspan="2"></th>
                @foreach($dayNames as $dayIndex => $dayName)
                    @php $dayLanes = (int) ($columns[$dayIndex + 1] ?? 1); @endphp
                    <th colspan="{{ $dayLanes }}">{{ $dayName }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach($dayNames as $dayIndex => $dayName)
                    @php
                        $dayLanes = (int) ($columns[$dayIndex + 1] ?? 1);
                        $laneWidth = round((95 / 7) / $dayLanes, 3);
                    @endphp
                    @for($lane = 0; $lane < $dayLanes; $lane++)
                        <th class="sg-lane" style="width: {{ $laneWidth }}%;"></th>
                    @endfor
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td class="sg-hour">{{ $row['hour'] }}</td>
                    @foreach($dayNames as $dayIndex => $dayName)
                        @php
                            $day = $dayIndex + 1;
                            $lanes = (int) ($columns[$day] ?? 1);
                            $laneCursor = 0;
                        @endphp
                        @foreach($row['cells'][$day] as $cell)
                            @php
                                $cellStart = $laneCursor;
                                $laneCursor += $cell['colspan'];
                                $cellClasses = [];

                                if (! $cell['block']) {
                                    if ($cellStart > 0) {
                                        $cellClasses[] = 'sg-no-left';
                                    }
                                    if ($laneCursor < $lanes) {
                                        $cellClasses[] = 'sg-no-right';
                                    }
                                }
                            @endphp
                            @if($cell['block'])
                                <td class="{{ implode(' ', $cellClasses) }}" colspan="{{ $cell['colspan'] }}" rowspan="{{ $cell['rowspan'] }}">
                                    <div class="schedule-block">
                                        <span class="sb-time">{{ $cell['block']['time'] }}</span>
                                        @if($cell['block']['location_name'])
                                            <span class="sb-aula">{{ $cell['block']['location_name'] }}</span>
                                        @endif
                                        <span class="sb-dates">
                                            @foreach($cell['block']['dates'] as $date)
                                                <span class="sb-date">{{ $date }}@if(!$loop->last),@endif</span>
                                            @endforeach
                                        </span>
                                    </div>
                                </td>
                            @else
                                <td class="{{ implode(' ', $cellClasses) }}" colspan="{{ $cell['colspan'] }}" rowspan="{{ $cell['rowspan'] }}"></td>
                            @endif
                        @endforeach
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
