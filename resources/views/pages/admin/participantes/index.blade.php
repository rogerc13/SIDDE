@extends('layouts.admin')

@section('content')
	
	<h3>Participantes</h3>

	<a href="javascript:crearParticipante('{{url('u/participantes')}}')" class="btn btn-primary"><i class="fa fa-plus" aria-hidden="true"></i> Nuevo Participante</a>

  <form action="pdf/participants" method="GET" style='all:unset'>
			@csrf
			<input type="hidden" id="hidden_name" name="hidden_name" value="{{request()->nombres}}">
			<input type="hidden" id="hidden_surname" name="hidden_surname" value="{{request()->apellidos}}">
			<input type="hidden" id="hidden_id" name="hidden_id" value="{{request()->cis}}">
			<input type="hidden" id="hidden_id_type" name="hidden_id_type" value="{{request()->id_type_search}}">
			<button class="btn btn-blue print-list-button"><i class="fa fa-file-pdf-o"></i> Descargar Lista de Usuarios</button> 
  </form>

  <!-- <a href="{{url('u/pdf/participants')}}" class="btn btn-blue print-list-button"><i class="glyphicon glyphicon-print"></i> Descargar Lista de Participantes</a> -->

  <br>
  <br>

<div class="row filtros">           
  <div class="col-md-12">
    <form class="form-horizontal">
      <div class="form-group">

            <div class="col-md-3 col-sm-6 col-xs-12">
              <label for="nombres" class="control-label">Nombre</label>              
              <input type="text" class="form-control" id="nombres" name="nombres" value="{{$nombres}}" />                
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
              <label for="apellidos" class="control-label">Apellido</label>              
              <input type="text" class="form-control" id="apellidos" name="apellidos" value="{{$apellidos}}" />                
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
              <label for="cis" class="control-label">C.I</label>
              <div class="input-group">
                <div class="input-group-btn">
                    <select name="id_type_search" id="id_type_search" class="form-select input-group-text btn btn-default btn-lg 
                    dropdown-toggle">
                        @foreach ($types as $type)
                        <option value="{{$type->id}}">{{$type->inital()}}</option>
                        @endforeach  
                    </select>
                </div>
                <input type="text" class="form-control" id="cis" name="cis" value="{{$cis}}" />
                <span class="input-group-btn"> <button class="btn btn-primary btn-lg" type="submit"><i class='entypo-search'></i></button> </span>
              </div>
            </div>
          </div>
      <!-- <div class="col-xs-12 margin-top text-center" style="margin-bottom: 10px;">
                <button type='submit' class='btn btn-primary'>Buscar <i class='fa fa-lg fa-search'></i></button>
          </div> -->
        </form> 
  </div>
</div>
<div class="row">						
	<div class="col-md-12">

    <div class="panel panel-success" data-collapsed="0">  

      <div class="panel-heading">
        <div class="panel-title">
            Participantes
        </div>
      </div>
      
      <div class="panel-body with-table table-responsive">  							
    		<table class="table table-striped table-bordered table-center">
    			<thead>
    				<tr>
    					<th>Nombre</th>
    					<th>Apellido</th>
    					<th>C.I</th>
    					<th>Correo</th>					
    					<th><i class="fa fa-cogs"></i></th>
    				</tr>
    			</thead>
    			
    			<tbody>
    		        @if(count($users) == 0)
                        <tr>
                            <td colspan="7">No se han encontrado resultados...</td>
                        </tr>
                    @endif
                    @foreach($users as $user)
                      <tr>
                          <td>{{$user->person->name}}</td>
                          <td>{{$user->person->last_name}}</td>
                          <td>{{$user->person->id_type_id  == 1 ? 'V' : 'E'}}-{{$user->person->id_format()}}</td>
                          <td>{{$user->email}}</td>                      
                          <td>
                              <a  title="Más Información" href="javascript:detallesParticipante('{{url('u/participantes/'.$user->id)}}')" class="btn btn-info btn-xs">
                                  <i class="entypo-search"></i>
                              </a>

                              <a  title="Editar Participante" href="javascript:editarParticipante('{{url('u/participantes/'.$user->id)}}')" class="btn btn-default btn-xs">
                                  <i class="entypo-pencil"></i>
                              </a>

                              <a  title="Acciones de formacion del Participante" href="{{url('u/participantes/acciones/'.$user->id)}}" class="btn btn-success btn-xs">
                                <i class="entypo-book"></i>
                              </a>

                              <a  title="Eliminar Participante" href="javascript:eliminarParticipante('{{url('u/participantes/'.$user->id)}}')" class="btn btn-danger btn-xs">
                                  <i class="entypo-trash"></i>
                              </a>
                          </td>
                      </tr>
                    @endforeach	
    			</tbody>
    		</table>
      </div>
    </div>
		<div align="center">{!! $users->appends(['nombres' => $nombres, 'apellidos' => $apellidos, 'cis' => $cis])->links() !!}</div>			
	</div>			
</div>
@stop

@section('modals')
	@include('pages.admin.participantes.create')
  @include('pages.admin.participantes.update')
	@include('pages.admin.participantes.delete')
  @include('pages.admin.participantes.details')
@stop