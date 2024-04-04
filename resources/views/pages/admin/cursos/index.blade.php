@extends('layouts.admin')
@push('JS')

@endpush
@section('content')
	<h3>Acciones de Formación</h3>
	<a href="javascript:crearAccion('{{url('u/acciones_formacion')}}')" class="btn btn-primary"><i class="fa fa-plus" aria-hidden="true"></i> Nueva acción de formación</a>
	<form action="pdf/courses" method="GET" style='all:unset'>
			@csrf
			<input type="hidden" id="hidden_category" name="hidden_category" value="{{request()->id_areas}}">
			<input type="hidden" id="hidden_title" name="hidden_title" value="{{(request()->titulos)}}">
			<button class="btn btn-blue print-list-button"><i class="fa fa-file-pdf-o"></i> Descargar Lista de Acciones de Formación</button> 
	</form>
	<br>
	<br>

<div class="row filtros">						
	<div class="col-md-12">
		<form class="form-horizontal">
			<div class="form-group">

		        <div class="col-md-4 col-sm-6 col-xs-12">
		          <label for="id_areas" class="control-label">Área</label>
		            <select name="id_areas" class="select2 " id="id_areas" data-allow-clear="true" required="true">
								<option value='0'>Todos</option>
		                @foreach($categorias as $categ)                                    
		                    @if($categ->id == $busqueda_area)
		                        <option value="{{$categ->id}}" selected>{{$categ->name}}</option>
		                    @else                                                
		                        <option value="{{$categ->id}}">{{$categ->name}}</option>
		                    @endif
		                @endforeach
		            </select>  
		       	</div>
		      	<div class="col-md-4 col-sm-6 col-xs-12">
		          <label for="titulos" class="control-label">Título</label>
		          <div class="input-group">
		          	<input type="text" class="form-control" id="titulos" name="titulos" value="{{$titulos}}" />
		          	<span class="input-group-btn"> <button class="btn btn-primary btn-lg" type="submit"><i class='entypo-search'></i></button> </span>
		          </div>
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
			  		Acciones de Formación
				</div>
			</div>
            
    		<div class="panel-body with-table table-responsive">							
				<table class="table table-striped table-bordered table-center">
					<thead>
						<tr>

							<th>Título</th>
							<th>Área</th>
							<th>Duración</th>
							<th><i class="fa fa-cogs"></i></th>
						</tr>
					</thead>
					
					<tbody>
				     	@if(count($cursos) == 0)
			           	<tr>
			            	<td colspan="7">No se han encontrado resultados...</td>
			           	</tr>
				      @endif
			         @foreach($cursos as $curso)
			            <tr>
							<td>{{$curso->title}}</td>
							<td>{{$curso->category->name}}</td>
							<td>{{$curso->duration}}</td>
							<td>
								<a  title="Más Información" href="javascript:detallesAccion('{{url('u/acciones_formacion/details/'.$curso->id)}}')" class="btn btn-info btn-xs">
									<i class="entypo-search"></i>
								</a>
								<a  title="Editar acción de formación" href="javascript:editarAccion('{{url('u/acciones_formacion/details/'.$curso->id)}}')" class="btn btn-default btn-xs">
									<i class="entypo-pencil"></i>
								</a>
								<a  title="Eliminar acción de formación" href="javascript:eliminarCurso('{{url('u/acciones_formacion/'.$curso->id)}}')" class="btn btn-danger btn-xs">
									<i class="entypo-trash"></i>
								</a>
							</td>
			            </tr>
			        	@endforeach		
					</tbody>
				</table>
			</div>
		</div>
		<div align="center">{!! $cursos->appends(['titulos' => $titulos, 'id_areas' => $busqueda_area])->links() !!}</div>					
	</div>
			
</div>
@stop

@section('modals')
    @include('pages.admin.cursos.create_test')
    @include('pages.admin.cursos.update')
    @include('pages.admin.cursos.details')
    @include('pages.admin.cursos.delete')
@stop