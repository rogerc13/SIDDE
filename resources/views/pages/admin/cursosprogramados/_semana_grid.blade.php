@php
    $gridCourse = $printHorarioCourse ?? $scheduled->course->title;
    $gridFacilitator = $printHorarioFacilitator ?? '';
@endphp
<div class="semana-grid">
    <div class="semana-gutter">
        <div class="semana-gutter-spacer"></div>
        @for($h = 6; $h <= 21; $h++)
            <div class="semana-hour">{{ sprintf('%02d:00', $h) }}</div>
        @endfor
    </div>
    <div class="semana-days">
        @foreach($dayNames as $i => $dayName)
            <div class="semana-day">
                <div class="semana-day-head">{{ $dayName }}</div>
                <div class="semana-day-body">
                    @foreach($printGroups as $g)
                        @if($g['weekday'] === $i + 1)
                            @php
                                $start = \Carbon\Carbon::parse($g['start_time']);
                                $end = \Carbon\Carbon::parse($g['end_time']);
                                $startMin = min(max($start->hour * 60 + $start->minute, 360), 1320);
                                $endMin = min(max($end->hour * 60 + $end->minute, 360), 1320);
                                $top = round(($startMin - 360) / 960 * 100, 3);
                                $height = round(max(2, $endMin - $startMin) / 960 * 100, 3);
                                $columns = max(1, (int) ($g['columns'] ?? 1));
                                $column = (int) ($g['column'] ?? 0);
                                $left = round($column / $columns * 100, 3);
                                $width = round(100 / $columns, 3);
                            @endphp
                            <div class="semana-block{{ $columns > 1 ? ' semana-block--split' : '' }}" style="top: {{ $top }}%; height: {{ $height }}%; left: calc({{ $left }}% + 2px); width: calc({{ $width }}% - 4px);">
                                <span class="sb-time">{{ $start->format('g:i A') }} – {{ $end->format('g:i A') }}</span>
                                <span class="sb-aula">{{ $g['location_name'] }}</span>
                                <span class="sb-course">{{ $gridCourse }}</span>
                                <span class="sb-fac">{{ $gridFacilitator }}</span>
                                <span class="sb-dates">
                                    @foreach($g['dates'] as $date)
                                        <span class="sb-date">{{ $date }}@if(!$loop->last),@endif</span>
                                    @endforeach
                                </span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
