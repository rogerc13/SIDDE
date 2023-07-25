@push('JS')
    <script src="{{asset('assets/js/evaluation.js')}}"></script>
@endpush
@extends('layouts.admin')
@section('content')	

	<h2><strong>{{$cursoprogramado->course->title}}</strong></h2>
  
  <br>
  @if (!Auth::user()->isFacilitador())
    <a href="javascript:asignarParticipante('{{url('u/af_programadas/'.$cursoprogramado->id.'/asignarParticipante')}}')" class="btn btn-blue"><i class="fa fa-plus" aria-hidden="true"></i> Asignar participante</a>
    <a href="javascript:crearParticipante('{{url('u/af_programadas/'.$cursoprogramado->id.'/participantes')}}')" class="btn btn-success"><i class="fa fa-plus" aria-hidden="true"></i> Registrar nuevo participante</a>    
  @else
    <div class="btn btn-success evaluate-button"><i class="fa fa-check" aria-hidden="true"></i><span> Evaluar Participantes</span></div>
  @endif
  <br>
  <br>
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
                @if (!Auth::User()->isFacilitador())
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
                            <td><span class="form-control">{{$participante->person->name}}</span></td>
                            <td><span class="form-control">{{$participante->person->last_name}}</span></td>
                            <td><span class="form-control">{{$participante->person->id_format()}}</span></td>
                            <td>
                              <select name="participant_status" id="participant_status" class="form-control" disabled>
                                    @foreach ($statuses as $status)
                                        <option course_id="{{$cursoprogramado->id}}" participant_id="{{$participante->id}}" value="{{$status->id}}" 
                                            @if ($status->id == $participante->participant_status_id)
                                            selected 
                                        @endif>
                                        <span>{{$status->name}}</span></option>
                                    @endforeach
                              </select>  
                            </td>
                            @if (!Auth::User()->isFacilitador())
                            <td>
                              
                                <a  title="Editar Estado del Participante" href="javascript:editarEstado('{{url('u/af_programadas/estado-participantes/'.$participante->id)}}')" class="btn btn-default btn-xs">
                                    <i class="entypo-pencil"></i>
                                </a>
                                <a  title="Eliminar Participante" href="javascript:eliminarPCurso('{{url('u/af_programadas/participantes/'.$participante->id)}}')" class="btn btn-danger btn-xs">
                                    <i class="entypo-trash"></i>
                                </a>    
                                
                                
                            </td>
                            @endif
                        </tr>
                      @endforeach	
      			</tbody>
      		</table>
        </div>
      </div>
		<div align="center">{!! $participantes->links() !!}</div>			
	</div>
      <a href="{{Auth::user()->isFacilitador() ? url("u/mis_acciones") : url("u/af_programadas")}}">
    <center><button type="button" class="btn btn-info" ><i class="entypo-back" aria-hidden="true"></i> Regresar</button></center> 
    </a>			
</div>
@stop

@section('modals')
	@include('pages.admin.participantes.create')
  @include('pages.admin.participantescursos.update')
	@include('pages.admin.participantescursos.delete')
  @include('pages.admin.participantescursos.asignar')
@stop