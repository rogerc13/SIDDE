<section class="sidde-panel" aria-labelledby="active-participants-heading">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title hd-card-title" id="active-participants-heading">Participantes activos</h2>
	</div>
	<div class="sidde-panel-body is-flush" role="region" aria-label="Participantes activos" tabindex="0">
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
	</div>
</section>
