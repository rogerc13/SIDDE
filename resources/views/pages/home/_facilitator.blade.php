<div class="sidde-page-header">
	<div class="sidde-header-lead">
		<span class="sidde-header-icon" aria-hidden="true">
			<i class="entypo-graduation-cap"></i>
		</span>
		<div class="sidde-header-copy">
			<div class="sidde-eyebrow-row">
				<span class="sidde-role-pill">{{ $logeado->role?->name ?? 'Facilitador' }}</span>
				<span aria-hidden="true">·</span>
				<span class="sidde-date-label">{{ \Carbon\Carbon::now()->locale('es')->translatedFormat('d \d\e F, Y') }}</span>
			</div>
			<h1 class="sidde-title">Hola, {{ $logeado->person?->name }}</h1>
			<p class="sidde-subtitle">Tu agenda de acciones de formación · próximos {{ $dashboard['windowDays'] }} días</p>
		</div>
	</div>
	<div class="sidde-header-actions">
		@can('misCursos', \App\Models\Scheduled::class)
			<a href="{{ route('mis_acciones') }}" class="sidde-quick-btn sidde-quick-btn--primary">
				<i class="entypo-list" aria-hidden="true"></i>
				<span>Mis acciones</span>
			</a>
		@endcan
		<a href="{{ url('u/pdf/mycourses') }}" class="sidde-quick-btn sidde-quick-btn--secondary">
			<i class="fa fa-file-pdf-o" aria-hidden="true"></i>
			<span>Descargar lista PDF</span>
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
			<i class="entypo-calendar"></i>
		</span>
		<span class="kpi-text">
			<span class="sidde-kpi-label">Programadas</span>
			<span class="sidde-kpi-value">{{ number_format($dashboard['scheduledTotal'], 0, ',', '.') }}</span>
			<span class="sidde-kpi-unit">próximos {{ $dashboard['windowDays'] }} días · {{ $dashboard['upcomingSessionsCount'] ?? 0 }} {{ ($dashboard['upcomingSessionsCount'] ?? 0) === 1 ? 'sesión' : 'sesiones' }}</span>
		</span>
	</div>
	<div class="sidde-kpi">
		<span class="kpi-icon kpi-icon--emerald" aria-hidden="true">
			<i class="entypo-users"></i>
		</span>
		<span class="kpi-text">
			<span class="sidde-kpi-label">Participantes</span>
			<span class="sidde-kpi-value">{{ number_format($dashboard['participantsTotal'], 0, ',', '.') }}</span>
			<span class="sidde-kpi-unit">inscritos en tus cursos</span>
		</span>
	</div>
	<div class="sidde-kpi">
		<span class="kpi-icon kpi-icon--blue" aria-hidden="true">
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
			<a class="sidde-panel-link sidde-panel-link--icon" href="{{ route('mis_acciones') }}" title="Ver todas" aria-label="Ver todas mis acciones de formación ({{ $dashboard['scheduledTotal'] }})"><i class="entypo-right-open-big" aria-hidden="true"></i></a>
		@endcan
	</div>
	<div class="sidde-panel-body is-flush table-responsive">
		<table class="sidde-table">
			<thead>
				<tr>
					<th scope="col">Título</th>
					<th scope="col">Inicio</th>
					<th scope="col">Culminación</th>
					<th scope="col">Próxima sesión</th>
					<th scope="col" class="text-center">Registrados</th>
					<th scope="col" class="text-center">Estado</th>
					<th scope="col" class="text-center"><span class="sr-only">Acciones</span></th>
				</tr>
			</thead>
			<tbody>
				@forelse ($dashboard['scheduled'] as $scheduled)
					@php
						$maxCap = (int) ($scheduled->course->capacity->first()->max ?? 0);
						$regCount = (int) $scheduled->participants_count;
						$capPct = $maxCap > 0 ? min(100, (int) round(($regCount / $maxCap) * 100)) : 0;
						$capFillClass = $capPct >= 100 ? 'is-full' : ($capPct >= 70 ? 'is-high' : '');
						$nextSession = $scheduled->relationLoaded('sessions') ? $scheduled->sessions->first() : null;
					@endphp
					<tr>
						<td data-label="Título">
							<span class="sidde-table-course-main">{{ $scheduled->course->title }}</span>
							@if (!empty($scheduled->course->code) || !empty($scheduled->course->modality?->name))
								<span class="sidde-table-course-sub">
									{{ $scheduled->course->code }}@if (!empty($scheduled->course->code) && !empty($scheduled->course->modality?->name)) · @endif{{ $scheduled->course->modality?->name }}
								</span>
							@endif
						</td>
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
						<td class="text-center" data-label="Registrados">
							<span>{{ $regCount }}/{{ $maxCap }}</span>
							@if ($maxCap > 0)
								<div class="sidde-capacity-wrap" style="margin: 4px auto 0; max-width: 110px;">
									<div class="sidde-capacity-bar" role="progressbar" aria-label="Ocupación de {{ $scheduled->course->title }}" aria-valuenow="{{ $capPct }}" aria-valuemin="0" aria-valuemax="100">
										<span class="sidde-capacity-fill {{ $capFillClass }}" style="width: {{ $capPct }}%;"></span>
									</div>
								</div>
							@endif
						</td>
						<td class="text-center" data-label="Estado">
							<span class="badge badge-{{ $scheduled->badgeStatus() }}">
								{{ $scheduled->courseStatus->name }}
							</span>
						</td>
						<td class="text-center" data-label="Detalle">
							<span class="sidde-table-actions">
								@can('get', $scheduled)
									<a href="{{ url('u/af_programadas/'.$scheduled->id.'/ver') }}" class="btn btn-info btn-xs" title="Ver detalles" aria-label="Ver detalles de {{ $scheduled->course->title }}">
										<i class="entypo-search" aria-hidden="true"></i>
									</a>
								@endcan
								@can('getAllPorCurso', \App\Models\Participant::class)
									<a href="{{ url('u/af_programadas/'.$scheduled->id.'/participantes') }}" class="btn btn-default btn-xs" title="Participantes" aria-label="Participantes de {{ $scheduled->course->title }}">
										<i class="entypo-users" aria-hidden="true"></i>
									</a>
								@endcan
							</span>
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="7">No tienes acciones de formación programadas en los próximos {{ $dashboard['windowDays'] }} días.</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
