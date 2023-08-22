function evaluate(data){
    console.log(data[0].scheduled_id);
    /* console.log(data);
    b = data.reduce(function(x,y){
            if(!x.some(function(el){return el.participant_id === y.participant_id;})) x.push(y);
            return x;
        },[]);
        console.log(b); */
    
    $.ajax({
            type: "POST",
            url: "/evaluation",
            data: {'data':data},
            dataType: "json",
            success: function (response) {
                console.log(response);
                success = true;
                method = "";
                window.location =
                    /* data[0].scheduled_id+ */ "onSubmitAlert/" + success;
            },
            error: function (response) {
                console.log(response);
                success = false;
                method = "";
                window.location = /* data[0].scheduled_id+ */"onSubmitAlert/" + success;
            }
        });
}

$(document).ready(function(){
    $.ajaxSetup({
            headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            }
        });
        $('.evaluate-button').on('click',function(e){
            $(".return-button").prop('disabled',true);
            if($('.participant_status').prop('disabled') == true){
                $('.evaluate-button span').html(' Finalizar Evaluación');
                $('.participant_status').each(function(){
                    $('.participant_status').prop('disabled', false);
                });
            }else{
                $('.participant_status').each(function(){
                    $(this).prop('disabled', true);
                });
                $('.evaluate-button span').html(' Evaluar Participantes');
                evaluate(data);
            }
            e.preventDefault();
        });
        let data = [];
        let b;
     /* $('.participant_status option').on('click',function(e){
        data.push({'scheduled_id':$(this).attr('course_id'),'participant_id':$(this).attr('participant_id'),'status_id':$(this).val()});
    }) */
     $('.participant_status').on('click',function(e){
         //console.log($(this.options[this.selectedIndex]).attr("course_id"));
         data.push({
            scheduled_id: $(this.options[this.selectedIndex]).attr("course_id"),
            participant_id: $(this.options[this.selectedIndex]).attr("participant_id"),
            status_id: $(this).val(),
        });
     })
});