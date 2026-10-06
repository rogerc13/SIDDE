<section class="sidde-panel" aria-labelledby="status-breakdown-heading">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title" id="status-breakdown-heading">Programas por estado</h2>
		@can('getAll', \App\Models\Scheduled::class)
			<a class="sidde-panel-link" href="{{ url('u/af_programadas') }}">Ver todas ({{ $dashboard['kpis']['scheduled'] }})</a>
		@endcan
	</div>
	<ul class="sidde-rail-list">
		@forelse ($dashboard['kpis']['byStatus'] as $status)
			<li class="sidde-rail-list-item">
				<span class="badge badge-{{ $status['badge'] }}">{{ $status['label'] }}</span>
				<span class="sidde-count">{{ number_format($status['amount'], 0, ',', '.') }}</span>
			</li>
		@empty
			<li class="empty-state">Sin programas registrados.</li>
		@endforelse
	</ul>
</section>
