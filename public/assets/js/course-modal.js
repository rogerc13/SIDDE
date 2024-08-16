function tabSwitch(tabIndex){
    //console.log(`tab index at tabSwitch: ${tabIndex}`);
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
        tabIndex = 3;
        tabSwitch(tabIndex);
    }
}

function tabCheck(a){
    //console.log(`a value: ${a}`);
    let inputElement = "." + a + "-tab-input";
    //console.log(`Tab Check before:${inputElement}`);
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
    if((a == '0')  && $('#accion-label').text() === 'Detalles Acción de formación'){
        $(".tab-button-next").removeClass("disabled");
        //console.log(`Enabled Details Modal a value: ${a}`);
    }

    if((a == '2') && $('#accion-label').text() === 'Detalles Acción de formación'){
        $('.tab-button-next').removeClass('disabled');
        //console.log(`Enabled Details Modal a value: ${a}`);
    }

    if ((a == "3") && ($("#accion-label").text() === "Detalles Acción de formación")) {
        $(".tab-button-close").show(); 
    }
    //END DETAILS MODAL

    //console.log(`Tab Check: ${inputElement}`);
        
}//end tabCheck

function tabButtons(tabIndex){
    $('.tab-button-next').off().on('click',function(){
        tabIndex = tabIndex+1;
        //console.log(`Tab Index on Next Click: ${tabIndex}`);
        tabSwitch(tabIndex);
        //console.log('next index:'+tabIndex);
    });
    $('.tab-button-back').off().on('click',function(){
        tabIndex = tabIndex-1;
        tabSwitch(tabIndex);
        //console.log('back index:'+tabIndex);
    });
}
function draggableCheck(){ //checks if content list items can be draggable
    if(($("#accion-label").text() === "Nueva acción de formación") || ($('#accion-label').text() === "Editar Acción de formación")){
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
    console.log('Content List Sort Check');
    //enables list elements to be sortable
}//end draggable check

function listButtonsCheck(){
    if(($("#accion-label").text() === "Nueva acción de formación") || ($('#accion-label').text() === "Editar Acción de formación")){
        $('.add-btn').show();
    }else{
        $('.add-btn').hide();
    }
}//end list buttons check

function setCourse(deleteHelper){
    let contentData = [];

    //let deleteHelper = updateDocuments();

    console.log(deleteHelper);

    $(".content-list li").each(function() { //create content list data array
        contentData.push($(this).text().trim())
    });
    
    let formData = new FormData ($('#accion-form').get(0));
    formData.append('content_data',contentData);
    method = formData.get('_method');

    if(method == 'PUT'){
        console.log("method put");
        deleteHelper = JSON.stringify(deleteHelper);
        formData.append('delete_flag',deleteHelper);
        //console.log(formData);
        //return console.log(formData);
        newUrl = "acciones_formacion/"+formData.get('course-id');
    }else{
        console.log("method POST");
        newUrl = "acciones_formacion/";
    }

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

function prerequisiteSelect(data){
    
    if($('#accion-label').html() == 'Detalles Acción de formación'){ //DETAILS MODAL
        $('.radio-prerequisite').hide();
        $('.select-prerequisite-helper').show();
        $('#prerequisite').html('');
        if(data[0].prerequisite.length > 0){
            //console.log(data[0].prerequisite[0]);
            if(data[0].prerequisite.length > 0){
                try {
                    $('#prerequisite').append(`<option>${data[0].prerequisite[0].prerequisite.title}</option>`).trigger('change');         
                } catch (error) {
                    $('#prerequisite').append(`<option>No Posee Prerequisito</option>`).trigger('change');    
                    //console.log(error);
                }
            }else if(data[0].prerequisite[0].prerequisite_id === null){
                $('#prerequisite').append(`<option>No Posee Prerequisito</option>`).trigger('change');
            }    
        }else{
            $('#prerequisite').append(`<option>No Posee Prerequisito</option>`).trigger('change');
        }
    }else if($('#accion-label').html() == 'Editar Acción de formación'){ //EDIT MODAL
        $('.radio-prerequisite').hide();
        $('.select-prerequisite-helper').show();
        //get course list to draw select
        $.ajax({
            type: "POST",
            url: "prerequisite",
            dataType: 'JSON',
            success: function(response){

                //filtering current course
                response.courses = response.courses.filter((element) =>{
                    return element.code !== data[0].code;
                });

                $('#prerequisite').html('');
                response.courses.forEach(element => {
                
                $('#prerequisite').append(`<option value="${element.code}">
                                                        ${element.code} |
                                                        ${element.title}
                                                        </option>`);
                });
                $("#prerequisite").append(
                    `<option value="">No Posee Prerequisito</option>`
                );
                if(data[0].prerequisite.length > 0){
                    
                    try {
                        $("#prerequisite").val(data[0].prerequisite[0].prerequisite.code).change();    
                    } catch (error) {
                        $("#prerequisite").val('').change();
                        //console.log(error);
                    }
                }else{
                    $("#prerequisite").val('').change();
                }   
            },
            error: function(response){
                console.log(response);
            }
        });
    }
}

function crearAccion(url){
    $('.course-code').parent().removeClass('has-error');
    $('.code-error-text').hide();
    $('.read-only-docs').hide();
    document.getElementById("accion-form").reset();
    $('#categoria_id').trigger("change");
    $(".fileinput-filename").empty();
    $(".loader").addClass("hidden");
    $("#accion-form").removeClass("hidden");
    $("[name=_method]").val("POST");
    $("#accion-label").html("<h3>Nueva acción de formación</h3>");
    $("#accion-modal").modal();   
    $(".no-docs").hide();
    $('.content-list').html('');
}// CREATE MODAL / CREAR ACCION DE FORMACION

function detallesAccion(url){
        
    $(".loader").removeClass("hidden");
    $("#accion-form").addClass("hidden");
    $("[name=_method]").val("GET");
    $("#accion-label").html("Detalles Acción de formación");
    $("#aceptar").addClass("hidden");

    $("#accion-form :input").prop('readonly', true);
    $( "#accion-form select" ).prop('disabled', true);
    $("#docs").addClass("hidden");
    $("#accion-aceptar").addClass("hidden");

    $.get(url,function(data,status){
            
            data=JSON.parse(data);
            console.log(data);

            $('#codigo').val(data[0].code);

            prerequisiteSelect(data);

            $('#titulo').val(data[0].title);
            $('#categoria_id').val(data[0].category_id).trigger("change");
            $('#modalidad_id').val(data[0].modality_id).trigger("change");
            $('#duracion').val(data[0].duration);
            $('#dirigido').val(data[0].addressed);
            $('#min').val(data[0].capacity[0].min);
            $('#max').val(data[0].capacity[0].max);
            $('#objetivo').val(data[0].objective);
            
            $('.content-list').html('');

            contentData = [];
            i = 0;
            data[0].content.forEach(element => {
                contentData[i] = element.text;
                $('.content-list').append(`<li value="${i++}" class="list-element list-group-item"><span class="list-text">${element.text}</span></li>`);

            });
            $('.content-list')

            if(typeof data[0].file !== 'undefined'){

                if(data[0].file.length > 0){
                    $('.read-only-docs').show();    
                    $('.no-docs').hide();
                    data[0].file.forEach(element => {
                        if(element.type_id == 1){
                            $('.facilitator_manual').val(element.path);
                        }
                        if(element.type_id == 2){
                            $('.participant_manual').val(element.path);
                        }
                        if(element.type_id == 3){
                            $('.course_guide').val(element.path);
                        }
                        if(element.type_id == 4){
                            $('.course_presentation').val(element.path);
                        }
                    });
                }else{
                    $('.read-only-docs').hide();
                    $('.no-docs').show();
                    
                }//else
            }//if                
            $(".loader").addClass("hidden");
            $("#accion-form").removeClass("hidden");
        });

    $("#accion-modal").modal();
    
   
}//DETAILS MODAL / DETALLES DE ACCION DE FORMACION

let deleteHelper;
function updateDocuments(){ //documents flags
    deleteHelper = {
        facilitator : false,
        manual: false,
        guide: false,
        presentation: false
    };
        $('#remove_facilitator').off().on('click',(e)=>{
            if(deleteHelper.facilitator == true){
                deleteHelper.facilitator = false;
            }else{
            deleteHelper.facilitator = true;
            $('#l_manual_f').html('');
            }
            console.log(deleteHelper);
            return deleteHelper;
        });

        $('#remove_manual').off().on('click',(e)=>{
            if(deleteHelper.manual == true){
                deleteHelper.manual = false;
            }else{
            deleteHelper.manual = true;
            $('#l_manual_p').html('');
            }
            console.log(deleteHelper);
            return deleteHelper;
        });

        $('#remove_guide').off().on('click',(e)=>{
            if(deleteHelper.guide == true){
                deleteHelper.guide = false;
            }else{
            deleteHelper.guide = true;
            $('#l_guia').html('');
            }
            console.log(deleteHelper);
            return deleteHelper;
        });

        $('#remove_presentation').off().on('click',(e)=>{
            if(deleteHelper.presentation == true){
                deleteHelper.presentation = false;
            }else{
            deleteHelper.presentation = true;
            $('#l_presentacion').html('');
            }
            console.log(deleteHelper);
            return deleteHelper;
        });

        console.log(deleteHelper);
        return deleteHelper;
}//end documents flags



function editarAccion(url){
    updateDocuments();
    $(".loader").removeClass("hidden");
    $('.course-code').parent().removeClass('has-error');
    $('.code-error-text').hide();
    $('.read-only-docs').hide();
    $("[name=_method]").val("PUT");
    $("#accion-label").html("Editar Acción de formación");

    $(".fileinput-filename").empty();


    $.get(url,function(data,status){
            data=JSON.parse(data);
            console.log(data);
            $('.course-id').val(data[0].id);
            $('.course-code').val(data[0].code);

            prerequisiteSelect(data);

            $('#titulo').val(data[0].title);
            $('#categoria_id').val(data[0].category_id).trigger("change");
            $('#modalidad_id').val(data[0].modality_id).trigger("change");
            $('#duracion').val(data[0].duration);
            $('#dirigido').val(data[0].addressed);
            $('#min').val(data[0].capacity[0].min);
            $('#max').val(data[0].capacity[0].max);
            $('#objetivo').val(data[0].objective);

            $('.content-list').html('');

            contentData = [];
            i = 0;
            data[0].content.forEach(element => {
                contentData[i] = element.text;
                $('.content-list').append(`<li value="${i++}" class="list-element list-group-item form-inline">
                        <span class="list-text">${element.text}</span>
                        <span class="badge list-item-edit" aria-hidden="true"><i class="fa fa-pencil"></i></span>
                <span class="badge list-item-delete" aria-hidden="true"><i class="fa fa-remove"></i></span>
                    </li>`);
               //console.log(i++ +' '+element.text);
            });

            eventRefresh('Edit Modal');

            if(data[0].file.length > 0){
                $('.no-docs').hide();
                $('.fileinput').addClass('fileinput-exists').removeClass('fileinput-new');
                f = 0;
                data[0].file.forEach(element => {
                    if(element.type_id == 1){
                        $('#l_manual_f').text(element.path);
                    }
                    if(element.type_id == 2){
                        $('#l_manual_p').text(element.path);
                    }
                    if(element.type_id == 3){
                        $('#l_guia').text(element.path);
                    }
                    if(element.type_id == 4){
                        $('#l_presentacion').text(element.path);
                    }
                    
                    f++;
                });
            }else{
                $('.fileinput').addClass('fileinput-new').removeClass('fileinput-exists');
            }

            $(".loader").addClass("hidden");
            $("#accion-form").removeClass("hidden");

        });
    $("#accion-modal").modal();
    
    
}//UPDATE MODAL / EDITAR ACCION DE FORMACION

function courseCodeValidation(){ //Course Code Validation
//course code validation
        //need to hide code validation message when edit modal opens for the first time
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
}

function modalCloses(){
    $(".modal").on("hidden.bs.modal", function () {
        $(".select2").prop("disabled", true);
        $(".select2").each(function () {
            $(this).prop("disabled", false);
        });

        $(".create-course-form :input").prop("disabled", false);
        $(".code-error-text").hide();
        
        console.log("closed!");
        
        tabIndex = 0;

        //console.log(`tab index ${tabIndex}`);
        $(".tab-button-next").show();
        $(".tab-button-back").hide();
        $(".tab-submit").hide();
        $(".tab-button-close").show();
        $(".tab-button-next").addClass("disabled");

        if($('#accion-label').text() === 'Detalles Acción de formación'){
            $('.course-code').parent().removeClass('has-error');
            $('.code-error-text').hide();
            
            $("#accion-form :input").prop('readonly', false);
            $("#accion-form select").prop('disabled', false);
            $("#accion-aceptar").removeClass("hidden");
            $("#docs").removeClass("hidden");
            
            $('.course-list').off();
        }
        $('.content-list').html('');
        tabSwitch(0);
    });//end ON modal close event
    
}

function modalOpens(){
    //when modal opens
    $('.modal').on('shown.bs.modal',function(){
        let tabIndex = 0;
        //console.log('Modal Open');

        tabCheck('0');
        tabButtons(tabIndex);
        draggableCheck();
        listButtonsCheck();

        $('.select2').each(function(){
            $(this).prop('disabled',false);
        });

        $('.0-tab-input.course-code').trigger('focus');

        //enable inputs when edit modal opens
        if($('#accion-label').html() == 'Editar Acción de formación'){
            try {
                $('.create-course-form :input').prop('disabled',false);
                $('.tab-button-next').removeClass('disabled');
                
            } catch (error) {
                //console.log(error);    
            }
        }else if($('#accion-label').html() == 'Detalles Acción de formación'){
            $('.tab-button-next').removeClass('disabled');
            //eventRefresh('Details Modal');
        }else{
            $('.create-course-form :input').prop('disabled',true);
            console.log('Edit Disabled on Modal Open');
            eventRefresh('Create Modal');
        }

        courseCodeValidation();
        
    });//end ON modal open event

    
}

function eventRefresh(msg){ //content list event refresh

    console.log(`Event Refresh Function on ${msg}`);

    let tempElement; //helper to store temporary element to undo deletion

    $('.undo-btn').off().on('click',function(){ //undo deletion
        console.log(tempElement);
        $('.content-list').append(tempElement);
    });

    if((msg == 'Edit Modal' || msg == 'Create Modal') && ($('.list-item-edit').attr('listener') !== 'true')){
        console.log('Edit List');
        //listerer check
        if($('.list-item-edit').attr('listener') !== 'true'){
            $('.list-item-edit').off().on('click',function(){ //edit
                console.log(`Clicked`);
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

                $('.list-item-edit').attr('listener','true');
                console.log('Edit event attached');

            });//edit list item
        }//listener
    }
    
    

    try {
        $('.list-item-delete').off().on('click',function(){ //delete
            //console.log($(this));
            //console.log($(this).parent('.list-element'));
            $('.undo-btn').show();
            tempElement = $(this).parent('.list-element'); 
            $(this).parent('.list-element').remove();
            return tempElement;
          }); // delete list item
    } catch (error) {
        console.log(`Coudln't Delete Element`);
        //console.log(error);
    }

    $('.add-btn').off().on('click',function(){// add new element
        let listItemValue = $('.list-element').length;
        $('.content-list').prepend(`<li value="${listItemValue}" class="list-element list-group-item form-inline">
                <span class="list-text">
                    Nuevo contenido
                </span>
                <span class="badge list-item-edit" aria-hidden="true"><i class="fa fa-pencil"></i></span>
                <span class="badge list-item-delete" aria-hidden="true"><i class="fa fa-remove"></i></span> 
            </li>`);
  
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
        eventRefresh('Create Modal');
      })//end add new element to list event
}//end event refresh

$(document).ready(function(){
    console.log('loaded');
    $.ajaxSetup({ //csrf token
        headers:{
            'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
        }
    });//end csrf token

    $('.tab-submit').hide();
    $('.tab-button-back').hide();
    $('.tab-button-next').addClass('disabled');

    //tab buttons
    

    modalCloses();
    modalOpens();
    
    //content list
    $('.undo-btn').hide();

    //enable navigation if inputs are not empty
    //tabCheck('0');


});