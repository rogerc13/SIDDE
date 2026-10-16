@php
	$totalScheduled = (int) ($dashboard['kpis']['scheduled'] ?? 0);
	$canListScheduled = \Illuminate\Support\Facades\Gate::allows('getAll', \App\Models\Scheduled::class);
@endphp

<section class="sidde-panel" aria-labelledby="status-breakdown-heading">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title" id="status-breakdown-heading">Programas por estado</h2>
		@if ($canListScheduled)
			<a class="sidde-panel-link" href="{{ url('u/af_programadas') }}">Ver todas ({{ $totalScheduled }})</a>
		@endif
	</div>

	@if ($totalScheduled > 0)
		<div class="sidde-status-stack" aria-hidden="true">
			@foreach ($dashboard['kpis']['byStatus'] as $status)
				@if ($status['amount'] > 0)
					<span
						class="sidde-status-segment sidde-status-segment--{{ $status['badge'] }}"
						style="width: {{ max(3, (int) ($status['percent'] ?? 0)) }}%;"
						title="{{ $status['label'] }}: {{ $status['amount'] }} ({{ $status['percent'] ?? 0 }}%)"
					></span>
				@endif
			@endforeach
		</div>
	@endif

	<ul class="sidde-rail-list">
		@forelse ($dashboard['kpis']['byStatus'] as $status)
			@php
				$pct = $status['percent'] ?? ($totalScheduled > 0 ? (int) round(($status['amount'] / $totalScheduled) * 100) : 0);
			@endphp
			<li class="sidde-rail-list-item">
				@if ($canListScheduled)
					<a class="sidde-status-row-link" href="{{ url('u/af_programadas?id_estado='.$status['id']) }}">
						<span class="badge badge-{{ $status['badge'] }}">{{ $status['label'] }}</span>
						<span class="sidde-status-metrics">
							<span class="sidde-status-pct">{{ $pct }}%</span>
							<span class="sidde-count">{{ number_format($status['amount'], 0, ',', '.') }}</span>
						</span>
					</a>
				@else
					<span class="badge badge-{{ $status['badge'] }}">{{ $status['label'] }}</span>
					<span class="sidde-status-metrics">
						<span class="sidde-status-pct">{{ $pct }}%</span>
						<span class="sidde-count">{{ number_format($status['amount'], 0, ',', '.') }}</span>
					</span>
				@endif
			</li>
		@empty
			<li class="empty-state">
				<i class="entypo-chart-pie" aria-hidden="true"></i>
				<span>Sin programas registrados.</span>
			</li>
		@endforelse
	</ul>
</section>
