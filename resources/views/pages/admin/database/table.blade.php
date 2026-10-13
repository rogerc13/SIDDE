@extends('layouts.admin')
@section('content')
	@include('includes.admin.page-heading', ['icon' => 'entypo-database', 'title' => 'Base de datos'])

	<a class="btn btn-default" href="{{ route('database.index') }}">
		<i class="entypo-left-open"></i>
		Volver
	</a>

	<br>
	<br>

	@include('pages.admin.database.partials.table', $tableProps)
@stop
