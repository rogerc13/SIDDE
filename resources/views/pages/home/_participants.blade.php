<section class="sidde-panel" aria-labelledby="active-participants-heading">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title" id="active-participants-heading">Participantes activos</h2>
		<span class="sidde-panel-caption">Próximos {{ $dashboard['windowDays'] }} días</span>
		@can('getAllParticipante', \App\Models\User::class)
			<a class="sidde-panel-link sidde-panel-link--icon" href="{{ url('u/participantes') }}" title="Ver todos" aria-label="Ver todos los participantes activos"><i class="entypo-right-open-big" aria-hidden="true"></i></a>
		@endcan
	</div>
	<ul class="sidde-rail-list">
		@forelse ($dashboard['activeParticipants'] as $person)
			@php
				$enrolment = $person->participant->first();
			@endphp
			<li class="sidde-rail-list-item">
				<span class="sidde-rail-person-group">
					@if ($person->avatar_path)
						<img src="{{ url('avatars/'.$person->avatar_path) }}" class="sui-avatar" alt="">
					@else
						<img src="{{ url('assets/images/photo.jpg') }}" class="sui-avatar" alt="">
					@endif
					<span class="sidde-rail-person-stack">
						<span class="sidde-rail-person">{{ $person->name }} {{ $person->last_name }}</span>
						<span class="sidde-rail-course">{{ $enrolment?->scheduled?->course?->title ?? '—' }}</span>
					</span>
				</span>
				<span>
					@if ($enrolment)
						<span class="badge badge-{{ $enrolment->badgeStatus() }}">
							{{ $enrolment->participantStatus->name }}
						</span>
					@endif
				</span>
			</li>
		@empty
			<li class="empty-state">
				<i class="entypo-graduation-cap" aria-hidden="true"></i>
				<span>Sin participantes con sesiones próximas.</span>
			</li>
		@endforelse
	</ul>
</section>
