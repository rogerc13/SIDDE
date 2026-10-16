<div class="sidde-page-header">
	<div class="sidde-header-lead">
		<span class="sidde-header-icon" aria-hidden="true">
			<i class="entypo-book-open"></i>
		</span>
		<div class="sidde-header-copy">
			<div class="sidde-eyebrow-row">
				<span class="sidde-role-pill">{{ $logeado->role?->name ?? 'Participante' }}</span>
				<span aria-hidden="true">·</span>
				<span class="sidde-date-label">{{ \Carbon\Carbon::now()->locale('es')->translatedFormat('d \d\e F, Y') }}</span>
			</div>
			<h1 class="sidde-title">Hola, {{ $logeado->person?->name }}</h1>
			<p class="sidde-subtitle">Mis acciones de formación y seguimiento académico</p>
		</div>
	</div>
	<div class="sidde-header-actions">
		@can('misCursos', \App\Models\Scheduled::class)
			<a href="{{ route('mis_acciones') }}" class="sidde-quick-btn sidde-quick-btn--primary">
				<i class="entypo-list" aria-hidden="true"></i>
				<span>Ver todas</span>
			</a>
		@endcan
		<a href="{{ url('u/pdf/mycourses') }}" class="sidde-quick-btn sidde-quick-btn--secondary">
			<i class="fa fa-file-pdf-o" aria-hidden="true"></i>
			<span>Descargar PDF</span>
		</a>
		<a href="{{ route('mis_datos') }}" class="sidde-quick-btn sidde-quick-btn--secondary">
			<i class="entypo-user" aria-hidden="true"></i>
			<span>Mi perfil</span>
		</a>
	</div>
</div>

<div class="sidde-dashboard-kpis sidde-kpi-strip">
	<div class="sidde-kpi">
		<span class="kpi-icon kpi-icon--amber" aria-hidden="true">
			<i class="entypo-book-open"></i>
		</span>
		<span class="kpi-text">
			<span class="sidde-kpi-label">Activas</span>
			<span class="sidde-kpi-value">{{ number_format($dashboard['activeEnrolmentsCount'] ?? 0, 0, ',', '.') }}</span>
			<span class="sidde-kpi-unit">en curso o por iniciar</span>
		</span>
	</div>
	<div class="sidde-kpi">
		<span class="kpi-icon kpi-icon--blue" aria-hidden="true">
			<i class="entypo-calendar"></i>
		</span>
		<span class="kpi-text">
			<span class="sidde-kpi-label">Sesiones próximas</span>
			<span class="sidde-kpi-value">{{ number_format($dashboard['upcomingSessionsCount'] ?? 0, 0, ',', '.') }}</span>
			<span class="sidde-kpi-unit">próximos {{ $dashboard['windowDays'] }} días</span>
		</span>
	</div>
	<div class="sidde-kpi">
		<span class="kpi-icon kpi-icon--emerald" aria-hidden="true">
			<i class="entypo-check"></i>
		</span>
		<span class="kpi-text">
			<span class="sidde-kpi-label">Aprobadas</span>
			<span class="sidde-kpi-value">{{ number_format($dashboard['approvedCount'] ?? 0, 0, ',', '.') }}</span>
			<span class="sidde-kpi-unit">de {{ number_format($dashboard['participantsTotal'], 0, ',', '.') }} registradas</span>
		</span>
	</div>
</div>

<div class="sidde-panel">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title">Mis acciones de formación</h2>
		@can('misCursos', \App\Models\Scheduled::class)
			<a class="sidde-panel-link sidde-panel-link--icon" href="{{ route('mis_acciones') }}" title="Ver todas" aria-label="Ver todas mis acciones de formación ({{ $dashboard['participantsTotal'] }})"><i class="entypo-right-open-big" aria-hidden="true"></i></a>
		@endcan
	</div>
	<div class="sidde-panel-body is-flush table-responsive">
		<table class="sidde-table">
			<thead>
				<tr>
					<th scope="col">Acción de Formación</th>
					<th scope="col">Facilitador</th>
					<th scope="col">Inicio</th>
					<th scope="col">Culminación</th>
					<th scope="col">Próxima sesión</th>
					<th scope="col" class="text-center">Estado</th>
					<th scope="col" class="text-center"><span class="sr-only">Acciones</span></th>
				</tr>
			</thead>
			<tbody>
				@forelse ($dashboard['participants'] as $participant)
					@php
						$scheduled = $participant->scheduled;
						$nextSession = $scheduled && $scheduled->relationLoaded('sessions') ? $scheduled->sessions->first() : null;
					@endphp
					<tr>
						<td data-label="Acción de Formación">
							<span class="sidde-table-course-main">{{ $scheduled->course->title }}</span>
							@if (!empty($scheduled->course->code) || !empty($scheduled->course->modality?->name))
								<span class="sidde-table-course-sub">
									{{ $scheduled->course->code }}@if (!empty($scheduled->course->code) && !empty($scheduled->course->modality?->name)) · @endif{{ $scheduled->course->modality?->name }}
								</span>
							@endif
						</td>
						<td data-label="Facilitador">{{ $scheduled->facilitator->person->name }} {{ $scheduled->facilitator->person->last_name }}</td>
						<td data-label="Inicio">@date($scheduled->start_date)</td>
						<td data-label="Culminación">@date($scheduled->end_date)</td>
						<td data-label="Próxima sesión">
							@if ($nextSession)
								<span class="sidde-table-course-main">@date($nextSession->session_date) · {{ \Carbon\Carbon::parse($nextSession->start_time)->format('H:i') }}</span>
								@if ($nextSession->location)
									<span class="sidde-table-course-sub"><i class="entypo-location" aria-hidden="true"></i> {{ $nextSession->location->name }}</span>
								@endif
							@else
								<span class="sidde-table-course-sub">—</span>
							@endif
						</td>
						<td class="text-center" data-label="Estado">
							<span class="badge badge-{{ $participant->badgeStatus() }}">
								{{ $participant->participantStatus->name }}
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
						<td colspan="7">No tienes acciones de formación asignadas.</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
