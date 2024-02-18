function tabSwitch(tabIndex){
    if(tabIndex === 0){
        $('.tab-button:eq(0)').tab('show');
        $('.tab-button-back').hide();
        $('.tab-button-close').show();
        tabCheck('0');
    }else if(tabIndex === 1){
        $('.tab-button:eq(1)').tab('show');
        $('.tab-button-back').show();
        $('.tab-button-close').hide();
        tabCheck('1');
    }else if(tabIndex === 2){
        $('.tab-button:eq(2)').tab('show');
        $('.tab-button-next').show();
        $('.tab-submit').hide();
        tabCheck('2');
    }else if(tabIndex === 3){
        $('.tab-button:eq(3)').tab('show');
        $('.tab-button-next').hide();
        $('.tab-submit').show();
        console.log('tab switch');
        //tabSwitch(tabIndex);
        
    }else if(tabIndex > 3){
        tabIndex = 2;
        tabSwitch(tabIndex);
    }
}

function tabCheck(a){
    let inputElement = "." + a + "-tab-input";
    $(inputElement).each(function () {
        if ($(this).val() !== "") {
            $(".tab-button-next").show();
            $(".tab-button-next").removeClass("disabled");
        } else {
            $(".tab-button-next").addClass("disabled");
        }
    });

    $(inputElement).off().on("input", function () {
        //triggers on every input element of the modal
        console.log("input event");
        console.log($(this).val());
        $(inputElement).each(function () {
            if ($(this).val() !== "") {
                $(".tab-button-next").show();
                $(".tab-button-next").removeClass("disabled");
            } else {
                $(".tab-button-next").addClass("disabled");
            }
        });

        //CREATE MODAL
        if (
            (($("#min").val() == "") ||
            ($("#min").val() == "")) &&
            (($("#accion-label").text() === "Nueva acción de formación") &&
            (a == '1'))
        ) {
            $(".tab-button-next").addClass("disabled");
            console.log("min max not set");
        }
        //END CREATE MODAL
    });

    if((a == '2') && ($('#contenido').val() == '') && ($('#objetivo').val() != '')){
        $('.tab-button-next').removeClass('disabled');
        console.log('empty content');
    }
    
    //DETAILS MODAL
    if((a == '0')  && $('#accion-label').text() === 'Detalles acción de formación'){
        $(".tab-button-next").removeClass("disabled");
        console.log('details');
    }

    if((a == '2') && $('#accion-label').text() === 'Detalles acción de formación'){
        console.log('event');
        $('.tab-button-next').removeClass('disabled');
    }

    if ((a == "3") && ($("#accion-label").text() === "Detalles acción de formación")) {
        $(".tab-button-close").show(); 
    }
    //END DETAILS MODAL

    console.log(inputElement);
        
}

$(document).ready(function(){
    $('.tab-submit').hide();
    $('.tab-button-back').hide();
    $('.tab-button-next').addClass('disabled');

    //tab buttons
    let tabIndex = 0;
    $('.tab-button-next').on('click',function(){
        tabIndex = tabIndex+1;
        tabSwitch(tabIndex);
        //console.log('next index:'+tabIndex);
    })
    $('.tab-button-back').on('click',function(){
        tabIndex = tabIndex-1;
        tabSwitch(tabIndex);
        //console.log('back index:'+tabIndex);
    })

    //when modal closes
    $(".modal").on("hidden.bs.modal", function () {
        $(".select2").prop("disabled", true);
        $(".select2").each(function () {
            $(this).prop("disabled", false);
        });

        $(".create-course-form :input").prop("disabled", false);
        $(".code-error-text").hide();
        console.log("closed!");
        
        tabIndex = 0;
        $(".tab-button-next").show();
        $(".tab-button-back").hide();
        $(".tab-submit").hide();
        $(".tab-button-close").show();
        $(".tab-button-next").addClass("disabled");
        tabSwitch(0);
    });
    
    //when modal opens
    $('.modal').on('shown.bs.modal',function(){
        
        tabCheck('0');
        console.log('open!');
        $('.select2').each(function(){
            $(this).prop('disabled',false);
        });

        //course code validation

        $('.0-tab-input.course-code').trigger('focus');
        $('.create-course-form :input').prop('disabled',true);
        $('.course-code').prop('disabled',false);

        $('.0-tab-input.course-code').on('input',function(){

            $('.0-tab-input.course-code').focus();
            console.log('code validation');
            let codeValue = $('.course-code').val();
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
                       // $('.course-code').trigger('focus');
                        
    
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
        });
    });

    //enable navigation if inputs are not empty
    tabCheck('0'); 
});