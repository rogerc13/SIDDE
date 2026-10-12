import { $, Chart, moment, refresh, renderReportHero, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByDate(response, payload = {}){ //reports by date
    refresh();
    const startDateFmt = formatServerDate(response.start_date);
    const endDateFmt = formatServerDate(response.end_date);
    $(".row-graphs").append(`<div class="panel panel-success by-date-line">
        <div class="panel-heading">
            <div class="panel-title">Cantidad de Acciones de Formación durante el Período ${startDateFmt} - ${endDateFmt} : ${response.total}</div>
        </div>    
        <div class="panel-body">
            <div class="h-25 col-xs-12 col-md-12 graph-container">
                <canvas id="myChart"></canvas>
            </div>
        </div>
        </div>`);

    renderReportHero({ meta: payload.meta });

    // Build array of {x, y} objects for time-based line chart, just like the other working graphs
    let lineData = [];
    if (Array.isArray(response.x) && Array.isArray(response.y)) {
        // Flatten x if it's an array of arrays (as in the controller)
        let xArr = response.x.map(val => Array.isArray(val) ? val[0] : val);
        lineData = xArr.map((date, idx) => {
            let yVal = response.y[idx];
            // keep zeros: empty days are part of the story for a per-day count
            return { x: date, y: (typeof yVal === 'number') ? yVal : null };
        });
    }
    const ctx = document.getElementById('myChart'); // DOM element
    // or to be explicit: const ctx = document.getElementById('myChart').getContext('2d');

    const markedDataset = applySmartLineMarkers({
        label: 'Cantidad de Acciones de Formación',
        data: lineData,
        fill: false,
        borderColor: 'rgb(75, 192, 192)',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        borderWidth: 3,
        tension: 0.5,
        cubicInterpolationMode: 'monotone'
    });

    const maxY = getMaxYFromDatasets([markedDataset]);

    var chart = new Chart(ctx, {
        type: "line",
        data: {
            labels: response.x,
            datasets: [markedDataset]
        },
        options: {
            // For daily charts, connect across nulls; for others, keep gaps.
            spanGaps: shouldConnectLineGaps(),
            responsive: true,
            maintainAspectRatio: false,
            elements: {
                point: {
                    radius: 0,
                    hoverRadius: 0
                }
            },
            scales: {
                x: {
                    type: 'time',
                    title: {
                        display: true,
                        text: 'Período de Tiempo'
                    },
                    time: {
                        parser: 'YYYY-MM-DD',
                        tooltipFormat: 'YYYY-MM-DD'
                    }
                },
                y: {
                    beginAtZero: true,
                    // Add a little headroom so the max doesn't touch the top edge
                    suggestedMax: maxY > 0 ? (maxY + 2) : 1,
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    }
                }
            },
        }
    });
}//end report by date

