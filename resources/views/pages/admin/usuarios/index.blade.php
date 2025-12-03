@extends('layouts.admin')

@section('content')
	
	<h3>Usuarios</h3>

	<a href="javascript:crearUsuario('{{url('u/usuarios')}}')" class="btn btn-primary"><i class="fa fa-plus" aria-hidden="true"></i> Nuevo Usuario</a>

  <form action="pdf/users" method="GET" style='all:unset'>
			@csrf
			<input type="hidden" id="hidden_name" name="hidden_name" value="{{request()->nombres}}">
			<input type="hidden" id="hidden_surname" name="hidden_surname" value="{{request()->apellidos}}">
			<input type="hidden" id="hidden_role" name="hidden_role" value="{{request()->busqueda_rol}}">
			<input type="hidden" id="hidden_id" name="hidden_id" value="{{request()->cis}}">
			<input type="hidden" id="hidden_id_type" name="hidden_id_type" value="{{request()->id_type}}">
			<button class="btn btn-blue print-list-button"><i class="fa fa-file-pdf-o"></i> Descargar Lista de Usuarios</button> 
  </form>

<!--   <a href="{{url('u/pdf/users')}}" class="btn btn-blue print-list-button"><i class="glyphicon glyphicon-print"></i> Descargar Lista de Usuarios</a> -->
  <br>
  <br>
<div class="row filtros">           
  <div class="col-md-12">
  <form class="form-horizontal" method="GET" action="{{ url('u/usuarios') }}">
  <div class="form-group" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem;">

            <div class="col-md-3 col-sm-6 col-xs-12">
              <label for="nombres" class="control-label">Nombre</label>              
              <input type="text" class="form-control" id="nombres" name="nombres" value="{{$nombres}}" />                
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
              <label for="apellidos" class="control-label">Apellido</label>              
              <input type="text" class="form-control" id="apellidos" name="apellidos" value="{{$apellidos}}" />                
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
              <label for="busqueda_rol" class="control-label">Rol</label>              
              <select name="busqueda_rol" class="form-control " id="busqueda_rol" >
                                <option value='0'>Todos</option>
                  @foreach($roles as $key => $value)
                      @if($key == $busqueda_rol)
                      <option value="{{$key}}" selected>{{$value}}</option>
                      @else
                      <option value="{{$key}}">{{$value}}</option>
                      @endif
                  @endforeach
              </select>               
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
              <label for="cis" class="control-label">C.I</label>
              <div class="input-group">
                <div class="input-group-btn">
                    <select name="id_type" id="id_type" class="form-select input-group-text btn btn-default btn-lg 
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
          <div class="col-md-1 col-sm-12 col-xs-12" style="min-width:60px;">
            <button type="button" class="btn btn-default btn-lg" onclick="window.location='{{ url('u/usuarios') }}'">
                <i class="fa fa-refresh"></i>
            </button> 
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
            Usuarios
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
    					<th>Rol</th>
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
                          <td>{{$user->role->name}}</td>
                          <td>
                              <a  title="Más Información" href="javascript:detallesUsuario('{{url('u/usuarios/'.$user->id)}}')" class="btn btn-info btn-xs">
                                  <i class="entypo-search"></i>
                              </a>

                              <a  title="Editar usuario" href="javascript:editarUsuario('{{url('u/usuarios/'.$user->id)}}')" class="btn btn-default btn-xs">
                                  <i class="entypo-pencil"></i>
                              </a>

                              <a  title="Eliminar usuario" href="javascript:eliminarUsuario('{{url('u/usuarios/'.$user->id)}}')" class="btn btn-danger btn-xs">
                                  <i class="entypo-trash"></i>
                              </a>
                          </td>
                      </tr>
                    @endforeach	
    			</tbody>
    		</table>
      </div>
    </div>
		<div align="center">{!! $users->appends(['nombres' => $nombres, 'apellidos' => $apellidos, 'busqueda_rol' => $busqueda_rol, 'cis' => $cis])->links() !!}</div>			
	</div>			
</div>
@stop

@section('modals')
	@include('pages.admin.usuarios.create')
  @include('pages.admin.usuarios.update')
	@include('pages.admin.usuarios.delete')
  @include('pages.admin.usuarios.details')
@stop