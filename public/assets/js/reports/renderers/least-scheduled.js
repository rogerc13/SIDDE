import { $, refresh, renderReportHero, initTablePagination } from '../shared.js';

export function reportByCourseLeastScheduled(response, payload = {}) {
    refresh();

    // Sort by scheduled-run count, ascending (a "least scheduled" ranking),
    // breaking ties by title for a deterministic order.
    const amountOf = (el) => Number(response.amountData[el.id] ?? 0);
    const sorted = [...response.courseData].sort((a, b) => {
        const diff = amountOf(a) - amountOf(b);
        if (diff !== 0) {
            return diff;
        }
        return String(a.title || '').localeCompare(String(b.title || ''), 'es');
    });

    $(".table-col-helper").append(`<div class="panel panel-success">
    <div class="panel-heading">
        <div class="panel-title">Acciones de Formación menos programadas (Histórico)</div>
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

    // Paginate the (potentially long) historical least-scheduled list
    initTablePagination(document.querySelector('.course-list'), 25);
}
