@extends('layouts.admin')

@section('content')	

	<h3>Áreas de Conocimiento</h3>

	<a href="javascript:crearCategoria('{{url('u/areas')}}')" class="btn btn-primary"><i class="fa fa-plus" aria-hidden="true"></i> Nueva Área de Conocimiento</a>
  	<form action="pdf/categories" method="GET" style='all:unset'>
			@csrf
			<input type="hidden" id="hidden_name" name="hidden_name" value="{{request()->name}}" />

			<button class="btn btn-blue print-list-button"><i class="fa fa-file-pdf-o"></i> Descargar Lista de Áreas de Conocimientos</button> 
	</form>
  <br>
  <br>
  <div class="row filtros">						
	<div class="col-md-12">
		<form class="form-horizontal" method="GET" action="{{ url('u/areas') }}">
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
					<button type="button" class="btn btn-default btn-lg" onclick="window.location='{{ url('u/areas') }}'">
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
          Áreas de Conocimiento
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
    		        @if(count($categorias) == 0)
                        <tr>
                            <td colspan="7">No se han encontrado resultados...</td>
                        </tr>
                    @endif
                    @foreach($categorias as $categoria)
                      <tr>
                          <td>{{$categoria->name}}</td>
                          <td>
                        <!--<a  title="Más Información" href="javascript:detallesCategoria('{{url('u/areas/'.$categoria->id)}}')" class="btn btn-info btn-xs">
                                  <i class="entypo-search"></i>
                              </a> -->

                              <a  title="Editar área de conocimiento" href="javascript:editarCategoria('{{url('u/areas/'.$categoria->id)}}')" class="btn btn-default btn-xs">
                                  <i class="entypo-pencil"></i>
                              </a>

                              <a  title="Eliminar área de conocimiento" href="javascript:eliminarCategoria('{{url('u/areas/'.$categoria->id)}}')" class="btn btn-danger btn-xs">
                                  <i class="entypo-trash"></i>
                              </a>
                          </td>
                      </tr>
                    @endforeach	
    			</tbody>
    		</table>
      </div>
    </div>
		<div align="center">{!! $categorias->links() !!}</div>			
	</div>			
</div>
@stop

@section('modals')
	@include('pages.admin.categorias.create')
  @include('pages.admin.categorias.update')
	@include('pages.admin.categorias.delete')
  @include('pages.admin.categorias.details')
@stop