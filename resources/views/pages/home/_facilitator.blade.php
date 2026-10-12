<div class="sidde-page-header">
	<div>
		<h1 class="sidde-title">Hola, {{ $logeado->person?->name }}</h1>
		<p class="sidde-subtitle">Tu agenda de acciones de formación</p>
	</div>
</div>

<div class="sidde-dashboard-kpis sidde-kpi-strip">
	<div class="sidde-kpi">
		<span class="kpi-icon" style="background-color: #f0ad4e;" aria-hidden="true">
			<i class="entypo-calendar"></i>
		</span>
		<span class="kpi-text">
			<span class="sidde-kpi-label">Programadas</span>
			<span class="sidde-kpi-value">{{ number_format($dashboard['scheduledTotal'], 0, ',', '.') }}</span>
			<span class="sidde-kpi-unit">próximos {{ $dashboard['windowDays'] }} días</span>
		</span>
	</div>
	<div class="sidde-kpi">
		<span class="kpi-icon" style="background-color: #8dc63f;" aria-hidden="true">
			<i class="entypo-users"></i>
		</span>
		<span class="kpi-text">
			<span class="sidde-kpi-label">Participantes</span>
			<span class="sidde-kpi-value">{{ number_format($dashboard['participantsTotal'], 0, ',', '.') }}</span>
			<span class="sidde-kpi-unit">inscritos</span>
		</span>
	</div>
	<div class="sidde-kpi">
		<span class="kpi-icon" style="background-color: #5d9cec;" aria-hidden="true">
			<i class="entypo-archive"></i>
		</span>
		<span class="kpi-text">
			<span class="sidde-kpi-label">Cupos disponibles</span>
			<span class="sidde-kpi-value">{{ number_format($dashboard['seatsAvailable'], 0, ',', '.') }}</span>
			<span class="sidde-kpi-unit">en total</span>
		</span>
	</div>
</div>

<div class="sidde-panel">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title">Mis próximas acciones de formación</h2>
		@can('misCursos', \App\Models\Scheduled::class)
			<a class="sidde-panel-link" href="{{ route('mis_acciones') }}">Ver todas ({{ $dashboard['scheduledTotal'] }})</a>
		@endcan
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
						<td data-label="Título">{{ $scheduled->course->title }}</td>
						<td data-label="Fecha I.">@date($scheduled->start_date)</td>
						<td data-label="Fecha C.">@date($scheduled->end_date)</td>
						<td class="text-center" data-label="Registrados">{{ $scheduled->participants_count }}/{{ $scheduled->course->capacity->first()->max ?? 0 }}</td>
						<td class="text-center" data-label="Estado">
							<span class="badge badge-{{ $scheduled->badgeStatus() }}">
								{{ $scheduled->courseStatus->name }}
							</span>
						</td>
						<td class="text-center" data-label="Detalle">
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
