@php
	$events = $dashboard['calendarEvents'] ?? [];
	$year = $dashboard['year'] ?? \Carbon\Carbon::now()->year;
@endphp

@push('CSS')
	<link rel="stylesheet" href="{{ url('assets/js/fullcalendar-2/fullcalendar.min.css') }}">
@endpush

<section class="sidde-panel home-calendar-panel" aria-labelledby="home-calendar-heading">
	<div class="sidde-panel-heading">
		<h2 class="sidde-panel-title" id="home-calendar-heading">Calendario de acciones de formación</h2>
		<span class="sidde-panel-caption">Año {{ $year }}</span>
		@can('misCursos', \App\Models\Scheduled::class)
			<a class="sidde-panel-link sidde-panel-link--icon" href="{{ route('mis_acciones') }}" title="Ver todas" aria-label="Ver todas mis acciones de formación del año {{ $year }}"><i class="entypo-right-open-big" aria-hidden="true"></i></a>
		@endcan
	</div>
	<div class="sidde-panel-body">
		<p class="home-calendar-help">Haz clic en una sesión para abrir la acción de formación. Los colores identifican el aula.</p>
		<div id="home-calendar" class="home-calendar" role="region" aria-label="Calendario de acciones de formación del año {{ $year }}"></div>
	</div>
</section>

@push('JS')
<script>
$(function () {
	var events = @json($events, JSON_UNESCAPED_SLASHES);
	var $calendar = $('#home-calendar');

	if (!$calendar.length || !$.fn.fullCalendar) {
		return;
	}

	$calendar.fullCalendar({
		timezone: 'America/Caracas',
		firstDay: 1,
		header: { left: 'prev,next today', center: 'title', right: 'month,agendaWeek,agendaDay' },
		defaultView: 'month',
		slotDuration: '00:30:00',
		allDaySlot: false,
		minTime: '06:00:00',
		maxTime: '22:00:00',
		height: 620,
		eventLimit: 4,
		editable: false,
		selectable: false,
		buttonText: {
			today: 'Hoy',
			month: 'Mes',
			agendaWeek: 'Semana',
			agendaDay: 'Día'
		},
		monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
		monthNamesShort: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
		dayNames: ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'],
		dayNamesShort: ['Dom','Lun','Mar','Mié','Jue','Vie','Sá'],
		dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','Sá'],
		columnFormat: { week: 'ddd D/M' },
		allDayText: 'Todo el día',
		eventLimitText: function (n) {
			return '+' + n + ' más';
		},
		events: events,
		eventClick: function (event) {
			if (event.url) {
				window.location.href = event.url;
			}

			return false;
		},
		eventRender: function (event, element) {
			var label = event.tooltip || event.title;
			element.attr('title', label);
			element.attr('aria-label', label);

			var sub = [event.locationName, event.facilitatorName].filter(Boolean).join(' · ');
			var $content = element.find('.fc-content');

			if (sub && $content.length && !element.closest('.fc-month-view').length) {
				$content.append($('<span class="fc-ev-sub"></span>').text(sub));
			}
		}
	});
});
</script>
@endpush
