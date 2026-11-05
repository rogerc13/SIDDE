@php
	$facilitatorTotal = (int) ($dashboard['kpis']['activeFacilitators'] ?? 0);
	$facilitatorWindow = 'próximos '.$dashboard['windowDays'].' días';
	$facilitatorPreview = $dashboard['activeFacilitators']->take(\App\Services\Dashboard\HomeDashboardService::ACTIVE_PREVIEW_LIMIT);
	$facilitatorHidden = max(0, $facilitatorTotal - $facilitatorPreview->count());
	$canListFacilitators = \Illuminate\Support\Facades\Gate::allows('getAllFacilitador', \App\Models\User::class);
@endphp

@if ($canListFacilitators)
	<a href="{{ url('u/facilitadores') }}" class="sidde-kpi sidde-kpi--facilitators" aria-label="Ver todos los facilitadores activos ({{ $facilitatorTotal }})">
@else
	<div class="sidde-kpi sidde-kpi--facilitators">
@endif
		<span class="sidde-kpi-lead">
			<span class="kpi-icon kpi-icon--emerald" aria-hidden="true">
				<i class="entypo-users"></i>
			</span>
			<span class="kpi-text">
				<span class="sidde-kpi-label">Facilitadores activos</span>
				<span class="sidde-kpi-value">{{ number_format($facilitatorTotal, 0, ',', '.') }}</span>
				<span class="sidde-kpi-unit">activos · {{ $facilitatorWindow }}</span>
			</span>
		</span>

		<span class="sidde-kpi-side">
			@if ($facilitatorPreview->isNotEmpty())
				<ul class="sidde-kpi-people">
					@foreach ($facilitatorPreview as $facilitator)
						@php($sessions = (int) $facilitator->upcoming_sessions_count)
						<li class="sidde-kpi-person">
							@if ($facilitator->person->avatar_path)
								<img src="{{ url('avatars/'.$facilitator->person->avatar_path) }}" class="sidde-kpi-avatar" alt="">
							@else
								<img src="{{ url('assets/images/photo.jpg') }}" class="sidde-kpi-avatar" alt="">
							@endif
							<span class="sidde-kpi-person-body">
								<span class="sidde-kpi-person-name" title="{{ $facilitator->person->name }} {{ $facilitator->person->last_name }}">{{ $facilitator->person->name }} {{ $facilitator->person->last_name }}</span>
								<span class="sidde-kpi-person-meta">{{ $sessions }} {{ $sessions === 1 ? 'clase' : 'clases' }}</span>
							</span>
						</li>
					@endforeach
					@if ($facilitatorHidden > 0)
						<li class="sidde-kpi-person sidde-kpi-person--more">+{{ $facilitatorHidden }} más</li>
					@endif
				</ul>
			@else
				<span class="sidde-kpi-empty">
					<i class="entypo-users" aria-hidden="true"></i>
					<span>Sin actividad en los próximos {{ $dashboard['windowDays'] }} días.</span>
				</span>
			@endif
		</span>
@if ($canListFacilitators)
	</a>
@else
	</div>
@endif
