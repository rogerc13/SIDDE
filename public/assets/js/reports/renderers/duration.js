import { $, Chart, moment, refresh, renderReportHero, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByDuration(response, payload = {}){ //reports by course duration
    console.log("duration");

    refresh();
    renderReportHero({ meta: payload.meta });

    //tables
    //Finished runs within the period (Culminado only)
    $(".table-col-helper")
        .append(`<section class="report-block courses-by-day-span">
            <h3 class="report-block-title">Acciones de Formación Culminadas en el período según cantidad de horas y días que abarcan</h3>
            <div class="report-block-card">
            <div class="table-responsive">
            <table class="course-day-span-all-time-list table table-striped table-bordered table-center">
            
                <thead>
                </thead>
                <tbody>
                </tbody>
            </table>
            </div>
            </div>
            </section>`);

    $('.course-day-span-all-time-list thead').append(`<tr>
        <th>Título</th>
        <th>Fecha de Inicio</th>
        <th>Fecha Fin</th>
        <th>Duración Horas</th>
        <th>Duración Días</th>
    </tr>`);

    if (Array.isArray(response.byDateRange) && response.byDateRange.length) {
        response.byDateRange.forEach(element => {
            $(".course-day-span-all-time-list tbody").append(`<tr>
                <td>${element.course_title ?? ''}</td>
                <td>${formatServerDate(element.start_date ?? '')}</td>
                <td>${formatServerDate(element.end_date ?? '')}</td>
                <td>${element.duration ?? ''}</td>
                <td>${element.duration_days ?? ''}</td>
            </tr>`);
        });
    } else {
        $(".course-day-span-all-time-list tbody").append(`<tr>
            <td colspan="5">No hay Acciones de Formación que mostrar para este período</td>
        </tr>`);
    }

    //Most duration hours — all-time, independent of the selected period
    $(".table-col-helper")
        .append(`<section class="report-block courses-by-hours">
        <h3 class="report-block-title">Cursos con más horas de duración (Histórico)</h3>
        <div class="report-block-card">
        <div class="table-responsive">
        <table class="course-most-duration-all-time-list table table-striped table-bordered table-center">
                <thead>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        </div>
        </section>`);

    $('.course-most-duration-all-time-list thead').append(`<tr>
    <th>Código</th>
    <th>Título</th>
    <th>Duración en Horas</th>
    </tr>`);

    response.mostDuration.forEach(element => {
        $('.course-most-duration-all-time-list tbody').append(`
        <tr>
            <td>${element.code}</td>
            <td>${element.title}</td>
            <td>${element.duration}</td>
        </tr>`);
    });

    // Paginate both duration tables (the historical list can be long)
    initTablePagination(document.querySelector('.course-day-span-all-time-list'), 25);
    initTablePagination(document.querySelector('.course-most-duration-all-time-list'), 25);
}//end report by course duration

