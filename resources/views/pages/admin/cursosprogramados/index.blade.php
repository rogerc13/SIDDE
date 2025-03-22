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
	<h3>Acciones de Formación Programadas</h3>
	@can('store','App\Scheduled')
		<a href="javascript:programarAccion('{{url('u/af_programadas')}}')" class="btn btn-primary"><i class="fa fa-plus" aria-hidden="true"></i> Programar Acción de Formación</a>
		<form action="pdf/scheduled" method="GET" style='all:unset'>
			@csrf
			<input type="hidden" id="hidden_title" name="hidden_title" value="{{request()->titulos}}">
			<input type="hidden" id="hidden_facilitator" name="hidden_facilitator" value="{{request()->id_facilitador}}">
			<input type="hidden" id="hidden_status" name="hidden_status" value="{{request()->id_estado}}">
			<input type="hidden" id="hidden_date" name="hidden_date" value="{{request()->fechas}}">
			<button class="btn btn-blue print-list-button"><i class="fa fa-file-pdf-o"></i> Descargar Lista de Acciones de Formación Programadas</button> 
		</form>
		
		<!-- <a href="{{url('u/pdf/scheduled')}}" class="btn btn-blue print-list-button"><i class="glyphicon glyphicon-print"></i> Descargar Lista de Acciones de Formación Programadas</a> -->

		<br>
		<br>
	@endcan
<div class="row filtros">
	<div class="col-md-12">
		<form class="form-horizontal">
			<div class="form-group">
		        <div class="col-md-4 col-sm-6 col-xs-12 margin-top">
		            <label for="titulos" class="control-label">Título</label>
		            <input type="text" class="form-control" id="titulos" name="titulos" value="{{$titulos}}" />
		        </div>
				

		        <div class="col-md-4 col-sm-6 col-xs-12">
		          	<label for="id_facilitador" class="control-label">Facilitador</label>

		            <select name="id_facilitador" class="select2 " id="id_facilitador" data-allow-clear="true" required="true">
	                                <option value='0'>Todos</option>
						
						@foreach($facilitadores as $facilitador)

                            @if($id_facilitador == $facilitador->id)
                                <option value="{{$facilitador->person->facilitator->id}}" selected>{{$facilitador->person->name}} {{$facilitador->person->last_name}} C.I:{{$facilitador->person->last_name}} C.I:{{$facilitador->person->id_type_id  == 1 ? 'V' : 'E'}}-{{$facilitador->person->id_format()}}</option>
                            @else
                                <option value="{{$facilitador->person->facilitator->id}}">{{$facilitador->person->name}} {{$facilitador->person->last_name}} C.I:{{$facilitador->person->id_type_id  == 1 ? 'V' : 'E'}}-{{$facilitador->person->id_format()}}</option>
                            @endif
                        @endforeach
		            </select>
		       	</div>
		        <div class="col-md-4 col-sm-6 col-xs-12">
		          	<label for="id_estado" class="control-label">Por Estado</label>

		            <select name="id_estado" class="select2 " id="id_estado" data-allow-clear="true" required="true">
	                                <option value='0'>Todos</option>

						@foreach($estados as $estado)

                            @if($id_estado == $estado->id)
                                <option value="{{$estado->id}}" selected>{{$estado->name}}</option>
                            @else
                                <option value="{{$estado->id}}">{{$estado->name}}</option>
                            @endif
                        @endforeach
		            </select>
		       	</div>

		      	<div class="col-md-4 col-sm-6 col-xs-12">
		          <label for="fechas" class="control-label">Por mes</label>
		          <div class="input-group">
		          	<input type='text' class='form-control dat' autocomplete="off" name='fechas' data-format="yyyy-mm" id='fechas' placeholder='Fechas' value='{{$fechas}}' />
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
	          		Acciones de Formación Programadas
	        	</div>
	      	</div>

	      	<div class="panel-body with-table table-responsive">
				<table class="table table-striped table-bordered table-center">
					<thead>
						<tr>

							<th>Título</th>
							<th>Facilitador</th>
							<th title="Fecha de Inicio">Fecha I.</th>
							<th title="Fecha de Culminación">Fecha C.</th>
							<th>Registrados</th>
							<th>Estado</th>
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
								<td>{{$cursop->facilitator->person->name}} {{$cursop->facilitator->person->last_name}}</td>
								@if($cursop->start_date)
									<td>{{date("d-m-Y", strtotime($cursop->start_date))}}</td>
								@else
									<td title="Sin fecha"><span class="badge badge-danger">S.F</span></td>
								@endif

								@if($cursop->start_date)
									<td>{{date("d-m-Y", strtotime($cursop->end_date))}}</td>
								@else
									<td title="Sin fecha"><span class="badge badge-danger">S.F</span></td>
								@endif

								<td>
									<span class="">
										{{count($cursop->participants)}}/{{$cursop->course->capacity[0]->max}}
									</span>

									{{-- <!--	{{count($cursop->participants)}}/{{$cursop->course->capacity->max}}
									</span>
									--> --}}
								</td>
								<td>
{{--									@if ($cursop->isPorDictar())--}}
{{--										<span class="badge badge-warning">--}}
{{--											{{$cursop->cpStatus->nombre}}--}}
{{--										</span>--}}
{{--									@elseif ($cursop->isEnCurso())--}}
{{--										<span class="badge badge-info">--}}
{{--											{{$cursop->cpStatus->nombre}}--}}
{{--										</span>--}}
{{--                                    @elseif ($cursop->isCulminado())--}}
{{--                                        <span class="badge badge-success">--}}
{{--											{{$cursop->cpStatus->nombre}}--}}
{{--										</span>--}}
{{--                                    @elseif ($cursop->isCancelado())--}}
{{--                                        <span class="badge badge-danger">--}}
{{--											{{$cursop->cpStatus->nombre}}--}}
{{--										</span>--}}
{{--									@endif--}}
                                    <span class="badge badge-{{$cursop->badgeStatus()}}">
                                        {{$cursop->courseStatus->name}}
                                    </span>
								</td>
								<td>
								  <!-- <a  title="Más Información" href="javascript:detallesAccion('{{url('u/af_programadas/'.$cursop->id)}}')" class="btn btn-info btn-xs">
								      <i class="entypo-search"></i>
								  </a> -->
								  @can('getAllPorCurso','App\Participant')
									  <a  title="Lista de participantes" href="{{url('u/af_programadas/'.$cursop->id.'/participantes')}}" class="btn btn-info btn-xs">
									      <i class="entypo-users"></i>
									  </a>
									  <a  title="Asignar participante" href="javascript:asignarParticipanteLista('{{url('u/af_programadas/'.$cursop->id.'/asignarParticipante')}}','{{$cursop->id}}')" value="{{$cursop->id}}" class="btn btn-success btn-xs {{$cursop->atMaxCapacity() ? 'disabled' :''}}">
									      <i class="entypo-user-add"></i>
									  </a>
								  @endcan

								  <a  title="Editar Programación" href="javascript:editarPrograma('{{url('u/af_programadas/'.$cursop->id)}}')" class="btn btn-default btn-xs {{$cursop->isCulminado() || $cursop->isCancelado() ? 'disabled' :''}}">
								      <i class="entypo-pencil"></i>
								  </a>

								  <a  title="Eliminar Acción de formación Programada" href="javascript:eliminarPrograma('{{url('u/af_programadas/'.$cursop->id)}}')" class="btn btn-danger btn-xs">
								      <i class="entypo-trash"></i>
								  </a>
								  <a  title="Cancelar Acción de formación Programada" href="javascript:cancelPrograma('{{url('u/af_programadas/cancel/'.$cursop->id)}}')" class="btn btn-danger btn-xs">
								      <i class="glyphicon glyphicon-ban-circle"></i>
								  </a>
								</td>
			            </tr>
			        	@endforeach
					</tbody>
				</table>
			</div>
		</div>
		<div align="center">{!! $cursos->appends(['titulos' => $titulos, 'id_facilitador' => $id_facilitador, 'fechas' => $fechas])->links() !!}</div>
	</div>

</div>
@stop

@section('modals')
    @include('pages.admin.cursosprogramados.create')
    @include('pages.admin.cursosprogramados.update')
    @include('pages.admin.cursosprogramados.details')
    @include('pages.admin.cursosprogramados.delete')
    @include('pages.admin.cursosprogramados.cancel')
    @include('pages.admin.cursosprogramados.asignar')
@stop
