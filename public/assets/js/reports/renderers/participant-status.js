import { $, Chart, moment, refresh, renderReportHero, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByParticipantStatus(response, payload = {}){
    //reports by participant status
    console.log("participant-by-status");
    refresh();

    renderReportHero({ meta: payload.meta });

    // Shared status palette (single source of truth, synced with the PDF).
    const getParticipantStatusColor = (statusName) => paletteParticipantStatusColor(statusName);

    // Chart containers (were missing, so charts had no canvas to render into)
    $(".row-graphs").append(`
        <section class="report-block">
            <h3 class="report-block-title">Distribución de Participantes por Estatus (Barras)</h3>
            <div class="report-block-card is-chart">
                <div class="graph-container">
                    <canvas id="myChart" data-chart-key="participant_status_bar"></canvas>
                </div>
            </div>
        </section>
        <section class="report-block">
            <h3 class="report-block-title">Distribución de Participantes por Estatus (Dona)</h3>
            <div class="report-block-card is-chart">
                <div class="doughnut-container">
                    <canvas id="doughnut" data-chart-key="participant_status_doughnut"></canvas>
                </div>
            </div>
        </section>
    `);

    const start_date = (response.dateRange && response.dateRange.startDate)
        ? response.dateRange.startDate
        : (response.byStatusByDateRange && response.byStatusByDateRange[0] ? response.byStatusByDateRange[0].date : '');

    const end_date = (response.dateRange && response.dateRange.endDate)
        ? response.dateRange.endDate
        : (response.byStatusByDateRange && response.byStatusByDateRange.length ? response.byStatusByDateRange[response.byStatusByDateRange.length - 1].date : '');

    // Format for display
    const start_date_fmt = formatServerDate(start_date);
    const end_date_fmt = formatServerDate(end_date);

    //total amount of paticipants all time given status

    //$().html(response.byAllTime.length);
    //console.log(response.byAllTime.length);

    //
    let participantStatusesDateRange = [];
    participantStatusesDateRange = response.allStatusbyDateRange.filter(
        (obj) => {
            return obj.countByStatus > 0;
        }
    );
    //returns Status name , amount

    //charts by date range
    //doughnut Data
    let doughnutDataStatus = [];
    let doughnutLabelsStatus = [];
    let amountHelperStatus = 0;
    let doughnutBackgroundColorStatus = [];
    response.labels.forEach((element) => {
        participantStatusesDateRange.forEach((helper) => {
            if (helper.status === element.label) {
                amountHelperStatus = amountHelperStatus + helper.countByStatus;
            }
        });
        doughnutDataStatus.push(amountHelperStatus);
        doughnutLabelsStatus.push(element.label);
        amountHelperStatus = 0;
        doughnutBackgroundColorStatus.push(getParticipantStatusColor(element.label));
    });
    //console.log(doughnutDataStatus);

    const doughnutStatus = document.getElementById('doughnut');
    var myDoughnutChart = new Chart(doughnutStatus, {
        type: "doughnut",
        data: {
            datasets: [
                {
                    data: doughnutDataStatus,
                    backgroundColor: doughnutBackgroundColorStatus,
                },
            ],
            labels: doughnutLabelsStatus,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: { display: false }
            }
        },
    });
    //bar chart
    const barStatus = document.getElementById('myChart');
    var myDoughnutChart = new Chart(barStatus, {
        type: "bar",
        data: {
            datasets: [
                {
                    data: doughnutDataStatus,
                    backgroundColor: doughnutBackgroundColorStatus,
                },
            ],
            labels: doughnutLabelsStatus,
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
        },
    });

    $(".table-col-helper").append(
        `<section class="report-block participant-during-period-with-status">
            <h3 class="report-block-title">Cantidad de Participantes Durante el Período ${start_date_fmt} - ${end_date_fmt}</h3>
            <div class="report-block-card">
                <div class="table-responsive">
                <table class="status-amount-table table table-striped table-bordered table-center">
                    <thead></thead>
                    <tbody></tbody>
                </table>
                </div>
            </div>
        </section>`
    );

    // Build a robust status -> count map (avoid depending on label order/indexes)
    const statusCounts = {};
    (response.allStatusbyDateRange || []).forEach((item) => {
        const name = item.status || item.name || item.statusName;
        if (!name) {
            return;
        }
        const n = Number(item.countByStatus ?? item.count ?? item.amount ?? 0);
        statusCounts[name] = (statusCounts[name] || 0) + (Number.isFinite(n) ? n : 0);
    });

    // Match the statuses seeded in ParticipantStatusSeeder
    const statusOrder = ['En Curso', 'Reprobado', 'Aprobado', 'Cancelado', 'Por Iniciar'];

    $(".status-amount-table thead").append(`<tr>
        <th>En Curso</th>
        <th>Reprobado</th>
        <th>Aprobado</th>
        <th>Cancelado</th>
        <th>Por Iniciar</th>
    </tr>`);

    $(".status-amount-table tbody").append(`<tr>
        <td>${statusCounts[statusOrder[0]] ?? 0}</td>
        <td>${statusCounts[statusOrder[1]] ?? 0}</td>
        <td>${statusCounts[statusOrder[2]] ?? 0}</td>
        <td>${statusCounts[statusOrder[3]] ?? 0}</td>
        <td>${statusCounts[statusOrder[4]] ?? 0}</td>
    </tr>`);

    // list of participants (all statuses) during the selected period
    $(".table-col-helper").append(
        `<section class="report-block participant-with-status">
        <h3 class="report-block-title">Lista de Participantes Durante el Período ${start_date_fmt} - ${end_date_fmt}</h3>
        <div class="report-block-card">
        <div class="table-responsive">
        <table class="all-time-list-table table table-striped table-bordered table-center">

                                <thead></thead>
                                <tbody></tbody>
                            </table>
        </div>
        </div>
        </section>`
    );

    $(".all-time-list-table thead").append(`<tr>
        <th>Nombres</th>
        <th>Apellidos</th>
        <th>Cédula</th>
        <th>Estatus</th>
        <th>Acción de Formación</th>
        <th>Fecha de Inicio</th>
    </tr>`);

    if (!(response.byAllTime || []).length) {
        $(".all-time-list-table tbody").append(`<tr>
            <td colspan="6">No hay participantes en el período o con el estatus seleccionado</td>
        </tr>`);
    } else {
        (response.byAllTime || []).forEach((element) => {
            const courseTitle = (element.scheduled && element.scheduled.course && element.scheduled.course.title) ? element.scheduled.course.title : 'Sin curso';
            const startDate = (element.scheduled && element.scheduled.start_date) ? element.scheduled.start_date : '';
            const startDateFmt = formatServerDate(startDate);
            const statusName = (element.participant_status && element.participant_status.name) ? element.participant_status.name : '';
            $(".all-time-list-table tbody").append(`<tr>
                                        <td>${element.person.name}</td>
                                        <td>${element.person.last_name}</td>
                                        <td>${element.person.id_number}</td>
                                        <td>${statusName}</td>
                                        <td>${courseTitle}</td>
                                        <td>${startDateFmt}</td>
                                        </tr>`);
        });
    }

    // Paginate the participant list (client-side)
    initTablePagination(document.querySelector('.participant-with-status .all-time-list-table'), 25);

    // participants not in a course: no longer shown in this report
    /* response.allStatusbyDateRange.forEach(element => {
                            console.log(`${element.status} : ${element.countByStatus}`);
                        }); */

    //console.log(response.notInCourse);
}//end report by participant status

