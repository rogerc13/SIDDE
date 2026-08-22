@extends('layouts.admin')

@push('CSS')
<link rel="stylesheet" href="{{url('assets/js/fullcalendar-2/fullcalendar.min.css')}}">
<style>
    .panel-body > h3 { margin: 0 0 14px; font-size: 20px; }
    .panel-body > h3 .badge { vertical-align: middle; font-size: 12px; margin-left: 8px; }

    /* KPI tiles — equalized via flex */
    .details-stats { display: flex; flex-wrap: wrap; margin-left: -8px; margin-right: -8px; margin-bottom: 24px; }
    .details-stats > div { display: flex; padding-left: 8px; padding-right: 8px; }
    .details-stats .stat-tile {
        background: #fafbfc;
        border: 1px solid #eef1f4;
        border-radius: 4px;
        padding: 10px 12px;
        flex: 1 1 auto;
        width: 100%;
    }
    .details-stats .stat-label { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #999; margin-bottom: 3px; }
    .details-stats .stat-label i { margin-right: 2px; }
    .details-stats .stat-value { display: block; font-size: 15px; font-weight: 600; color: #333; word-break: break-word; }
    .details-stats .stat-sub { display: inline-block; font-size: 12px; color: #888; margin-top: 1px; }
    .details-stats .stat-value-range { font-size: 13px; white-space: nowrap; line-height: 22px; }
    .details-stats .stat-value-range .range-arrow { color: #999; font-weight: 400; padding: 0 1px; }
    .details-stats .mini-budget { height: 5px; margin: 7px 0 0; border-radius: 3px; background: #e9ecef; }

    /* Header actions: keep the group look but with spacing between buttons */
    .panel-options .btn-group > .btn { border-radius: 4px !important; }
    .panel-options .btn-group > .btn + .btn { margin-left: 5px; }
    .panel-default > .panel-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding-left: 15px;
        padding-right: 15px;
    }
    .panel-default > .panel-heading::before,
    .panel-default > .panel-heading::after { display: none !important; }
    .panel-default > .panel-heading > .panel-options { float: none; }

    /* Unified section spacing */
    #details-calendar { background: #fff; }
    #details-calendar .fc-header-title { font-size: 15px !important; line-height: 22px !important; margin: 0 !important; }
    .calendar-legend { margin-top: 8px; font-size: 12px; color: #777; }
    .calendar-legend .legend-swatch { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 4px; vertical-align: middle; }
    .calendar-legend .legend-swatch { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 4px; vertical-align: middle; }
    @media print {
        .sidebar-nav, .panel-options, .no-print { display: none !important; }
        .panel { border: none; box-shadow: none; }
    }
</style>
@endpush

@section('content')
@php
    $sessionCount = count($existingSessions);
    $sessionWord = $sessionCount === 1 ? 'sesión' : 'sesiones';
    $courseDuration = $scheduled->course->duration;
    $hoursPct = $courseDuration > 0 ? min(100, round(($totalHours / $courseDuration) * 100)) : 0;
    $hoursBarClass = $totalHours >= $courseDuration ? 'progress-bar-success' : ($hoursPct > 60 ? 'progress-bar-info' : 'progress-bar-warning');
@endphp
<div class="panel panel-default">
    <div class="panel-heading">
        <div class="panel-title">Detalles de la Acción de Formación Programada</div>
        <div class="panel-options">
            <div class="btn-group no-print" role="group">
                <a href="{{ url('u/af_programadas') }}" class="btn btn-blue"><i class="entypo-back"></i> Volver</a>
                <a href="javascript:void(0);" onclick="window.print()" class="btn btn-success"><i class="fa fa-print"></i> Imprimir</a>
                @can('update', $scheduled)
                    <a href="{{ url('u/af_programadas/'.$scheduled->id.'/editar') }}" class="btn btn-primary"><i class="fa fa-pencil"></i> Editar</a>
                @endcan
            </div>
        </div>
    </div>
    <div class="panel-body">
        <h3 style="margin: 0 0 12px;">
            {{ $scheduled->course->title }}
            <span class="badge badge-{{ $scheduled->badgeStatus() }}">{{ $scheduled->courseStatus->name }}</span>
        </h3>

        <div class="row details-stats">
            <div class="col-sm-3 col-xs-6">
                <div class="stat-tile">
                    <span class="stat-label"><i class="entypo-user"></i> Facilitador</span>
                    <span class="stat-value">{{ $scheduled->facilitator->person->name }} {{ $scheduled->facilitator->person->last_name }}</span>
                </div>
            </div>
            <div class="col-sm-3 col-xs-6">
                <div class="stat-tile">
                    <span class="stat-label"><i class="entypo-calendar"></i> Fechas</span>
                    <span class="stat-value stat-value-range">{{ \Carbon\Carbon::parse($scheduled->start_date)->format('d-m-Y') }} <span aria-hidden="true" class="range-arrow">→</span> {{ \Carbon\Carbon::parse($scheduled->end_date)->format('d-m-Y') }}</span>
                </div>
            </div>
            <div class="col-sm-3 col-xs-6">
                <div class="stat-tile">
                    <span class="stat-label"><i class="entypo-clock"></i> Duración</span>
                    <span class="stat-value">{{ $totalHours }} / {{ $courseDuration }}h</span>
                    <span class="stat-sub">{{ $sessionCount }} {{ $sessionWord }}</span>
                    <div class="progress mini-budget">
                        <div class="progress-bar {{ $hoursBarClass }}" role="progressbar" aria-label="Horas asignadas" style="width: {{ $hoursPct }}%"></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-3 col-xs-6">
                <div class="stat-tile">
                    <span class="stat-label"><i class="entypo-users"></i> Registrados</span>
                    <span class="stat-value">{{ $participantsCount }} @if($capacityMax)/ {{ $capacityMax }}@endif</span>
                    @can('getAllPorCurso','App\Participant')
                        <a class="stat-sub no-print" href="{{ url('u/af_programadas/'.$scheduled->id.'/participantes') }}">gestionar participantes</a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="panel panel-success" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">Sesiones</div>
            </div>
            <div class="panel-body with-table table-responsive">
                <table class="table table-striped table-bordered table-center">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th>Ubicación</th>
                    <th>Fecha</th>
                    <th>Horario</th>
                    <th class="text-center">Dur.</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
                @if(count($existingSessions) === 0)
                <tr><td colspan="6" class="text-center text-muted">No hay sesiones registradas.</td></tr>
                @endif
                @foreach($existingSessions as $i => $s)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td title="{{ $s['location_name'] }}">{{ $s['location_name'] }}</td>
                    <td data-fecha="{{ $s['session_date'] }}"><small class="text-muted">{{ \Carbon\Carbon::parse($s['session_date'])->locale('es')->shortDayName }}</small> {{ \Carbon\Carbon::parse($s['session_date'])->format('d-m-Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($s['start_time'])->format('g:i A') }} – {{ \Carbon\Carbon::parse($s['end_time'])->format('g:i A') }}</td>
                    <td class="text-center">{{ $s['duration_hours'] }} hrs</td>
                    <td class="notes-cell" title="{{ $s['notes'] }}">{{ $s['notes'] ?: '-' }}</td>
                </tr>
                @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel panel-success" data-collapsed="0" style="margin-top: 15px;">
            <div class="panel-heading">
                <div class="panel-title">Calendario</div>
            </div>
            <div class="panel-body">
                <div id="details-calendar-wrap">
                    <div id="details-calendar"></div>
                </div>
                <div class="calendar-legend no-print">
                    <span class="legend-swatch" style="background:#27ae60;"></span>Sesiones de este curso
                    <span class="legend-swatch" style="background:#999; margin-left: 12px;"></span>Ocupado (otros cursos)
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('JS')
<script>
var detailSessions = @json($existingSessions);
var detailScheduledId = {{ $scheduled->id }};

function detailLocationColor(locationId) {
    var n = parseInt(locationId, 10);
    var hue = isNaN(n) ? 145 : (n * 137.508) % 360;
    return 'hsl(' + Math.round(hue) + ', 65%, 45%)';
}

$(function() {
    $('#details-calendar').fullCalendar({
        timezone: 'America/Caracas',
        firstDay: 1,
        header: { left: 'prev,next today', center: 'title', right: 'month,agendaWeek,agendaDay' },
        defaultView: 'agendaWeek',
        slotDuration: '00:30:00',
        allDaySlot: false,
        minTime: '06:00:00',
        maxTime: '22:00:00',
        contentHeight: 480,
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            agendaWeek: 'Semana',
            agendaDay: 'Día'
        },
        monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
        monthNamesShort: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
        dayNames: ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'],
        dayNamesShort: ['Dom','Lun','Mar','Mié','Jue','Vie','Sá'],
        eventClick: function(event) { return false; },
        events: function(start, end, timezone, callback) {
            var sessionEvents = detailSessions.map(function(s) {
                return {
                    id: 'session-' + s.id,
                    title: s.location_name,
                    start: s.session_date + 'T' + s.start_time,
                    end: s.session_date + 'T' + s.end_time,
                    color: detailLocationColor(s.location_id),
                    allDay: false
                };
            });
            $.ajax({
                url: '{{ url("u/af_programadas/blocked-slots") }}',
                type: 'GET',
                dataType: 'json',
                data: {
                    start: start.format('YYYY-MM-DD'),
                    end: end.format('YYYY-MM-DD'),
                    exclude_scheduled_id: detailScheduledId
                },
                success: function(blocked) {
                    var bg = (Array.isArray(blocked) ? blocked : []).map(function(e) {
                        return {
                            title: e.title,
                            start: e.start,
                            end: e.end,
                            rendering: 'background',
                            allDay: false
                        };
                    });
                    callback(sessionEvents.concat(bg));
                },
                error: function() {
                    callback(sessionEvents);
                }
            });
        },
        eventRender: function(event, element) {
            element.attr('title', event.title);
            if (event.rendering === 'background') {
                element.addClass('fc-bgevent-plain');
            }
        }
    });

    if (detailSessions.length) {
        var dates = detailSessions.map(function(s) { return s.session_date; }).sort();
        $('#details-calendar').fullCalendar('gotoDate', dates[0]);
    }
});
</script>
@endpush
