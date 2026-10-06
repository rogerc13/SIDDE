<section class="sidde-panel" aria-labelledby="upcoming-heading">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title" id="upcoming-heading">Próximas Acciones de Formación</h2>
		@can('getAll', \App\Models\Scheduled::class)
			<a class="sidde-panel-link" href="{{ url('u/af_programadas') }}">Ver todas</a>
		@endcan
	</div>
	<div class="sidde-agenda">
		@forelse ($dashboard['upcomingScheduled'] as $scheduled)
			<div class="sidde-agenda-row">
				<div class="sidde-agenda-date" aria-label="Inicio @date($scheduled->start_date)">
					<span class="sidde-agenda-date-day">{{ \Carbon\Carbon::parse($scheduled->start_date)->format('d') }}</span>
					<span class="sidde-agenda-date-month">{{ \Carbon\Carbon::parse($scheduled->start_date)->locale('es')->translatedFormat('M') }}</span>
				</div>
				<div class="sidde-agenda-course">
					@can('get', $scheduled)
						<a class="sidde-agenda-course-title" href="{{ url('u/af_programadas/'.$scheduled->id.'/ver') }}">{{ $scheduled->course->title }}</a>
					@else
						<span class="sidde-agenda-course-title">{{ $scheduled->course->title }}</span>
					@endcan
					<span class="sidde-agenda-course-sub">{{ $scheduled->facilitator->person->name }} {{ $scheduled->facilitator->person->last_name }} · {{ $scheduled->participants_count }}/{{ $scheduled->course->capacity->first()->max ?? 0 }} registrados</span>
				</div>
				<span class="sidde-agenda-meta">Culmina @date($scheduled->end_date)</span>
				<span class="sidde-status"><span class="badge badge-{{ $scheduled->badgeStatus() }}">{{ $scheduled->courseStatus->name }}</span></span>
				<span class="sidde-action">
					@can('get', $scheduled)
						<a href="{{ url('u/af_programadas/'.$scheduled->id.'/ver') }}" class="btn btn-info btn-xs" title="Ver detalles" aria-label="Ver detalles de {{ $scheduled->course->title }}"><i class="entypo-search" aria-hidden="true"></i></a>
					@endcan
				</span>
			</div>
		@empty
			<div class="empty-state">No hay acciones de formación programadas próximas.</div>
		@endforelse
	</div>
</section>
