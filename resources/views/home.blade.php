@extends('layouts.admin')

@section('bodyClass', 'home-page')

@push('CSS')
	<link rel="stylesheet" href="{{ url('assets/css/sidde-ui.css') }}">
@endpush

@section('content')
	<div class="home-dashboard sidde-ui">

		@if ($dashboard['mode'] === 'overview')
			<div class="sidde-page-header">
				<div>
					<h1 class="sidde-title">Bienvenido a SIDDE</h1>
					<p class="sidde-subtitle">Sistema de Desarrollo y Diseño Educativo</p>
				</div>
			</div>

			@include('pages.home._kpis')

			<div class="sidde-dashboard-main">
				@include('pages.home._upcoming')
				<div class="sidde-dashboard-rail">
					@include('pages.home._facilitators')
					@include('pages.home._participants')
					@include('pages.home._status')
				</div>
			</div>
		@elseif ($dashboard['mode'] === 'facilitator')
			@include('pages.home._facilitator')
		@else
			@include('pages.home._participant')
		@endif

	</div>
@stop
