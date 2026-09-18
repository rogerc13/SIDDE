@extends('layouts.admin')

@push('CSS')
<link rel="stylesheet" href="{{url('assets/js/fullcalendar-2/fullcalendar.min.css')}}">
<style>
    .details-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
    .details-header-main { min-width: 0; }
    .details-header h3 { margin: 0 0 4px; font-size: 20px; }
    .details-header h3 .badge { vertical-align: middle; font-size: 12px; margin-left: 8px; }
    .details-meta { margin: 0; font-size: 12px; }
    .details-tabs { margin-bottom: 0; }
    .details-tab-content { background: #fff; border: 1px solid #e5e5e5; border-top: 0; padding: 18px; margin-bottom: 20px; border-radius: 0 0 3px 3px; }
    .details-tab-content .ficha-table { margin-bottom: 0; }

    /* Documentos tab */
    .doc-grid { display: flex; flex-wrap: wrap; gap: 14px; }
    .doc-tile {
        flex: 1 1 220px; max-width: 260px;
        display: flex; flex-direction: column; align-items: center; text-align: center;
        padding: 16px 14px; border: 1px solid #eef1f4; border-radius: 6px; background: #fbfcfd;
    }
    .doc-tile--empty { opacity: 0.6; }
    .doc-thumb {
        width: 56px; height: 56px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 26px; margin-bottom: 10px;
    }
    .doc-thumb--1 { background: #2c81ba; }
    .doc-thumb--2 { background: #27ae60; }
    .doc-thumb--3 { background: #e0a32e; }
    .doc-thumb--4 { background: #8e5bc0; }
    .doc-info { width: 100%; min-width: 0; margin-bottom: 10px; }
    .doc-title { display: block; font-size: 13px; font-weight: 600; color: #333; }
    .doc-sub { display: block; font-size: 11px; color: #8a97a3; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .doc-download { margin-top: auto; }

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

    /* Header actions */
    .details-actions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
    .details-actions .dropdown { display: inline-block; }
    .details-actions .dropdown-menu { min-width: 200px; font-size: 13px; }
    .details-actions .dropdown-menu > li > a > i { width: 16px; text-align: center; margin-right: 6px; color: #7a8794; }
    .details-actions .dropdown-menu > li.disabled > a { pointer-events: none; }

    /* Unified section spacing */
    #details-calendar { background: #fff; }
    #details-calendar .fc-header-title { font-size: 15px !important; line-height: 22px !important; margin: 0 !important; }
    #details-calendar .fc-ev-sub { display: block; font-size: 10px; line-height: 1.15; opacity: 0.9; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #details-calendar .fc-event .fc-title { display: block; overflow: hidden; }
    #details-calendar .fc-event { overflow: hidden; }
    #details-calendar .fc-month-view .fc-ev-sub { display: none; }
    .calendar-legend { margin-top: 8px; font-size: 12px; color: #777; }
    .calendar-legend .legend-swatch { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 4px; vertical-align: middle; }
    .calendar-legend .legend-swatch { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 4px; vertical-align: middle; }
    @media print {
        .sidebar-nav, .panel-options, .no-print { display: none !important; }
        .panel { border: none; box-shadow: none; }
        body.printing-ficha .details-header,
        body.printing-ficha .details-stats,
        body.printing-ficha .details-tabs,
        body.printing-ficha #tab-programacion,
        body.printing-ficha #tab-participantes,
        body.printing-ficha #tab-documentos { display: none !important; }
        body.printing-ficha #tab-ficha { display: block !important; }
        body.printing-ficha .details-tab-content { border: 0; padding: 0; margin: 0; background: transparent; }
    }
    .ficha-actions { margin-bottom: 14px; text-align: right; }
</style>
@endpush

@section('content')
@php
    $sessionCount = count($existingSessions);
    $sessionWord = $sessionCount === 1 ? 'sesión' : 'sesiones';
    $courseDuration = $scheduled->course->duration;
    $hoursPct = $courseDuration > 0 ? min(100, round(($totalHours / $courseDuration) * 100)) : 0;
    $hoursBarClass = $totalHours >= $courseDuration ? 'progress-bar-success' : ($hoursPct > 60 ? 'progress-bar-info' : 'progress-bar-warning');
    $createdAt = $scheduled->created_at ? \Carbon\Carbon::parse($scheduled->created_at) : null;
    $updatedAt = $scheduled->updated_at ? \Carbon\Carbon::parse($scheduled->updated_at) : null;
    $backUrl = Auth::user()->isFacilitador() ? url('u/mis_acciones') : url('u/af_programadas');
    $canManage = Auth::user()->isAdministrador() || Auth::user()->isTecEducativa() || Auth::user()->isProgramador();
    $docTypes = [
        1 => ['label' => 'Manual del Facilitador', 'icon' => 'fa-file-pdf-o'],
        2 => ['label' => 'Manual del Participante', 'icon' => 'fa-file-pdf-o'],
        3 => ['label' => 'Guía', 'icon' => 'fa-file-pdf-o'],
        4 => ['label' => 'Presentación', 'icon' => 'fa-file-pdf-o'],
    ];
    $filesByType = $scheduled->course->file->keyBy('type_id');
@endphp

<div class="details-header">
    <div class="details-header-main">
        <h3>
            {{ $scheduled->course->title }}
            <span class="badge badge-{{ $scheduled->badgeStatus() }}">{{ $scheduled->courseStatus->name }}</span>
        </h3>
        @if($createdAt)
        <p class="details-meta text-muted">
            <i class="entypo-clock"></i> Creada: {{ $createdAt->format('d-m-Y g:i A') }}
            @if($updatedAt && $updatedAt->gt($createdAt))
                <span aria-hidden="true">·</span> Actualizada: {{ $updatedAt->format('d-m-Y g:i A') }}
            @endif
        </p>
        @endif
    </div>
    <div class="details-actions no-print">
        <a href="{{ $backUrl }}" class="btn btn-blue"><i class="entypo-back"></i> Volver</a>
        @can('store','App\Models\Participant')
            <a href="javascript:asignarParticipanteLista('{{url('u/af_programadas/'.$scheduled->id.'/asignarParticipante')}}','{{$scheduled->id}}')" class="btn btn-primary {{($scheduled->atMaxCapacity() || !$scheduled->isPorDictar()) ? 'disabled' : ''}}">
                <i class="entypo-user-add"></i> Asignar participante
            </a>
        @endcan
        @if($canManage)
        <div class="dropdown">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Más funciones <span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right">
                <li class="{{ $scheduled->isCulminado() || $scheduled->isCancelado() ? 'disabled' : '' }}">
                    <a href="{{ url('u/af_programadas/'.$scheduled->id.'/editar') }}"><i class="entypo-pencil"></i> Editar programación</a>
                </li>
                <li role="separator" class="divider"></li>
                <li class="{{ $scheduled->isCancelado() ? 'disabled' : '' }}">
                    <a href="javascript:cancelPrograma('{{url('u/af_programadas/cancel/'.$scheduled->id)}}')"><i class="glyphicon glyphicon-ban-circle"></i> Cancelar</a>
                </li>
                @can('delete', $scheduled)
                <li class="{{ $scheduled->canBeDeleted() ? '' : 'disabled' }}">
                    <a href="javascript:eliminarPrograma('{{url('u/af_programadas/'.$scheduled->id)}}')"><i class="entypo-trash"></i> Eliminar</a>
                </li>
                @endcan
            </ul>
        </div>
        @endif
    </div>
</div>

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
            <span class="stat-value stat-value-range">@date($scheduled->start_date) <span aria-hidden="true" class="range-arrow">→</span> @date($scheduled->end_date)</span>
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
            @can('getAllPorCurso','App\Models\Participant')
                <a class="stat-sub no-print" href="{{ url('u/af_programadas/'.$scheduled->id.'/participantes') }}">gestionar participantes</a>
            @endcan
        </div>
    </div>
</div>

<ul class="nav nav-tabs details-tabs" role="tablist">
    <li role="presentation" class="active">
        <a href="#tab-programacion" aria-controls="tab-programacion" role="tab" data-toggle="tab"><i class="entypo-calendar"></i> Programación</a>
    </li>
    <li role="presentation">
        <a href="#tab-participantes" aria-controls="tab-participantes" role="tab" data-toggle="tab"><i class="entypo-users"></i> Participantes</a>
    </li>
    <li role="presentation">
        <a href="#tab-documentos" aria-controls="tab-documentos" role="tab" data-toggle="tab"><i class="entypo-download"></i> Documentos</a>
    </li>
    <li role="presentation">
        <a href="#tab-ficha" aria-controls="tab-ficha" role="tab" data-toggle="tab"><i class="entypo-doc-text"></i> Ficha Técnica</a>
    </li>
</ul>
<div class="tab-content details-tab-content">
    <div role="tabpanel" class="tab-pane active" id="tab-programacion">
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
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($existingSessions) === 0)
                        <tr><td colspan="5" class="text-center text-muted">No hay sesiones registradas.</td></tr>
                        @endif
                        @foreach($existingSessions as $i => $s)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td title="{{ $s['location_name'] }}">{{ $s['location_name'] }}</td>
                            <td data-fecha="{{ $s['session_date'] }}"><small class="text-muted">{{ \Carbon\Carbon::parse($s['session_date'])->locale('es')->shortDayName }}</small> @date($s['session_date'])</td>
                            <td>{{ \Carbon\Carbon::parse($s['start_time'])->format('g:i A') }} – {{ \Carbon\Carbon::parse($s['end_time'])->format('g:i A') }}</td>
                            <td class="text-center">{{ $s['duration_hours'] }} hrs</td>
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
    <div role="tabpanel" class="tab-pane" id="tab-participantes">
        @include('pages.admin.participantescursos._content', [
            'cursoprogramado' => $scheduled,
            'participantes' => $participantes,
            'statuses' => $statuses,
            'canEvaluate' => $canEvaluate,
        ])
    </div>
    <div role="tabpanel" class="tab-pane" id="tab-documentos">
        @if($filesByType->isEmpty())
            <p class="text-muted"><i class="entypo-info-circled"></i> Este curso no posee documentos cargados.</p>
        @else
            <div class="doc-grid">
                @foreach($docTypes as $typeId => $meta)
                    @php $file = $filesByType->get($typeId); @endphp
                    <div class="doc-tile {{ $file ? '' : 'doc-tile--empty' }}">
                        <div class="doc-thumb doc-thumb--{{ $typeId }}">
                            <i class="fa {{ $meta['icon'] }}"></i>
                        </div>
                        <div class="doc-info">
                            <span class="doc-title">{{ $meta['label'] }}</span>
                            <span class="doc-sub">{{ $file ? basename($file->path) : 'No disponible' }}</span>
                        </div>
                        @if($file)
                            <a href="{{ url('download/'.$scheduled->course->id.'/'.$typeId) }}" class="btn btn-primary btn-sm doc-download"><i class="fa fa-download"></i> Descargar</a>
                        @else
                            <button type="button" class="btn btn-default btn-sm doc-download" disabled><i class="fa fa-download"></i> Descargar</button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    <div role="tabpanel" class="tab-pane" id="tab-ficha">
        <div class="ficha-actions no-print">
            <button type="button" class="btn btn-success" id="print-ficha-btn"><i class="fa fa-file-pdf-o"></i> Imprimir Ficha Técnica</button>
        </div>
        @include('pages.public.partials.ficha_tecnica_body', ['curso' => $scheduled->course])
    </div>
</div>
@endsection

@section('modals')
    @include('pages.admin.cursosprogramados.delete')
    @include('pages.admin.cursosprogramados.cancel')
    @include('pages.admin.participantescursos.asignar')
    @include('pages.admin.participantes.create')
    @include('pages.admin.participantescursos.update')
    @include('pages.admin.participantescursos.delete')
@stop

@push('JS')
<script src="{{asset('assets/js/evaluation.js')}}"></script>
<script>
var detailSessions = @json($existingSessions);
var detailScheduledId = {{ $scheduled->id }};
var detailCourseTitle = @json($scheduled->course->title);
var detailFacilitatorName = @json($scheduled->facilitator->person->name . ' ' . $scheduled->facilitator->person->last_name);

function detailLocationColor(locationId) {
    var n = parseInt(locationId, 10);
    var hue = isNaN(n) ? 145 : (n * 137.508) % 360;
    return 'hsl(' + Math.round(hue) + ', 65%, 45%)';
}

function detailTime12(t) {
    var parts = String(t).split(':');
    if (parts.length < 2) return t;
    var h = parseInt(parts[0], 10);
    var m = parts[1];
    var period = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return h + ':' + m + ' ' + period;
}

function detailDateLabel(iso) {
    var parts = String(iso).split('-');
    return parts.length === 3 ? parts[2] + '-' + parts[1] + '-' + parts[0] : iso;
}

var detailsCalendarInitialized = false;

function initDetailsCalendar() {
    if (detailsCalendarInitialized || !$('#tab-programacion').hasClass('active')) return;
    detailsCalendarInitialized = true;
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
        columnFormat: { week: 'ddd D/M' },
        eventClick: function(event) { return false; },
        events: function(start, end, timezone, callback) {
            var sessionEvents = detailSessions.map(function(s) {
                return {
                    id: 'session-' + s.id,
                    title: s.location_name,
                    start: s.session_date + 'T' + s.start_time,
                    end: s.session_date + 'T' + s.end_time,
                    color: detailLocationColor(s.location_id),
                    allDay: false,
                    courseName: detailCourseTitle,
                    facilitatorName: detailFacilitatorName,
                    tooltip: [s.location_name, detailCourseTitle, detailFacilitatorName, detailDateLabel(s.session_date) + ' ' + detailTime12(s.start_time) + ' – ' + detailTime12(s.end_time)].filter(Boolean).join(' · ')
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
            element.attr('title', event.tooltip || event.title);
            if (event.rendering === 'background') {
                element.addClass('fc-bgevent-plain');
                return;
            }
            var sub = [event.courseName, event.facilitatorName].filter(Boolean).join(' · ');
            var $content = element.find('.fc-content');
            if (sub && $content.length && !element.closest('.fc-month-view').length) {
                $content.append($('<span class="fc-ev-sub"></span>').text(sub));
            }
        }
    });

    if (detailSessions.length) {
        var dates = detailSessions.map(function(s) { return s.session_date; }).sort();
        $('#details-calendar').fullCalendar('gotoDate', dates[0]);
    }
}

$(function() {
    if ($('#tab-programacion').hasClass('active')) {
        initDetailsCalendar();
    }
    $('a[href="#tab-programacion"]').on('shown.bs.tab', function () {
        initDetailsCalendar();
    });

    $('#print-ficha-btn').on('click', function () {
        document.body.classList.add('printing-ficha');
        window.print();
        document.body.classList.remove('printing-ficha');
    });
    window.addEventListener('afterprint', function () {
        document.body.classList.remove('printing-ficha');
    });
});
</script>
@endpush
