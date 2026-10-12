import { $, Chart, moment, refresh, renderReportHero, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByParticipantAverage(response, payload = {}){
    console.log("participant-average");
    refresh();
    const rows = Array.isArray(response.data) ? response.data : [];
    const startFmt = formatServerDate(response.dateRange && response.dateRange.startDate ? response.dateRange.startDate : '');
    const endFmt = formatServerDate(response.dateRange && response.dateRange.endDate ? response.dateRange.endDate : '');

    if (rows.length === 0) {
        renderReportHero({ meta: payload.meta, empty: 'No existen Acciones de Formación' });
        return;
    }

    // group per-course rows into their date buckets
    const buckets = new Map();
    rows.forEach((row) => {
        if (!buckets.has(row.date)) buckets.set(row.date, []);
        buckets.get(row.date).push(row);
    });
    const dates = Array.from(buckets.keys()).sort();
    const round2 = (v) => Math.round(v * 100) / 100;
    const seriesFor = (field) => dates.map((d) => {
        const group = buckets.get(d);
        const avg = group.reduce((s, r) => s + (Number(r[field]) || 0), 0) / group.length;
        return { x: d, y: round2(avg) };
    });

    $(".row-graphs").append(`<div class="panel panel-success by-average-line">
        <div class="panel-heading">
            <div class="panel-title">Promedio de Participantes por Acción de Formación durante el Período ${startFmt} - ${endFmt}</div>
        </div>
        <div class="panel-body">
            <div class="col-xs-12 col-md-12 graph-container">
                <canvas id="myChart"></canvas>
            </div>
        </div>
    </div>`);

    renderReportHero({ meta: payload.meta });

    const palette = [
        { label: 'Promedio de Participantes', key: 'total', color: categoryColor(0) },
        { label: 'Promedio Aprobados', key: 'approved', color: categoryColor(1) },
        { label: 'Promedio Reprobados', key: 'failed', color: categoryColor(2) },
    ];
    const datasets = palette.map(p => applySmartLineMarkers({
        label: p.label,
        data: seriesFor(p.key),
        fill: false,
        borderColor: p.color,
        backgroundColor: hexToRgba(p.color, 0.2),
        borderWidth: 3,
        tension: 0.5,
    }));

    const maxY = getMaxYFromDatasets(datasets);

    new Chart(document.getElementById('myChart'), {
        type: "line",
        data: { labels: dates, datasets: datasets },
        options: {
            spanGaps: shouldConnectLineGaps(),
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'time',
                    title: { display: true, text: 'Período de Tiempo' },
                    time: { parser: 'YYYY-MM-DD', tooltipFormat: 'YYYY-MM-DD' }
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: maxY > 0 ? (maxY + 1) : 1,
                    ticks: { precision: 0 }
                }
            },
        }
    });
}//end report by participant average

