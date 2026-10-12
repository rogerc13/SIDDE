import { $, Chart, moment, refresh, renderReportHero, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByCourseStatus(response, payload = {}){ //reports by course status
    console.log("status");

    refresh();
    renderReportHero({ meta: payload.meta });
    const startFmt = formatServerDate(response.dateRange && response.dateRange.startDate ? response.dateRange.startDate : '');
    const endFmt = formatServerDate(response.dateRange && response.dateRange.endDate ? response.dateRange.endDate : '');

    // Shared status palette (single source of truth, synced with the PDF).
    const getCourseStatusColor = (statusName) => paletteCourseStatusColor(statusName);
    $(".row-graphs")
        .append(`<section class="report-block">
            <h3 class="report-block-title">Distribución de Acciones de Formación por Estatus durante el período ${startFmt} - ${endFmt}</h3>
            <div class="report-block-card is-chart">
                <div class="graph-container">
                    <canvas id="myChart" data-chart-key="status_bar"></canvas>
                </div>
            </div>
        </section>`);

    const ctx = document.getElementById('myChart'); //linear graph selector


    //linear graph data
    let statuses = [];
    response.statuses.forEach((element) => {
        const statusColor = getCourseStatusColor(element);
        // For each status, build an array of {x, y} objects for the time-based line chart
        let statusData = response.x.map(date => {
            let found = response.y.find(obj => obj.status === element && obj.x === date);
            return {
                x: date,
                y: found ? (found.y > 0 ? found.y : null) : null
            };
        });
        let status = {
            label: element,
            // ensure value exists at every x -> contiguous line
            data: buildSeriesFor(element, response.x, response.y, 'status'),
            showLine: true,
            fill: false,
            borderColor: statusColor,
            backgroundColor: statusColor,
            borderWidth: 3,
        };
        statuses.push(applySmartLineMarkers(status));
    });
    // Remove statuses with no non-null points (all zeros / empty)
    statuses = statuses.filter((obj) => {
        return Array.isArray(obj.data) && obj.data.some(p => p && p.y !== null);
    });
    //console.log(statuses);

    const maxY = getMaxYFromDatasets(statuses);

    //doughnut data
    const courseData = response.courseData;

    let doughnutData = [];
    let doughnutLabels = [];
    let doughnutBackgroundColor = [];

    courseData.forEach((element) => {
        doughnutData.push(element.amount);
        doughnutLabels.push(element.statusName);
        doughnutBackgroundColor.push(getCourseStatusColor(element.statusName));
    });

    //linear graph draw — stacked composition per bucket reads better than
    //overlapping sparse lines for status distribution over time
    var chart = new Chart(ctx, {
        type: "bar",
        data: {
            //labels:response.x,
            datasets: statuses,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
                    },
                    stacked: true,
                },
                y: {
                    beginAtZero: true,
                    min: 0,
                    stacked: true,
                    // Add a little headroom so the max doesn't touch the top edge
                    suggestedMax: maxY > 0 ? (maxY + 2) : 1,
                    title: {
                        display: true,
                        text: 'Cantidad de Acciones de Formación'
                    },
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    }
                }
            },
            showLines: true,
                plugins: {
                title: { display: false }
            }
        },
    });

    // Doughnuts stop being legible past a handful of slices.
    if (doughnutData.length <= 6) {
        $(".row-graphs")
            .append(`<section class="report-block">
                <h3 class="report-block-title">Distribución de Acciones de Formación por Estatus durante el período ${startFmt} - ${endFmt}</h3>
                <div class="report-block-card is-chart">
                    <div class="doughnut-container">
                        <canvas id="doughnut" data-chart-key="status_doughnut"></canvas>
                    </div>
                </div>
            </section>`);

        const doughnut = document.getElementById('doughnut');

        new Chart(doughnut, {
            type: "doughnut",
            data: {
                datasets: [
                    {
                        data: doughnutData,
                        backgroundColor: doughnutBackgroundColor,
                    },
                ],
                labels: doughnutLabels,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: false }
                }
            },
        });
    }

    
    //list data table

    $(".table-col-helper").append(
        `<section class="report-block course-status-panel">
        <h3 class="report-block-title">Cantidad de Acciones de Formación por Estatus durante el período ${startFmt} - ${endFmt}</h3>
        <div class="report-block-card">
        <div class="table-responsive">
        <table class="course-status-amount-table table table-striped table-bordered table-center">
            <thead></thead>
            <tbody></tbody>
        </table>
        </div>
        </div>
        </section>`
    );

    $(".course-status-amount-table thead").append(`<tr>
                                                ${response.statuses.map(s => `<th>${s}</th>`).join('')}
                                            </tr>`);

     $(".course-status-amount-table tbody").append(`<tr></tr>`);
    
    $(".table-col-helper")
        .append(`<section class="report-block courses-by-date-range-status">
        <h3 class="report-block-title">Acciones de Formación Durante el período ${startFmt} - ${endFmt}</h3>
        <div class="report-block-card">
                <div class="table-responsive">
                    <table class="course-list table table-striped table-bordered table-center">
                    <thead>
                    </thead>
                    <tbody>
                    </tbody>
                    </table>
                </div>
        </div>
                </section>`);
    $(".course-list thead").append(`<tr>
                        <th>Título</th>
                        <th>Fecha de Inicio</th>
                        <th>Fecha de Culminación</th>
                        <th>Estatus</th>
                        </tr>`);

    

    courseData.forEach((element) => {
            element.courseData.forEach((helperA) => {
            //console.log(helperA);
            $(".course-list tbody").append(`<tr>
                                            <td>${helperA.course.title}</td>
                                            <td>${formatServerDate(helperA.start_date)}</td>
                                            <td>${formatServerDate(helperA.end_date)}</td>
                                            <td>${helperA.course_status.name}</td>
                                        </tr>`);
        });

        $('.course-status-amount-table tbody tr').append(`<td>${element.amount}</td>`);
    });

    // Paginate the (potentially long) per-run detail table
    initTablePagination(document.querySelector('.courses-by-date-range-status .course-list'), 25);
}//end report by course status

