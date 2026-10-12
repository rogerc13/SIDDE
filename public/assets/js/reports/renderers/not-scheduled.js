import { $, Chart, moment, refresh, renderReportHero, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByCourseNotScheduled(response, payload = {}){
    console.log("not-scheduled");
    refresh();
    //draw table and show amount of courses
    if (response.data.length != 0) {
        renderReportHero({ meta: payload.meta });

        $(".table-col-helper").append(`<section class="report-block">
            <h3 class="report-block-title">Acciones de Formación No Programadas (Histórico)</h3>
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
                                        <th>Código</th>
                                        <th>Título</th>
                                        <th>Area de Conocimiento</th>
                                    </tr>`);

        response.data.forEach((element) => {
            $(".course-list tbody").append(`<tr>
                                        <td>${element.code}</td>
                                        <td>${element.title}</td>
                                        <td>${element.category.name}</td>
                                    </tr>`);
        });
    } else {
        renderReportHero({ meta: payload.meta, empty: 'No existen Acciones de Formación sin programar' });
    }

    // Paginate the (potentially long) not-scheduled list
    initTablePagination(document.querySelector('.course-list'), 25);
}
