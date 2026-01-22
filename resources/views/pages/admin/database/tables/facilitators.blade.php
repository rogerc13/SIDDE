@extends('layouts.admin')
@section('content')
	<h3>Administrador <small>Base de datos</small></h3>

	<a class="btn btn-default" href="{{ route('database.index') }}">
		<i class="entypo-left-open"></i>
		Volver
	</a>

	@include('pages.admin.database.partials.table', ['table' => $table, 'columns' => $columns, 'rows' => $rows])
@stop
