<div class="sidde-page-header">
	<div>
		<div class="sidde-eyebrow">Inicio</div>
		<h1 class="sidde-title">Hola, {{ $logeado->person?->name }}</h1>
		<p class="sidde-subtitle">Tu agenda de acciones de formación</p>
	</div>
</div>

<div class="sidde-panel">
	<div class="sidde-panel-heading">
		<h4 class="sidde-panel-title hd-card-title">Mis próximas acciones de formación</h4>
	</div>
	<div class="sidde-panel-body is-flush table-responsive">
		<table class="sidde-table">
			<thead>
				<tr>
					<th scope="col">Título</th>
					<th scope="col">Fecha I.</th>
					<th scope="col">Fecha C.</th>
					<th scope="col" class="text-center">Registrados</th>
					<th scope="col" class="text-center">Estado</th>
					<th scope="col" class="text-center"><span class="sr-only">Acciones</span></th>
				</tr>
			</thead>
			<tbody>
				@forelse ($dashboard['scheduled'] as $scheduled)
					<tr>
						<td>{{ $scheduled->course->title }}</td>
						<td>@date($scheduled->start_date)</td>
						<td>@date($scheduled->end_date)</td>
						<td class="text-center">{{ $scheduled->participants_count }}/{{ $scheduled->course->capacity->first()->max ?? 0 }}</td>
						<td class="text-center">
							<span class="badge badge-{{ $scheduled->badgeStatus() }}">
								{{ $scheduled->courseStatus->name }}
							</span>
						</td>
						<td class="text-center">
							@can('get', $scheduled)
								<a href="{{ url('u/af_programadas/'.$scheduled->id.'/ver') }}" class="btn btn-info btn-xs" title="Ver detalles" aria-label="Ver detalles de {{ $scheduled->course->title }}">
									<i class="entypo-search" aria-hidden="true"></i>
								</a>
							@endcan
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="6">No tienes acciones de formación programadas en los próximos {{ $dashboard['windowDays'] }} días.</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
