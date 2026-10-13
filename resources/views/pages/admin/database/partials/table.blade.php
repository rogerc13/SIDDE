@push('CSS')
	<link rel="stylesheet" href="{{ asset('assets/css/database-tables.css') }}">
@endpush

<div class="database-page">
	<section class="database-block">
		<h3 class="database-block-title">{{ $table }}</h3>

		<div class="database-card">
			<div class="table-responsive">
				<table class="table table-striped table-bordered table-center">
					<thead>
						<tr>
							@foreach($columns as $column)
								<th>{{ $column }}</th>
							@endforeach
						</tr>
					</thead>
					<tbody>
						@if($rows->count() === 0)
							<tr>
								<td colspan="{{ max(count($columns), 1) }}">No hay registros para mostrar.</td>
							</tr>
						@endif

						@foreach($rows as $row)
							<tr>
								@foreach($columns as $column)
									@php($rawValue = data_get($row, $column))
									@php($valueMaps = $valueMaps ?? [])
									@if(isset($valueMaps[$column]) && $rawValue !== null)
										<td>{{ $valueMaps[$column][$rawValue] ?? $rawValue }}</td>
									@else
										<td>{{ $rawValue }}</td>
									@endif
								@endforeach
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>

		<div class="database-pagination">{!! $rows->links() !!}</div>
	</section>
</div>
