@extends('layouts.admin')

@section('content')
	
	<h3>Facilitadores</h3>

	<a href="javascript:crearFacilitador('{{url('u/facilitadores')}}')" class="btn btn-primary"><i class="fa fa-plus" aria-hidden="true"></i> Nuevo Facilitador</a>
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
            Facilitadores
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
                          <td>{{$user->nombre}}</td>
                          <td>{{$user->apellido}}</td>
                          <td>{{$user->ci}}</td>
                          <td>{{$user->email}}</td>                      
                          <td>
                              <a  title="Más Información" href="javascript:detallesFacilitador('{{url('u/facilitadores/'.$user->id)}}')" class="btn btn-info btn-xs">
                                  <i class="entypo-search"></i>
                              </a>

                              <a  title="Editar Facilitador" href="javascript:editarFacilitador('{{url('u/facilitadores/'.$user->id)}}')" class="btn btn-default btn-xs">
                                  <i class="entypo-pencil"></i>
                              </a>

                              <a  title="Acciones de formacion del facilitador" href="{{url('u/facilitadores/acciones/'.$user->id)}}" class="btn btn-success btn-xs">
                                <i class="entypo-book"></i>
                              </a>

                              <a  title="Eliminar Facilitador" href="javascript:eliminarFacilitador('{{url('u/facilitadores/'.$user->id)}}')" class="btn btn-danger btn-xs">
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
	@include('pages.admin.facilitadores.create')
  @include('pages.admin.facilitadores.update')
	@include('pages.admin.facilitadores.delete')
  @include('pages.admin.facilitadores.details')
@stop