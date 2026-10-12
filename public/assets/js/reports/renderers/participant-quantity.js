import { $, Chart, moment, refresh, renderReportHero, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByParticipantQuantity(response, payload = {}){
    //reports by participant quantity
    console.log("participant-by-quantity");
    refresh();

    // runs without participants carry no signal for this comparison
    const withParticipants = response.dateRangeAmountPerCourse != 0
        ? response.dateRangeAmountPerCourse.filter((element) => Number(element.count) > 0)
        : [];

    if (withParticipants.length !== 0) {
        renderReportHero({ meta: payload.meta });

        //draw list
        $(".table-col-helper").append(`<section class="report-block">
            <h3 class="report-block-title">Acciones de Formación por Cantidad de Participantes</h3>
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

        $('.course-list thead').append(`<tr>
        <th>Título</th>
        <th>Fecha de Inicio</th>
        <th>Fecha de Culminación</th>
        <th>Cantidad de Participantes</th>
        <th>Ocupación %</th>
        </tr>`);

        withParticipants.forEach(element => {
            $(".course-list tbody").append(`<tr>
                <td>${element.course}</td>
                <td>${formatServerDate(element.start_date || '')}</td>
                <td>${formatServerDate(element.end_date || '')}</td>
                <td>${element.count}</td>
                <td>${element.fillRate !== null ? element.fillRate + '%' : '—'}</td>
            </tr>`);
        });
    } else {
        renderReportHero({ meta: payload.meta, empty: 'No existen Acciones de Formación con participantes asignados' });
    }

    // Paginate the (potentially long) participant-quantity table
    initTablePagination(document.querySelector('.course-list'), 25);
}//end report by participant quantity

