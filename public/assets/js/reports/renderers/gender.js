import { $, Chart, moment, refresh, renderReportHero, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByGender(response, payload = {}){
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

    renderReportHero({ meta: payload.meta });

    // Graph containers
    $(".row-graphs").append(`
        <section class="report-block">
            <h3 class="report-block-title">Distribución de Participantes por Género (Barras)</h3>
            <div class="report-block-card is-chart">
                <div class="graph-container">
                    <canvas id="genderBar"></canvas>
                </div>
            </div>
        </section>
        <section class="report-block">
            <h3 class="report-block-title">Distribución de Participantes por Género (Dona)</h3>
            <div class="report-block-card is-chart">
                <div class="doughnut-container">
                    <canvas id="genderDoughnut"></canvas>
                </div>
            </div>
        </section>
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
        `<section class="report-block" style="page-break-inside: avoid">
            <h3 class="report-block-title">Cantidad de Participantes por Género durante el período ${startFmt} - ${endFmt}</h3>
            <div class="report-block-card">
                <div class="table-responsive">
                <table class="gender-amount-table table table-striped table-bordered table-center">
                    <thead></thead>
                    <tbody></tbody>
                </table>
                </div>
            </div>
        </section>`
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

