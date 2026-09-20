import { $, Chart, moment, refresh, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByCanceled(response){
    console.log("canceled");
    refresh();
    const list = Array.isArray(response.data) ? response.data : [];
    const startFmt = formatServerDate(response.dateRange && response.dateRange.startDate ? response.dateRange.startDate : '');
    const endFmt = formatServerDate(response.dateRange && response.dateRange.endDate ? response.dateRange.endDate : '');

    if (list.length !== 0) {
        $(".course-amount-number span").html(
            `<h3>Cantidad de Acciones de Formación Canceladas ${startFmt} - ${endFmt}: ${response.total ?? list.length}</h3>`
        );

        $(".table-col-helper").append(`<div class="panel panel-success">
            <div class="panel-heading">
                <div class="panel-title">
                    Acciones de Formación Canceladas durante el período ${startFmt} - ${endFmt}
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
        $(".course-amount-number").append(
            `<h3>No existen Acciones de Formación Canceladas en el período ${startFmt} - ${endFmt}</h3>`
        );
    }

    // Paginate the (potentially long) canceled-courses table
    initTablePagination(document.querySelector('.course-list'), 25);
}

