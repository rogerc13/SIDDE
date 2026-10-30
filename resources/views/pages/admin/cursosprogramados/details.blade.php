@extends('layouts.admin')

@push('CSS')
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

    /* Static weekly schedule grid */
    .semana-caption { margin-bottom: 10px; font-size: 12px; color: #555; }
    .semana-caption strong { display: block; font-size: 14px; color: #333; margin-bottom: 2px; }
    .semana-caption > span { display: block; }
    .semana-grid { display: flex; border: 1px solid #d5dbe1; border-radius: 3px; overflow: hidden; background: #fff; }
    .semana-gutter { width: 58px; flex-shrink: 0; border-right: 1px solid #d5dbe1; background: #fafbfc; }
    .semana-gutter-spacer { height: 28px; border-bottom: 1px solid #d5dbe1; }
    .semana-hour { height: 40px; font-size: 10px; color: #7a8794; text-align: right; padding-right: 6px; box-sizing: border-box; border-bottom: 1px solid #eef1f4; }
    .semana-days { display: flex; flex: 1; }
    .semana-day { flex: 1 1 0; min-width: 0; border-right: 1px solid #eef1f4; }
    .semana-day:last-child { border-right: 0; }
    .semana-day-head { height: 28px; line-height: 28px; text-align: center; font-size: 11px; font-weight: 600; background: #f5f7f9; border-bottom: 1px solid #d5dbe1; }
    .semana-day-body { position: relative; height: 640px; background-image: repeating-linear-gradient(to bottom, transparent, transparent 39px, #eef1f4 39px, #eef1f4 40px); }
    .semana-block { position: absolute; box-sizing: border-box; min-width: 0; background: #eef8f1; border: 1px solid #27ae60; border-left: 3px solid #27ae60; border-radius: 3px; padding: 3px 5px; font-size: 9px; line-height: 1.25; overflow: hidden; color: #2c3e50; }
    .semana-block span { display: block; }
    .semana-block--split { padding: 2px 3px; font-size: 8px; }
    .semana-block--split .sb-course, .semana-block--split .sb-fac { display: none; }
    .semana-block .sb-time { font-weight: 700; }
    .semana-block .sb-aula { color: #1f7a44; }
    .semana-block .sb-dates { color: #444; margin-top: 2px; }
    .calendar-legend { margin-top: 8px; font-size: 12px; color: #777; }
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

        @page { size: landscape; margin: 10mm; }
    }
    .ficha-actions { margin-bottom: 14px; text-align: right; }
    .horario-actions { margin-bottom: 14px; text-align: right; }
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
    $backUrl = (Auth::user()->isFacilitador() || Auth::user()->isParticipante()) ? url('u/mis_acciones') : url('u/af_programadas');
    $canManage = Auth::user()->isAdministrador() || Auth::user()->isTecEducativa() || Auth::user()->isProgramador();
    $isParticipant = Auth::user()->isParticipante();
    $docTypes = [
        1 => ['label' => 'Manual del Facilitador', 'icon' => 'fa-file-pdf-o'],
        2 => ['label' => 'Manual del Participante', 'icon' => 'fa-file-pdf-o'],
        3 => ['label' => 'Guía', 'icon' => 'fa-file-pdf-o'],
        4 => ['label' => 'Presentación', 'icon' => 'fa-file-pdf-o'],
    ];
    $filesByType = $scheduled->course->file->keyBy('type_id');
    if ($isParticipant) {
        $docTypes = array_intersect_key($docTypes, [2 => true]);
        $filesByType = $filesByType->only([2]);
    }

    $dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    $printGroups = $scheduled->weeklySessionLayout();
    $printHorarioCourse = $scheduled->course->title;
    $printHorarioFacilitator = trim($scheduled->facilitator->person->name . ' ' . $scheduled->facilitator->person->last_name);
@endphp

<div class="details-header">
    <div class="details-header-main">
        <h3>
            {{ $scheduled->course->title }}
            <span class="badge badge-{{ $scheduled->badgeStatus() }}">{{ $scheduled->courseStatus->name }}</span>
        </h3>
        @if(!$isParticipant && $createdAt)
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
    @if(!$isParticipant)
    <div class="col-sm-3 col-xs-6">
        <div class="stat-tile">
            <span class="stat-label"><i class="entypo-users"></i> Registrados</span>
            <span class="stat-value">{{ $participantsCount }} @if($capacityMax)/ {{ $capacityMax }}@endif</span>
            @can('getAllPorCurso','App\Models\Participant')
                <a class="stat-sub no-print" href="{{ url('u/af_programadas/'.$scheduled->id.'/participantes') }}">gestionar participantes</a>
            @endcan
        </div>
    </div>
    @endif
</div>

<ul class="nav nav-tabs details-tabs" role="tablist">
    <li role="presentation" class="active">
        <a href="#tab-programacion" aria-controls="tab-programacion" role="tab" data-toggle="tab"><i class="entypo-calendar"></i> Programación</a>
    </li>
    @if(!$isParticipant)
    <li role="presentation">
        <a href="#tab-participantes" aria-controls="tab-participantes" role="tab" data-toggle="tab"><i class="entypo-users"></i> Participantes</a>
    </li>
    @endif
    <li role="presentation">
        <a href="#tab-documentos" aria-controls="tab-documentos" role="tab" data-toggle="tab"><i class="entypo-download"></i> Documentos</a>
    </li>
    <li role="presentation">
        <a href="#tab-ficha" aria-controls="tab-ficha" role="tab" data-toggle="tab"><i class="entypo-doc-text"></i> Ficha Técnica</a>
    </li>
</ul>
<div class="tab-content details-tab-content">
    <div role="tabpanel" class="tab-pane active" id="tab-programacion">
        <div class="horario-actions no-print">
            <a href="{{ url('u/pdf/schedule/'.$scheduled->id) }}" class="btn btn-success"><i class="fa fa-file-pdf-o"></i> Descargar horario</a>
        </div>
        <div class="panel panel-success" data-collapsed="0" id="panel-sesiones">
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

        <div class="panel panel-success semana-panel" data-collapsed="0" style="margin-top: 15px;">
            <div class="panel-heading no-print">
                <div class="panel-title">Calendario semanal</div>
            </div>
            <div class="panel-body">
                <div id="details-semana">
                    <div class="semana-caption">
                        <strong>{{ $scheduled->course->title }}</strong>
                        <span>{{ $printHorarioFacilitator }} <span aria-hidden="true">·</span> @date($scheduled->start_date) <span aria-hidden="true">→</span> @date($scheduled->end_date)</span>
                    </div>
                    @include('pages.admin.cursosprogramados._semana_grid', [
                        'printGroups' => $printGroups,
                        'dayNames' => $dayNames,
                        'printHorarioCourse' => $printHorarioCourse,
                        'printHorarioFacilitator' => $printHorarioFacilitator,
                    ])
                </div>
                <div class="calendar-legend no-print">
                    <span class="legend-swatch" style="background:#27ae60;"></span>Sesiones de este curso
                </div>
            </div>
        </div>
    </div>
    @if(!$isParticipant)
    <div role="tabpanel" class="tab-pane" id="tab-participantes">
        @include('pages.admin.participantescursos._content', [
            'cursoprogramado' => $scheduled,
            'participantes' => $participantes,
            'statuses' => $statuses,
            'canEvaluate' => $canEvaluate,
        ])
    </div>
    @endif
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
    @if($canManage)
        @include('pages.admin.cursosprogramados.delete')
        @include('pages.admin.cursosprogramados.cancel')
    @endif
    @if(!$isParticipant)
        @include('pages.admin.participantescursos.asignar')
        @include('pages.admin.participantes.create')
        @include('pages.admin.participantescursos.update')
        @include('pages.admin.participantescursos.delete')
    @endif
@stop

@push('JS')
@if(!$isParticipant)
<script src="{{asset('assets/js/evaluation.js')}}"></script>
@endif
<script>
$(function() {
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
