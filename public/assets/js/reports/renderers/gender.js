import { $, Chart, moment, refresh, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByGender(response){
    console.log('gender');
    refresh();

    const start = (response.dateRange && response.dateRange.startDate) ? response.dateRange.startDate : '';
    const end = (response.dateRange && response.dateRange.endDate) ? response.dateRange.endDate : '';
    const startFmt = formatServerDate(start);
    const endFmt = formatServerDate(end);
    const rows = Array.isArray(response.rows) ? response.rows : [];

    // Always render both genders, even if one is zero
    const fixedLabels = ['Masculino', 'Femenino'];
    const rowByLabel = {};
    rows.forEach(r => {
        if (r && r.label) {
            rowByLabel[r.label] = r;
        }
    });

    const maleCount = Number((rowByLabel['Masculino'] && rowByLabel['Masculino'].amount) ? rowByLabel['Masculino'].amount : 0) || 0;
    const femaleCount = Number((rowByLabel['Femenino'] && rowByLabel['Femenino'].amount) ? rowByLabel['Femenino'].amount : 0) || 0;

    const labels = fixedLabels;
    const data = [maleCount, femaleCount];
    const total = maleCount + femaleCount;

    const maleShare = total > 0 ? Math.round(maleCount / total * 100) : 0;
    const femaleShare = total > 0 ? Math.round(femaleCount / total * 100) : 0;

    $(".course-amount-number").html(
        `<h3>Distribución de Participantes por Género durante el período ${startFmt} - ${endFmt} : ${total}` +
        (total > 0 ? ` &middot; Masculino: ${maleShare}% &middot; Femenino: ${femaleShare}%` : '') +
        `</h3>`
    );

    // Graph containers
    $(".row-graphs").append(`
        <div class="col-md-6">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <div class="panel-title">Distribución de Participantes por Género (Barras)</div>
                </div>
                <div class="panel-body">
                    <div class="h-25 col-xs-12 col-md-12 graph-container">
                        <canvas id="genderBar"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <div class="panel-title">Distribución de Participantes por Género (Dona)</div>
                </div>
                <div class="panel-body">
                    <div class="doughnut-container">
                        <canvas id="genderDoughnut"></canvas>
                    </div>
                </div>
            </div>
        </div>
    `);

    // Shared gender palette (single source of truth, synced with the PDF).
    const backgroundColors = labels.map((label) => paletteGenderColor(label));
    const borderColors = labels.map((label) => paletteGenderColor(label));

    // Doughnut
    const doughnutEl = document.getElementById('genderDoughnut');
    new Chart(doughnutEl, {
        type: 'doughnut',
        data: {
            datasets: [{
                data,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1,
            }],
            labels,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: { display: false }
            }
        }
    });

    // Bar
    const barEl = document.getElementById('genderBar');
    new Chart(barEl, {
        type: 'bar',
        data: {
            datasets: [{
                data,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1,
            }],
            labels,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: { display: false },
                legend: {
                    display: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0,
                    }
                }
            }
        }
    });

    // Table (fixed columns: Masculino / Femenino / Total)
    $(".table-col-helper").append(
        `<div class="panel panel-success" style="page-break-inside: avoid">
            <div class="panel-heading">
                <div class="panel-title">Cantidad de Participantes por Género durante el período ${startFmt} - ${endFmt}</div>
            </div>
            <div class="panel-body with-table table-responsive">
                <table class="gender-amount-table table table-striped table-bordered table-center">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>`
    );

    $(".gender-amount-table thead").append(`<tr>
        <th>Masculino</th>
        <th>Femenino</th>
        <th>Total</th>
    </tr>`);

    $(".gender-amount-table tbody").append(`<tr>
        <td>${maleCount}</td>
        <td>${femaleCount}</td>
        <td>${total}</td>
    </tr>`);
}

