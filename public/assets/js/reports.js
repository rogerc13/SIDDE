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
$(document).ready(function(){

    $('#date_range').on('change',function (e) { 
        //console.log('change');
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

    $('.print-report').on('click',function (e){
        window.print();
        e.preventDefault();
    });

    $.ajaxSetup({
            headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            }
        });

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
            let ctx = $('#myChart');
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
                        $(".course-amount-number span").html(
                            `Cantidad de Cursos entre ${response.start_date} y ${response.end_date} : ${response.total}`
                        );
                        var chart = new Chart(ctx, {
                            type: "line",
                            data: {
                                datasets: [
                                    {
                                        label: "Cantidad de Cursos",
                                        data: response.y,
                                        fill: false,
                                        borderColor: "steelblue",
                                    },
                                ],
                                labels: response.x,
                            },
                        });
                        break;
                    case "category":
                        let categories = [];
                        response.categories.forEach((element) => {
                            let category = {
                                label: element,
                                data: response.y.filter((obj) => {
                                    //return {x,y} = (obj.category === element && obj.y !== 0) && {x,y};
                                    return (
                                        obj.category === element && obj.y !== 0
                                    );
                                }),
                                showLine: true,
                                fill: false,
                                borderColor: `#${Math.floor(
                                    Math.random() * 16777215
                                ).toString(16)}`,
                            };
                            categories.push(category);
                        });
                        categories = categories.filter((obj) => {
                            return obj.data.length > 0;
                        });
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
                                                labelString:
                                                    "Período de Tiempo",
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
                                            },
                                        },
                                    ],
                                },
                            },
                        });

                        break;
                    case "status":
                        console.log("status");
                        //linear graph data
                        let statuses = [];
                        let fillColor = [];
                        response.statuses.forEach((element) => {
                            fillColor = `#${Math.floor(
                                Math.random() * 16777215
                            ).toString(16)}`;
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
                                borderColor: fillColor,
                                backgroundColor: fillColor,
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
                        });
                        statuses = statuses.filter((obj) => {
                            return obj.data.length > 0;
                        });
                        console.log(statuses);
                        //list data
                        courseData = response.courseData.filter((obj) => {
                            return obj.courseData.length !== 0;
                        });

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
                        });
                        //doughnut data
                        let doughnutData = [];
                        let doughnutLabels = [];
                        let doughnutBackgroundColor = [];
                        let amountHelp = 0;
                        statuses.forEach((element) => {
                            //console.log(element);
                            element.data.forEach((helperB) => {
                                amountHelp = amountHelp + helperB.y;
                            });
                            //console.log(amountHelp);
                            doughnutData.push(amountHelp);
                            amountHelp = 0;
                            doughnutLabels.push(element.label);
                            doughnutBackgroundColor.push(
                                `#${Math.floor(
                                    Math.random() * 16777215
                                ).toString(16)}`
                            );
                        });
                        var chart = new Chart(ctx, {
                            type: "scatter",
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
                                                labelString:
                                                    "Período de Tiempo",
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
                                                labelString: "Cantidad",
                                            },
                                            /* type:'linear', */
                                        },
                                    ],
                                },
                                spanGaps: true,
                                showLines: true,
                            },
                        });
                        //doughnut
                        let doughnut = $("#doughnut");
                        var myDoughnutChart = new Chart(doughnut, {
                            type: "doughnut",
                            data: {
                                datasets: [
                                    {
                                        data: doughnutData,
                                        backgroundColor:
                                            doughnutBackgroundColor,
                                    },
                                ],
                                labels: doughnutLabels,
                            },
                        });
                        break;
                    case "duration":
                        console.log("duration");
                        break;
                    case "participant-by-status":
                        console.log("participant-by-status");
                        break;
                    case "participant-by-quantity":
                        console.log("participant-by-quantity");
                        break;
                    case "participant-average":
                        console.log("participant-average");
                        break;
                    case "most-scheduled":
                        console.log("most-scheduled");
                        //draw list of courses
                        let totalAmountOfCourses = 0;
                        response.courseData.forEach((element) => {
                            let amount;
                            for (const property in response.amountData) {
                                if (property == element.id) {
                                    amount = response.amountData[property];
                                }
                            }
                            $(".course-list tbody").append(`<tr>
                                                        <td>${element.id}</td>
                                                        <td>${element.code}</td>
                                                        <td>${element.title}</td>
                                                        <td>
                                                            ${amount}
                                                       </td></tr>`);
                            totalAmountOfCourses =
                                totalAmountOfCourses + amount;
                        });
                        console.log(totalAmountOfCourses);
                        break;
                    case "not-scheduled":
                        console.log('not-scheduled');
                        //draw table and show amount of courses
                        //$('').html(`Cantidad de Cursos no Programados: ${response.data.length}`)
                        response.data.forEach(element => {
                            $('.course-list tbody').append(`<tr>
                                                            <td>${element.code}</td>
                                                            <td>${element.title}</td>
                                                            <td>${element.category.name}</td>
                                                        </tr>`);
                        })
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