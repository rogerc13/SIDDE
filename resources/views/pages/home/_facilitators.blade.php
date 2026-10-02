<section class="sidde-panel" aria-labelledby="active-facilitators-heading">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title hd-card-title" id="active-facilitators-heading">Facilitadores activos</h2>
	</div>
	<ul class="sidde-rail-list">
		@forelse ($dashboard['activeFacilitators'] as $facilitator)
			<li class="sidde-rail-list-item">
				<span class="sidde-rail-person">
					@if ($facilitator->person->avatar_path)
						<img src="{{ url('avatars/'.$facilitator->person->avatar_path) }}" class="sui-avatar" alt="">
					@else
						<img src="{{ url('assets/images/photo.jpg') }}" class="sui-avatar" alt="">
					@endif
					{{ $facilitator->person->name }} {{ $facilitator->person->last_name }}
				</span>
				<span class="count-pill" title="Sesiones en los próximos {{ $dashboard['windowDays'] }} días">
					{{ $facilitator->upcoming_sessions_count }}
				</span>
			</li>
		@empty
			<li class="empty-state">Sin actividad en los próximos {{ $dashboard['windowDays'] }} días.</li>
		@endforelse
	</ul>
</section>
