@extends('layouts.admin')

@section('content')

@push('JS')
<script>
    function crearSesion(url, date){
        document.getElementById("session-form").reset();
        $(".loader").addClass("hidden");
        $("#session-form").removeClass("hidden");
        $("[name=_method]").val("POST");
        $("#session-label").html("Nueva Sesión");
        $("#session-form").attr("action", url);
        
        if(date){
            $('#fecha_sesion').val(date.format('YYYY-MM-DD'));
        }
        
        $("#session-modal").modal();
    }

    function editarSesion(sessionId, scheduledId){
        $(".loader").removeClass("hidden");
        $("#session-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#session-label").html("Editar Sesión");
        $("#session-form").attr("action", '{{ url("u/af_programadas/sesiones/") }}/' + sessionId);

        var url = '{{ url("u/af_programadas/sesiones/") }}/' + sessionId;
        $.get(url, function(data, status){
            data = JSON.parse(data);
            $('#ubicacion').val(data.location_id);
            $('#fecha_sesion').val(data.session_date);
            $('#hora_inicio').val(data.start_time.substring(0, 5));
            $('#hora_fin').val(data.end_time.substring(0, 5));
            $('#notas').val(data.notes);
            $(".loader").addClass("hidden");
            $("#session-form").removeClass("hidden");
        });

        $("#session-modal").modal();

        $("#session-modal").on("hidden.bs.modal", function () {
            $("#session-form :input").prop('readonly', false);
            $("#session-form select").prop('disabled', false);
            $("#session-aceptar").removeClass("hidden");
        });
    }

    function eliminarSesion(sessionId){
        $(".loader").addClass("hidden");
        $("#session-form-delete").removeClass("hidden");
        $("[name=_method]").val("DELETE");
        $("#session-label-delete").html("Eliminar Sesión");
        $("#session-form-delete").attr("action", '{{ url("u/af_programadas/sesiones/") }}/' + sessionId);
        $("#session-modal-delete").modal();
    }
</script>
@endpush

<div class="row">
    <div class="col-md-12">
        <h3>Sesiones del Curso</h3>
        
        <div class="panel panel-info">
            <div class="panel-heading">
                <div class="panel-title">
                    {{$scheduled->course->title}} - Facilitador: {{$scheduled->facilitator->person->name}} {{$scheduled->facilitator->person->last_name}}
                </div>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Período:</strong> {{date("d-m-Y", strtotime($scheduled->start_date))}} al {{date("d-m-Y", strtotime($scheduled->end_date))}}
                    </div>
                    <div class="col-md-4">
                        <strong>Duración Total:</strong> {{$scheduled->course->duration}} horas
                    </div>
                    <div class="col-md-4">
                        <strong>Horas Asignadas:</strong> {{$scheduled->totalSessionHours()}} / {{$scheduled->course->duration}} horas
                    </div>
                </div>
                <div class="row" style="margin-top: 10px;">
                    <div class="col-md-12">
                        <div class="progress">
                            @php
                                $percentage = $scheduled->course->duration > 0 ? ($scheduled->totalSessionHours() / $scheduled->course->duration) * 100 : 0;
                                $progressClass = 'progress-bar-info';
                                if($percentage >= 100) $progressClass = 'progress-bar-success';
                                elseif($percentage > 75) $progressClass = 'progress-bar-info';
                                elseif($percentage > 50) $progressClass = 'progress-bar-warning';
                                else $progressClass = 'progress-bar-danger';
                            @endphp
                            <div class="progress-bar {{$progressClass}}" role="progressbar" aria-valuenow="{{$percentage}}" aria-valuemin="0" aria-valuemax="100" style="width: {{min($percentage, 100)}}%">
                                {{round($percentage)}}%
                            </div>
                        </div>
                        @if($scheduled->remainingHours() > 0)
                            <span class="text-danger"><strong>Horas restantes: {{$scheduled->remainingHours()}} horas</strong></span>
                        @else
                            <span class="text-success"><strong>Todas las horas han sido asignadas</strong></span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <a href="javascript:crearSesion('{{url('u/af_programadas/'.$scheduled->id.'/sesiones')}}', null)" class="btn btn-primary"><i class="fa fa-plus" aria-hidden="true"></i> Nueva Sesión</a>
        <a href="{{url('u/af_programadas')}}" class="btn btn-default"><i class="fa fa-arrow-left" aria-hidden="true"></i> Volver</a>

        <br><br>

        <div class="panel panel-success" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    Calendario de Sesiones
                </div>
            </div>
            <div class="panel-body">
                <div id="calendar"></div>
            </div>
        </div>

        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    Lista de Sesiones
                </div>
            </div>
            <div class="panel-body with-table">
                <table class="table table-striped table-bordered table-center">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora Inicio</th>
                            <th>Hora Fin</th>
                            <th>Duración</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Notas</th>
                            <th><i class="fa fa-cogs"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($scheduled->sessions) == 0)
                            <tr>
                                <td colspan="8">No se han encontrado sesiones...</td>
                            </tr>
                        @endif
                        @foreach($scheduled->sessions as $session)
                            <tr>
                                <td>{{date("d-m-Y", strtotime($session->session_date))}}</td>
                                <td>{{date("h:i A", strtotime($session->start_time))}}</td>
                                <td>{{date("h:i A", strtotime($session->end_time))}}</td>
                                <td>{{$session->durationHours()}} hrs</td>
                                <td>{{$session->location->name}}</td>
                                <td>
                                    @if($session->status == 'scheduled')
                                        <span class="badge badge-info">Programada</span>
                                    @elseif($session->status == 'completed')
                                        <span class="badge badge-success">Completada</span>
                                    @elseif($session->status == 'cancelled')
                                        <span class="badge badge-danger">Cancelada</span>
                                    @endif
                                </td>
                                <td>{{$session->notes}}</td>
                                <td>
                                    <a title="Editar sesión" href="javascript:editarSesion('{{$session->id}}', '{{$scheduled->id}}')" class="btn btn-default btn-xs">
                                        <i class="entypo-pencil"></i>
                                    </a>
                                    <a title="Eliminar sesión" href="javascript:eliminarSesion('{{$session->id}}')" class="btn btn-danger btn-xs">
                                        <i class="entypo-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@section('modals')
<div class="modal fade" id="session-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="session-label"></h4>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>
            <form class="form-horizontal hidden" method="POST" id='session-form' enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">

                <div class="modal-body">
                    <div class="form-group">
                        <div class="col-lg-12 col-md-12">
                            {{ Form::label('ubicacion', 'Ubicación') }}
                            {{ Form::select('ubicacion', $locations->pluck('name', 'id'), null, ['class' => 'form-control select2', 'id' => 'ubicacion', 'required', 'placeholder' => 'Seleccionar ubicación']) }}
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-12 col-md-12">
                            {{ Form::label('fecha_sesion', 'Fecha de la Sesión') }}
                            {{ Form::text('fecha_sesion', null, ['class' => 'form-control input-lg dat2', 'id' => 'fecha_sesion', 'required', 'autocomplete' => 'off']) }}
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-6 col-md-6">
                            {{ Form::label('hora_inicio', 'Hora de Inicio') }}
                            {{ Form::text('hora_inicio', null, ['class' => 'form-control timepicker', 'id' => 'hora_inicio', 'required', 'data-show-meridian' => 'true', 'data-default-time' => 'false', 'data-minute-step' => '15']) }}
                        </div>
                        <div class="col-lg-6 col-md-6">
                            {{ Form::label('hora_fin', 'Hora de Fin') }}
                            {{ Form::text('hora_fin', null, ['class' => 'form-control timepicker', 'id' => 'hora_fin', 'required', 'data-show-meridian' => 'true', 'data-default-time' => 'false', 'data-minute-step' => '15']) }}
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-12 col-md-12">
                            {{ Form::label('notas', 'Notas') }}
                            {{ Form::textarea('notas', null, ['class' => 'form-control', 'id' => 'notas', 'rows' => '3', 'maxlength' => '500']) }}
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style='text-align: center;'>
                    {{ Form::submit('Aceptar', ['class' => 'btn btn-primary', 'id' => 'session-aceptar']) }}
                    <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="session-modal-delete" tabindex="-1" role="dialog" aria-labelledby="session-modal-delete" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3 class="modal-title" id="session-label-delete">Eliminar</h3>
            </div>
            <div class="loader text-center">
                <i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Loading...</span>
            </div>

            <div class="modal-body">
                <p>¿Está seguro que desea eliminar esta sesión?</p>
            </div>

            <div class="modal-footer">
                <form class="form-horizontal hidden" method="POST" id='session-form-delete'>
                    {!! csrf_field() !!}
                    <input type="hidden" name="_method" value="POST">
                    <button class="btn btn-danger" id="btn-action">Eliminar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@push('JS')
<script src="{{url('assets/js/bootstrap-datepicker.js')}}"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2();
        
        $('.dat2').datepicker({
            format: "yyyy-mm-dd",
            todayHighlight: true
        });

        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            defaultView: 'agendaWeek',
            slotMinutes: 15,
            allDaySlot: false,
            minTime: '06:00:00',
            maxTime: '22:00:00',
            events: '{{ url("u/af_programadas/" . $scheduled->id . "/sesiones/data") }}',
            eventClick: function(event) {
                editarSesion(event.id, '{{$scheduled->id}}');
            },
            dayClick: function(date) {
                crearSesion('{{url('u/af_programadas/'.$scheduled->id.'/sesiones')}}', date);
            },
            eventRender: function(event, element) {
                element.attr('title', event.title);
            }
        });
    });
</script>
@endpush
