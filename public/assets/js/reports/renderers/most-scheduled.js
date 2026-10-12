import { $, Chart, moment, refresh, renderReportHero, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByCourseMostScheduled(response, payload = {}){ //reports by course most scheduled
    console.log("most-scheduled");
    //draw list of courses
    refresh();

    // sort by scheduled-run count, descending (a "most scheduled" ranking)
    const amountOf = (el) => Number(response.amountData[el.id] ?? 0);
    const sorted = [...response.courseData].sort((a, b) => amountOf(b) - amountOf(a));

    $(".table-col-helper").append(`<section class="report-block">
    <h3 class="report-block-title">Acciones de Formación más programadas (Histórico)</h3>
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
        <th>Código</th>
        <th>Título</th>
        <th>Cantidad</th>
    </tr>`);

    sorted.forEach((element) => {
        const amount = amountOf(element) || 0;
        $(".course-list tbody").append(`<tr>
                                    <td>${element.code}</td>
                                    <td>${element.title}</td>
                                    <td>${amount}
                                    </td></tr>`);
    });

    renderReportHero({ meta: payload.meta });

    // Paginate the (potentially long) historical most-scheduled list
    initTablePagination(document.querySelector('.course-list'), 25);
}//end report by course most scheduled

