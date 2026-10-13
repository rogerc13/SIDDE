@extends('layouts.admin')

@push('CSS')
	<link rel="stylesheet" href="{{ asset('assets/css/database-tables.css') }}">
@endpush

@section('content')
	@include('includes.admin.page-heading', ['icon' => 'entypo-database', 'title' => 'Base de datos'])

	<div class="database-page">
		<form method="GET" action="{{ route('database.index') }}" class="database-toolbar" role="search">
			<div class="database-toolbar-search">
				<i class="entypo-search" aria-hidden="true"></i>
				<input
					type="search"
					name="q"
					value="{{ $search }}"
					class="form-control"
					placeholder="Buscar tabla…"
					aria-label="Buscar tabla"
				>
			</div>

			<div class="database-toolbar-actions">
				<button type="submit" class="btn btn-primary btn-sm"><i class="entypo-search" aria-hidden="true"></i> Buscar</button>
				@if($search !== '')
					<a href="{{ route('database.index') }}" class="btn btn-default btn-sm">Limpiar</a>
				@endif
			</div>
		</form>

		@if(empty($groups))
			<div class="database-empty-block">
				<i class="entypo-search" aria-hidden="true"></i>
				No se encontraron tablas para «{{ $search }}».
			</div>
		@endif

		@foreach($groups as $group)
			<section class="database-block">
				<div class="database-block-header">
					<h3 class="database-block-title">{{ $group['label'] }}</h3>
					<span class="database-block-count">{{ count($group['tables']) }} tablas</span>
				</div>

				<div class="database-index-grid">
					@foreach($group['tables'] as $table)
						<a class="database-index-item" href="{{ url('database/' . $table['name']) }}">
							<span class="database-index-name">{{ $table['name'] }}</span>
							<span class="database-index-meta">
								{{ number_format($table['rows'], 0, ',', '.') }} registros · {{ $table['columns'] }} columnas
							</span>
						</a>
					@endforeach
				</div>
			</section>
		@endforeach
	</div>
@stop
