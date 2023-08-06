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

        //Graph Amount of Courses By Date
        $('.generate').click(function (e) { 
            //data to send
            let selected = $('.selector').find(':selected').val();
            let formData = $('.report-form').serialize();
            //reset graph container
            $('.graph-container').children().remove();
            $('.graph-container').append('<canvas id="myChart" width="400" height="400"></canvas>');
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
                $('.course-amount-number span').html(`Cantidad de Cursos entre ${response.start_date} y ${response.end_date} : ${response.total}`);
                var chart = new Chart(ctx, {
                                type: 'line',
                                data:{
                                    datasets: [{
                                        label:"Cantidad de Cursos",
                                        data:response.y,
                                        fill:false,
                                        borderColor: 'steelblue',
                                    }],
                                    labels:response.x,
                                }, 
                                })
            }, 
            error:function(response){
                console.log("error "+response);
            }
            });
            e.preventDefault();
        });
        //Graph Amount of Courses per Category
        $('.generate-category').click(function (e) { 
            //data to send
            let selected = $('.selector').find(':selected').val();
            let formData = $('.report-form').serialize();
            //reset graph container
            $('.graph-container').children().remove();
            $('.graph-container').append('<canvas id="myChart" width="400" height="400"></canvas>');
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
                var chart = new Chart(ctx, {
                                type: 'line',
                                data:{
                                    datasets: [{
                                        label:response.categories,
                                        data:response.y,
                                        fill:false,
                                        borderColor: 'steelblue',
                                    }],
                                    labels:response.x,
                                }, 
                                })
                /*  let names = [];
                response.names.forEach(element => {
                    names.push(element.name);
                });
                */

                /* let data = response;
                let graph = new Rickshaw.Graph({
                    width: 580,
                    height: 250, 
                    element: document.querySelector('#chart'),
                    renderer: 'line',
                    series: [{
                        color: 'steelblue',
                        name: "Cantidad de Cursos por Fecha",
                        data: data.data
                    }]
            });
                let axes = new Rickshaw.Graph.Axis.X({
                    graph: graph,
                    tickFormat: function(x){
                        return new Date(x).toISOString().split('T')[0];
                    }});
                let y_axis = new Rickshaw.Graph.Axis.Y({
                    graph:graph,
                    orientation: 'left',
                    tickFormat: Rickshaw.Fixtures.Number.formatKMBT,
                    element: document.querySelector('#y_axis'),
                });
                let legend = new Rickshaw.Graph.Legend({
                    element: document.querySelector('#legend'),
                    graph:graph
                });
                
                graph.render();
                new Rickshaw.Graph.HoverDetail({
                    graph:graph,
                    formatter: function(x){
                        return new Date(x).toISOString().split('T')[0];
                    }
                    
                });
                $('.course-amount-number span').html(`<h3>Cantidad de Cursos en Este Periodo de Tiempo : ${data.total}</h3>`);
 */
            }, 
            error:function(response){
                console.log(response);
            }
            });
            e.preventDefault();
        });
})