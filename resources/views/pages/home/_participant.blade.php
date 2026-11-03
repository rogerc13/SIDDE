<div class="sidde-page-header">
	<div class="sidde-header-lead">
		<span class="sidde-header-icon" aria-hidden="true">
			<i class="entypo-book-open"></i>
		</span>
		<div class="sidde-header-copy">
			<div class="sidde-eyebrow-row">
				<span class="sidde-role-pill">{{ $logeado->role?->name ?? 'Participante' }}</span>
				<span aria-hidden="true">·</span>
				<span class="sidde-date-label">{{ \Carbon\Carbon::now()->locale('es')->translatedFormat('d \d\e F, Y') }}</span>
			</div>
			<h1 class="sidde-title">Hola, {{ $logeado->person?->name }}</h1>
			<p class="sidde-subtitle">Tus acciones de formación del año {{ $dashboard['year'] }}</p>
		</div>
	</div>
	<div class="sidde-header-actions">
		@can('misCursos', \App\Models\Scheduled::class)
			<a href="{{ route('mis_acciones') }}" class="sidde-quick-btn sidde-quick-btn--primary">
				<i class="entypo-list" aria-hidden="true"></i>
				<span>Ver todas</span>
			</a>
		@endcan
		<a href="{{ url('u/pdf/mycourses') }}" class="sidde-quick-btn sidde-quick-btn--secondary">
			<i class="fa fa-file-pdf-o" aria-hidden="true"></i>
			<span>Descargar PDF</span>
		</a>
		<a href="{{ route('mis_datos') }}" class="sidde-quick-btn sidde-quick-btn--secondary">
			<i class="entypo-user" aria-hidden="true"></i>
			<span>Mi perfil</span>
		</a>
	</div>
</div>

<div class="sidde-dashboard-kpis sidde-kpi-strip">
	<div class="sidde-kpi">
		<span class="kpi-icon kpi-icon--amber" aria-hidden="true">
			<i class="entypo-calendar"></i>
		</span>
		<span class="kpi-text">
			<span class="sidde-kpi-label">Programadas</span>
			<span class="sidde-kpi-value">{{ number_format($dashboard['kpis']['programadas'], 0, ',', '.') }}</span>
		</span>
	</div>
	<div class="sidde-kpi">
		<span class="kpi-icon kpi-icon--blue" aria-hidden="true">
			<i class="entypo-clock"></i>
		</span>
		<span class="kpi-text">
			<span class="sidde-kpi-label">Por dictar</span>
			<span class="sidde-kpi-value">{{ number_format($dashboard['kpis']['porDictar'], 0, ',', '.') }}</span>
		</span>
	</div>
	<div class="sidde-kpi">
		<span class="kpi-icon kpi-icon--emerald" aria-hidden="true">
			<i class="entypo-check"></i>
		</span>
		<span class="kpi-text">
			<span class="sidde-kpi-label">Culminadas</span>
			<span class="sidde-kpi-value">{{ number_format($dashboard['kpis']['culminadas'], 0, ',', '.') }}</span>
		</span>
	</div>
</div>

@include('pages.home._calendar')
