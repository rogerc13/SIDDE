@php
	$window = 'próximos '.$dashboard['windowDays'].' días';
	$kpis = $dashboard['kpis'];
	$activeScheduled = (int) ($kpis['activeScheduled'] ?? 0);
	$cards = [
		[
			'value' => $kpis['courses'],
			'label' => 'Acciones de formación',
			'unit' => 'en el catálogo',
			'icon' => 'entypo-book-open',
			'tone' => 'kpi-icon--blue',
			'ability' => 'getAll',
			'model' => \App\Models\Course::class,
			'url' => url('u/acciones_formacion'),
		],
		[
			'value' => $kpis['scheduled'],
			'label' => 'Programadas',
			'unit' => $activeScheduled > 0 ? number_format($activeScheduled, 0, ',', '.').' activas · en total' : 'en total',
			'icon' => 'entypo-calendar',
			'tone' => 'kpi-icon--amber',
			'ability' => 'getAll',
			'model' => \App\Models\Scheduled::class,
			'url' => url('u/af_programadas'),
		],
		[
			'value' => $kpis['activeFacilitators'],
			'label' => 'Facilitadores',
			'unit' => 'activos · '.$window,
			'icon' => 'entypo-users',
			'tone' => 'kpi-icon--emerald',
			'ability' => 'getAllFacilitador',
			'model' => \App\Models\User::class,
			'url' => url('u/facilitadores'),
		],
		[
			'value' => $kpis['activeParticipants'],
			'label' => 'Participantes',
			'unit' => 'activos · '.$window,
			'icon' => 'entypo-graduation-cap',
			'tone' => 'kpi-icon--purple',
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
			<span class="kpi-icon {{ $card['tone'] }}" aria-hidden="true">
				<i class="{{ $card['icon'] }}"></i>
			</span>
			<span class="kpi-text">
				<span class="sidde-kpi-label">{{ $card['label'] }}</span>
				<span class="sidde-kpi-value">{{ number_format($card['value'], 0, ',', '.') }}</span>
				<span class="sidde-kpi-unit">{{ $card['unit'] }}</span>
			</span>
		@if ($allowed)
			</a>
		@else
			</div>
		@endif
	@endforeach
</div>
