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

    $('#date_range option').click(function (e) { 
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
        $('.generate').click(function (e) { 
            //data to send
            let selected = $('.selector').find(':selected').val();
            let formData = $('.report-form').serialize();
            console.log(selected);
            //reset graph container
            $('.graph-container').children().remove();
            $('.graph-container').append('<div class="col-xs12 col-md-12 graph-container">'+
                                            '<div style="" id="graphByDate">'+
                                                '<div id="y_axis">'+
                                                '</div>'+
                                                '<div id="chart">'+
                                                '</div>'+
                                            '</div>'+
                                            '<div id="legend">'+
                                            '</div>'+
                                        '</div>');
            $.ajax({
            type: "POST",
            data: formData,
            url: '/reports/'+selected,
            success: function(response){

                $('.print-report').removeAttr('disabled');

                console.log(response);
                response = JSON.parse(response);
                console.log(response);
                //console.log(response);
                /*  let names = [];
                response.names.forEach(element => {
                    names.push(element.name);
                });
                */

                let data = response;
                let graph = new Rickshaw.Graph({
                    width: 580,
                    height: 250, 
                    element: document.querySelector('#graphByDate'),
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
                        return new Date(x).toLocaleDateString();
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
                let hoverDetail = new Rickshaw.Graph.HoverDetail({
                    graph:graph
                });
                graph.render();
                
                $('.course-amount-number span').html(`<h3>Cantidad de Cursos en Este Periodo de Tiempo : ${data.total}</h3>`);

            }, 
            error:function(response){
                console.log(response);
            }
            });
            e.preventDefault();
        });
})