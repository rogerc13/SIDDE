<br>

<div class="row">
	<div class="col-md-12">
		<div class="panel panel-success" data-collapsed="0">
			<div class="panel-heading">
				<div class="panel-title">{{ $table }}</div>
			</div>

			<div class="panel-body with-table table-responsive">
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

				<div align="center">{!! $rows->links() !!}</div>
			</div>
		</div>
	</div>
</div>
