import { $, Chart, moment, refresh, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByParticipantQuantity(response){
    //reports by participant quantity
    console.log("participant-by-quantity");
    refresh();

    // runs without participants carry no signal for this comparison
    const withParticipants = response.dateRangeAmountPerCourse != 0
        ? response.dateRangeAmountPerCourse.filter((element) => Number(element.count) > 0)
        : [];

    if (withParticipants.length !== 0) {
        const fillRows = withParticipants.filter((e) => e.fillRate !== null);
        const avgFill = fillRows.length
            ? Math.round(fillRows.reduce((s, e) => s + Number(e.fillRate), 0) / fillRows.length)
            : null;
        $(".course-amount-number span").html(
            `<h3>Acciones de Formación con Participantes en el período: ${withParticipants.length}` +
            (avgFill !== null ? ` &middot; Ocupación promedio: ${avgFill}%` : '') +
            `</h3>`
        );

        //draw list
        $(".table-col-helper").append(`<div class="panel panel-success">
            <div class="panel-heading">
                <div class="panel-title">
                    Acciones de Formación por Cantidad de Participantes
                </div>
            </div>
            <div class="panel-body with-table table-responsive">
            <table class="course-list table table-striped table-bordered table-center">
                <thead>
                </thead>
                <tbody>
                </tbody>
            </table>
            </div>
            </div>`);

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
        $('.course-amount-number').append('<h3>No existen Acciones de Formación con participantes asignados en este período de tiempo</h3>');
    }

    // Paginate the (potentially long) participant-quantity table
    initTablePagination(document.querySelector('.course-list'), 25);
}//end report by participant quantity

