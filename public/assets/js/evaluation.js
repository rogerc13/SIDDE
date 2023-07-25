function evaluate(data){
    
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
                console.log(response.success);     
            },
            error: function (response) {
                console.log(response.error);
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
            if($('#participant_status').prop('disabled') == true){
                $('.evaluate-button span').html(' Finalizar Evaluación');
                $('#participant_status').prop('disabled', false);
            }else{
                $('#participant_status').prop('disabled', true);
                $('.evaluate-button span').html(' Evaluar Participantes');
                evaluate(data);
            }
            e.preventDefault();
        });
        let data = [];
        let b;
     $('#participant_status option').click(function(e){
        data.push({'scheduled_id':$(this).attr('course_id'),'participant_id':$(this).attr('participant_id'),'status_id':$(this).val()});
    })
});