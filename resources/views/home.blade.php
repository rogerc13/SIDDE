@extends('layouts.admin')

@section('bodyClass', 'home-page')

@push('CSS')
	<link rel="stylesheet" href="{{ url('assets/css/sidde-ui.css') }}">
	<style>
		/* Home-only additions on top of the shared SIDDE UI system. */
		.home-dashboard {
			--home-radius: 4px;
			--home-radius-sm: 3px;
			padding-bottom: 24px;
		}

		.home-dashboard .sidde-panel,
		.home-dashboard .sidde-kpi {
			border-radius: var(--home-radius);
		}

		.home-dashboard .home-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

		.home-dashboard .sidde-kpi { display: flex; align-items: center; gap: 14px; }
		.home-dashboard a.sidde-kpi {
			color: inherit;
			text-decoration: none;
			transition: border-color 0.15s ease, box-shadow 0.15s ease;
		}
		.home-dashboard a.sidde-kpi:hover,
		.home-dashboard a.sidde-kpi:focus-visible {
			border-color: var(--sidde-accent);
			box-shadow: var(--sidde-shadow-lg);
		}
		.home-dashboard .kpi-icon {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 56px;
			height: 56px;
			border-radius: var(--home-radius);
			font-size: 24px;
			color: #fff;
			flex: 0 0 56px;
		}
		.home-dashboard .kpi-text { display: flex; flex-direction: column; min-width: 0; }
		.home-dashboard .kpi-text .sidde-kpi-label { white-space: nowrap; }

		.home-dashboard .sui-avatar {
			width: 30px;
			height: 30px;
			border-radius: 50%;
			object-fit: cover;
			margin-right: 8px;
			flex: 0 0 30px;
		}
		.home-dashboard .count-pill {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			min-width: 28px;
			padding: 2px 8px;
			border-radius: 999px;
			font-size: 12px;
			font-weight: 700;
			color: var(--sidde-accent-text);
			background: #fdecea;
			border: 1px solid #f6c9c4;
		}
		.home-dashboard .sidde-agenda-date {
			border-radius: var(--home-radius-sm);
		}
		.home-dashboard .empty-state { color: var(--sidde-muted-strong); padding: 14px 18px; font-size: 13px; }

		.home-dashboard .badge { font-size: 11.5px; font-weight: 600; }

		@media (prefers-reduced-motion: reduce) {
			.home-dashboard a.sidde-kpi { transition: none; }
		}

		@media (max-width: 991px) {
			.home-page .page-container { display: block; padding-left: 0 !important; }
			.home-page .page-container .sidebar-menu { position: relative !important; width: 100% !important; min-height: 0 !important; }
			.home-page .page-container .sidebar-menu.fixed .sidebar-menu-inner { position: relative !important; width: 100% !important; }
			.home-page .page-container .sidebar-menu .logo-env { padding: 15px 20px; }
			.home-page .page-container .sidebar-menu .sidebar-collapse { display: none !important; }
			.home-page .page-container .sidebar-menu .sidebar-mobile-menu { display: block !important; }
			.home-page .page-container .sidebar-menu #main-menu { display: none !important; }
			.home-page .page-container .main-content { display: block !important; min-height: auto !important; padding: 20px !important; }
		}
	</style>
@endpush

@section('content')
	<div class="home-dashboard sidde-ui">

		@if ($dashboard['mode'] === 'overview')
			<div class="sidde-page-header">
				<div>
					<div class="sidde-eyebrow">Inicio</div>
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
				</div>
			</div>
		@elseif ($dashboard['mode'] === 'facilitator')
			@include('pages.home._facilitator')
		@else
			@include('pages.home._participant')
		@endif

	</div>
@stop
