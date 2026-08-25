        //select time range
        //select intervals, time steps
        //type of report
        //additional filters: finished courses, participant status
        //set conditionals depending on selected filters eg: a 6 month period cant have an anual interval
        //if any graph or list exists on button submit, clear the report container
        //
        //show graph
        //show pie graph
        //show amount of courses or people queried along with graphs if necessary
        //show list of courses or people if necessary
        //print report
function refresh(){
    // keep the inner span: several renderers write summaries into it
    $(".course-amount-number").html("<span></span>");
    $('.row-graphs').html('');
    $(".table-col-helper").html("");
}

// -----------------------------
// Report tables: pagination (client-side)
// -----------------------------
let __reportTableIdCounter = 0;

function ensureTableId(tableEl){
    if (!tableEl) {
        return null;
    }
    if (tableEl.id) {
        return tableEl.id;
    }
    __reportTableIdCounter += 1;
    tableEl.id = `report-table-${__reportTableIdCounter}`;
    return tableEl.id;
}

function getPaginationState(tableEl){
    const pageSize = Number(tableEl.dataset.reportPageSize || 0);
    const page = Number(tableEl.dataset.reportPage || 1);
    return {
        pageSize: Number.isFinite(pageSize) && pageSize > 0 ? pageSize : null,
        page: Number.isFinite(page) && page > 0 ? page : 1,
    };
}

function renderTablePage(tableEl){
    if (!tableEl || !tableEl.tBodies || !tableEl.tBodies[0]) {
        return;
    }
    const state = getPaginationState(tableEl);
    if (!state.pageSize) {
        return;
    }

    const tbody = tableEl.tBodies[0];
    const rows = Array.from(tbody.rows || []);
    const totalRows = rows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / state.pageSize));
    const page = Math.min(Math.max(1, state.page), totalPages);
    tableEl.dataset.reportPage = String(page);

    const startIdx = (page - 1) * state.pageSize;
    const endIdx = startIdx + state.pageSize;

    rows.forEach((row, idx) => {
        row.style.display = (idx >= startIdx && idx < endIdx) ? '' : 'none';
    });

    const pager = document.querySelector(`.report-pagination-controls[data-report-pagination-for="${CSS.escape(tableEl.id)}"]`);
    if (pager) {
        const label = pager.querySelector('.report-pagination-label');
        const prevBtn = pager.querySelector('.report-pagination-prev');
        const nextBtn = pager.querySelector('.report-pagination-next');

        if (label) {
            label.textContent = `Página ${page} de ${totalPages} (${totalRows})`;
        }
        if (prevBtn) {
            prevBtn.disabled = page <= 1;
        }
        if (nextBtn) {
            nextBtn.disabled = page >= totalPages;
        }
    }
}

function initTablePagination(tableEl, pageSize){
    if (!tableEl) {
        return;
    }
    ensureTableId(tableEl);

    const size = Number(pageSize);
    if (!Number.isFinite(size) || size <= 0) {
        return;
    }

    tableEl.dataset.reportPageSize = String(size);
    if (!tableEl.dataset.reportPage) {
        tableEl.dataset.reportPage = '1';
    }

    const existing = document.querySelector(`.report-pagination-controls[data-report-pagination-for="${CSS.escape(tableEl.id)}"]`);
    if (!existing) {
        const controls = document.createElement('div');
        controls.className = 'report-pagination-controls text-center';
        controls.setAttribute('data-report-pagination-for', tableEl.id);
        controls.style.marginTop = '8px';

        controls.innerHTML = `
            <button type="button" class="btn btn-default btn-sm report-pagination-prev">Anterior</button>
            <span class="report-pagination-label" style="margin: 0 10px;"></span>
            <button type="button" class="btn btn-default btn-sm report-pagination-next">Siguiente</button>
        `;

        // Insert right after the table
        tableEl.parentNode.insertBefore(controls, tableEl.nextSibling);
    }

    renderTablePage(tableEl);
}
function normalizeSortText(value){
    if (value === null || value === undefined) {
        return '';
    }
    return String(value).replace(/\s+/g, ' ').trim();
}

function parseLocaleNumber(text){
    const t0 = normalizeSortText(text);
    if (!t0) {
        return null;
    }

    // Remove non-numeric characters except separators and minus.
    let t = t0.replace(/\s/g, '').replace(/[^0-9,\.\-]/g, '');
    if (!t || t === '-' || t === '.' || t === ',') {
        return null;
    }

    const lastDot = t.lastIndexOf('.');
    const lastComma = t.lastIndexOf(',');

    if (lastDot !== -1 && lastComma !== -1) {
        // If both separators exist, decide which is decimal by last occurrence.
        if (lastComma > lastDot) {
            // 1.234,56 -> 1234.56
            t = t.replace(/\./g, '').replace(',', '.');
        } else {
            // 1,234.56 -> 1234.56
            t = t.replace(/,/g, '');
        }
    } else if (lastComma !== -1) {
        // Only comma. If it looks like decimal, convert to dot; else treat as thousand sep.
        if (/,-?\d{1,4}$/.test(t)) {
            t = t.replace(',', '.');
        } else if (/,\d{1,4}$/.test(t)) {
            t = t.replace(',', '.');
        } else {
            t = t.replace(/,/g, '');
        }
    } else if (lastDot !== -1) {
        // Only dot. If it doesn't look like decimal, treat as thousand sep.
        if (!/\.\d{1,4}$/.test(t)) {
            t = t.replace(/\./g, '');
        }
    }

    const num = Number(t);
    return Number.isFinite(num) ? num : null;
}

function parseDateValue(text){
    const t0 = normalizeSortText(text);
    if (!t0) {
        return null;
    }
    // Take first token in case it includes time.
    const t = t0.split(' ')[0];

    // YYYY-MM-DD
    let m = t.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (m) {
        const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
        const time = d.getTime();
        return Number.isNaN(time) ? null : time;
    }

    // DD/MM/YYYY
    m = t.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (m) {
        const d = new Date(Number(m[3]), Number(m[2]) - 1, Number(m[1]));
        const time = d.getTime();
        return Number.isNaN(time) ? null : time;
    }

    // DD-MM-YYYY
    m = t.match(/^(\d{2})-(\d{2})-(\d{4})$/);
    if (m) {
        const d = new Date(Number(m[3]), Number(m[2]) - 1, Number(m[1]));
        const time = d.getTime();
        return Number.isNaN(time) ? null : time;
    }

    return null;
}

function getCellSortValue(cell){
    if (!cell) {
        return '';
    }
    // Prefer data-sort-value if present, so views can override without changing display.
    const attr = cell.getAttribute('data-sort-value');
    if (attr !== null && attr !== undefined) {
        return attr;
    }
    return cell.textContent;
}

function sortTableByColumn(tableEl, colIndex, direction){
    if (!tableEl) {
        return;
    }
    const tbody = tableEl.tBodies && tableEl.tBodies[0];
    if (!tbody) {
        return;
    }
    const rows = Array.from(tbody.rows || []);
    if (rows.length <= 1) {
        return;
    }

    const collator = (typeof Intl !== 'undefined' && Intl.Collator)
        ? new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' })
        : null;

    const dir = direction === 'desc' ? -1 : 1;

    const decorated = rows.map((row, idx) => {
        const cell = row.cells ? row.cells[colIndex] : null;
        const raw = normalizeSortText(getCellSortValue(cell));
        const num = parseLocaleNumber(raw);
        const date = parseDateValue(raw);

        return {
            row,
            idx,
            raw,
            num,
            date,
        };
    });

    decorated.sort((a, b) => {
        // Empty values always sort last (both directions)
        const aEmpty = !a.raw;
        const bEmpty = !b.raw;
        if (aEmpty && bEmpty) {
            return a.idx - b.idx;
        }
        if (aEmpty) {
            return 1;
        }
        if (bEmpty) {
            return -1;
        }

        // If both parse as numbers, compare numbers
        if (a.num !== null && b.num !== null) {
            if (a.num === b.num) {
                return a.idx - b.idx;
            }
            return (a.num < b.num ? -1 : 1) * dir;
        }

        // If both parse as dates, compare dates
        if (a.date !== null && b.date !== null) {
            if (a.date === b.date) {
                return a.idx - b.idx;
            }
            return (a.date < b.date ? -1 : 1) * dir;
        }

        // Otherwise, compare as text (natural sort when available)
        const cmp = collator ? collator.compare(a.raw, b.raw) : a.raw.localeCompare(b.raw);
        if (cmp === 0) {
            return a.idx - b.idx;
        }
        return cmp * dir;
    });

    // Re-append in sorted order
    const frag = document.createDocumentFragment();
    decorated.forEach(item => frag.appendChild(item.row));
    tbody.appendChild(frag);

    // If the table is paginated, re-render the current page
    if (tableEl && tableEl.dataset && tableEl.dataset.reportPageSize) {
        renderTablePage(tableEl);
    }
}

function decorateSortableReportTables($root){
    const root = ($root && $root.length) ? $root.get(0) : document;
    const tables = root.querySelectorAll('table');

    tables.forEach(tableEl => {
        if (tableEl.dataset && tableEl.dataset.reportSortReady === '1') {
            return;
        }
        const thead = tableEl.tHead;
        if (!thead || !thead.rows || !thead.rows.length) {
            return;
        }

        const headerRow = thead.rows[0];
        const ths = Array.from(headerRow.cells || []).filter(cell => cell && cell.tagName === 'TH');
        if (ths.length === 0) {
            return;
        }

        // Mark headers as interactive
        ths.forEach(th => {
            th.style.cursor = 'pointer';
            th.setAttribute('tabindex', '0');
            th.setAttribute('role', 'button');
            if (!th.getAttribute('aria-sort')) {
                th.setAttribute('aria-sort', 'none');
            }
            if (!th.getAttribute('title')) {
                th.setAttribute('title', 'Click para ordenar');
            }

            // Add a sort icon indicator using existing Font Awesome (FA4 style).
            // Only add once per header.
            if (!th.querySelector('.report-sort-icon')) {
                const icon = document.createElement('i');
                icon.className = 'fa fa-sort report-sort-icon text-muted';
                icon.setAttribute('aria-hidden', 'true');
                icon.style.marginLeft = '6px';
                th.appendChild(icon);
            }
        });

        tableEl.dataset.reportSortReady = '1';
    });
}

// helper: build a point for every x label so the dataset is contiguous
function buildSeriesFor(label, xLabels, points, keyName){
    // points assumed like { x: '2025-10-01', y: 3, category: 'X' } or { x, y, status }
    const map = {};
    points.forEach(p => {
        if(p[keyName] === label){
            // Treat 0 (and negative) as no-data so it doesn't render
            map[p.x] = (typeof p.y === 'number' && p.y > 0) ? p.y : null;
        }
    });
    // return null for missing values so Chart.js will connect across gaps when spanGaps:true
    return xLabels.map(x => ({ x: x, y: (map[x] !== undefined ? map[x] : null) }));
}


function participantStatusSelect(){ //participant status select dropdown
    $.ajax({
        type:'GET',
        url: '/reports/participant-status-select',
        dataType: 'json',
        success: function(response){
            // If server returns proper JSON, jQuery already gives an object here.
            // If it comes back as a string for some reason, fallback to parsing.
            if (typeof response === 'string') {
                response = JSON.parse(response);
            }

            (response.statuses || []).forEach(status => {
                $("#participant_status").append(
                    `<option value="${status.id}">${status.name}</option>`
                );
            });
        },
        error: function(response){
            console.log(response);
        }
    });
};

function shouldHideLinePoints(){
    // Hide point markers for daily granularity to keep the chart readable
    return $('#step').val() === '1 day';
}

function shouldConnectLineGaps(){
    // For daily charts, connect across nulls so the line reads as a trend
    return $('#step').val() === '1 day';
}

function applySmartLineMarkers(dataset){
    const points = Array.isArray(dataset.data) ? dataset.data : [];

    const nonNullIndexes = [];
    const values = [];
    points.forEach((p, idx) => {
        if (p && p.y !== null && typeof p.y === 'number') {
            nonNullIndexes.push(idx);
            values.push(p.y);
        }
    });

    if (nonNullIndexes.length === 0) {
        return {
            ...dataset,
            pointRadius: 0,
            pointHoverRadius: 0,
        };
    }

    const firstIdx = nonNullIndexes[0];
    const lastIdx = nonNullIndexes[nonNullIndexes.length - 1];
    const minVal = Math.min(...values);
    const maxVal = Math.max(...values);

    let minIdx = firstIdx;
    let maxIdx = firstIdx;
    for (const idx of nonNullIndexes) {
        const y = points[idx].y;
        if (y === minVal) {
            minIdx = idx;
            break;
        }
    }
    for (const idx of nonNullIndexes) {
        const y = points[idx].y;
        if (y === maxVal) {
            maxIdx = idx;
            break;
        }
    }

    const markerIndexes = new Set([firstIdx, lastIdx, minIdx, maxIdx]);

    return {
        ...dataset,
        pointRadius: (ctx) => (markerIndexes.has(ctx.dataIndex) ? 3 : 0),
        pointHoverRadius: (ctx) => (markerIndexes.has(ctx.dataIndex) ? 5 : 0),
        pointHitRadius: (ctx) => (markerIndexes.has(ctx.dataIndex) ? 8 : 0),
    };
}

function getMaxYFromDatasets(datasets){
    let maxY = 0;
    (datasets || []).forEach(ds => {
        const points = Array.isArray(ds.data) ? ds.data : [];
        points.forEach(p => {
            if (p && typeof p.y === 'number' && p.y > maxY) {
                maxY = p.y;
            }
        });
    });
    return maxY;
}

// Format server date (server supplies YYYY-MM-DD). Returns DD-MM-YYYY for display.
function formatServerDate(dateStr){
    if (!dateStr && dateStr !== 0) return '';
    try {
        if (typeof moment === 'function') {
            // Try strict parse for common formats; fall back to loose parse.
            let m = moment(dateStr, ['YYYY-MM-DD','YYYY-M-DD','YYYY-MM-D','YYYY-M-D','DD-MM-YYYY','D-M-YYYY'], true);
            if (!m.isValid()) {
                m = moment(dateStr);
            }
            return m.isValid() ? m.format('DD-MM-YYYY') : String(dateStr);
        }
    } catch (e) {
        // fall through to naive fallback
    }

    // Naive fallback: handle 'YYYY-MM-DD' -> 'DD-MM-YYYY'
    const ymd = /^(\d{4})-(\d{2})-(\d{2})$/;
    const dmy = /^(\d{2})-(\d{2})-(\d{4})$/;
    let m = String(dateStr).match(ymd);
    if (m) return `${m[3]}-${m[2]}-${m[1]}`;
    m = String(dateStr).match(dmy);
    if (m) return String(dateStr);
    // Try Date parse
    const dt = new Date(dateStr);
    if (!Number.isNaN(dt.getTime())){
        const dd = String(dt.getDate()).padStart(2,'0');
        const mm = String(dt.getMonth()+1).padStart(2,'0');
        const yy = dt.getFullYear();
        return `${dd}-${mm}-${yy}`;
    }
    return String(dateStr);
}

function reportByDate(response){ //reports by date
    refresh();
    const startDateFmt = formatServerDate(response.start_date);
    const endDateFmt = formatServerDate(response.end_date);
    $(".row-graphs").append(`<div class="panel panel-success by-date-line">
        <div class="panel-heading">
            <div class="panel-title">Cantidad de Acciones de Formación durante el Período ${startDateFmt} - ${endDateFmt} : ${response.total}</div>
        </div>    
        <div class="panel-body">
            <div class="h-25 col-xs-12 col-md-12 graph-container">
                <canvas id="myChart"></canvas>
            </div>
        </div>
        </div>`);

    $('.course-amount-number span').html("");
    $(".course-amount-number span").append(
        `<h3 class="text-center">Cantidad de Acciones de Formación durante el Período ${startDateFmt} - ${endDateFmt} : ${response.total}</h3>`
    );

    // Build array of {x, y} objects for time-based line chart, just like the other working graphs
    let lineData = [];
    if (Array.isArray(response.x) && Array.isArray(response.y)) {
        // Flatten x if it's an array of arrays (as in the controller)
        let xArr = response.x.map(val => Array.isArray(val) ? val[0] : val);
        lineData = xArr.map((date, idx) => {
            let yVal = response.y[idx];
            // keep zeros: empty days are part of the story for a per-day count
            return { x: date, y: (typeof yVal === 'number') ? yVal : null };
        });
    }
    const ctx = document.getElementById('myChart'); // DOM element
    // or to be explicit: const ctx = document.getElementById('myChart').getContext('2d');

    const markedDataset = applySmartLineMarkers({
        label: 'Cantidad de Acciones de Formacion',
        data: lineData,
        fill: false,
        borderColor: 'rgb(75, 192, 192)',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        borderWidth: 3,
        tension: 0.5,
        cubicInterpolationMode: 'monotone'
    });

    const maxY = getMaxYFromDatasets([markedDataset]);

    var chart = new Chart(ctx, {
        type: "line",
        data: {
            labels: response.x,
            datasets: [markedDataset]
        },
        options: {
            // For daily charts, connect across nulls; for others, keep gaps.
            spanGaps: shouldConnectLineGaps(),
            responsive: true,
            maintainAspectRatio: false,
            elements: {
                point: {
                    radius: 0,
                    hoverRadius: 0
                }
            },
            scales: {
                x: {
                    type: 'time',
                    title: {
                        display: true,
                        text: 'Período de Tiempo'
                    },
                    time: {
                        parser: 'YYYY-MM-DD',
                        tooltipFormat: 'YYYY-MM-DD'
                    }
                },
                y: {
                    beginAtZero: true,
                    // Add a little headroom so the max doesn't touch the top edge
                    suggestedMax: maxY > 0 ? (maxY + 2) : 1,
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    }
                }
            },
        }
    });
}//end report by date

function reportByCategory(response){
    
    refresh();
    const startFmt = formatServerDate(response.dateRange && response.dateRange.startDate ? response.dateRange.startDate : '');
    const endFmt = formatServerDate(response.dateRange && response.dateRange.endDate ? response.dateRange.endDate : '');
    $(".row-graphs")
        .append(`<div class="col-md-6">
            <div class="panel panel-success line-graph-panel">
                <div class="panel-heading">
                    <div class="panel-title">Cantidad de Acciones de Formacion por Áreas de Conocimiento durante el período ${startFmt} - ${endFmt}</div>
                </div>
                <div class="panel-body">
                    <div class="graph-container">
                        <canvas id="myChart"></canvas>
                    </div>
                </div>
            </div>
        </div>`);

    const ctx = document.getElementById('myChart');

    // Fixed categorical palette: stable across renders and shared by every
    // chart in this report (random colors collided and changed per render).
    const CATEGORY_PALETTE = [
        '#4E79A7', '#F28E2B', '#E15759', '#76B7B2', '#59A14F',
        '#EDC948', '#B07AA1', '#FF9DA7', '#9C755F', '#BAB0AC',
        '#86BCB6', '#D37295', '#FABFD2', '#B6992D', '#499894',
        '#D4A6C8', '#1170AA', '#7FBFF2', '#57606C', '#FC7D0B',
    ];
    const paletteColor = (i) => CATEGORY_PALETTE[i % CATEGORY_PALETTE.length];

    // Only categories with at least one run in the period get charted; zeros
    // remain visible in the summary table below.
    const activeCategoryNames = response.categories.filter((name) =>
        response.courseData.some((c) => c.categoryName === name && Number(c.amount) > 0)
    );

    // Rankings read better as bars than as a multi-line plot: one bar per
    // category with runs in the period, largest at the top.
    const rankedCategories = response.courseData
        .filter((c) => Number(c.amount) > 0)
        .sort((a, b) => (Number(b.amount) || 0) - (Number(a.amount) || 0));

    let categories = rankedCategories.map((c, idx) => ({
        label: c.categoryName,
        data: [Number(c.amount) || 0],
        backgroundColor: paletteColor(idx),
        borderColor: paletteColor(idx),
        borderWidth: 1,
    }));

    var chart = new Chart(ctx, {
        type: "bar",
        data: {
            labels: rankedCategories.map(c => c.categoryName),
            datasets: categories,
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: `Cantidad de Acciones de Formacion por Áreas de Conocimiento durante el período ${startFmt} - ${endFmt}`,
                }
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
        
    $(".row-graphs").append(`
        <div class="col-md-6">
            <div class="panel panel-success doughnut-panel">
                <div class="panel-heading">
                    <div class="panel-title">
                        Distribución de Acciones de Formación por Areas de Conocimiento durante el período ${startFmt} - ${endFmt}
                    </div>
                </div>
                <div class="panel-body">
                    <div class="doughnut-container">
                        <canvas id="doughnut"></canvas>
                    </div>
                </div>
            </div>
        </div>`);

    //doughnut data — only categories with runs in the period
    let doughnutData = [];
    let doughnutLabels = [];
    let doughnutBackgroundColor = [];

    response.courseData.forEach((element) => {
        if (Number(element.amount) === 0) {
            return;
        }
        const idx = activeCategoryNames.indexOf(element.categoryName);
        doughnutData.push(element.amount);
        doughnutLabels.push(element.categoryName);
        doughnutBackgroundColor.push(paletteColor(idx >= 0 ? idx : doughnutLabels.length - 1));
    });

    const doughnutEl = document.getElementById('doughnut');
    var myDoughnutChart = new Chart(doughnutEl, {
        type: "doughnut",
        data: {
            datasets: [
                {
                    data: doughnutData,
                    backgroundColor: doughnutBackgroundColor,
                },
            ],
            labels: doughnutLabels,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: `Distribución de Acciones de Formación por Areas de Conocimiento durante el período ${startFmt} - ${endFmt}`,
                }
            }
        },
    });

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
        <th>Área de Conomiento</th>
        <th>Cantidad de Acciones de Formación</th>
    </tr>`);

    response.courseData.slice().sort((a, b) => (Number(b.amount) || 0) - (Number(a.amount) || 0)).forEach(element => {
        $(".course-category-list tbody").append(`<tr><td>${element.categoryName}</td>
                                                     <td>${element.amount}</td></tr>`);
    });
    
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
    
}//end report by category

function reportByCourseStatus(response){ //reports by course status
    console.log("status");

    refresh();
    const startFmt = formatServerDate(response.dateRange && response.dateRange.startDate ? response.dateRange.startDate : '');
    const endFmt = formatServerDate(response.dateRange && response.dateRange.endDate ? response.dateRange.endDate : '');

    const COURSE_STATUS_COLORS = {
        'por iniciar': '#FAD839',
        'por dictar': '#FAD839',
        'en curso': '#21A9E1',
        'cancelado': '#CC2424',
        'culminado': '#00A651',
    };

    const normalizeCourseStatusKey = (value) => {
        return String(value ?? '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    };

    const getCourseStatusColor = (statusName) => {
        const key = normalizeCourseStatusKey(statusName);
        return COURSE_STATUS_COLORS[key] || '#999999';
    };
    $(".row-graphs")
        .append(`<div class="col-md-6">
            <div class="panel panel-success by-status-line-panel">
                <div class="panel-heading">
                    <div class="panel-title">Distribución de Acciones de Formación por Estatus durante el período ${startFmt} - ${endFmt}</div>
                </div>
                <div class="panel-body">
                    <div class="h-25 col-xs-12 col-md-12 graph-container">
                        <canvas id="myChart"></canvas>
                    </div>
                </div>
            </div>
        </div>`);

    const ctx = document.getElementById('myChart'); //linear graph selector


    //linear graph data
    let statuses = [];
    response.statuses.forEach((element) => {
        const statusColor = getCourseStatusColor(element);
        // For each status, build an array of {x, y} objects for the time-based line chart
        let statusData = response.x.map(date => {
            let found = response.y.find(obj => obj.status === element && obj.x === date);
            return {
                x: date,
                y: found ? (found.y > 0 ? found.y : null) : null
            };
        });
        let status = {
            label: element,
            // ensure value exists at every x -> contiguous line
            data: buildSeriesFor(element, response.x, response.y, 'status'),
            showLine: true,
            fill: false,
            borderColor: statusColor,
            backgroundColor: statusColor,
            borderWidth: 3,
        };
        statuses.push(applySmartLineMarkers(status));
    });
    // Remove statuses with no non-null points (all zeros / empty)
    statuses = statuses.filter((obj) => {
        return Array.isArray(obj.data) && obj.data.some(p => p && p.y !== null);
    });
    //console.log(statuses);

    const maxY = getMaxYFromDatasets(statuses);

    //doughnut data
    courseData = response.courseData;

    let doughnutData = [];
    let doughnutLabels = [];
    let doughnutBackgroundColor = [];

    courseData.forEach((element) => {
        doughnutData.push(element.amount);
        doughnutLabels.push(element.statusName);
        doughnutBackgroundColor.push(getCourseStatusColor(element.statusName));
    });

    //linear graph draw — stacked composition per bucket reads better than
    //overlapping sparse lines for status distribution over time
    var chart = new Chart(ctx, {
        type: "bar",
        data: {
            //labels:response.x,
            datasets: statuses,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'time',
                    title: {
                        display: true,
                        text: 'Período de Tiempo'
                    },
                    time: {
                        parser: 'YYYY-MM-DD',
                        tooltipFormat: 'YYYY-MM-DD'
                    },
                    stacked: true,
                },
                y: {
                    beginAtZero: true,
                    min: 0,
                    stacked: true,
                    // Add a little headroom so the max doesn't touch the top edge
                    suggestedMax: maxY > 0 ? (maxY + 2) : 1,
                    title: {
                        display: true,
                        text: 'Cantidad de Acciones de Formación'
                    },
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    }
                }
            },
            showLines: true,
                plugins: {
                title: {
                    display: true,
                    text: `Distribución de Acciones de Formación por Estatus durante el período ${startFmt} - ${endFmt}`,
                }
            }
        },
    });

    //doughnut graph draw
    $(".row-graphs")
        .append(`<div class="col-md-6">
            <div class="panel panel-success by-status-doughnut-panel">
                <div class="panel-heading">
                    <div class="panel-title">Distribución de Acciones de Formación por Estatus durante el período ${startFmt} - ${endFmt}</div>
                </div>
                <div class="panel-body">
                    <div class="doughnut-container">
                        <canvas id="doughnut"></canvas>
                    </div>
                </div>
            </div>
        </div>`);

    const doughnut = document.getElementById('doughnut');

    var myDoughnutChart = new Chart(doughnut, {
        type: "doughnut",
        data: {
            datasets: [
                {
                    data: doughnutData,
                    backgroundColor: doughnutBackgroundColor,
                },
            ],
            labels: doughnutLabels,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: `Distribución de Acciones de Formación por Estatus durante el período ${startFmt} - ${endFmt}`,
                }
            }
        },
    });

    
    //list data table

    $(".table-col-helper").append(
        `<div class="panel panel-success course-status-panel">
        <div class="panel-heading">
            <div class="panel-title">Cantidad de Acciones de Formación por Estatus durante el período ${startFmt} - ${endFmt}</div>
        </div>
        <div class="panel-body with-table table-responsive"> 
        <table class="course-status-amount-table table table-striped table-bordered table-center">
            <thead></thead>
            <tbody></tbody>
        </table>
        </div>
        </div>`
    );

    $(".course-status-amount-table thead").append(`<tr>
                                                ${response.statuses.map(s => `<th>${s}</th>`).join('')}
                                            </tr>`);

     $(".course-status-amount-table tbody").append(`<tr></tr>`);
    
    $(".table-col-helper")
        .append(`<div class="panel panel-success courses-by-date-range-status">
        <div class="panel-heading">
            <div class="panel-title">Acciones de Formación Durante el período ${startFmt} - ${endFmt}</div>
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
                        <th>Título</th>
                        <th>Fecha de Inicio</th>
                        <th>Fecha de Culminación</th>
                        <th>Estatus</th>
                        </tr>`);

    

    courseData.forEach((element) => {
            element.courseData.forEach((helperA) => {
            //console.log(helperA);
            $(".course-list tbody").append(`<tr>
                                            <td>${helperA.course.title}</td>
                                            <td>${formatServerDate(helperA.start_date)}</td>
                                            <td>${formatServerDate(helperA.end_date)}</td>
                                            <td>${helperA.course_status.name}</td>
                                        </tr>`);
        });

        $('.course-status-amount-table tbody tr').append(`<td>${element.amount}</td>`);
    });

    // Paginate the (potentially long) per-run detail table
    initTablePagination(document.querySelector('.courses-by-date-range-status .course-list'), 25);
}//end report by course status

function reportByDuration(response){ //reports by course duration
    console.log("duration");

    refresh();
    const startFmt = formatServerDate(response.dateRange && response.dateRange.startDate ? response.dateRange.startDate : '');
    const endFmt = formatServerDate(response.dateRange && response.dateRange.endDate ? response.dateRange.endDate : '');

    $(".course-amount-number").html(
        `<h3>Total de horas impartidas durante el período ${startFmt} - ${endFmt} : ${response.finishedByDateRange} Horas</h3>`
    );

    //tables
    //Finished runs within the period (Culminado only)
    $(".table-col-helper")
        .append(`<div class="panel panel-success courses-by-day-span">
            <div class="panel-heading">
                <div class="panel-title">Acciones de Formación Culminadas en el período según cantidad de horas y días que abarcan</div>
            </div>
            <div class="panel-body with-table table-responsive">
            <table class="course-day-span-all-time-list table table-striped table-bordered table-center">
            
                <thead>
                </thead>
                <tbody>
                </tbody>
            </table>
            </div>
            </div>`);

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
        .append(`<div class="panel panel-success courses-by-hours">
        <div class="panel-heading">
            <div class="panel-title">Cursos con más horas de duración (Histórico)</div>
        </div>
        <div class="panel-body with-table table-responsive">
        <table class="course-most-duration-all-time-list table table-striped table-bordered table-center">
                <thead>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        </div>`);

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
    
    
    
}//end report by course duration

function reportByParticipantStatus(response){
    //reports by participant status
    console.log("participant-by-status");
    refresh();

    const PARTICIPANT_STATUS_COLORS = {
        'en curso': '#00A651',
        'aprobado': '#21A9E1',
        'reprobado': '#CC2424',
        'suspendido': '#CC2424',
        'cancelado': '#F0F0F1',
        'por iniciar': '#FAD839',
        'en espera de curso': '#FAD839',
    };

    const normalizeParticipantStatusKey = (value) => {
        return String(value ?? '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    };

    const getParticipantStatusColor = (statusName) => {
        const key = normalizeParticipantStatusKey(statusName);
        return PARTICIPANT_STATUS_COLORS[key] || '#999999';
    };

    // Chart containers (were missing, so charts had no canvas to render into)
    $(".row-graphs").append(`
        <div class="col-md-6">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <div class="panel-title">Distribución de Participantes por Estatus (Barras)</div>
                </div>
                <div class="panel-body">
                    <div class="h-25 col-xs-12 col-md-12 graph-container">
                        <canvas id="myChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <div class="panel-title">Distribución de Participantes por Estatus (Dona)</div>
                </div>
                <div class="panel-body">
                    <div class="doughnut-container">
                        <canvas id="doughnut"></canvas>
                    </div>
                </div>
            </div>
        </div>
    `);

    const start_date = (response.dateRange && response.dateRange.startDate)
        ? response.dateRange.startDate
        : (response.byStatusByDateRange && response.byStatusByDateRange[0] ? response.byStatusByDateRange[0].date : '');

    const end_date = (response.dateRange && response.dateRange.endDate)
        ? response.dateRange.endDate
        : (response.byStatusByDateRange && response.byStatusByDateRange.length ? response.byStatusByDateRange[response.byStatusByDateRange.length - 1].date : '');

    // Format for display
    const start_date_fmt = formatServerDate(start_date);
    const end_date_fmt = formatServerDate(end_date);

    //total amount of paticipants all time given status

    //$().html(response.byAllTime.length);
    //console.log(response.byAllTime.length);

    //
    let participantStatusesDateRange = [];
    participantStatusesDateRange = response.allStatusbyDateRange.filter(
        (obj) => {
            return obj.countByStatus > 0;
        }
    );
    //returns Status name , amount

    //charts by date range
    //doughnut Data
    let doughnutDataStatus = [];
    let doughnutLabelsStatus = [];
    let amountHelperStatus = 0;
    let doughnutBackgroundColorStatus = [];
    response.labels.forEach((element) => {
        participantStatusesDateRange.forEach((helper) => {
            if (helper.status === element.label) {
                amountHelperStatus = amountHelperStatus + helper.countByStatus;
            }
        });
        doughnutDataStatus.push(amountHelperStatus);
        doughnutLabelsStatus.push(element.label);
        amountHelperStatus = 0;
        doughnutBackgroundColorStatus.push(getParticipantStatusColor(element.label));
    });
    //console.log(doughnutDataStatus);

    const doughnutStatus = document.getElementById('doughnut');
    var myDoughnutChart = new Chart(doughnutStatus, {
        type: "doughnut",
        data: {
            datasets: [
                {
                    data: doughnutDataStatus,
                    backgroundColor: doughnutBackgroundColorStatus,
                },
            ],
            labels: doughnutLabelsStatus,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: `Distribución de Participantes por Estatus en el Período ${start_date_fmt} - ${end_date_fmt}`,
                }
            }
        },
    });
    //bar chart
    const barStatus = document.getElementById('myChart');
    var myDoughnutChart = new Chart(barStatus, {
        type: "bar",
        data: {
            datasets: [
                {
                    data: doughnutDataStatus,
                    backgroundColor: doughnutBackgroundColorStatus,
                },
            ],
            labels: doughnutLabelsStatus,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: `Distribución de Participantes por Estatus en el Período ${start_date_fmt} - ${end_date_fmt}`,
                },
                legend: {
                    display: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0,
                    }
                }
            }
        },
    });

    $(".table-col-helper").append(
        `<div class="panel panel-success participant-during-period-with-status">
            <div class="panel-heading">
                <div class="panel-title">Cantidad de Participantes Durante el Período ${start_date_fmt} - ${end_date_fmt}</div>
            </div>
            <div class="panel-body with-table table-responsive">
                <table class="status-amount-table table table-striped table-bordered table-center">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>`
    );

    // Build a robust status -> count map (avoid depending on label order/indexes)
    const statusCounts = {};
    (response.allStatusbyDateRange || []).forEach((item) => {
        const name = item.status || item.name || item.statusName;
        if (!name) {
            return;
        }
        const n = Number(item.countByStatus ?? item.count ?? item.amount ?? 0);
        statusCounts[name] = (statusCounts[name] || 0) + (Number.isFinite(n) ? n : 0);
    });

    // Match the statuses seeded in ParticipantStatusSeeder
    const statusOrder = ['En Curso', 'Reprobado', 'Aprobado', 'Cancelado', 'Por Iniciar'];

    $(".status-amount-table thead").append(`<tr>
        <th>En Curso</th>
        <th>Reprobado</th>
        <th>Aprobado</th>
        <th>Cancelado</th>
        <th>Por Iniciar</th>
    </tr>`);

    $(".status-amount-table tbody").append(`<tr>
        <td>${statusCounts[statusOrder[0]] ?? 0}</td>
        <td>${statusCounts[statusOrder[1]] ?? 0}</td>
        <td>${statusCounts[statusOrder[2]] ?? 0}</td>
        <td>${statusCounts[statusOrder[3]] ?? 0}</td>
        <td>${statusCounts[statusOrder[4]] ?? 0}</td>
    </tr>`);

    // list of participants (all statuses) during the selected period
    $(".table-col-helper").append(
        `<div class="panel panel-success participant-with-status">
        <div class="panel-heading">
            <div class="panel-title">Lista de Participantes Durante el Período ${start_date_fmt} - ${end_date_fmt}</div>
        </div>
        <div class="panel-body with-table table-responsive">
        <table class="all-time-list-table table table-striped table-bordered table-center">

                                <thead></thead>
                                <tbody></tbody>
                            </table>
        </div>
        </div>`
    );

    $(".all-time-list-table thead").append(`<tr>
        <th>Nombres</th>
        <th>Apellidos</th>
        <th>Cédula</th>
        <th>Estatus</th>
        <th>Acción de Formación</th>
        <th>Fecha de Inicio</th>
    </tr>`);

    if (!(response.byAllTime || []).length) {
        $(".all-time-list-table tbody").append(`<tr>
            <td colspan="6">No hay participantes en el período o con el estatus seleccionado</td>
        </tr>`);
    } else {
        (response.byAllTime || []).forEach((element) => {
            const courseTitle = (element.scheduled && element.scheduled.course && element.scheduled.course.title) ? element.scheduled.course.title : 'Sin curso';
            const startDate = (element.scheduled && element.scheduled.start_date) ? element.scheduled.start_date : '';
            const startDateFmt = formatServerDate(startDate);
            const statusName = (element.participant_status && element.participant_status.name) ? element.participant_status.name : '';
            $(".all-time-list-table tbody").append(`<tr>
                                        <td>${element.person.name}</td>
                                        <td>${element.person.last_name}</td>
                                        <td>${element.person.id_number}</td>
                                        <td>${statusName}</td>
                                        <td>${courseTitle}</td>
                                        <td>${startDateFmt}</td>
                                        </tr>`);
        });
    }

    // Paginate the participant list (client-side)
    initTablePagination(document.querySelector('.participant-with-status .all-time-list-table'), 25);

    // participants not in a course: no longer shown in this report
    /* response.allStatusbyDateRange.forEach(element => {
                            console.log(`${element.status} : ${element.countByStatus}`);
                        }); */

    //console.log(response.notInCourse);
}//end report by participant status

function reportByParticipantQuantity(response){
    //reports by participant quantity
    console.log("participant-by-quantity");
    refresh();

    // runs without participants carry no signal for this comparison
    const withParticipants = response.dateRangeAmountPerCourse != 0
        ? response.dateRangeAmountPerCourse.filter((element) => Number(element.count) > 0)
        : [];

    if (withParticipants.length !== 0) {
        $(".course-amount-number span").html(
            `<h3>Acciones de Formación con Participantes en el período: ${withParticipants.length}</h3>`
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
        </tr>`);

        withParticipants.forEach(element => {
            $(".course-list tbody").append(`<tr>
                <td>${element.course}</td>
                <td>${formatServerDate(element.start_date || '')}</td>
                <td>${formatServerDate(element.end_date || '')}</td>
                <td>${element.count}</td>
            </tr>`);
        });
    } else {
        $('.course-amount-number').append('<h3>No existen Acciones de Formación con participantes asignados en este período de tiempo</h3>');
    }
}//end report by participant quantity

function reportByGender(response){
    console.log('gender');
    refresh();

    const start = (response.dateRange && response.dateRange.startDate) ? response.dateRange.startDate : '';
    const end = (response.dateRange && response.dateRange.endDate) ? response.dateRange.endDate : '';
    const startFmt = formatServerDate(start);
    const endFmt = formatServerDate(end);
    const rows = Array.isArray(response.rows) ? response.rows : [];

    // Always render both genders, even if one is zero
    const fixedLabels = ['Masculino', 'Femenino'];
    const rowByLabel = {};
    rows.forEach(r => {
        if (r && r.label) {
            rowByLabel[r.label] = r;
        }
    });

    const maleCount = Number((rowByLabel['Masculino'] && rowByLabel['Masculino'].amount) ? rowByLabel['Masculino'].amount : 0) || 0;
    const femaleCount = Number((rowByLabel['Femenino'] && rowByLabel['Femenino'].amount) ? rowByLabel['Femenino'].amount : 0) || 0;

    const labels = fixedLabels;
    const data = [maleCount, femaleCount];
    const total = maleCount + femaleCount;

    $(".course-amount-number").html(
        `<h3>Distribución de Participantes por Género durante el período ${startFmt} - ${endFmt} : ${total}</h3>`
    );

    // Graph containers
    $(".row-graphs").append(`
        <div class="col-md-6">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <div class="panel-title">Distribución de Participantes por Género (Barras)</div>
                </div>
                <div class="panel-body">
                    <div class="h-25 col-xs-12 col-md-12 graph-container">
                        <canvas id="genderBar"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <div class="panel-title">Distribución de Participantes por Género (Dona)</div>
                </div>
                <div class="panel-body">
                    <div class="doughnut-container">
                        <canvas id="genderDoughnut"></canvas>
                    </div>
                </div>
            </div>
        </div>
    `);

    // Colors
    const GENDER_COLORS = {
        'Masculino': '#42B6E6',
        'Femenino': '#D44545',
    };

    const backgroundColors = labels.map((label) => GENDER_COLORS[label] || '#999999');
    const borderColors = labels.map((label) => GENDER_COLORS[label] || '#999999');

    // Doughnut
    const doughnutEl = document.getElementById('genderDoughnut');
    new Chart(doughnutEl, {
        type: 'doughnut',
        data: {
            datasets: [{
                data,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1,
            }],
            labels,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: `Distribución de Participantes por Género en el Período ${startFmt} - ${endFmt}`,
                }
            }
        }
    });

    // Bar
    const barEl = document.getElementById('genderBar');
    new Chart(barEl, {
        type: 'bar',
        data: {
            datasets: [{
                data,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1,
            }],
            labels,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: `Distribución de Participantes por Género en el Período ${startFmt} - ${endFmt}`,
                },
                legend: {
                    display: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0,
                    }
                }
            }
        }
    });

    // Table (fixed columns: Masculino / Femenino / Total)
    $(".table-col-helper").append(
        `<div class="panel panel-success" style="page-break-inside: avoid">
            <div class="panel-heading">
                <div class="panel-title">Cantidad de Participantes por Género durante el período ${startFmt} - ${endFmt}</div>
            </div>
            <div class="panel-body with-table table-responsive">
                <table class="gender-amount-table table table-striped table-bordered table-center">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>`
    );

    $(".gender-amount-table thead").append(`<tr>
        <th>Masculino</th>
        <th>Femenino</th>
        <th>Total</th>
    </tr>`);

    $(".gender-amount-table tbody").append(`<tr>
        <td>${maleCount}</td>
        <td>${femaleCount}</td>
        <td>${total}</td>
    </tr>`);
}

function reportByCourseMostScheduled(response){ //reports by course most scheduled
    console.log("most-scheduled");
    //draw list of courses
    refresh();

    // sort by scheduled-run count, descending (a "most scheduled" ranking)
    const amountOf = (el) => Number(response.amountData[el.id] ?? 0);
    const sorted = [...response.courseData].sort((a, b) => amountOf(b) - amountOf(a));

    $(".table-col-helper").append(`<div class="panel panel-success">
    <div class="panel-heading">
        <div class="panel-title">Acciones de Formación más programadas (Histórico)</div>
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

    let totalAmountOfCourses = 0;
    sorted.forEach((element) => {
        const amount = amountOf(element) || 0;
        $(".course-list tbody").append(`<tr>
                                    <td>${element.code}</td>
                                    <td>${element.title}</td>
                                    <td>${amount}
                                    </td></tr>`);
        totalAmountOfCourses = totalAmountOfCourses + amount;
    });

    $('.course-amount-number span').html(
        `<h3>Cantidad Total de Cursos Programados: ${totalAmountOfCourses}</h3>`
    );
}//end report by course most scheduled

function reportByParticipantAverage(response){
    console.log("participant-average");
    refresh();
    const rows = Array.isArray(response.data) ? response.data : [];
    const startFmt = formatServerDate(response.dateRange && response.dateRange.startDate ? response.dateRange.startDate : '');
    const endFmt = formatServerDate(response.dateRange && response.dateRange.endDate ? response.dateRange.endDate : '');

    if (rows.length === 0) {
        $(".course-amount-number").append(
            `<h3>No existen Acciones de Formación en el período ${startFmt} - ${endFmt}</h3>`
        );
        return;
    }

    // group per-course rows into their date buckets
    const buckets = new Map();
    rows.forEach((row) => {
        if (!buckets.has(row.date)) buckets.set(row.date, []);
        buckets.get(row.date).push(row);
    });
    const dates = Array.from(buckets.keys()).sort();
    const round2 = (v) => Math.round(v * 100) / 100;
    const seriesFor = (field) => dates.map((d) => {
        const group = buckets.get(d);
        const avg = group.reduce((s, r) => s + (Number(r[field]) || 0), 0) / group.length;
        return { x: d, y: round2(avg) };
    });

    const overallAvgTotal = round2(rows.reduce((s, r) => s + (Number(r.total) || 0), 0) / rows.length);

    $(".row-graphs").append(`<div class="panel panel-success by-average-line">
        <div class="panel-heading">
            <div class="panel-title">Promedio de Participantes por Acción de Formación durante el Período ${startFmt} - ${endFmt}</div>
        </div>
        <div class="panel-body">
            <div class="col-xs-12 col-md-12 graph-container">
                <canvas id="myChart"></canvas>
            </div>
        </div>
    </div>`);

    $('.course-amount-number span').html("");
    $(".course-amount-number span").append(
        `<h3 class="text-center">Promedio de Participantes por Acción de Formación: ${overallAvgTotal}</h3>`
    );

    const palette = [
        { label: 'Promedio de Participantes', key: 'total', color: 'rgb(75, 192, 192)' },
        { label: 'Promedio Aprobados', key: 'approved', color: 'rgb(54, 162, 235)' },
        { label: 'Promedio Reprobados', key: 'failed', color: 'rgb(255, 99, 132)' },
    ];
    const datasets = palette.map(p => applySmartLineMarkers({
        label: p.label,
        data: seriesFor(p.key),
        fill: false,
        borderColor: p.color,
        backgroundColor: p.color.replace('rgb(', 'rgba(').replace(')', ', 0.2)'),
        borderWidth: 3,
        tension: 0.5,
    }));

    const maxY = getMaxYFromDatasets(datasets);

    new Chart(document.getElementById('myChart'), {
        type: "line",
        data: { labels: dates, datasets: datasets },
        options: {
            spanGaps: shouldConnectLineGaps(),
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'time',
                    title: { display: true, text: 'Período de Tiempo' },
                    time: { parser: 'YYYY-MM-DD', tooltipFormat: 'YYYY-MM-DD' }
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: maxY > 0 ? (maxY + 1) : 1,
                    ticks: { precision: 0 }
                }
            },
        }
    });
}//end report by participant average

function reportByCanceled(response){
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
}

function reportByCourseNotScheduled(response){
    console.log("not-scheduled");
    refresh();
    //draw table and show amount of courses
    if (response.data.length != 0) {
        $(".course-amount-number").append(
            `<h3>Cantidad de Acciones de Formación No Programadas: ${response.data.length}</h3>`
        );

        $(".table-col-helper").append(`<div class="panel panel-success">
            <div class="panel-heading">
				<div class="panel-title">
			  		Acciones de Formación No Programadas
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
}



$(document).ready(function(){
    $(".loader").addClass("hidden");
    $(".participant-status-container").hide();

    // Sorting: use event delegation so it works for dynamically generated report tables.
    $(document)
        .off('click.reportSort', '.table-col-helper table thead th')
        .on('click.reportSort', '.table-col-helper table thead th', function(){
            const th = this;
            const tableEl = th.closest('table');
            if (!tableEl) {
                return;
            }

            // Do not interfere with DataTables (if any table gets upgraded elsewhere).
            if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.dataTable) {
                const $table = $(tableEl);
                if ($table.hasClass('dataTable')) {
                    return;
                }
            }

            const headerRow = th.parentElement;
            const ths = Array.from(headerRow.children);
            const colIndex = ths.indexOf(th);
            if (colIndex < 0) {
                return;
            }

            const currentCol = tableEl.dataset.reportSortCol;
            const currentDir = tableEl.dataset.reportSortDir;
            const nextDir = (String(currentCol) === String(colIndex) && currentDir === 'asc') ? 'desc' : 'asc';

            tableEl.dataset.reportSortCol = String(colIndex);
            tableEl.dataset.reportSortDir = nextDir;

            // Update aria-sort
            ths.forEach(cell => {
                if (cell && cell.tagName === 'TH') {
                    cell.setAttribute('aria-sort', 'none');

                    // Reset icons in this header row
                    const icon = cell.querySelector('.report-sort-icon');
                    if (icon) {
                        icon.classList.remove('fa-sort-asc', 'fa-sort-desc');
                        // Ensure base sort icon exists
                        if (!icon.classList.contains('fa-sort')) {
                            icon.classList.add('fa-sort');
                        }
                    }
                }
            });
            th.setAttribute('aria-sort', nextDir === 'asc' ? 'ascending' : 'descending');

            // Toggle icon on the active column
            const activeIcon = th.querySelector('.report-sort-icon');
            if (activeIcon) {
                activeIcon.classList.remove('fa-sort');
                activeIcon.classList.toggle('fa-sort-asc', nextDir === 'asc');
                activeIcon.classList.toggle('fa-sort-desc', nextDir === 'desc');
            }

            sortTableByColumn(tableEl, colIndex, nextDir);
        })
        .off('keydown.reportSort', '.table-col-helper table thead th')
        .on('keydown.reportSort', '.table-col-helper table thead th', function(e){
            // Enter/Space should activate sorting for accessibility.
            const key = e.key || e.keyCode;
            if (key === 'Enter' || key === ' ' || key === 13 || key === 32) {
                e.preventDefault();
                $(this).trigger('click');
            }
        });

    // Pagination controls (delegated so it works for dynamic tables)
    $(document)
        .off('click.reportPaginationPrev', '.report-pagination-prev')
        .on('click.reportPaginationPrev', '.report-pagination-prev', function(){
            const controls = this.closest('.report-pagination-controls');
            if (!controls) {
                return;
            }
            const tableId = controls.getAttribute('data-report-pagination-for');
            if (!tableId) {
                return;
            }
            const tableEl = document.getElementById(tableId);
            if (!tableEl) {
                return;
            }
            const current = Number(tableEl.dataset.reportPage || 1);
            tableEl.dataset.reportPage = String(Math.max(1, current - 1));
            renderTablePage(tableEl);
        })
        .off('click.reportPaginationNext', '.report-pagination-next')
        .on('click.reportPaginationNext', '.report-pagination-next', function(){
            const controls = this.closest('.report-pagination-controls');
            if (!controls) {
                return;
            }
            const tableId = controls.getAttribute('data-report-pagination-for');
            if (!tableId) {
                return;
            }
            const tableEl = document.getElementById(tableId);
            if (!tableEl) {
                return;
            }
            const state = getPaginationState(tableEl);
            const tbody = tableEl.tBodies && tableEl.tBodies[0];
            const totalRows = tbody ? (tbody.rows ? tbody.rows.length : 0) : 0;
            const totalPages = state.pageSize ? Math.max(1, Math.ceil(totalRows / state.pageSize)) : 1;
            tableEl.dataset.reportPage = String(Math.min(totalPages, state.page + 1));
            renderTablePage(tableEl);
        });

    function toYmd(date){
        // Prefer moment if present; fallback to native Date
        if (typeof moment === 'function') {
            return moment(date).format('YYYY-MM-DD');
        }
        const d = (date instanceof Date) ? date : new Date(date);
        return d.toISOString().slice(0, 10);
    }

    function getRangeDays(startYmd, endYmd){
        if (!startYmd || !endYmd) {
            return null;
        }
        if (typeof moment === 'function') {
            const start = moment(startYmd, 'YYYY-MM-DD');
            const end = moment(endYmd, 'YYYY-MM-DD');
            if (!start.isValid() || !end.isValid()) {
                return null;
            }
            return end.diff(start, 'days') + 1;
        }
        const start = new Date(startYmd);
        const end = new Date(endYmd);
        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
            return null;
        }
        const msPerDay = 24 * 60 * 60 * 1000;
        return Math.floor((end.getTime() - start.getTime()) / msPerDay) + 1;
    }

    function updateStepOptionsBySelectedRange(){
        const startYmd = $('#start_date').val();
        const endYmd = $('#end_date').val();
        const rangeDays = getRangeDays(startYmd, endYmd);

        // If the range is invalid (end before start), don't lock the user out; just enable all.
        $('#step option').prop('disabled', false);

        if (rangeDays === null || rangeDays <= 0) {
            return;
        }

        const stepMinDays = {
            '1 day': 1,
            '1 week': 7,
            '15 days': 15,
            '1 month': 30,
            '4 months': 120,
            '6 months': 180,
            '1 year': 365,
        };

        $('#step option').each(function(){
            const value = $(this).val();
            const minDays = stepMinDays[value];
            if (minDays !== undefined && minDays > rangeDays) {
                $(this).prop('disabled', true);
            }
        });

        // If current selection became invalid, reset to daily.
        if ($('#step option:selected').prop('disabled')) {
            $('#step').val('1 day').change();
        }
    }

    function updateGenerateButtonDisabledState(){
        const today = toYmd(new Date());
        const startYmd = $('#start_date').val();
        const endYmd = $('#end_date').val();
        const disable = (startYmd === today && endYmd === today);
        $('.generate').prop('disabled', disable);
    }

    // Set sane defaults (previously defaulted to "1 Mes")
    if (!$('#end_date').val()) {
        $('#end_date').val(toYmd(new Date()));
    }
    if (!$('#start_date').val()) {
        if (typeof moment === 'function') {
            $('#start_date').val(moment($('#end_date').val(), 'YYYY-MM-DD').subtract(1, 'month').format('YYYY-MM-DD'));
        } else {
            const end = new Date($('#end_date').val());
            end.setMonth(end.getMonth() - 1);
            $('#start_date').val(toYmd(end));
        }
    }

    updateStepOptionsBySelectedRange();
    updateGenerateButtonDisabledState();

    // Participant status selector removed from the view; keep container hidden if present.

    $('#start_date, #end_date').off().on('change', function(){
        const start = $('#start_date').val();
        const end = $('#end_date').val();

        // If user selected an inverted range, swap to keep UX friendly
        if (start && end) {
            const rangeDays = getRangeDays(start, end);
            if (rangeDays !== null && rangeDays <= 0) {
                $('#start_date').val(end);
                $('#end_date').val(start);
            }
        }

        updateStepOptionsBySelectedRange();
        updateGenerateButtonDisabledState();
    });

    $('.print-report').off().on('click', function (e){
        e.preventDefault();

        // Build a POST form so the browser handles the file download.
        const $form = $('.report-form');
        if (!$form.length) {
            return;
        }

        const action = '/reports/pdf';
        const data = $form.serializeArray();

        function getPanelTitleFor(el){
            if (!el) {
                return '';
            }
            const panel = el.closest('.panel');
            if (panel) {
                const t = panel.querySelector('.panel-title');
                if (t && t.textContent) {
                    return t.textContent.replace(/\s+/g, ' ').trim();
                }
            }
            return '';
        }

        function canvasToPngDataUrl(canvas){
            try {
                const w = canvas.width || 0;
                const h = canvas.height || 0;
                if (!w || !h) {
                    return null;
                }

                const tmp = document.createElement('canvas');
                tmp.width = w;
                tmp.height = h;
                const ctx = tmp.getContext('2d');
                if (!ctx) {
                    return null;
                }
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, w, h);
                ctx.drawImage(canvas, 0, 0);

                // PNG is lossless and keeps chart text/lines crisp in PDFs.
                return tmp.toDataURL('image/png');
            } catch (_e) {
                return null;
            }
        }

        // Collect chart images in DOM order.
        // Prefer .row-graphs, but fall back to any visible canvases inside the report container.
        let canvases = Array.from(document.querySelectorAll('.row-graphs canvas'));
        if (!canvases.length) {
            canvases = Array.from(document.querySelectorAll('.report-view canvas'));
        }
        canvases.forEach((canvas, idx) => {
            const img = canvasToPngDataUrl(canvas);
            if (!img) {
                return;
            }
            const title = getPanelTitleFor(canvas) || `Gráfico ${idx + 1}`;
            data.push({ name: `charts[${idx}][title]`, value: title });
            data.push({ name: `charts[${idx}][image]`, value: img });
        });

        // Ensure report-type is included (it is, but keep this defensive)
        const selected = $('.selector').find(':selected').val();
        const hasType = data.some(i => i && i.name === 'report-type');
        if (!hasType) {
            data.push({ name: 'report-type', value: selected });
        }

        const formEl = document.createElement('form');
        formEl.method = 'POST';
        formEl.action = action;
        formEl.style.display = 'none';

        data.forEach((item) => {
            if (!item || !item.name) {
                return;
            }
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = item.name;
            input.value = item.value ?? '';
            formEl.appendChild(input);
        });

        document.body.appendChild(formEl);
        formEl.submit();
        formEl.remove();
    });

    // re-run the report when a status filter is chosen
    $(document).on('change', '#participant_status', function () {
        if ($('.selector').find(':selected').val() === 'participant-by-status') {
            $('.generate').trigger('click');
        }
    });

    $.ajaxSetup({
            headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            }
        });

    // participant status filter: only shown for the participant-by-status report
    let participantStatusOptionsLoaded = false;

    //Reports by type selected
    $('.generate').click(function (e) {
            //data to send
            let selected = $('.selector').find(':selected').val();

            const showStatusFilter = selected === 'participant-by-status';
            $('.participant-status-container').toggle(showStatusFilter);
            if (showStatusFilter && !participantStatusOptionsLoaded) {
                participantStatusSelect();
                participantStatusOptionsLoaded = true;
            }

            // The two "Histórico" reports ignore the date range and interval by
            // design; disable those filters so they can't be submitted as a
            // silent no-op, and so the UI reflects the actual query scope.
            const historicalTypes = ['most-scheduled', 'not-scheduled'];
            const disableDateFilters = historicalTypes.includes(selected);
            $('#date-range-filters').toggle(!disableDateFilters);
            $('#date-range-filters').find('input, select').prop('disabled', disableDateFilters);

            // Drop any previously chosen participant status when leaving the
            // participant-by-status report so it isn't carried into other types.
            if (!showStatusFilter) {
                $('#participant_status').val('');
            }

            let formData = $('.report-form').serialize();
            //reset graph container
            $('.graph-container').children().remove();
            $('.doughnut-container').children().remove();
            $('.graph-container').append('<canvas id="myChart"></canvas>');
            $('.doughnut-container').append('<canvas id="doughnut"></canvas>');
            
            $.ajax({
            type: "POST",
            data: formData,
            url: '/reports/'+selected,
            dataType: 'json',
            beforeSend: function(){
                console.log('waiting for response');
                $(".loader").removeClass("hidden");
            },
            success: function(response){
                $(".loader").addClass("hidden");
                $('.print-report').removeAttr('disabled');
                //console.log(response);
                //response = JSON.parse(response);
                console.log(response);
                //console.log(response.y);
                switch (selected) {
                    case "date":
                        reportByDate(response);
                        break;
                    case "category":
                        reportByCategory(response);
                        break;
                    case "status":
                        reportByCourseStatus(response);
                        break;
                    case "duration":
                        reportByDuration(response);
                        break;
                    case "participant-by-status":
                        reportByParticipantStatus(response);
                        break;
                    case "participant-by-quantity":
                        reportByParticipantQuantity(response);
                        break;
                    case "gender":
                        reportByGender(response);
                        break;
                    case "participant-average":
                        reportByParticipantAverage(response);
                        break;
                    case "most-scheduled":
                        reportByCourseMostScheduled(response);
                        break;
                    case "not-scheduled":
                        reportByCourseNotScheduled(response);
                        break;
                    case "canceled":
                        reportByCanceled(response);
                        break;
                    default:
                        break;
                }

                // After the report draws its tables, mark headers as sortable.
                decorateSortableReportTables($('.table-col-helper'));
            }, 
            error:function(response){
                console.log("error "+response);
            }
            });
            e.preventDefault();
        });
})