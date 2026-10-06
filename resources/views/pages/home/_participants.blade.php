<section class="sidde-panel" aria-labelledby="active-participants-heading">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title" id="active-participants-heading">Participantes activos</h2>
		<span class="sidde-panel-caption">Próximos {{ $dashboard['windowDays'] }} días</span>
		@can('getAllParticipante', \App\Models\User::class)
			<a class="sidde-panel-link" href="{{ url('u/participantes') }}">Ver todos</a>
		@endcan
	</div>
	<ul class="sidde-rail-list">
			@forelse ($dashboard['activeParticipants'] as $person)
				@php($enrolment = $person->participant->first())
				<li class="sidde-rail-list-item">
					<span class="sidde-rail-person">{{ $person->name }} {{ $person->last_name }}</span>
					<span class="sidde-rail-course">{{ $enrolment?->scheduled?->course?->title ?? '—' }}</span>
					<span>
						@if ($enrolment)
							<span class="badge badge-{{ $enrolment->badgeStatus() }}">
								{{ $enrolment->participantStatus->name }}
							</span>
						@endif
					</span>
				</li>
			@empty
				<li class="empty-state">Sin participantes con sesiones próximas.</li>
			@endforelse
	</ul>
</section>
