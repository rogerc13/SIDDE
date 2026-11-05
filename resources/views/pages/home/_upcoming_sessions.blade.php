@php
	$todayStr = \Carbon\Carbon::today()->toDateString();
	$focusDays = $dashboard['focusWindowDays'] ?? 7;
	$todayCount = (int) ($dashboard['todaySessionsCount'] ?? 0);
@endphp

<section class="sidde-panel" aria-labelledby="upcoming-sessions-heading">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title" id="upcoming-sessions-heading">Próximas Clases</h2>
		<span class="sidde-panel-caption">Hoy y próximos {{ $focusDays }} días</span>
		@if ($todayCount > 0)
			<span class="count-pill" style="margin-left: auto;">{{ $todayCount }} {{ $todayCount === 1 ? 'clase hoy' : 'clases hoy' }}</span>
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
