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

function participantStatusSelect(){ //participant status select dropdown
    $.ajax({
        type:'GET',
        url: '/reports/participant-status-select',
        success: function(response){
            //console.log(JSON.parse(response));
            response = JSON.parse(response);
            response.statuses.forEach(status => {
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

function reportByDate(response){ //reports by date
    refresh();
    $(".row-graphs").append(`<div class="panel panel-success by-date-line">
        <div class="panel-heading">
            <div class="panel-title">Cantidad de Acciones de Formación durante el Período ${response.start_date} - ${response.end_date} : ${response.total}</div>
        </div>    
        <div class="panel-body">
            <div class="h-25 col-xs-12 col-md-12 graph-container">
                <canvas id="myChart" width="200" height="200"></canvas>
            </div>
        </div>
        </div>`);

    let ctx = $("#myChart");

    $('.course-amount-number span').html("");
    $(".course-amount-number span").append(
        `<h3 class="text-center">Cantidad de Acciones de Formación durante el Período ${response.start_date} - ${response.end_date} : ${response.total}</h3>`
    );
    var chart = new Chart(ctx, {
        type: "line",
        data: {
            datasets: [
                {
                    label: "Cantidad de Acciones de Formación",
                    data: response.y,
                    fill: false,
                    borderColor: "steelblue",
                },
            ],
            labels: response.x,
        },
        options: {
            title: {
                display: true,
                text: `Acciones de Formación durante el Período ${response.start_date} - ${response.end_date}`,
            },
        },
    });
}//end report by date

function reportByCategory(response){
    
    refresh();
    $(".row-graphs")
        .append(`<div class="panel panel-success line-graph-panel col-md-6">
                <div class="panel-heading">
                    <div class="panel-title">Cantidad de Acciones de Formacion por Áreas de Conocimiento durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}</div>
                </div>
                <div class="panel-body">
                    <div class="graph-container">
                     <canvas id="myChart" width="200" height="200"></canvas>
                    </div>
                </div>
            </div>`);

    let ctx = $("#myChart");
    let categories = [];

    //console.log(response.categories);

    //line graph data
    let fillColor = [];
    let colorHelp = 0;
    let regHex=/^#([0-9a-f]{3}){1,2}$/i;
    response.categories.forEach(element => {
        //fillColor.push(`#${Math.floor(Math.random() * 16777215).toString(16)}`);
        fillColor.push(
            "#000000".replace(/0/g,function(){
               
                return (~~(Math.random()*16)).toString(16);
            })
        );
        console.log(regHex.test(fillColor[colorHelp]));
        let category = {
            label: element,
            data: response.y.filter((obj) => {
                return obj.category === element && obj.y !== 0;
            }),
            showLine: true,
            fill: false,
            borderColor: fillColor[colorHelp],
            backgroundColor: fillColor[colorHelp],
            borderWidth: 1,
        };
        categories.push(category);
        colorHelp++;
    });

    /* categories = categories.filter((obj) => {
        return obj.data.length > 0;
    }); */

    

/*     response.graphData.forEach(element => {
        fillColor.push(`#${Math.floor(Math.random() * 16777215).toString(16)}`);
        response.y.forEach(yHelper => {
            let category = {
            label: yHelper.category,
            data: yHelper.y,
            showline: true,
            fill: false,
            borderColor: fillColor[colorHelp],
            backgroundColor: fillColor[colorHelp],
        };
        })
        categories.push(category);
        colorHelp++;
    });
 */
    console.log(categories);
    
    var chart = new Chart(ctx, {
        type: "line",
        data: {
            //labels:response.x,
            datasets: categories,
        },
        options: {
            scales: {
                xAxes: [
                    {
                        type: "time",
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: "Período de Tiempo",
                        },
                        ticks: {
                            //beginAtZero: true,
                            major: {
                                fontStyle: "bold",
                                fontColor: "black",
                            },
                        },
                    },
                ],
                yAxes: [
                    {   
                        display:true,
                        scaleLabel:{
                            display:true,
                            labelString: "Cantidad de Acciones de Formación",
                        },
                        ticks: {
                            beginAtZero: true,
                            stepSize: 5,
                        },
                    },
                ],
            },
            spanGaps: true,
            showLines: true,
            title: {
                display: true,
                text: `Cantidad de Acciones de Formacion por Áreas de Conocimiento durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}`,
            },
        },
    });
        
    $(".row-graphs").append(`
        <div class="panel panel-success doughnut-panel col-md-6">
            <div class="panel-heading">
                <div class="panel-title">
                    Distribución de Acciones de Formación por Areas de Conocimiento durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}
                </div>
            </div>
            <div class="panel-body">
                <div class="  doughnut-container">
                    <canvas id="doughnut" width="400" height="400"></canvas>
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

    let doughnut = $("#doughnut"); //div selector

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
            title: {
                display: true,
                text: `Distribución de Acciones de Formación por Areas de Conocimiento durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}`,
            },
        },
    });

    //tables
    $(".table-col-helper")
        .append(`<div class="panel panel-success course-by-category-list">
                    
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
    
    $(".table-col-helper").append(`<div class="panel panel-success course-by-category-by-date-range">
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
        .append(`<div class="panel panel-success by-status-line-panel col-md-6 ">
            <div class="panel-heading">
                <div class="panel-title">Distribución de Acciones de Formación por Estatus durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}</div>
            </div>
            <div class="panel-body">
                <div class="h-25 col-xs-12 col-md-12 graph-container">
                <canvas id="myChart" width="400" height="430"></canvas>
                </div>
            </div>
            </div>`);

    let ctx = $("#myChart"); //linear graph selector

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
        let status = {
            label: element,
            data: response.y
                .filter((obj) => {
                    return obj.status === element;
                    //return {x,y} = (obj.status === element /* && obj.y !== 0 */) && {x,y} ;
                })
                .map((x) => {
                    if (x.y > 0) {
                        return { x: x.x, y: x.y };
                    } else {
                        return { x: x.x, y: 0 };
                    }
                }),
            showLine: true,
            fill: false,
            borderColor: fillColor[colorHelp],
            backgroundColor: fillColor[colorHelp],
            borderWidth: 1,
            //spanGaps:false,
            /* points:[{display:response.y.filter(obj => {
                                            return obj.status === element;
                                        }).map((x) => {
                                            if(x.y > 0 ){
                                                return true;
                                            }else{
                                                return false;
                                            }
                                        })},] */
        };
        statuses.push(status);
        colorHelp++;
    });
    statuses = statuses.filter((obj) => {
        return obj.data.length > 0;
    });
    //console.log(statuses);

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
            scales: {
                xAxes: [
                    {
                        type: "time",
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: "Período de Tiempo",
                        },
                        ticks: {
                            //beginAtZero: true,
                            major: {
                                fontStyle: "bold",
                                fontColor: "black",
                            },
                        },
                    },
                ],
                yAxes: [
                    {
                        ticks: {
                            stepSize: 1,
                            beginAtZero: true,
                            min: 0,
                            max: 4,
                        },
                        scaleLabel: {
                            display: true,
                            labelString: "Cantidad de Acciones de Formación",
                        },
                        /* type:'linear', */
                    },
                ],
            },
            spanGaps: true,
            showLines: true,
            title: {
                display: true,
                text: `Distribución de Acciones de Formación por Estatus durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}`,
            },
        },
    });

    //doughnut graph draw
    $(".row-graphs")
        .append(`<div class="panel panel-success by-status-doughnut-panel col-md-6">
            <div class="panel-heading">
                <div class="panel-title">Distribución de Acciones de Formación por Estatus durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}</div>
            </div>
            <div class="panel-body">
                <div class="doughnut-container">
                    <canvas id="doughnut" width="400" height="400"></canvas>
                </div>
            </div>
        </div>`);

    let doughnut = $("#doughnut"); //div selector

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
            title: {
                display: true,
                text: `Distribución de Acciones de Formación por Estatus durante el período ${response.dateRange.startDate} - ${response.dateRange.endDate}`,
            },
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

    response.spansMostDays.forEach(element => {
        $(".course-day-span-all-time-list tbody").append(`<tr>
            <td>${element.course.title}</td>
            <td>${element.start_date}</td>
            <td>${element.end_date}</td>
            <td>${element.course.duration}</td>
            <td>${element.max_difference}</td>
        </tr>`);
    });

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

    let doughnutStatus = $("#doughnut");
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
            title: {
                display: true,
                text: `Distribución de Participantes por Estatus en el Período ${start_date} - ${end_date}`,
            },
        },
    });
    //bar chart
    let barStatus = $("#myChart");
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
            title: {
                display: true,
                text: `Distribución de Participantes por Estatus en el Período ${start_date} - ${end_date}`,
            },
            legend: {
                display: false,
            },
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

    //list of participants per given status all time
    $(".table-col-helper").append(
        `<div class="panel panel-success participant-with-status">
        <div class="panel-heading">
            <div class="panel-title">Lista de Participantes Con Estatus:</div>
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
                                            </tr>`);

    response.byAllTime.forEach((element) => {
        $(".all-time-list-table tbody").append(`<tr>
                                    <td>${element.person.name}</td>
                                    <td>${element.person.last_name}</td>
                                    <td>${element.person.id_number}</td>
                                    </tr>`);
    });

    //participants not in a course //missing last participated course if any
    $(".table-col-helper").append(
        `<div class="panel panel-success participant-not-asigned">
        <div class="panel-heading">
            <div class="panel-title">Participantes No Asignados a Cursos</div>
        </div>
            <div class="panel-body with-table table-responsive"><table class="not-in-course-list-table table table-striped table-bordered table-center">
                                <thead></thead>
                                <tbody></tbody>
                            </table>
            </div>
        </div>`
    );

    $(".not-in-course-list-table thead").append(`<tr>
                                                <th>Nombres</th>
                                                <th>Apellidos</th>
                                                <th>Cédula</th>
                                            </tr>`);
    response.notInCourse.forEach((element) => {
        $(".not-in-course-list-table tbody").append(`<tr>
                                    <td>${element.name}</td>
                                    <td>${element.last_name}</td>
                                    <td>${element.id_number}</td>
                                    </tr>`);
    });
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
        <th>Cantidad de Participantes</th>
        </tr>`);

        response.dateRangeAmountPerCourse.forEach(element => {
            $(".course-list tbody").append(`<tr>
                <td>${element.course}</td>
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
   $(".participant-status-container").hide();
    $('.selector').on('click',function(){
        if($(this).val() == 'participant-by-status'){
            $(".participant-status-container").show();
            //console.log('show');
        }else{
            $(".participant-status-container").hide();
            //console.log("hide");
        }
    });

    $('#date_range').off().on('change',function (e) { 
        let optionIndex = $(this).index();
        $('#step').val('1 day').change();
        if(optionIndex != 5){
            $('#step option').each(function(){
                $(this).prop('disabled',false);
            });
            $('#step option').slice(optionIndex+3,7).each(function(){
                $(this).prop('disabled', true);
            });
        }        
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
            $('.graph-container').append('<canvas id="myChart" width="400" height="400"></canvas>');
            $('.doughnut-container').append('<canvas id="doughnut" width="400" height="400"></canvas>');
            
            $.ajax({
            type: "POST",
            data: formData,
            url: '/reports/'+selected,
            success: function(response){
                $('.print-report').removeAttr('disabled');
                //console.log(response);
                response = JSON.parse(response);
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