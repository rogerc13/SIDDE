$(document).ready(function(){
    $.ajaxSetup({
            headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            }
    });    
    $('#participant_status option').click(function(e){
        let status = $(this).val();
        let participantId = $(this).attr('participant_id');
        let data = {'status':status,'participantId':participantId};
        console.log(data);
         $.ajax({
            type: "POST",
            url: "/evaluation",
            data: data,
            dataType: "json",
            success: function (response) {
                console.log(response);
                
            },
            error: function (response) {
                console.log(response);
            }
        }); 
    })
})