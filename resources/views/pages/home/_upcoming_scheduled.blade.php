@php
	$todayStr = \Carbon\Carbon::today()->toDateString();
@endphp

<section class="sidde-panel sidde-panel--condensed" aria-labelledby="upcoming-heading">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title" id="upcoming-heading">Próximas Acciones de Formación</h2>
		@can('getAll', \App\Models\Scheduled::class)
			<a class="sidde-panel-link sidde-panel-link--icon" href="{{ url('u/af_programadas') }}" title="Ver todas" aria-label="Ver todas las acciones de formación"><i class="entypo-right-open-big" aria-hidden="true"></i></a>
		@endcan
	</div>

	<ul class="sidde-condensed-scroll" tabindex="0" aria-label="Acciones de formación próximas">
		@forelse ($dashboard['upcomingScheduled'] as $scheduled)
			@php
				$startCarbon = \Carbon\Carbon::parse($scheduled->start_date);
				$startStr = $startCarbon->toDateString();
				$dateMod = $startStr === $todayStr ? 'is-today' : ($startStr < $todayStr ? 'is-ongoing' : '');
				$maxCap = (int) ($scheduled->course->capacity->first()->max ?? 0);
				$regCount = (int) $scheduled->participants_count;
				$canViewScheduled = \Illuminate\Support\Facades\Gate::allows('get', $scheduled);
			@endphp
			<li class="sidde-condensed-item">
				@if ($canViewScheduled)
					<a class="sidde-condensed-link" href="{{ url('u/af_programadas/'.$scheduled->id.'/ver') }}">
				@else
					<div class="sidde-condensed-link">
				@endif
					<span class="sidde-agenda-date {{ $dateMod }}" aria-label="Inicio @date($scheduled->start_date)">
						<span class="sidde-agenda-date-day">{{ $startCarbon->format('d') }}</span>
						<span class="sidde-agenda-date-month">{{ $startCarbon->locale('es')->translatedFormat('M') }}</span>
					</span>
					<span class="sidde-condensed-body">
						@if (!empty($scheduled->course->code))
							<span class="sidde-course-code">{{ $scheduled->course->code }}</span>
						@endif
						<span class="sidde-condensed-text">
							<span class="sidde-agenda-course-title">{{ $scheduled->course->title }}</span>
							<span class="sidde-condensed-sub">{{ $scheduled->facilitator->person->name }} {{ $scheduled->facilitator->person->last_name }} · {{ $regCount }}/{{ $maxCap }} registrados</span>
						</span>
					</span>
					<span class="sidde-status"><span class="badge badge-{{ $scheduled->badgeStatus() }}">{{ $scheduled->courseStatus->name }}</span></span>
				@if ($canViewScheduled)
					</a>
				@else
					</div>
				@endif
			</li>
		@empty
			<li class="empty-state">
				<i class="entypo-calendar" aria-hidden="true"></i>
				<span>No hay acciones de formación programadas próximas.</span>
			</li>
		@endforelse
	</ul>
</section>
