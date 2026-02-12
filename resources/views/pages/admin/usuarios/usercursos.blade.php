@extends('layouts.admin')

@section('content')

@push('JS')
	<script src="{{url('assets/js/bootstrap-datepicker.js')}}"></script>

	<script>
		$('.dat').datepicker({
			    format: "mm-yyyy",
			    viewMode: "months", 
			    minViewMode: "months"
			    
		});
		
	</script>



@endpush

<h2>Acciones de formación de:</h2>
<h3>{{$usuario->person->name}} {{$usuario->person->last_name}} - {{$usuario->role->name}}</h3>
<br>
<form action="{{ url('u/pdf/person_courses/' . $usuario->person->id) }}" method="GET" style='all:unset'>
	@csrf
	<input type="hidden" id="person_id" name="person_id" value="{{$usuario->person->id}}">
	<input type="hidden" id="hidden_title" name="hidden_title" value="{{request()->titulos}}">
	<input type="hidden" id="hidden_facilitator" name="hidden_facilitator" value="{{request()->id_facilitador}}">
	<input type="hidden" id="hidden_status" name="hidden_status" value="{{request()->id_estado}}">
	<input type="hidden" id="hidden_date" name="hidden_date" value="{{request()->fechas}}">
	<button class="btn btn-blue print-list-button"><i class="fa fa-file-pdf-o"></i> Descargar Lista de Acciones de Formación Programadas</button> 
</form>
<br>
<br>
<div class="row filtros">						
	<div class="col-md-12">
		<form class="form-horizontal">
			<div class="form-group" style="display: flex; flex-wrap: wrap; align-items: flex-end; column-gap: 0; row-gap: 1rem; margin-bottom: 10px;">
				<div class="col-md-4 col-sm-6 col-xs-12 margin-top">
					<label for="titulos" class="control-label">Título</label>
					<input type="text" class="form-control" id="titulos" name="titulos" value="{{$titulos}}" />
				</div>

				@if($usuario->isParticipante())
					<div class="col-md-4 col-sm-6 col-xs-12">
						<label for="id_facilitador" class="control-label">Facilitador</label>
						<select name="id_facilitador" class="select2 form-control" id="id_facilitador" data-allow-clear="true" required="true">
							<option value='0'>Todos</option>
							@foreach($facilitadores as $facilitador)
								@if($id_facilitador == $facilitador->id)
									<option value="{{$facilitador->person->facilitator->id}}" selected>{{$facilitador->person->name}} {{$facilitador->person->last_name}} C.I:{{$facilitador->person->id_format()}}</option>
								@else 
									<option value="{{$facilitador->person->facilitator->id}}">{{$facilitador->person->name}} {{$facilitador->person->last_name}} C.I:{{$facilitador->person->id_format()}}</option>
								@endif
							@endforeach	                
						</select>  
					</div>
				@endif

				<div class="col-md-3 col-sm-6 col-xs-12">
					<label for="fechas" class="control-label">Por mes</label>
					<div class="input-group">
						<input type='text' class='form-control dat' autocomplete="off" name='fechas' data-format="yyyy-mm" id='fechas' placeholder='Fechas' value='{{$fechas}}' />
						<span class="input-group-btn"> <button class="btn btn-primary btn-lg" type="submit"><i class='entypo-search'></i></button> </span>
					</div>
				</div>
				<div class="col-md-1 col-sm-12 col-xs-12" style="min-width:60px;">
					<button type="button" class="btn btn-default btn-lg" onclick="window.location='{{ url('u/participantes/acciones/'. request()->segment(count(request()->segments())) . '') }}'">
						<i class="fa fa-refresh"></i>
					</button>
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
	          		Acciones de formación
	        	</div>
	      	</div>
	            
	      	<div class="panel-body with-table table-responsive">								
				<table class="table table-striped table-bordered table-center">
					<thead>
						<tr>
							
							<th>Título</th>
							 @if($usuario->isParticipante())
							 	<th>Facilitador</th>
							 @endif
							<th title="Fecha de Inicio">Fecha I.</th>
							<th title="Fecha de Culminación">Fecha C.</th>				
							<th><i class="fa fa-cogs"></i></th>
						</tr>
					</thead>
					
					<tbody>
				     	@if(count($cursos) == 0)
			           	<tr>
			            	<td colspan="7">No se han encontrado resultados...</td>
			           	</tr>
				      @endif
			         @foreach($cursos as $cursop)
			            <tr>
								
								<td>{{$cursop->course->title}}</td>
								@if($usuario->isParticipante())
									<td>{{$cursop->facilitator->person->name}} {{$cursop->facilitator->person->last_name}}</td>
								@endif
								<td>{{date("d-m-Y", strtotime($cursop->start_date))}}</td>
								<td>{{date("d-m-Y", strtotime($cursop->end_date))}}</td>
								<td>
								  <a  title="Más Información" href="{{url("acciones_formacion/".$cursop->course->id)}}" class="btn btn-info btn-xs">
								      <i class="entypo-search"></i>
								  </a>
								</td>
			            </tr>
			        	@endforeach		
					</tbody>
				</table>
			</div>
		</div>
		@if($usuario->isParticipante())
			<div align="center">{!! $cursos->appends(['titulos' => $titulos, 'id_facilitador' => $id_facilitador, 'fechas' => $fechas])->links() !!}</div>
		@else
			<div align="center">{!! $cursos->appends(['titulos' => $titulos, 'fechas' => $fechas])->links() !!}</div>
		@endif			
	</div>
	@if($usuario->isParticipante())
		<a href="{{url("u/participantes")}}">	
	@else
		<a href="{{url("u/facilitadores")}}">
	@endif
    	<center><button type="button" class="btn btn-info" ><i class="entypo-back" aria-hidden="true"></i> Regresar</button></center> 
    </a>		
</div>
@stop
