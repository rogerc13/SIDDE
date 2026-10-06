<section class="sidde-panel" aria-labelledby="active-facilitators-heading">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title" id="active-facilitators-heading">Facilitadores activos</h2>
		<span class="sidde-panel-caption">Próximos {{ $dashboard['windowDays'] }} días</span>
		@can('getAllFacilitador', \App\Models\User::class)
			<a class="sidde-panel-link" href="{{ url('u/facilitadores') }}">Ver todos</a>
		@endcan
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
				<span class="count-pill">
					{{ (int) $facilitator->upcoming_sessions_count }} {{ (int) $facilitator->upcoming_sessions_count === 1 ? 'sesión' : 'sesiones' }}
				</span>
			</li>
		@empty
			<li class="empty-state">Sin actividad en los próximos {{ $dashboard['windowDays'] }} días.</li>
		@endforelse
	</ul>
</section>
