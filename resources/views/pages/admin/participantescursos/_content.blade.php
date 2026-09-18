<br>
@if (Auth::user()->can('store', 'App\Models\Participant'))
    <a href="javascript:asignarParticipanteLista('{{url('u/af_programadas/'.$cursoprogramado->id.'/asignarParticipante')}}','{{$cursoprogramado->id}}')" value="{{$cursoprogramado->id}}" class="btn btn-blue {{($cursoprogramado->atMaxCapacity() || !$cursoprogramado->isPorDictar()) ? 'disabled' :''}}"><i class="fa fa-plus" aria-hidden="true"></i> Asignar participante</a>
    <a href="javascript:crearParticipante('{{url('u/af_programadas/'.$cursoprogramado->id.'/participantes')}}')" class="btn btn-success {{($cursoprogramado->atMaxCapacity() || !$cursoprogramado->isPorDictar()) ? 'disabled' :''}}"><i class="fa fa-plus" aria-hidden="true"></i> Registrar nuevo participante</a>
@endif
<a href="{{url('u/pdf/participants/'.$cursoprogramado->id)}}" class="btn btn-success"><i class="fa fa-file-pdf-o " aria-hidden="true"></i> Imprimir Lista de Participantes</a>
<br>
<br>
<div id="evaluation-feedback" class="alert hidden" role="alert"></div>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-success" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    Lista de participantes
                </div>
            </div>
            <div class="panel-body with-table table-responsive">
                <table class="table table-striped table-bordered table-center">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>C.I</th>
                            <th>Estado</th>
                            @if (!Auth::User()->isFacilitador() && ($canEvaluate || Auth::user()->can('delete', 'App\Models\Participant')))
                                <th><i class="fa fa-cogs"></i></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($participantes) == 0)
                            <tr>
                                <td colspan="7">No se han encontrado resultados...</td>
                            </tr>
                        @endif
                        @foreach($participantes as $participante)
                            <tr>
                                <td><span class="">{{$participante->person->name}}</span></td>
                                <td><span class="">{{$participante->person->last_name}}</span></td>
                                <td><span class="">{{$participante->person->id_type_id == 1 ? 'V' : 'E'}}-{{$participante->person->id_format()}}</span></td>
                                <td>
                                    <select name="participant_status[{{$participante->id}}]" class="participant_status form-control"
                                        data-participant-id="{{$participante->id}}"
                                        data-current-status-id="{{$participante->participant_status_id}}"
                                        data-url="{{url('u/af_programadas/estado-participantes/'.$participante->id)}}"
                                        @if (!$canEvaluate)
                                        disabled
                                        @endif>
                                        @foreach ($statuses as $status)
                                            <option value="{{$status->id}}"
                                                @if ($status->id == $participante->participant_status_id)
                                                selected
                                            @endif>{{$status->name}}</option>
                                        @endforeach
                                    </select>
                                    <span class="participant_status_helper badge badge-{{$participante->badgeStatus()}}">{{$participante->participantStatus->name}}</span>
                                </td>
                                @if (!Auth::User()->isFacilitador() && ($canEvaluate || Auth::user()->can('delete', 'App\Models\Participant')))
                                    <td>
                                        @if ($canEvaluate)
                                            <a  title="Editar Estado del Participante" href="javascript:editarEstado({{$participante->id}})" class="btn btn-default btn-xs">
                                                <i class="entypo-pencil"></i>
                                            </a>
                                        @endif
                                        @if (Auth::user()->can('delete', 'App\Models\Participant') && !$cursoprogramado->isCancelado() && !$cursoprogramado->isCulminado())
                                            <a  title="Eliminar Participante" href="javascript:eliminarPCurso('{{url('u/af_programadas/participantes/'.$participante->id)}}')" class="btn btn-danger btn-xs">
                                                <i class="entypo-trash"></i>
                                            </a>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
