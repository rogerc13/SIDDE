@extends('layouts.admin')

@section('content')	

	<h3>Ubicaciones</h3>

	<a href="javascript:crearLocation('{{url('u/ubicaciones')}}')" class="btn btn-primary"><i class="fa fa-plus" aria-hidden="true"></i> Nueva Ubicación</a>

  <br>
  <br>
  <div class="row filtros">						
	<div class="col-md-12">
		<form class="form-horizontal" method="GET" action="{{ url('u/ubicaciones') }}">
			  <div class="form-group" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem;">
		      	<div class="col-md-4 col-sm-6 col-xs-12">
		          <label for="name" class="control-label">Nombre</label>
		          <div class="input-group">
		          	<input type="text" class="form-control" id="name" name="name" value="{{$name}}" />
		          	<span class="input-group-btn">
						<button class="btn btn-primary btn-lg" type="submit"><i class='entypo-search'></i></button>
					</span>
		          </div>
		        </div>
				<div class="col-md-2 col-sm-12 col-xs-12" style="min-width:120px;">
					<button type="button" class="btn btn-default btn-lg" onclick="window.location='{{ url('u/ubicaciones') }}'">
						<i class="fa fa-refresh"></i>
					</button>
				</div>
	       	</div>
      	</form>	
	</div>
</div>
<div class="row">						
	<div class="col-md-12">		
    <div class="panel panel-success" data-collapsed="0">  

      <div class="panel-heading">
        <div class="panel-title">
          Ubicaciones
        </div>
      </div>
            
      <div class="panel-body with-table">						
    		<table class="table table-striped table-bordered table-center">
    			<thead>
    				<tr>
    					<th>Nombre</th>
    					<th><i class="fa fa-cogs"></i></th>
    				</tr>
    			</thead>
    			
    			<tbody>
    		        @if(count($locations) == 0)
                        <tr>
                            <td colspan="2">No se han encontrado resultados...</td>
                        </tr>
                    @endif
                    @foreach($locations as $location)
                      <tr>
                          <td>{{$location->name}}</td>
                          <td>
                              <a  title="Editar ubicación" href="javascript:editarLocation('{{url('u/ubicaciones/'.$location->id)}}')" class="btn btn-default btn-xs">
                                  <i class="entypo-pencil"></i>
                              </a>

                              <a  title="Eliminar ubicación" href="javascript:eliminarLocation('{{url('u/ubicaciones/'.$location->id)}}')" class="btn btn-danger btn-xs">
                                  <i class="entypo-trash"></i>
                              </a>
                          </td>
                      </tr>
                    @endforeach	
    			</tbody>
    		</table>
      </div>
    </div>
		<div align="center">{!! $locations->links() !!}</div>			
	</div>			
</div>
@stop

@section('modals')
	@include('pages.admin.ubicaciones.create')
  @include('pages.admin.ubicaciones.update')
	@include('pages.admin.ubicaciones.delete')
  @include('pages.admin.ubicaciones.details')
@stop
