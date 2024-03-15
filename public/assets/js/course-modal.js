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
        
}//end tabCheck

function draggableCheck(){ //checks if content list items can be draggable
    if(($("#accion-label").text() === "Nueva acción de formación") || ($('#accion-label').text() === "Editar acción de formación")){
        $('.sortable').sortable({
            disabled: false
        });
        $(".sortable").sortable({
            connectWith: ".sortable"
            });
        $(".sortable").disableSelection();
    }else{
        $('.sortable').sortable({
            disabled: false
        });
        $('.sortable').sortable({
            disabled: true
        });
    }
    //enables list elements to be sortable
}//end draggable check

function listButtonsCheck(){
    if(($("#accion-label").text() === "Nueva acción de formación") || ($('#accion-label').text() === "Editar acción de formación")){
        $('.add-btn').show();
    }else{
        $('.add-btn').hide();
    }
}//end list buttons check

function setCourse(){
    let contentData = [];

    $(".content-list li").each(function() { //create content list data array
        contentData.push($(this).text().trim())
    });
    
    let formData = new FormData ($('#accion-form').get(0));
    formData.append('content_data',contentData);
    method = formData.get('_method');
    //console.log('method = '+method);
    
    //console.log(formData.get('content_data'));
    
    //console.log('titulo: '+formData.get('title'));
    
    if(method == 'PUT'){
        console.log("method put");
        newUrl = "acciones_formacion/"+formData.get('course-id');
    }else{
        console.log("method POST");
        newUrl = "acciones_formacion/";
    }

    //console.log(formData);
    
    //console.log('course id: '+formData.get('course-id'))
    //console.log('form data '+formData.get('title'));
    //console.log('content data '+contentData);
    //e.preventDefault();
    //return;
    /* for (var [key, value] of formData.entries()) { 
        console.log(key, value);
    } */

    $.ajax({       
        data:formData,
        type:"POST",
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf_token"]').attr('content')},
        url: newUrl,
        dataType: "json",
        contentType: false,
        processData: false,
        success: function (response){
            console.log('success tree');
            console.log(response);
            success = true;
            method = '';
            window.location = "acciones_formacion/onSubmitAlert/"+success;
        },
        error: function (response){
            console.log('error tree');
            success = false;
            console.log(response);
            method = '';
            window.location = "acciones_formacion/onSubmitAlert/"+success;
        }
    });//end ajax call
}

$(document).ready(function(){

    $.ajaxSetup({ //csrf token
        headers:{
            'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
        }
    });//end csrf token

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
    });//end ON modal close event
    
    //when modal opens
    $('.modal').on('shown.bs.modal',function(){
        
        tabCheck('0');
        
        console.log('open!');

        draggableCheck();
        listButtonsCheck();

        $('.select2').each(function(){
            $(this).prop('disabled',false);
        });

        $('.0-tab-input.course-code').trigger('focus');

        //enable inputs when edit modal opens
        if($('#accion-label').html() == 'Editar acción de formación'){
            $('.create-course-form :input').prop('disabled',false);
        }else{
            $('.create-course-form :input').prop('disabled',true);
        }

        //course code validation

        $('.course-code').prop('disabled',false);

        $('.0-tab-input.course-code').on('input',function(){ //code validation ajax event call

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
        });//end code validation event ajax call
    });//end ON modal open event

    //content list
    $('.undo-btn').hide();

    let tempElement; //helper to store temporary element to undo deletion

    $('.undo-btn').on('click',function(){ //undo deletion
        console.log(tempElement);
        $('.content-list').append(tempElement);
        eventRefresh();
    });

    function eventRefresh(){ //content list event refresh

        console.log('function loaded');
    
        $('.list-item-edit').off();
        $('.list-item-delete').off();
    
        $('.list-item-edit').on('click',function(){ //edit
            let textElement = $(this).siblings('.list-text');
            textElement.on('keypress',function(e){
                if((e.key === 'Enter') || (e.key === '.')){
                    textElement.blur();
                }
            });

            console.log('edit function');
            $(this).siblings().prop('contenteditable',true);
            let contentEle = $(this).siblings()[0];
            //console.log($(contentEle).children());
            const range = document.createRange();
            const selection = window.getSelection();
            range.setStart(contentEle, contentEle.childNodes.length);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);



        });//edit list item
    
        $('.list-item-delete').on('click',function(){ //delete
          //console.log($(this));
          //console.log($(this).parent('.list-element'));
          $('.undo-btn').show();
          tempElement = $(this).parent('.list-element'); 
          $(this).parent('.list-element').remove();
          return tempElement;
        }); // delete list item
        return tempElement;
    }//end event refresh


    
    $('.add-btn').on('click',function(){// add new element
        let listItemValue = $('.list-element').length;
        $('.content-list').prepend(`<li value="${listItemValue}" class="list-element list-group-item"><span class="list-text"><p>Nuevo contenido</p></span><span class="glyphicon glyphicon-pencil list-item-edit" aria-hidden="true"></span><span class="glyphicon glyphicon-trash list-item-delete" aria-hidden="true"></span></li>`);
        
        //console.log(listItemValue);

        //console.log($(`.list-element[value=${listItemValue}]`).children());
  
        $(`.list-element[value=${listItemValue}] span:first-child`).prop('contenteditable',true);
  
        let contentEle = $(`.list-element[value=${listItemValue}] span:first-child`)[0];
        let tempElement = $(`.list-element[value=${listItemValue}] .list-text`);
        const range = document.createRange();
        const selection = window.getSelection();
        range.setStart(contentEle, contentEle.children.length);
        range.collapse(true);
        selection.removeAllRanges();
        selection.addRange(range);
        
        //need event lose focus on 'enter' or 'dot' keypress

        //console.log($(`.list-element[value=${listItemValue}] .list-text`));
        tempElement.on('keypress',function(e){
            if((e.key === 'Enter') || (e.key === '.')){
                tempElement.blur();
            }
        }); //end lose focus event

        eventRefresh();
  
      })//end add new element to list event
      
    //enable navigation if inputs are not empty
    tabCheck('0'); 
});