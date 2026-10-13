@push('CSS')
	<link rel="stylesheet" href="{{ asset('assets/css/database-tables.css') }}">
@endpush

<div class="database-page">
	<section class="database-block">
		<div class="database-block-header">
			<h3 class="database-block-title">{{ $table }}</h3>
			<span class="database-block-count">{{ number_format($rows->total(), 0, ',', '.') }} registros</span>
		</div>

		<form method="GET" action="{{ url()->current() }}" class="database-toolbar" role="search">
			@if($sort !== '')
				<input type="hidden" name="sort" value="{{ $sort }}">
				<input type="hidden" name="dir" value="{{ $dir }}">
			@endif

			<div class="database-toolbar-search">
				<i class="entypo-search" aria-hidden="true"></i>
				<input
					type="search"
					name="q"
					value="{{ $search }}"
					class="form-control"
					placeholder="Buscar en {{ $table }}…"
					aria-label="Buscar en la tabla {{ $table }}"
				>
			</div>

			<div class="database-toolbar-actions">
				<label class="database-toolbar-field">
					<span>Filas</span>
					<select name="per_page" class="form-control input-sm" onchange="this.form.submit()" aria-label="Filas por página">
						@foreach(\App\Services\Database\DatabaseTablePresenter::PAGE_SIZES as $size)
							<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
						@endforeach
					</select>
				</label>

				<label class="database-toolbar-toggle">
					<input type="checkbox" name="cols_all" value="1" @checked($showAllColumns) onchange="this.form.submit()">
					<span>Columnas técnicas</span>
				</label>

				@if($hasDeleted)
					<label class="database-toolbar-toggle">
						<input type="checkbox" name="hide_deleted" value="1" @checked($hideDeleted) onchange="this.form.submit()">
						<span>Ocultar eliminados</span>
					</label>
				@endif

				<button type="submit" class="btn btn-primary btn-sm"><i class="entypo-search" aria-hidden="true"></i> Buscar</button>

				@if($search !== '')
					<a href="{{ request()->fullUrlWithQuery(['q' => null]) }}" class="btn btn-default btn-sm">Limpiar</a>
				@endif
			</div>
		</form>

		<div class="database-card">
			<div class="table-responsive">
				<table class="table table-striped table-bordered table-center database-table">
					<caption class="sr-only">Tabla {{ $table }} — {{ number_format($rows->total(), 0, ',', '.') }} registros</caption>
					<thead>
						<tr>
							@if($hasDeleted)
								<th scope="col" class="database-col-status">Estado</th>
							@endif

							@foreach($displayColumns as $column)
								@php($isSorted = $sort === $column['name'])
								@php($nextDir = $isSorted && $dir === 'asc' ? 'desc' : 'asc')
								<th scope="col" aria-sort="{{ $isSorted ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
									<a class="database-sort" href="{{ request()->fullUrlWithQuery(['sort' => $column['name'], 'dir' => $nextDir]) }}">
										{{ $presenter->label($column['name']) }}
										<i class="fa fa-sort{{ $isSorted ? ($dir === 'asc' ? '-asc' : '-desc') : '' }}" aria-hidden="true"></i>
									</a>
								</th>
							@endforeach

							@if($actionsView)
								<th scope="col" class="database-col-actions">
									<i class="fa fa-cogs" aria-hidden="true"></i>
									<span class="sr-only">Acciones</span>
								</th>
							@endif
						</tr>
					</thead>
					<tbody>
						@if($rows->count() === 0)
							<tr>
								<td colspan="{{ count($displayColumns) + ($hasDeleted ? 1 : 0) + ($actionsView ? 1 : 0) }}" class="database-empty">
									<i class="entypo-search" aria-hidden="true"></i>
									@if($search !== '')
										No se encontraron registros para «{{ $search }}».
									@else
										No hay registros para mostrar.
									@endif
								</td>
							</tr>
						@endif

						@foreach($rows as $row)
							@php($isDeleted = $hasDeleted && data_get($row, 'deleted_at') !== null)
							<tr @class(['is-deleted' => $isDeleted])>
								@if($hasDeleted)
									<td class="database-col-status">
										@if($isDeleted)
											<span class="badge badge-danger">Eliminado</span>
										@else
											<span class="badge badge-success">Activo</span>
										@endif
									</td>
								@endif

								@foreach($displayColumns as $column)
									@php($rawValue = data_get($row, $column['name']))
									@if(isset($valueMaps[$column['name']]) && $rawValue !== null)
										<td>{{ $valueMaps[$column['name']][$rawValue] ?? $rawValue }}</td>
									@elseif($presenter->isSensitive($column['name']))
										<td class="database-col-numeric">{{ ($rawValue === null || $rawValue === '') ? '' : '••••••' }}</td>
									@elseif($presenter->isDateTime($column['type']) && $rawValue !== null && $rawValue !== '')
										<td class="database-col-numeric">{{ \App\Models\Funciones::formatDate($rawValue, $presenter->dateFormat($column['type'])) }}</td>
									@elseif($presenter->isNumeric($column['type']))
										<td class="database-col-numeric">{{ $rawValue }}</td>
									@elseif($presenter->isText($column['type']) && is_string($rawValue) && mb_strlen($rawValue) > $presenter->maxTextLength())
										<td title="{{ $rawValue }}">{{ \Illuminate\Support\Str::limit($rawValue, $presenter->maxTextLength()) }}</td>
									@else
										<td>{{ $rawValue }}</td>
									@endif
								@endforeach

								@if($actionsView)
									@include($actionsView, ['row' => $row])
								@endif
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>

		<div class="database-pagination">
			<p class="database-pagination-summary">
				Mostrando {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} de {{ number_format($rows->total(), 0, ',', '.') }}
			</p>
			{!! $rows->links() !!}
		</div>
	</section>
</div>
