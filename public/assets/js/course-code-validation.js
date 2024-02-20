$(document).ready(function (){
    console.log('validation loaded');
    
     $.ajaxSetup({
            headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            }
    });

    //console.log('code is '+$('.course-code').val());
    //$('.code-error-text').hide(); //hides helper text

    $('.modal').on('shown.bs.modal',function(){
        console.log('modal open on validation');
        console.log($('.0-tab-input.course-code').val());
        /* $('.0-tab-input.course-code').on("input",function(){
            console.log('code validation');
            console.log('codigo '+$('.course-code').val());
        }); */
        $('.0-tab-input.course-code').on('input',function(){
            console.log('code validation');
            console.log(this.val());
        });
    });

    /* $('#codigo').off().on("input",function () { 
        //e.preventDefault();
        console.log('test test tes');
        let codeValue = $(this).val();
        //codeValue.push(1050);
        console.log("at js: "+codeValue);
        console.log('code test');
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
                    console.log("code already exists");
                    $('.create-course-form :input').prop('disabled',true);
                    $('.course-code').prop('disabled',false);
                    

                }else{
                    $('.course-code').parent().removeClass('has-error');
                    $('.code-error-text').hide();
                    console.log("code available");
                    $('.create-course-form :input').prop('disabled',false);
                    $('.select2').prop('disabled',false);
                }
                //console.log("success");
            },
            error: function(response){
            
            }
        });
        
    }); */
});