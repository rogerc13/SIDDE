import { $, Chart, moment, refresh, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByCourseNotScheduled(response){
    console.log("not-scheduled");
    refresh();
    //draw table and show amount of courses
    if (response.data.length != 0) {
        $(".course-amount-number").append(
            `<h3>Cantidad de Acciones de Formación No Programadas (Histórico): ${response.data.length}</h3>`
        );

        $(".table-col-helper").append(`<div class="panel panel-success">
            <div class="panel-heading">
				<div class="panel-title">
			  		Acciones de Formación No Programadas (Histórico)
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
                                    </tr>`);

        response.data.forEach((element) => {
            $(".course-list tbody").append(`<tr>
                                        <td>${element.code}</td>
                                        <td>${element.title}</td>
                                        <td>${element.category.name}</td>
                                    </tr>`);
        });
    } else {
        $(".course-amount-number").append(
            "<h3>No existen Acciones de Formación sin programar</h3>"
        );
    }

    // Paginate the (potentially long) not-scheduled list
    initTablePagination(document.querySelector('.course-list'), 25);
}
