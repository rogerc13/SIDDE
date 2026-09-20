import { $, Chart, moment, refresh, formatServerDate, initTablePagination, decorateSortableReportTables, buildSeriesFor, applySmartLineMarkers, getMaxYFromDatasets, shouldConnectLineGaps, categoryColor, othersColor, courseStatusColor as paletteCourseStatusColor, participantStatusColor as paletteParticipantStatusColor, genderColor as paletteGenderColor, hexToRgba } from "../shared.js";
export function reportByCategory(response){
    
    refresh();
    const startFmt = formatServerDate(response.dateRange && response.dateRange.startDate ? response.dateRange.startDate : '');
    const endFmt = formatServerDate(response.dateRange && response.dateRange.endDate ? response.dateRange.endDate : '');
    $(".row-graphs")
        .append(`<div class="col-md-6">
            <div class="panel panel-success line-graph-panel">
                <div class="panel-heading">
                    <div class="panel-title">Cantidad de Acciones de Formación por Áreas de Conocimiento durante el período ${startFmt} - ${endFmt}</div>
                </div>
                <div class="panel-body">
                    <div class="graph-container">
                        <canvas id="myChart"></canvas>
                    </div>
                </div>
            </div>
        </div>`);

    const ctx = document.getElementById('myChart');

    // Shared categorical palette (single source of truth, synced with the PDF).
    const paletteColor = (i) => categoryColor(i);

    // Chart only categories with at least one run in the period; zeros
    // remain visible in the summary table below. To keep the bar/doughnut
    // legible when many categories tie at a low count, show the top N
    // individually and roll the rest into a single "Otras" slice.
    const TOP_N_CATEGORIES = 10;
    const nonZeroCategories = response.courseData
        .filter((c) => Number(c.amount) > 0)
        .sort((a, b) => (Number(b.amount) || 0) - (Number(a.amount) || 0));

    let chartEntries = nonZeroCategories.map((c, idx) => ({
        name: c.categoryName,
        amount: Number(c.amount) || 0,
        color: paletteColor(idx),
    }));

    if (nonZeroCategories.length > TOP_N_CATEGORIES) {
        const othersAmount = nonZeroCategories
            .slice(TOP_N_CATEGORIES)
            .reduce((sum, c) => sum + (Number(c.amount) || 0), 0);
        chartEntries = nonZeroCategories
            .slice(0, TOP_N_CATEGORIES)
            .map((c, idx) => ({
                name: c.categoryName,
                amount: Number(c.amount) || 0,
                color: paletteColor(idx),
            }));
        chartEntries.push({ name: 'Otras', amount: othersAmount, color: othersColor() });
    }

    // A single dataset with one value per category (not one dataset per
    // category). With indexAxis 'y' this places bar i on label-row i so the
    // bars line up with their y-axis labels instead of all collapsing onto
    // the first category row.
    const categories = [{
        label: 'Cantidad de Acciones de Formación',
        data: chartEntries.map(e => e.amount),
        backgroundColor: chartEntries.map(e => e.color),
        borderColor: chartEntries.map(e => e.color),
        borderWidth: 1,
    }];

    var chart = new Chart(ctx, {
        type: "bar",
        data: {
            labels: chartEntries.map(e => e.name),
            datasets: categories,
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: { display: true, text: 'Cantidad de Acciones de Formación' },
                    ticks: { precision: 0 }
                },
                y: {
                    ticks: { precision: 0 }
                }
            },
        },
    });
        
    // Doughnuts stop being legible past a handful of slices, so only render
    // one when there are few enough categories. The bar chart always remains.
    if (chartEntries.length <= 6) {
        $(".row-graphs").append(`
            <div class="col-md-6">
                <div class="panel panel-success doughnut-panel">
                    <div class="panel-heading">
                        <div class="panel-title">
Distribución de Acciones de Formación por Áreas de Conocimiento durante el período ${startFmt} - ${endFmt}
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="doughnut-container">
                            <canvas id="doughnut"></canvas>
                        </div>
                    </div>
                </div>
            </div>`);

        const doughnutEl = document.getElementById('doughnut');
        new Chart(doughnutEl, {
            type: "doughnut",
            data: {
                datasets: [
                    {
                        data: chartEntries.map(e => e.amount),
                        backgroundColor: chartEntries.map(e => e.color),
                    },
                ],
                labels: chartEntries.map(e => e.name),
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

    //tables
    $(".table-col-helper")
        .append(`<div class="panel panel-success course-by-category-list" style="page-break-inside: avoid">
                    
                    <div class="panel-heading">
                        <div class="panel-title">Cantidad de Acciones de Formación por Áreas de Conocimiento durante el período ${startFmt} - ${endFmt}</div>
                    </div>         
                    <div class="panel-body with-table table-responsive">   
                    <table class="course-category-list table table-striped table-bordered table-center">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                    </div>
                </div>`);
    
    $('.course-category-list thead').append(`<tr>
        <th>Área de Conocimiento</th>
        <th>Cantidad de Acciones de Formación</th>
    </tr>`);

    response.courseData.slice().sort((a, b) => (Number(b.amount) || 0) - (Number(a.amount) || 0)).forEach(element => {
        $(".course-category-list tbody").append(`<tr><td>${element.categoryName}</td>
                                                     <td>${element.amount}</td></tr>`);
    });

    // Paginate the (potentially long) category summary table
    initTablePagination(document.querySelector('.course-category-list'), 25);
    
    $(".table-col-helper").append(`<div class="panel panel-success course-by-category-by-date-range" style="page-break-inside: avoid">
            <div class="panel-heading">
            <div class="panel-title">Acciones de Formación por Áreas de Conocimiento durante el período ${startFmt} - ${endFmt}</div>
        </div>
            <div class="panel-body with-table table-responsive">
                <table class="course-data-category-list table table-striped table-bordered table-center">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>`);

    $(".course-data-category-list thead").append(`<tr>
                        <th>Título</th>
                        <th>Fecha de Inicio</th>
                        <th>Fecha de Culminación</th>
                        <th>Area de Conocimiento</th>
                        </tr>`);

    response.courseData.forEach(element => {
        element.courseData.forEach(helper => {
           $(".course-data-category-list tbody").append(`<tr>
            <td>${helper.course.title}</td>
            <td>${formatServerDate(helper.start_date)}</td>
            <td>${formatServerDate(helper.end_date)}</td>
            <td>${helper.course.category.name}</td>
        </tr>`);
        })
    });

    // Paginate the (potentially long) per-course detail table
    initTablePagination(document.querySelector('.course-data-category-list'), 25);
    
}//end report by category

