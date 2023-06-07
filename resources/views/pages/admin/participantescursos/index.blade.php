@extends('layouts.admin')

@section('content')	

	<h2><strong>{{$cursoprogramado->curso->titulo}}</strong></h2>
  
  <br>


	<a href="javascript:asignarParticipante('{{url('u/af_programadas/'.$cursoprogramado->id.'/asignarParticipante')}}')" class="btn btn-blue"><i class="fa fa-plus" aria-hidden="true"></i> Asignar participante</a>
  <a href="javascript:crearParticipante('{{url('u/af_programadas/'.$cursoprogramado->id.'/participantes')}}')" class="btn btn-success"><i class="fa fa-plus" aria-hidden="true"></i> Registrar nuevo participante</a>

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
      					<th><i class="fa fa-cogs"></i></th>
      				</tr>
      			</thead>
      			
      			<tbody>
      		        @if(count($participantes) == 0)
                          <tr>
                              <td colspan="7">No se han encontrado resultados...</td>
                          </tr>
                      @endif                  
                          @php
                            //var_dump($estados);
                            //print_r($participantes);
                          @endphp
                      @foreach($participantes as $participante)
                            @php
                              //var_dump($participante->estado);
                            @endphp
                        <tr>
                            <td>{{$participante->participante->nombre}}</td>
                            <td>{{$participante->participante->apellido}}</td>
                            <td>{{$participante->participante->ci}}</td>
                            <td>{{$participante->getEstado()}}</td>
                            <td>
                                <a  title="Editar Estado del Participante" href="javascript:editarEstado('{{url('u/af_programadas/estado-participantes/'.$participante->id)}}')" class="btn btn-default btn-xs">
                                    <i class="entypo-pencil"></i>
                                </a>

                                <a  title="Eliminar Participante" href="javascript:eliminarPCurso('{{url('u/af_programadas/participantes/'.$participante->id)}}')" class="btn btn-danger btn-xs">
                                    <i class="entypo-trash"></i>
                                </a>
                            </td>
                        </tr>
                      @endforeach	
      			</tbody>
      		</table>
        </div>
      </div>
		<div align="center">{!! $participantes->links() !!}</div>			
	</div>
      <a href="{{url("u/af_programadas")}}">
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