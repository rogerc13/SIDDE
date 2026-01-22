@extends('layouts.admin')
@section('content')
	<h3>Administrador</h3>

	<div class="row">
		<div class="col-md-12">
			<div class="panel panel-success" data-collapsed="0">
				<div class="panel-heading">
					<div class="panel-title"><i class="entypo-database"></i> Base de datos</div>
				</div>

				<div class="panel-body">
					@if(empty($tables))
						<p>No se encontraron tablas para mostrar.</p>
					@else
						<div class="row">
							@foreach($tables as $table)
								<div class="col-md-4 col-sm-6" style="margin-bottom: 10px;">
									<a class="btn btn-default btn-block" href="{{ url('database/' . $table) }}">
										{{ $table }}
									</a>
								</div>
							@endforeach
						</div>
					@endif
				</div>
			</div>
		</div>
	</div>
@stop
