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
            table-layout: fixed;
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
            width: 46px;
        }
        table.schedule-grid td.sg-hour {
            background-color: #f6f8f9;
            color: #6b7684;
            font-size: 8px;
            text-align: right;
            padding: 2px 4px;
        }
        table.schedule-grid tbody td { height: 17px; }
        .schedule-block {
            background-color: #eef8f1;
            border-left: 2px solid #27ae60;
            padding: 2px 3px;
            font-size: 7.5px;
            line-height: 1.25;
            color: #1f2733;
        }
        .schedule-block span { display: block; }
        .schedule-block .sb-time { font-weight: bold; }
        .schedule-block .sb-aula { color: #1f7a44; }
        .schedule-block .sb-dates { color: #444; }
    </style>

    <table class="schedule-grid">
        <thead>
            <tr>
                <th class="sg-hour"></th>
                @foreach($dayNames as $dayName)
                    <th>{{ $dayName }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td class="sg-hour">{{ $row['hour'] }}</td>
                    @foreach($dayNames as $dayIndex => $dayName)
                        @php $day = $dayIndex + 1; @endphp
                        @if(array_key_exists($day, $row['cells']))
                            @php $cell = $row['cells'][$day]; @endphp
                            @if($cell === null)
                                <td></td>
                            @else
                                <td rowspan="{{ $cell['rowspan'] }}">
                                    <div class="schedule-block">
                                        <span class="sb-time">{{ $cell['time'] }}</span>
                                        @if($cell['location_name'])
                                            <span class="sb-aula">{{ $cell['location_name'] }}</span>
                                        @endif
                                        <span class="sb-dates">{{ implode(', ', $cell['dates']) }}</span>
                                    </div>
                                </td>
                            @endif
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
