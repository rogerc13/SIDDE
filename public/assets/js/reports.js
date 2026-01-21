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
    $(".course-amount-number").html("");
    $('.row-graphs').html('');
    $(".table-col-helper").html("");
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

function reportByDate(response){ //reports by date
    refresh();
    $(".row-graphs").append(`<div class="panel panel-success by-date-line">
        <div class="panel-heading">
            <div class="panel-title">Cantidad de Acciones de Formación durante el Período ${response.start_date} - ${response.end_date} : ${response.total}</div>
        </div>    
        <div class="panel-body">
            <div class="h-25 col-xs-12 col-md-12 graph-container">
                <canvas id="myChart"></canvas>
            </div>
        </div>
        </div>`);

    $('.course-amount-number span').html("");
    $(".course-amount-number span").append(
        `<h3 class="text-center">Cantidad de Acciones de Formación durante el Período ${response.start_date} - ${response.end_date} : ${response.total}</h3>`
    );

    // Build array of {x, y} objects for time-based line chart, just like the other working graphs
    let lineData = [];
    if (Array.isArray(response.x) && Array.isArray(response.y)) {
        // Flatten x if it's an array of arrays (as in the controller)
        let xArr = response.x.map(val => Array.isArray(val) ? val[0] : val);
        lineData = xArr.map((date, idx) => {
            let yVal = response.y[idx];
            return {
                x: date,
                y: (typeof yVal === 'number' && yVal !== 0) ? yVal : null
            };
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
    $(".row-graphs")
        .append(`<div class="col-md-6">
            <div class="panel panel-success line-graph-panel">
                <div class="panel-heading">
                    <div class="panel-title">Cantidad de Acciones de Formacion por Áreas de Conocimiento durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}</div>
                </div>
                <div class="panel-body">
                    <div class="graph-container">
                        <canvas id="myChart"></canvas>
                    </div>
                </div>
            </div>
        </div>`);

    const ctx = document.getElementById('myChart');
    let categories = [];

    //console.log(response.categories);

    //line graph data
    let fillColor = [];
    let colorHelp = 0;
    let regHex=/^#([0-9a-f]{3}){1,2}$/i;
    response.categories.forEach(element => {
       
        fillColor.push(
            "#000000".replace(/0/g,function(){

                return (~~(Math.random()*16)).toString(16);
            })
        );

        // For each category, build an array of {x, y} objects for the time-based line chart
        let categoryData = response.x.map(date => {
            // Find the y value for this category and date
            let found = response.y.find(obj => obj.category === element && obj.x === date);
            return {
                x: date,
                y: found ? (found.y === 0 ? null : found.y) : null
            };
        });
        let category = {
            label: element,
            // build a point for every response.x so line is contiguous
            data: buildSeriesFor(element, response.x, response.y, 'category'),
            showLine: true,
            fill: false,
            borderColor: fillColor[colorHelp],
            backgroundColor: fillColor[colorHelp],
            borderWidth: 3,
        };
        categories.push(category);
        colorHelp++;
    });

    // Remove categories that have no non-null points (all zeros / empty)
    categories = categories.filter(ds => Array.isArray(ds.data) && ds.data.some(p => p && p.y !== null));

    console.log(categories);
    
    categories = categories.map(ds => applySmartLineMarkers(ds));

    var chart = new Chart(ctx, {
        type: "line",
        data: {
            //labels:response.x,
            datasets: categories,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            elements: {
                point: {
                    radius: 0,
                    hoverRadius: 0
                }
            },
            // For daily charts, connect across nulls; for others, keep gaps.
            spanGaps: shouldConnectLineGaps(),
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
                    title: {
                        display: true,
                        text: 'Cantidad de Acciones de Formación'
                    },
                    ticks: {
                        stepSize: 5
                    }
                }
            },
            showLines: true,
            plugins: {
                title: {
                    display: true,
                    text: `Cantidad de Acciones de Formacion por Áreas de Conocimiento durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}`,
                }
            }
        },
    });
        
    $(".row-graphs").append(`
        <div class="col-md-6">
            <div class="panel panel-success doughnut-panel">
                <div class="panel-heading">
                    <div class="panel-title">
                        Distribución de Acciones de Formación por Areas de Conocimiento durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}
                    </div>
                </div>
                <div class="panel-body">
                    <div class="doughnut-container">
                        <canvas id="doughnut"></canvas>
                    </div>
                </div>
            </div>
        </div>`);

    //doughnut data
    let doughnutData = [];
    let doughnutLabels = [];
    let doughnutBackgroundColor = [];
    let colorHelpDoughnut = 0;

    response.courseData.forEach(element => {
        doughnutData.push(element.amount);
        doughnutLabels.push(element.categoryName);
        doughnutBackgroundColor.push(fillColor[colorHelpDoughnut]);
        colorHelpDoughnut++;
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
                    text: `Distribución de Acciones de Formación por Areas de Conocimiento durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}`,
                }
            }
        },
    });

    //tables
    $(".table-col-helper")
        .append(`<div class="panel panel-success course-by-category-list" style="page-break-inside: avoid">
                    
                    <div class="panel-heading">
                        <div class="panel-title">Cantidad de Acciones de Formación por Áreas de Conocimiento durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}</div>
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

    response.courseData.forEach(element => {
        $(".course-category-list tbody").append(`<tr><td>${element.categoryName}</td>
                                                     <td>${element.amount}</td></tr>`);
    });
    
    $(".table-col-helper").append(`<div class="panel panel-success course-by-category-by-date-range" style="page-break-inside: avoid">
        <div class="panel-heading">
            <div class="panel-title">Acciones de Formación por Áreas de Conocimiento durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}</div>
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
            <td>${helper.start_date}</td>
            <td>${helper.end_date}</td>
            <td>${helper.course.category.name}</td>
        </tr>`);
        })
    });
    
}//end report by category

function reportByCourseStatus(response){ //reports by course status
    console.log("status");

    refresh();
    $(".row-graphs")
        .append(`<div class="col-md-6">
            <div class="panel panel-success by-status-line-panel">
                <div class="panel-heading">
                    <div class="panel-title">Distribución de Acciones de Formación por Estatus durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}</div>
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
    let fillColor = [];
    let colorHelp = 0;
    response.statuses.forEach((element) => {
        fillColor.push(
            "#000000".replace(/0/g,function(){
                return (~~(Math.random()*16)).toString(16);
            })
        );
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
            borderColor: fillColor[colorHelp],
            backgroundColor: fillColor[colorHelp],
            borderWidth: 3,
        };
        statuses.push(applySmartLineMarkers(status));
        colorHelp++;
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
    let colorHelpDoughnut = 0;

    courseData.forEach((element) => {
        doughnutData.push(element.amount);
        doughnutLabels.push(element.statusName);
        doughnutBackgroundColor.push(fillColor[colorHelpDoughnut]);
        colorHelpDoughnut++;
    });

    //linear graph draw
    var chart = new Chart(ctx, {
        type: "line",
        data: {
            //labels:response.x,
            datasets: statuses,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            elements: {
                point: {
                    radius: 0,
                    hoverRadius: 0
                }
            },
            // For daily charts, connect across nulls; for others, keep gaps.
            spanGaps: shouldConnectLineGaps(),
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
                    min: 0,
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
                    text: `Distribución de Acciones de Formación por Estatus durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}`,
                }
            }
        },
    });

    //doughnut graph draw
    $(".row-graphs")
        .append(`<div class="col-md-6">
            <div class="panel panel-success by-status-doughnut-panel">
                <div class="panel-heading">
                    <div class="panel-title">Distribución de Acciones de Formación por Estatus durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}</div>
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
                    text: `Distribución de Acciones de Formación por Estatus durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}`,
                }
            }
        },
    });

    
    //list data table

    $(".table-col-helper").append(
        `<div class="panel panel-success course-status-panel">
        <div class="panel-heading">
            <div class="panel-title">Cantidad de Acciones de Formación por Estatus durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}</div>
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
                                                <th>Por Dictar</th>
                                                <th>En Curso</th>
                                                <th>Culminado</th>
                                                <th>Cancelado</th>
                                            </tr>`);

     $(".course-status-amount-table tbody").append(`<tr></tr>`);
    
    $(".table-col-helper")
        .append(`<div class="panel panel-success courses-by-date-range-status">
        <div class="panel-heading">
            <div class="panel-title">Acciones de Formación Durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}</div>
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
                                            <td>${helperA.start_date}</td>
                                            <td>${helperA.end_date}</td>
                                            <td>${helperA.course_status.name}</td>
                                        </tr>`);
        });

        $('.course-status-amount-table tbody tr').append(`<td>${element.amount}</td>`);
    });
}//end report by course status

function reportByDuration(response){ //reports by course duration
    console.log("duration");

    refresh();

    $(".course-amount-number").html(
        `<h3>Total de horas impartidas durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate} : ${response.finishedByDateRange} Horas</h3>`
    );

    //tables
    //Spans Most Days ALL TIME
    $(".table-col-helper")
        .append(`<div class="panel panel-success courses-by-day-span">
            <div class="panel-heading">
                <div class="panel-title">Acciones de Formacion según cantidad de horas y días que abarcan</div>
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

    if (Array.isArray(response.byDateRange)) {
        response.byDateRange.forEach(element => {
            $(".course-day-span-all-time-list tbody").append(`<tr>
                <td>${element.course_title ?? ''}</td>
                <td>${element.start_date ?? ''}</td>
                <td>${element.end_date ?? ''}</td>
                <td>${element.duration ?? ''}</td>
                <td>${element.duration_days ?? ''}</td>
            </tr>`);
        });
    }

    //Most duration hours
    $(".table-col-helper")
        .append(`<div class="panel panel-success courses-by-hours">
        <div class="panel-heading">
            <div class="panel-title">Acciones de Formacion según cantidad de horas</div>
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

    let start_date = response.byStatusByDateRange[0].date;

    let end_date =
        response.byStatusByDateRange[response.byStatusByDateRange.length - 1]
            .date;

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
        doughnutBackgroundColorStatus.push(
            "#000000".replace(/0/g,function(){
               
                return (~~(Math.random()*16)).toString(16);
            })
        );
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
                    text: `Distribución de Participantes por Estatus en el Período ${start_date} - ${end_date}`,
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
                    text: `Distribución de Participantes por Estatus en el Período ${start_date} - ${end_date}`,
                },
                legend: {
                    display: false,
                }
            }
        },
    });

    $(".table-col-helper").append(
        `<div class="panel panel-success participant-during-period-with-status">
            <div class="panel-heading">
                <div class="panel-title">Cantidad de Participantes Durante el Período ${start_date} - ${end_date}</div>
            </div>
            <div class="panel-body with-table table-responsive">
                <table class="status-amount-table table table-striped table-bordered table-center">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>`
    );
    $(".status-amount-table thead").append(`<tr>
                                                <th>En Curso</th>
                                                <th>Aprobado</th>
                                                <th>Reprobado</th>
                                                <th>Cancelado</th>
                                            </tr>`);
    $(".status-amount-table tbody").append(`<tr>
                                                <td>${doughnutDataStatus[0]}</td>
                                                <td>${doughnutDataStatus[2]}</td>
                                                <td>${doughnutDataStatus[1]}</td>
                                                <td>${doughnutDataStatus[3]}</td>
                                            </tr>`);

    const selectedStatusName = response.selectedStatusName
        || $('#participant_status option:selected').text()
        || '';

    //list of participants per given status all time
    $(".table-col-helper").append(
        `<div class="panel panel-success participant-with-status">
        <div class="panel-heading">
            <div class="panel-title">Lista de Participantes Con Estatus: ${selectedStatusName}</div>
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
                                                <th>Acción de Formación</th>
                                                <th>Fecha de Inicio</th>
                                            </tr>`);

    response.byAllTime.forEach((element) => {
        const courseTitle = (element.scheduled && element.scheduled.course && element.scheduled.course.title) ? element.scheduled.course.title : 'Sin curso';
        const startDate = (element.scheduled && element.scheduled.start_date) ? element.scheduled.start_date : '';
        $(".all-time-list-table tbody").append(`<tr>
                                    <td>${element.person.name}</td>
                                    <td>${element.person.last_name}</td>
                                    <td>${element.person.id_number}</td>
                                    <td>${courseTitle}</td>
                                    <td>${startDate}</td>
                                    </tr>`);
    });

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
    //amount of participants all time
    //$('').html(`Cantidad Total de Participantes: ${response.amountAllTime[0].amount}`)
    //list of courses per participant in a date range
    if (response.dateRangeAmountPerCourse != 0) {
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

        response.dateRangeAmountPerCourse.forEach(element => {
            $(".course-list tbody").append(`<tr>
                <td>${element.course}</td>
                <td>${element.start_date || ''}</td>
                <td>${element.end_date || ''}</td>
                <td>${element.count}</td>
            </tr>`);
        });
    } else {
        $('.course-amount-number').append('<h3>No existen Acciones de Formación con participantes asignados en este período de tiempo</h3>');
    }

    //graphs
    //doughnut all time
}//end report by participant quantity

function reportByCourseMostScheduled(response){ //reports by course most scheduled
    console.log("most-scheduled");
    //draw list of courses
    refresh();
    
    $(".table-col-helper").append(`<div class="panel panel-success">
    <div class="panel-heading">
        <div class="panel-title">Acciones de Formación más programadas</div>
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
    response.courseData.forEach((element) => {
        let amount;
        for (const property in response.amountData) {
            if (property == element.id) {
                amount = response.amountData[property];
            }
        }
        $(".course-list tbody").append(`<tr>                    
                                    <td>${element.code}</td>
                                    <td>${element.title}</td>
                                    <td>${amount}
                                    </td></tr>`);
        totalAmountOfCourses = totalAmountOfCourses + amount;
    });

    $(".course-amount-number span").html(
        `<h3>Cantidad Total de Cursos Programados: ${totalAmountOfCourses}</h3>`
    );
}//end report by course most scheduled

function reportByParticipantAverage(response){
    console.log("participant-average");
}//end report by participant average

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

    $('.selector').on('click',function(){
        if($(this).val() == 'participant-by-status'){
            $(".participant-status-container").show();
            //console.log('show');
        }else{
            $(".participant-status-container").hide();
            //console.log("hide");
        }
    });

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

    $('.print-report').off().on('click',function (e){
        window.print();
        e.preventDefault();
    });

    $.ajaxSetup({
            headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            }
        });

    participantStatusSelect(); //draws participant status selector
        //Reports by type selected
        $('.generate').click(function (e) { 
            //data to send
            let selected = $('.selector').find(':selected').val();
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
                    case "participant-average":
                        reportByParticipantAverage(response);
                        break;
                    case "most-scheduled":
                        reportByCourseMostScheduled(response);
                        break;
                    case "not-scheduled":
                        reportByCourseNotScheduled(response);
                        break;
                    default:
                        break;
                }
            }, 
            error:function(response){
                console.log("error "+response);
            }
            });
            e.preventDefault();
        });
})