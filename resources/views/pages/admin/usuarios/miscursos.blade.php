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


	@include('includes.admin.page-heading', ['icon' => 'entypo-list', 'title' => 'Mis acciones de formación'])

	<br>
	<form action="pdf/mycourses" method="GET" style='all:unset'>
		@csrf 
		<input type="hidden" id="hidden_title" name="hidden_title" value="{{request()->titulos}}">
		<input type="hidden" id="hidden_facilitator" name="hidden_facilitator" value="{{request()->id_facilitador}}">
		<input type="hidden" id="hidden_status" name="hidden_status" value="{{request()->id_estado}}">
		<input type="hidden" id="hidden_date" name="hidden_date" value="{{request()->fechas}}">
		<button class="btn btn-blue print-list-button"><i class="fa fa-file-pdf-o"></i> Descargar Mi Lista de Mis Acciones de Formación Programadas</button> 
	</form>
	<br>

<div class="row filtros">						
	<div class="col-md-12">
		<form class="form-horizontal">
			<div class="form-group">
		        <div class="col-md-4 col-sm-6 col-xs-12 margin-top">
		            <label for="titulos" class="control-label">Título</label>
		            <input type="text" class="form-control" id="titulos" name="titulos" placeholder="Buscar por título" value="{{$titulos}}" />
		             
		        </div>

		        @if($logeado->isParticipante())
			        <div class="col-md-4 col-sm-6 col-xs-12">
			          	<label for="id_facilitador" class="control-label">Facilitador</label>

			            <select name="id_facilitador" class="select2 " id="id_facilitador" data-allow-clear="true" required="true">
		                                <option value='0'>Todos</option>
							@foreach($facilitadores as $facilitador)
	                            @if($id_facilitador == $facilitador->id)
	                                <option value="{{$facilitador->person->facilitator->id}}" selected>{{$facilitador->person->name}} {{$facilitador->person->last_name}} C.I:{{$facilitador->ci}}</option>
	                            @else 
	                                <option value="{{$facilitador->person->facilitator->id}}">{{$facilitador->person->name}} {{$facilitador->person->last_name}} C.I:{{$facilitador->person->id_number}}</option>
	                            @endif
	                        @endforeach		                
			            </select>  
			       	</div>
		       	@endif

		      	<div class="col-md-4 col-sm-6 col-xs-12">
		          <label for="fechas" class="control-label">Por mes</label>
		          <div class="input-group">
		          <input type='text' class='form-control dat' autocomplete="off" name='fechas' data-format="mm-yyyy" id='fechas' placeholder='MM-AAAA' value='{{$fechas}}' />
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
	          		Mis acciones de formación
	        	</div>
	      	</div>
	            
	      	<div class="panel-body with-table table-responsive">						
				<table class="table table-striped table-bordered table-center">
					<thead>
						<tr>
							
							<th>Título</th>
							 @if($logeado->isParticipante())
							 	<th>Facilitador</th>
							 @endif
							<th title="Fecha de Inicio">Fecha I.</th>
							<th title="Fecha de Culminación">Fecha C.</th>				
							<th>Estado</th>
							{{-- @php
								dd($logeado->isFacilitador());
							@endphp --}}
							@if($logeado->isFacilitador())
								<th>Registrados</th>				
							@endif
							<th><i class="fa fa-cogs"></i></th>
						</tr>
					</thead>
					
					<tbody>
				     	@if(count($cursos) == 0)
						
			           	<tr>
			            	<td colspan="6">No se han encontrado resultados...</td>
			           	</tr>
				      @endif
			         @foreach($cursos as $cursop)
					 	
			            <tr>
								<td>{{$cursop->course->title}}</td>
								@if($logeado->isParticipante())
									<td>{{$cursop->facilitator->person->name}} {{$cursop->facilitator->person->last_name}}</td>
								@endif
								<td>@date($cursop->start_date)</td>
								<td>@date($cursop->end_date)</td>
								<td>
									<span class="badge badge-{{$cursop->badgeStatus()}}">{{$cursop->courseStatus->name}}</span>
								</td>
								@if ($logeado->isFacilitador())	
								<td>
										<span class="badge badge-success">
											{{count($cursop->participants)}}
										</span>
								</td>
								@endif
								<td>
							@if($logeado->isFacilitador())
							<a  title="Ver detalles" href="{{url('u/af_programadas/'.$cursop->id.'/ver')}}" class="btn btn-info btn-xs">
								<i class="entypo-search"></i>
							</a>
							<a  title="Lista de participantes" href="{{url('u/af_programadas/'.$cursop->id.'/ver')}}#tab-participantes" class="btn btn-info btn-xs">
								<i class="entypo-users"></i>
							</a>
							<a  title="{{($cursop->course->file->count() == '0') ? 'No Posee Documentos' : 'Descargar Documentos del Curso'}}" href="javascript:documentModal('{{url('download/'.$cursop->course->id.'/0')}}')" class="btn btn-info btn-xs {{($cursop->course->file->count() == '0') ? 'disabled' : ''}}">
								<i class="entypo-download"></i>
							</a>
							@elseif($logeado->isParticipante())
							<a  title="Ver detalles" href="{{url('u/af_programadas/'.$cursop->id.'/ver')}}" class="btn btn-info btn-xs">
								<i class="entypo-search"></i>
							</a>
							<a  title="{{($cursop->course->file->count() == '0') ? 'No Posee Documentos' : 'Descargar Manual del Participante'}}" href="javascript:documentModal('{{url('download/'.$cursop->course->id.'/0')}}')" class="btn btn-info btn-xs {{($cursop->course->file->count() == '0') ? 'disabled' : ''}}">
								<i class="entypo-download"></i>
							</a>
							@endif
								</td>
			            </tr>
			        @endforeach		
					</tbody>
				</table>
			</div>
		</div>
	 @if($logeado->isParticipante())
			<div align="center">{!! $cursos->appends(['titulos' => $titulos, 'id_facilitador' => $id_facilitador, 'fechas' => $fechas])->links() !!}</div>
		@else
			<div align="center">{!! $cursos->appends(['titulos' => $titulos, 'fechas' => $fechas])->links() !!}</div>
		@endif	 				
	</div>
			
</div>
@stop
@section('modals')
	@include('pages.admin.usuarios.document_modal');
@stop