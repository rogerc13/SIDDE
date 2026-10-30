@php
	$todayStr = \Carbon\Carbon::today()->toDateString();
	$focusDays = $dashboard['focusWindowDays'] ?? 7;
	$todayCount = (int) ($dashboard['todaySessionsCount'] ?? 0);
@endphp

<section class="sidde-panel" aria-labelledby="upcoming-heading">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title" id="upcoming-heading">Próximas Acciones de Formación</h2>
		@can('getAll', \App\Models\Scheduled::class)
			<a class="sidde-panel-link sidde-panel-link--icon" href="{{ url('u/af_programadas') }}" title="Ver todas" aria-label="Ver todas las acciones de formación"><i class="entypo-right-open-big" aria-hidden="true"></i></a>
		@endcan
	</div>
	<div class="sidde-agenda">
		@forelse ($dashboard['upcomingScheduled'] as $scheduled)
			@php
				$startCarbon = \Carbon\Carbon::parse($scheduled->start_date);
				$startStr = $startCarbon->toDateString();
				$dateMod = $startStr === $todayStr ? 'is-today' : ($startStr < $todayStr ? 'is-ongoing' : '');
				$maxCap = (int) ($scheduled->course->capacity->first()->max ?? 0);
				$regCount = (int) $scheduled->participants_count;
				$nextSession = $scheduled->relationLoaded('sessions') ? $scheduled->sessions->first() : null;
			@endphp
			<div class="sidde-agenda-row">
				<div class="sidde-agenda-date {{ $dateMod }}" aria-label="Inicio @date($scheduled->start_date)">
					<span class="sidde-agenda-date-day">{{ $startCarbon->format('d') }}</span>
					<span class="sidde-agenda-date-month">{{ $startCarbon->locale('es')->translatedFormat('M') }}</span>
				</div>
				<div class="sidde-agenda-course">
					<div class="sidde-agenda-title-row">
						@if (!empty($scheduled->course->code))
							<span class="sidde-course-code">{{ $scheduled->course->code }}</span>
						@endif
						@can('get', $scheduled)
							<a class="sidde-agenda-course-title" href="{{ url('u/af_programadas/'.$scheduled->id.'/ver') }}">{{ $scheduled->course->title }}</a>
						@else
							<span class="sidde-agenda-course-title">{{ $scheduled->course->title }}</span>
						@endcan
					</div>
					<span class="sidde-agenda-course-sub">{{ $scheduled->facilitator->person->name }} {{ $scheduled->facilitator->person->last_name }} · {{ $regCount }}/{{ $maxCap }} registrados</span>
					@if ($nextSession)
						<span class="sidde-agenda-next-session">
							<i class="entypo-clock" aria-hidden="true"></i>
							Próx. sesión: @date($nextSession->session_date) ({{ \Carbon\Carbon::parse($nextSession->start_time)->format('H:i') }})@if ($nextSession->location) · {{ $nextSession->location->name }}@endif
						</span>
					@endif
				</div>
				<span class="sidde-agenda-meta">Culmina @date($scheduled->end_date)</span>
				<span class="sidde-status"><span class="badge badge-{{ $scheduled->badgeStatus() }}">{{ $scheduled->courseStatus->name }}</span></span>
				<span class="sidde-action">
					@can('get', $scheduled)
						<a href="{{ url('u/af_programadas/'.$scheduled->id.'/ver') }}" class="sidde-icon-btn" title="Ver detalles" aria-label="Ver detalles de {{ $scheduled->course->title }}"><i class="entypo-search" aria-hidden="true"></i></a>
					@endcan
				</span>
			</div>
		@empty
			<div class="empty-state">
				<i class="entypo-calendar" aria-hidden="true"></i>
				<span>No hay acciones de formación programadas próximas.</span>
			</div>
		@endforelse
	</div>
</section>

<section class="sidde-panel" aria-labelledby="upcoming-sessions-heading">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title" id="upcoming-sessions-heading">Próximas Clases</h2>
		<span class="sidde-panel-caption">Hoy y próximos {{ $focusDays }} días</span>
		@if ($todayCount > 0)
			<span class="count-pill" style="margin-left: auto;">{{ $todayCount }} {{ $todayCount === 1 ? 'sesión hoy' : 'sesiones hoy' }}</span>
		@endif
	</div>
	<ul class="sidde-session-list">
		@forelse (($dashboard['upcomingSessions'] ?? []) as $session)
			@php
				$sessionDate = \Carbon\Carbon::parse($session->session_date);
				$isToday = $sessionDate->toDateString() === $todayStr;
			@endphp
			<li class="sidde-session-item">
				<div class="sidde-session-when {{ $isToday ? 'is-today' : '' }}">
					<span class="sidde-session-day">{{ $isToday ? 'Hoy' : $sessionDate->locale('es')->translatedFormat('D d M') }}</span>
					<span class="sidde-session-hours">{{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}</span>
				</div>
				<div class="sidde-session-body">
					@can('get', $session->scheduled)
						<a class="sidde-session-title" href="{{ url('u/af_programadas/'.$session->scheduled->id.'/ver') }}">{{ $session->scheduled->course->title }}</a>
					@else
						<span class="sidde-session-title">{{ $session->scheduled->course->title }}</span>
					@endcan
					<span class="sidde-session-sub">{{ $session->scheduled->facilitator->person->name }} {{ $session->scheduled->facilitator->person->last_name }}</span>
				</div>
				<span class="sidde-location-pill">
					<i class="entypo-location" aria-hidden="true"></i>
					{{ $session->location?->name ?? 'Sin aula' }}
				</span>
			</li>
		@empty
			<li class="empty-state">
				<i class="entypo-clock" aria-hidden="true"></i>
				<span>No hay sesiones programadas para los próximos {{ $focusDays }} días.</span>
			</li>
		@endforelse
	</ul>
</section>
