@php
	$kpis = $dashboard['kpis'];
	$cards = [
		[
			'value' => $kpis['courses'],
			'label' => 'Acciones',
			'icon' => 'entypo-book-open',
			'color' => '#5d9cec',
			'ability' => 'getAll',
			'model' => \App\Models\Course::class,
			'url' => url('u/acciones_formacion'),
		],
		[
			'value' => $kpis['scheduled'],
			'label' => 'Programadas',
			'icon' => 'entypo-calendar',
			'color' => '#f0ad4e',
			'ability' => 'getAll',
			'model' => \App\Models\Scheduled::class,
			'url' => url('u/af_programadas'),
		],
		[
			'value' => $kpis['activeFacilitators'],
			'label' => 'Facilitadores',
			'icon' => 'entypo-users',
			'color' => '#8dc63f',
			'ability' => 'getAllFacilitador',
			'model' => \App\Models\User::class,
			'url' => url('u/facilitadores'),
		],
		[
			'value' => $kpis['activeParticipants'],
			'label' => 'Participantes',
			'icon' => 'fa fa-graduation-cap',
			'color' => '#a97fe0',
			'ability' => 'getAllParticipante',
			'model' => \App\Models\User::class,
			'url' => url('u/participantes'),
		],
	];
@endphp

<div class="sidde-dashboard-kpis">
	@foreach ($cards as $card)
		@php($allowed = \Illuminate\Support\Facades\Gate::allows($card['ability'], $card['model']))
		@if ($allowed)
			<a href="{{ $card['url'] }}" class="sidde-kpi">
		@else
			<div class="sidde-kpi">
		@endif
			<span class="kpi-icon" style="background-color: {{ $card['color'] }};" aria-hidden="true">
				<i class="{{ $card['icon'] }}"></i>
			</span>
			<span class="kpi-text">
				<span class="sidde-kpi-value">{{ number_format($card['value'], 0, ',', '.') }}</span>
				<span class="sidde-kpi-label">{{ $card['label'] }}</span>
			</span>
		@if ($allowed)
			</a>
		@else
			</div>
		@endif
	@endforeach
</div>
