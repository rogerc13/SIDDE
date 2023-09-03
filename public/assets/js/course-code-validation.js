$(document).ready(function (){
    
    $.ajaxSetup({
            headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            }
        });
    $('.code-error-text').hide(); //hides helper text

    $('.course-code').off().on('input',function (e) { 
        e.preventDefault();
        let codeValue = $(this).val();
        //codeValue.push(1050);
        console.log("at js: "+codeValue);
        $.ajax({
            data: {'codeValue' : codeValue},
            type:'POST',
            url: 'codes',
            dataType: 'json',
            success: function(response){
                //console.log(response);
                if(response === true){
                    $('.course-code').parent().addClass('has-error');
                    $('.code-error-text').show();
                    //console.log("code already exists");
                    $('.create-course-form :input').prop('disabled',true);
                    $('.course-code').prop('disabled',false);
                    

                }else{
                    $('.course-code').parent().removeClass('has-error');
                    $('.code-error-text').hide();
                    //console.log("code available");
                    $('.create-course-form :input').prop('disabled',false);
                    $('.select2').prop('disabled',false);
                }
                //console.log("success");
            },
            error: function(response){
            
            }
        });
        
    });
});