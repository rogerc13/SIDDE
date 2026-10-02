<div class="sidde-page-header">
	<div>
		<div class="sidde-eyebrow">Inicio</div>
		<h1 class="sidde-title">Hola, {{ $logeado->person?->name }}</h1>
		<p class="sidde-subtitle">Mis acciones de formación</p>
	</div>
</div>

<div class="sidde-panel">
	<div class="sidde-panel-heading">
		<h4 class="sidde-panel-title hd-card-title">Mis acciones de formación</h4>
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
				</tr>
			</thead>
			<tbody>
				@forelse ($dashboard['participants'] as $participant)
					<tr>
						<td>{{ $participant->scheduled->course->title }}</td>
						<td>{{ $participant->scheduled->facilitator->person->name }} {{ $participant->scheduled->facilitator->person->last_name }}</td>
						<td>@date($participant->scheduled->start_date)</td>
						<td>@date($participant->scheduled->end_date)</td>
						<td class="text-center">
							<span class="badge badge-{{ $participant->badgeStatus() }}">
								{{ $participant->participantStatus->name }}
							</span>
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="5">No tienes acciones de formación asignadas.</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
