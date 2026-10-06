<div class="sidde-page-header">
	<div>
		<div class="sidde-eyebrow">Inicio</div>
		<h1 class="sidde-title">Hola, {{ $logeado->person?->name }}</h1>
		<p class="sidde-subtitle">Mis acciones de formación</p>
	</div>
</div>

<div class="sidde-panel">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title">Mis acciones de formación</h2>
		@can('misCursos', \App\Models\Scheduled::class)
			<a class="sidde-panel-link" href="{{ route('mis_acciones') }}">Ver todas ({{ $dashboard['participantsTotal'] }})</a>
		@endcan
	</div>
	<div class="sidde-panel-body is-flush table-responsive">
		<table class="sidde-table">
			<thead>
				<tr>
					<th scope="col">Acción de Formación</th>
					<th scope="col">Facilitador</th>
					<th scope="col">Fecha I.</th>
					<th scope="col">Fecha C.</th>
					<th scope="col" class="text-center">Estado</th>
					<th scope="col" class="text-center"><span class="sr-only">Acciones</span></th>
				</tr>
			</thead>
			<tbody>
				@forelse ($dashboard['participants'] as $participant)
					<tr>
						<td data-label="Acción de Formación">{{ $participant->scheduled->course->title }}</td>
						<td data-label="Facilitador">{{ $participant->scheduled->facilitator->person->name }} {{ $participant->scheduled->facilitator->person->last_name }}</td>
						<td data-label="Fecha I.">@date($participant->scheduled->start_date)</td>
						<td data-label="Fecha C.">@date($participant->scheduled->end_date)</td>
						<td class="text-center" data-label="Estado">
							<span class="badge badge-{{ $participant->badgeStatus() }}">
								{{ $participant->participantStatus->name }}
							</span>
						</td>
						<td class="text-center" data-label="Detalle">
							@can('get', $participant->scheduled)
								<a href="{{ url('u/af_programadas/'.$participant->scheduled->id.'/ver') }}" class="btn btn-info btn-xs" title="Ver detalles" aria-label="Ver detalles de {{ $participant->scheduled->course->title }}">
									<i class="entypo-search" aria-hidden="true"></i>
								</a>
							@endcan
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="6">No tienes acciones de formación asignadas.</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
