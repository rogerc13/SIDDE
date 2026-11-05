@extends('layouts.admin')

@section('bodyClass', 'home-page')

@push('CSS')
	<link rel="stylesheet" href="{{ url('assets/css/sidde-ui.css') }}?v={{ filemtime(public_path('assets/css/sidde-ui.css')) }}">
@endpush

@section('content')
	<div class="home-dashboard sidde-ui">

		@if ($dashboard['mode'] === 'overview')
			<div class="sidde-page-header">
				<div class="sidde-header-lead">
					<div class="sidde-header-copy">
						<div class="sidde-eyebrow-row">
							<span class="sidde-date-label">{{ \Carbon\Carbon::now()->locale('es')->translatedFormat('d \d\e F, Y') }}</span>
						</div>
						<h1 class="sidde-title">Bienvenido a SIDDE{{ $logeado?->person?->name ? ', '.$logeado->person->name : '' }}</h1>
						<p class="sidde-subtitle">Sistema de Desarrollo y Diseño Educativo</p>
					</div>
				</div>
				<div class="sidde-header-actions">
					@can('store', \App\Models\Scheduled::class)
						<a href="{{ url('u/af_programadas/crear') }}" class="sidde-quick-btn sidde-quick-btn--primary">
							<i class="entypo-plus" aria-hidden="true"></i>
							<span>Programar A.F.</span>
						</a>
					@endcan
					@can('getAll', \App\Models\Course::class)
						<a href="{{ url('u/acciones_formacion') }}" class="sidde-quick-btn sidde-quick-btn--secondary">
							<i class="entypo-book-open" aria-hidden="true"></i>
							<span>Catálogo A.F.</span>
						</a>
					@endcan
					@if (isset($logeado) && ! $logeado->isParticipante() && ! $logeado->isFacilitador())
						<a href="{{ url('/reports') }}" class="sidde-quick-btn sidde-quick-btn--secondary">
							<i class="entypo-chart-area" aria-hidden="true"></i>
							<span>Reportes</span>
						</a>
					@endif
				</div>
			</div>

			@include('pages.home._kpis')

			<div class="sidde-dashboard-main">
				<div class="sidde-dashboard-primary">
					@include('pages.home._upcoming')
				</div>
				<div class="sidde-dashboard-rail">
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
