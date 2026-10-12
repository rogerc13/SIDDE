import { $, Chart, moment, refresh, renderReportHero, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByCanceled(response, payload = {}){
    console.log("canceled");
    refresh();
    const list = Array.isArray(response.data) ? response.data : [];
    const startFmt = formatServerDate(response.dateRange && response.dateRange.startDate ? response.dateRange.startDate : '');
    const endFmt = formatServerDate(response.dateRange && response.dateRange.endDate ? response.dateRange.endDate : '');

    if (list.length !== 0) {
        renderReportHero({ meta: payload.meta });

        $(".table-col-helper").append(`<section class="report-block">
            <h3 class="report-block-title">Acciones de Formación Canceladas durante el período ${startFmt} - ${endFmt}</h3>
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
                                        <th>Inicio</th>
                                        <th>Fin</th>
                                    </tr>`);

        list.forEach((element) => {
            $(".course-list tbody").append(`<tr>
                                        <td>${element.code}</td>
                                        <td>${element.title}</td>
                                        <td>${element.category}</td>
                                        <td>${formatServerDate(element.start_date)}</td>
                                        <td>${formatServerDate(element.end_date)}</td>
                                    </tr>`);
        });
    } else {
        renderReportHero({ meta: payload.meta, empty: 'No existen Acciones de Formación Canceladas' });
    }

    // Paginate the (potentially long) canceled-courses table
    initTablePagination(document.querySelector('.course-list'), 25);
}

